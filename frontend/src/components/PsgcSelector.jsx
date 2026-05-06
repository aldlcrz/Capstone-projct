"use client";
import React, { useState, useEffect, useRef } from "react";
import { ChevronDown, Loader2 } from "lucide-react";

export default function PsgcSelector({ value, onChange }) {
  const [isOpen, setIsOpen] = useState(false);
  const [tab, setTab] = useState("region");
  const dropdownRef = useRef(null);

  const [regions, setRegions] = useState([]);
  const [provinces, setProvinces] = useState([]);
  const [cities, setCities] = useState([]);
  const [barangays, setBarangays] = useState([]);

  const [loading, setLoading] = useState(false);

  // Close dropdown on outside click
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  // Fetch initial regions
  useEffect(() => {
    if (isOpen && regions.length === 0) {
      setLoading(true);
      fetch("https://psgc.gitlab.io/api/regions/")
        .then((res) => res.json())
        .then((data) => {
          // sort by name or regionName
          data.sort((a, b) => a.name.localeCompare(b.name));
          setRegions(data);
          setLoading(false);
        })
        .catch((err) => {
          console.error(err);
          setLoading(false);
        });
    }
  }, [isOpen, regions.length]);

  const handleSelectRegion = async (region) => {
    onChange({ ...value, region: region.name, province: "", city: "", barangay: "", regionCode: region.code, provinceCode: "", cityCode: "" });
    setLoading(true);
    setProvinces([]);
    setCities([]);
    setBarangays([]);
    
    try {
      const resProv = await fetch(`https://psgc.gitlab.io/api/regions/${region.code}/provinces/`);
      const dataProv = await resProv.json();
      
      if (dataProv.length > 0) {
        dataProv.sort((a, b) => a.name.localeCompare(b.name));
        setProvinces(dataProv);
        setTab("province");
      } else {
        // Handle regions without provinces (like NCR)
        const resCity = await fetch(`https://psgc.gitlab.io/api/regions/${region.code}/cities-municipalities/`);
        const dataCity = await resCity.json();
        dataCity.sort((a, b) => a.name.localeCompare(b.name));
        setCities(dataCity);
        setTab("city"); // skip province
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSelectProvince = async (province) => {
    onChange({ ...value, province: province.name, city: "", barangay: "", provinceCode: province.code, cityCode: "" });
    setLoading(true);
    setCities([]);
    setBarangays([]);
    try {
      const res = await fetch(`https://psgc.gitlab.io/api/provinces/${province.code}/cities-municipalities/`);
      const data = await res.json();
      data.sort((a, b) => a.name.localeCompare(b.name));
      setCities(data);
      setTab("city");
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSelectCity = async (city) => {
    onChange({ ...value, city: city.name, barangay: "", cityCode: city.code });
    setLoading(true);
    setBarangays([]);
    try {
      const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays/`);
      const data = await res.json();
      data.sort((a, b) => a.name.localeCompare(b.name));
      setBarangays(data);
      setTab("barangay");
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleSelectBarangay = (barangay) => {
    onChange({ ...value, barangay: barangay.name });
    setIsOpen(false);
  };

  const displayValue = () => {
    const parts = [];
    if (value.province || value.region) parts.push(value.province || value.region);
    if (value.city) parts.push(value.city);
    if (value.barangay) parts.push(value.barangay);
    return parts.length > 0 ? parts.join(", ") : "Region, Province, City, Barangay";
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <button 
        type="button"
        onClick={() => { setIsOpen(!isOpen); if(!isOpen && !value.region) setTab("region"); }} 
        className="w-full px-4 py-3 border border-gray-200 rounded flex justify-between items-center text-sm text-gray-600 bg-white hover:border-[#C0422A] transition-colors focus:outline-none focus:border-[#C0422A]"
      >
        <span className={displayValue() === "Region, Province, City, Barangay" ? "text-gray-400" : "text-black"}>{displayValue()}</span>
        <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>
      
      {isOpen && (
        <div className="absolute top-full left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden flex flex-col max-h-[350px]">
          <div className="flex border-b border-gray-100 shrink-0">
            {["Region", "Province", "City", "Barangay"].map(t => {
              const lowerT = t.toLowerCase();
              let isDisabled = false;
              if (lowerT === "province" && !value.region) isDisabled = true;
              if (lowerT === "city" && !value.region) isDisabled = true;
              if (lowerT === "barangay" && !value.city) isDisabled = true;
              
              return (
                <button 
                  key={t}
                  type="button"
                  disabled={isDisabled}
                  onClick={() => setTab(lowerT)} 
                  className={`flex-1 py-3 text-[13px] font-medium transition-colors border-b-2 ${tab === lowerT ? 'border-[#C0422A] text-[#C0422A]' : 'border-transparent text-gray-500 hover:text-gray-700'} ${isDisabled ? 'opacity-30 cursor-not-allowed' : ''}`}
                >
                  {t}
                </button>
              );
            })}
          </div>
          
          <div className="flex-1 overflow-y-auto p-2">
            {loading ? (
              <div className="flex justify-center items-center py-12">
                <Loader2 className="w-6 h-6 text-[#C0422A] animate-spin" />
              </div>
            ) : (
              <div className="space-y-1">
                {tab === "region" && regions.map(loc => (
                  <button key={loc.code} type="button" onClick={() => handleSelectRegion(loc)} className={`w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-[#FFF5F2] hover:text-[#C0422A] transition-colors ${value.region === loc.name ? 'bg-[#FFF5F2] text-[#C0422A] font-medium' : 'text-gray-700'}`}>
                    {loc.name}
                  </button>
                ))}
                
                {tab === "province" && provinces.map(loc => (
                  <button key={loc.code} type="button" onClick={() => handleSelectProvince(loc)} className={`w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-[#FFF5F2] hover:text-[#C0422A] transition-colors ${value.province === loc.name ? 'bg-[#FFF5F2] text-[#C0422A] font-medium' : 'text-gray-700'}`}>
                    {loc.name}
                  </button>
                ))}
                
                {tab === "city" && cities.map(loc => (
                  <button key={loc.code} type="button" onClick={() => handleSelectCity(loc)} className={`w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-[#FFF5F2] hover:text-[#C0422A] transition-colors ${value.city === loc.name ? 'bg-[#FFF5F2] text-[#C0422A] font-medium' : 'text-gray-700'}`}>
                    {loc.name}
                  </button>
                ))}
                
                {tab === "barangay" && barangays.map(loc => (
                  <button key={loc.code} type="button" onClick={() => handleSelectBarangay(loc)} className={`w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-[#FFF5F2] hover:text-[#C0422A] transition-colors ${value.barangay === loc.name ? 'bg-[#FFF5F2] text-[#C0422A] font-medium' : 'text-gray-700'}`}>
                    {loc.name}
                  </button>
                ))}
                
                {((tab === "province" && provinces.length === 0) || (tab === "city" && cities.length === 0) || (tab === "barangay" && barangays.length === 0)) && !loading && (
                  <div className="py-8 text-center text-sm text-gray-400">
                    No items found. Please select the previous option first.
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
