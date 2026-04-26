"use client";
import React from "react";
import { MapContainer, TileLayer, Marker, Popup } from "react-leaflet";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { X, Navigation } from "lucide-react";

// Fix for default marker icons in Leaflet
const customIcon = typeof window !== 'undefined' ? new L.Icon({
  iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
  iconRetinaUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
}) : null;

export default function ShopMap({ seller, onClose }) {
  if (!seller?.shopLatitude || !seller?.shopLongitude) return null;
  
  const position = [seller.shopLatitude, seller.shopLongitude];
  const googleMapsUrl = `https://www.google.com/maps?q=${seller.shopLatitude},${seller.shopLongitude}`;

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-6 lg:p-8">
      <div className="absolute inset-0 bg-[#2A1E14]/60 backdrop-blur-sm" onClick={onClose} />
      <div className="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col border border-white/20 animate-fade-up">
        {/* Header */}
        <div className="p-6 border-b border-[var(--border)] flex items-center justify-between bg-[#FDFCFB]">
          <div>
            <div className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-[0.2em] mb-1">Workshop Location</div>
            <h2 className="text-xl font-bold font-serif text-[#2A2A2A]">{seller.shopName}</h2>
          </div>
          <button onClick={onClose} className="p-3 bg-white hover:bg-[var(--cream)] rounded-full transition-all border border-[var(--border)] active:scale-95 shadow-sm">
            <X className="w-5 h-5 text-[var(--muted)]" />
          </button>
        </div>

        {/* Map Area */}
        <div className="h-[400px] relative z-10 w-full bg-[#FDFBF9]">
          <MapContainer center={position} zoom={16} scrollWheelZoom={true} style={{ height: "100%", width: "100%" }}>
            <TileLayer
              attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
              url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            {customIcon && (
              <Marker position={position} icon={customIcon}>
                <Popup>
                  <div className="text-center p-1">
                    <div className="font-bold text-[var(--rust)]">{seller.shopName}</div>
                    <div className="text-[10px] mt-1 italic">{seller.location}</div>
                  </div>
                </Popup>
              </Marker>
            )}
          </MapContainer>
        </div>

        {/* Footer */}
        <div className="p-6 bg-[#FDFCFB] border-t border-[var(--border)] flex flex-col sm:flex-row items-center gap-4">
          <div className="flex-1 text-center sm:text-left">
            <div className="text-xs text-[var(--muted)] mb-1">Workshop Address</div>
            <div className="text-sm font-bold text-[#2A2A2A]">{seller.location || "Lumban, Laguna"}</div>
          </div>
          <a
            href={googleMapsUrl}
            target="_blank"
            rel="noreferrer"
            className="w-full sm:w-auto bg-[var(--rust)] text-white px-8 py-4 rounded-2xl text-[10px] uppercase tracking-[0.2em] font-extrabold flex items-center justify-center gap-3 shadow-lg active:scale-95 transition-all"
          >
            <Navigation className="w-4 h-4" /> Go to Shop
          </a>
        </div>
      </div>
    </div>
  );
}
