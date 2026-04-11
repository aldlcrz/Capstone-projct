"use client";
import React, { useState, useEffect, useRef } from 'react';
import { MapContainer, TileLayer, Marker, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Search, Loader2, Navigation } from 'lucide-react';
import { Geolocation } from '@capacitor/geolocation';
import { Capacitor } from '@capacitor/core';

// Fix Leaflet marker icon issue in Next.js
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png').default?.src || '/images/marker-icon-2x.png',
  iconUrl: require('leaflet/dist/images/marker-icon.png').default?.src || '/images/marker-icon.png',
  shadowUrl: require('leaflet/dist/images/marker-shadow.png').default?.src || '/images/marker-shadow.png',
});

// Create custom icon to avoid webpack issues if the above fails
const customIcon = new L.Icon({
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

// Component to handle map clicks for reverse geocoding
function MapEvents({ onLocationSelect, markerPosition, setMarkerPosition }) {
  useMapEvents({
    click(e) {
      const newPos = [e.latlng.lat, e.latlng.lng];
      setMarkerPosition(newPos);
      if (onLocationSelect) {
        onLocationSelect(e.latlng.lat, e.latlng.lng);
      }
    },
  });

  return markerPosition === null ? null : (
    <Marker position={markerPosition} icon={customIcon} />
  );
}

export default function LocationPicker({ 
  onLocationFound, 
  onConfirm,
  initialLat = 14.2952, // Default to Lumban, Laguna approx
  initialLng = 121.4647,
  autoLocate = true
}) {
  const [position, setPosition] = useState([initialLat, initialLng]);
  const [searchQuery, setSearchQuery] = useState('');
  const [searching, setSearching] = useState(false);
  const [addressDetails, setAddressDetails] = useState('');
  const mapRef = useRef(null);
  const initialLocateRef = useRef(false);

  // Auto-locate user on mount if requested
  useEffect(() => {
    if (autoLocate && !initialLocateRef.current) {
        initialLocateRef.current = true;
        
        // Slight timeout to ensure map context is ready
        const timer = setTimeout(() => {
            handleCurrentLocation();
        }, 800);
        return () => clearTimeout(timer);
    }
  }, [autoLocate]);

  // Auto-fetch details if initial position changes externally
  useEffect(() => {
    if (initialLat !== 14.2952 || initialLng !== 121.4647) {
      setPosition([initialLat, initialLng]);
      performReverseGeocoding(initialLat, initialLng);
    }
  }, [initialLat, initialLng]);

  const handleCurrentLocation = async () => {
    setSearching(true);
    try {
      let coords;
      if (Capacitor.isNativePlatform()) {
        const position = await Geolocation.getCurrentPosition();
        coords = position.coords;
      } else {
        // Fallback to browser geolocation
        const pos = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject);
        });
        coords = pos.coords;
      }

      const { latitude: lat, longitude: lon } = coords;
      setPosition([lat, lon]);
      
      if (mapRef.current) {
        mapRef.current.flyTo([lat, lon], 16);
      }
      
      await performReverseGeocoding(lat, lon);
      
      if (onConfirm) {
        setTimeout(() => onConfirm(), 500); // Auto-confirm after locating
      }
    } catch (error) {
      console.error("Geolocation failed:", error);
      alert("Could not determine your current location. Please check your permissions.");
    } finally {
      setSearching(false);
    }
  };

  const performReverseGeocoding = async (lat, lon) => {
    setSearching(true);
    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1`, {
        headers: {
          'Accept-Language': 'en-US,en;q=0.9',
          'User-Agent': 'LumbaRongApp/1.0' // Nominatim requires a user agent
        }
      });
      const data = await res.json();
      if (data && data.address) {
        const { address } = data;
        const formattedAddress = data.display_name;
        setAddressDetails(formattedAddress);
        
        // Map Nominatim fields to our form fields
        const parsedData = {
          street: address.road || address.pedestrian || address.neighbourhood || '',
          barangay: address.suburb || address.quarter || address.village || '',
          city: address.city || address.town || address.municipality || '',
          province: address.state || address.region || address.county || '',
          postalCode: address.postcode || '',
        };
        
        if (onLocationFound) {
          onLocationFound({ lat, lng: lon, address: parsedData, formatted: formattedAddress });
        }
      }
    } catch (error) {
      console.error("Reverse geocoding failed", error);
    } finally {
      setSearching(false);
    }
  };

  const handleSearchClick = async (e) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      await handleSearch();
      if (onConfirm) {
          // Tiny buffer to let Leaflet animations or address states settle before unmounting map
          setTimeout(() => onConfirm(), 200);
      }
    } else {
      // If no search text, use current location and pin
      await handleCurrentLocation();
    }
  };

  const handleSearch = async () => {
    if (!searchQuery.trim()) return;

    setSearching(true);
    try {
      // Append Philippines to improve search relevance
      const searchTerms = searchQuery + ", Philippines";
      const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchTerms)}&limit=1`, {
         headers: { 'User-Agent': 'LumbaRongApp/1.0' }
      });
      const data = await res.json();
      
      if (data && data.length > 0) {
        const { lat, lon, display_name } = data[0];
        const numLat = parseFloat(lat);
        const numLon = parseFloat(lon);
        
        setPosition([numLat, numLon]);
        setAddressDetails(display_name);
        
        // Also trigger reverse geocoding on the found point to get the exact parts (street, barangay, etc)
        performReverseGeocoding(numLat, numLon);
        
        if (mapRef.current) {
          mapRef.current.flyTo([numLat, numLon], 16);
        }
      } else {
        alert("Location not found. Try variations of the address.");
      }
    } catch (error) {
      console.error("Geocoding failed", error);
    } finally {
      setSearching(false);
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (searchQuery.trim()) {
        handleSearch();
      }
    }
  };

  const handleMapSelect = (lat, lng) => {
    setPosition([lat, lng]);
    performReverseGeocoding(lat, lng);
  };

  return (
    <div className="flex flex-col gap-4 border-2 border-[var(--border)] rounded-2xl p-4 bg-white relative overflow-hidden z-20">
      
      {/* Search Input */}
      <div className="flex gap-2 relative z-30">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
          <input 
            type="text" 
            placeholder="Search city, street, or landmark..." 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            onKeyDown={handleKeyDown}
            className="w-full pl-9 pr-4 py-3 bg-[var(--input-bg)] text-[var(--charcoal)] placeholder:text-[var(--muted)]/50 border border-[var(--border)] outline-none focus:border-[var(--rust)] rounded-xl text-xs font-bold transition-all"
          />
        </div>
        <button 
          type="button" 
          onClick={handleSearchClick}
          disabled={searching}
          className="bg-[var(--rust)] text-white px-5 rounded-xl text-[11px] uppercase tracking-widest font-bold hover:bg-[#b03b25] transition-all flex items-center justify-center min-w-[140px] gap-2 shrink-0 shadow-sm"
        >
          {searching ? <Loader2 className="w-4 h-4 animate-spin" /> : <><Navigation className="w-3.5 h-3.5" /> Pin Address</>}
        </button>
      </div>

      {/* Map Container */}
      <div className="h-64 w-full rounded-xl overflow-hidden shadow-inner border border-[var(--border)] relative z-10">
        <MapContainer 
          center={position} 
          zoom={13} 
          scrollWheelZoom={true} 
          style={{ height: '100%', width: '100%' }}
          ref={mapRef}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <MapEvents 
            markerPosition={position} 
            setMarkerPosition={setPosition} 
            onLocationSelect={handleMapSelect} 
          />
        </MapContainer>
      </div>

      {/* Status Bar */}
      <div className="pt-2 flex flex-col gap-2">
         {addressDetails && (
            <div className="bg-green-50 border border-green-200 text-green-800 text-[10px] font-bold p-3 rounded-lg leading-relaxed flex items-center justify-between">
               <span className="truncate flex-1 pr-4">{addressDetails}</span>
               <span className="text-green-600 bg-white px-2 py-1 rounded shadow-sm opacity-80 shrink-0">📍 Pinned</span>
            </div>
         )}
         <div className="text-[10px] text-[var(--muted)] font-medium italic text-right">
             Drag map or tap anywhere to set exact location.
         </div>
      </div>
    </div>
  );
}
