"use client";
import React, { useState, useEffect, useMemo } from "react";
import { 
  Plus, 
  MapPin, 
  Trash2, 
  Home, 
  Navigation,
  Loader2,
  X,
  Phone,
  User,
  Check,
  ChevronRight,
  Search,
  Pencil,
  ChevronDown,
  Map as MapIcon,
  Briefcase
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getTokenForRole } from "@/lib/api";
import dynamic from 'next/dynamic';
import {
  INPUT_LIMITS,
  normalizeAddressPayload,
  sanitizeAddressLineInput,
  sanitizePersonNameInput,
  sanitizePhoneInput,
  sanitizePlaceNameInput,
  sanitizePostalCodeInput,
} from "@/lib/inputValidation";
import PsgcSelector from "@/components/PsgcSelector";

const LocationPickerMap = dynamic(() => import('@/components/LocationPicker'), { ssr: false });

export default function AddressManager({ onUpdate }) {
  const [addresses, setAddresses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [view, setView] = useState("list"); // "list" or "form"
  const [editingAddress, setEditingAddress] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [showPsgcSelector, setShowPsgcSelector] = useState(false);
  const [psgcTab, setPsgcTab] = useState("region");
  const [showMapModal, setShowMapModal] = useState(false);

  const fetchAddresses = async () => {
    const token = getTokenForRole("customer");
    if (!token) {
      setLoading(false);
      return;
    }
    try {
      setLoading(true);
      const response = await api.get("/addresses");
      setAddresses(response.data);
    } catch (error) {
      console.error("Failed to fetch addresses:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAddresses();
  }, []);

  const filteredAddresses = useMemo(() => {
    return addresses.filter(addr => {
      const s = searchQuery.toLowerCase();
      return (
        addr.recipientName?.toLowerCase().includes(s) ||
        addr.houseNo?.toLowerCase().includes(s) ||
        addr.street?.toLowerCase().includes(s) ||
        addr.barangay?.toLowerCase().includes(s) ||
        addr.city?.toLowerCase().includes(s)
      );
    });
  }, [addresses, searchQuery]);

  const handleEdit = (addr) => {
    const names = (addr.recipientName || "").split(" ");
    setEditingAddress({
      ...addr,
      firstName: names[0] || "",
      lastName: names.slice(1).join(" ") || "",
      label: addr.label || (addr.isDefault ? "Home" : "Work")
    });
    setView("form");
  };

  const handleAddNew = () => {
    setEditingAddress({
      recipientName: "",
      firstName: "",
      lastName: "",
      phone: "",
      houseNo: "",
      street: "",
      barangay: "",
      city: "",
      province: "",
      postalCode: "",
      label: "Home",
      isDefault: false,
      latitude: null,
      longitude: null
    });
    setView("form");
  };

  const handleSave = async () => {
    if (!editingAddress.firstName && !editingAddress.recipientName) {
      alert("Please enter a name.");
      return;
    }
    if (!editingAddress.phone) {
      alert("Please enter a phone number.");
      return;
    }

    setLoading(true);
    try {
      const combinedName = editingAddress.recipientName || `${editingAddress.firstName} ${editingAddress.lastName}`.trim();
      const payload = {
        ...normalizeAddressPayload({ ...editingAddress, recipientName: combinedName }),
        latitude: editingAddress.latitude,
        longitude: editingAddress.longitude,
        isDefault: editingAddress.isDefault,
        label: editingAddress.label
      };

      if (editingAddress.id) {
        await api.put(`/addresses/${editingAddress.id}`, payload);
      } else {
        await api.post("/addresses", payload);
      }
      
      await fetchAddresses();
      if (onUpdate) onUpdate();
      setView("list");
    } catch (err) {
      alert(err.response?.data?.message || "Failed to save address.");
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this address?")) return;
    try {
      await api.delete(`/addresses/${id}`);
      fetchAddresses();
      if (onUpdate) onUpdate();
    } catch (error) {
      alert("Failed to delete address.");
    }
  };

  if (view === "form") {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between border-b border-stone-100 pb-5">
          <div className="flex items-center gap-4">
            <button onClick={() => setView("list")} className="p-2 hover:bg-stone-50 rounded-full transition-colors">
              <ChevronRight className="w-5 h-5 rotate-180" />
            </button>
            <h2 className="text-xl font-medium text-stone-800">{editingAddress?.id ? "Edit Address" : "New Address"}</h2>
          </div>
        </div>

        <div className="max-w-2xl space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="relative">
              <label className="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1 block">Full Name</label>
              <input 
                type="text" 
                placeholder="Full Name" 
                value={editingAddress.recipientName || (editingAddress.firstName + " " + editingAddress.lastName).trim()} 
                onChange={(e) => {
                  const val = e.target.value;
                  const parts = val.split(" ");
                  setEditingAddress({
                    ...editingAddress, 
                    recipientName: val,
                    firstName: parts[0] || "",
                    lastName: parts.slice(1).join(" ") || ""
                  });
                }} 
                className="w-full px-4 py-3 border border-stone-200 rounded-sm focus:border-(--rust) outline-none text-sm placeholder-stone-300" 
              />
            </div>
            <div className="relative">
              <label className="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1 block">Phone Number</label>
              <input 
                type="text" 
                placeholder="Phone Number" 
                value={editingAddress.phone} 
                onChange={(e) => setEditingAddress({...editingAddress, phone: sanitizePhoneInput(e.target.value)})} 
                className="w-full px-4 py-3 border border-stone-200 rounded-sm focus:border-(--rust) outline-none text-sm placeholder-stone-300" 
              />
            </div>
          </div>

          <div className="relative">
            <label className="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1 block">Region, Province, City, Barangay</label>
            <PsgcSelector 
              value={editingAddress} 
              onChange={(newVal) => setEditingAddress(newVal)} 
            />
          </div>

          <div className="relative">
            <label className="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1 block">Postal Code</label>
            <input 
              type="text" 
              placeholder="Postal Code" 
              value={editingAddress.postalCode} 
              onChange={(e) => setEditingAddress({...editingAddress, postalCode: sanitizePostalCodeInput(e.target.value)})} 
              className="w-full px-4 py-3 border border-stone-200 rounded-sm focus:border-(--rust) outline-none text-sm placeholder-stone-300" 
            />
          </div>

          <div className="relative">
            <label className="text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1 block">Street Name, Building, House No.</label>
            <textarea 
              placeholder="Street Name, Building, House No." 
              value={editingAddress.houseNo} 
              onChange={(e) => setEditingAddress({...editingAddress, houseNo: e.target.value})} 
              className="w-full px-4 py-3 border border-stone-200 rounded-sm focus:border-(--rust) outline-none text-sm placeholder-stone-300 min-h-[100px] resize-none" 
            />
          </div>

          {/* REALTIME MAP SECTION */}
          <div className="relative border border-stone-100 rounded-sm overflow-hidden bg-stone-50 group">
            {(!editingAddress.latitude || !editingAddress.longitude) ? (
              <button 
                onClick={() => setShowMapModal(true)} 
                className="w-full h-40 flex flex-col items-center justify-center gap-2 hover:bg-stone-100 transition-colors"
              >
                <div className="w-10 h-10 rounded-full border border-stone-300 flex items-center justify-center text-stone-300 group-hover:border-(--rust) group-hover:text-(--rust) transition-all">
                  <Plus className="w-6 h-6" />
                </div>
                <span className="text-sm font-medium text-stone-400 group-hover:text-(--rust)">Add Geolocation Pin</span>
              </button>
            ) : (
              <div className="h-48 relative">
                <LocationPickerMap 
                  initialLat={editingAddress.latitude} 
                  initialLng={editingAddress.longitude}
                  readOnly={true}
                />
                <div className="absolute inset-0 bg-black/5 pointer-events-none" />
                <button 
                  onClick={() => setShowMapModal(true)} 
                  className="absolute top-4 right-4 z-20 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full shadow-lg border border-stone-100 text-(--rust) hover:bg-white text-xs font-bold flex items-center gap-2"
                >
                  <MapIcon className="w-3.5 h-3.5" /> Change pin point
                </button>
              </div>
            )}
          </div>

          <div className="space-y-4">
            <label className="text-[10px] font-bold text-stone-400 uppercase tracking-widest block">Label As:</label>
            <div className="flex gap-4">
              {[
                { id: "Home", icon: <Home className="w-4 h-4" /> },
                { id: "Work", icon: <Briefcase className="w-4 h-4" /> }
              ].map(l => (
                <button 
                  key={l.id} 
                  type="button"
                  onClick={() => setEditingAddress({...editingAddress, label: l.id})} 
                  className={`flex-1 flex items-center justify-center gap-2 py-3 rounded-sm border transition-all text-sm font-medium ${editingAddress.label === l.id ? 'border-(--rust) text-(--rust) bg-(--rust)/5 shadow-sm' : 'border-stone-100 text-stone-400 hover:border-stone-200'}`}
                >
                  {l.icon} {l.id}
                </button>
              ))}
            </div>
          </div>

          <div className="flex items-center gap-3 py-2">
            <label className="flex items-center gap-3 cursor-pointer group">
              <div 
                onClick={() => setEditingAddress({...editingAddress, isDefault: !editingAddress.isDefault})}
                className={`w-5 h-5 rounded-sm border flex items-center justify-center transition-all ${editingAddress.isDefault ? 'bg-(--rust) border-(--rust)' : 'border-stone-200 group-hover:border-stone-400'}`}
              >
                {editingAddress.isDefault && <Check className="w-3.5 h-3.5 text-white" />}
              </div>
              <span className="text-sm text-stone-500 select-none">Set as Default Address</span>
            </label>
          </div>

          <div className="pt-8 flex items-center justify-end gap-8">
            <button onClick={() => setView("list")} className="text-sm font-medium text-stone-400 hover:text-stone-800 transition-colors">Cancel</button>
            <button 
              onClick={handleSave} 
              disabled={loading} 
              className="px-16 py-3.5 bg-(--rust) text-white rounded-sm text-sm font-bold uppercase tracking-widest hover:opacity-95 transition-all shadow-md shadow-(--rust)/10 disabled:opacity-50 flex items-center gap-3"
            >
              {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : "Submit"}
            </button>
          </div>
        </div>

        {/* Realtime Map Picker Overlay */}
        <AnimatePresence>
          {showMapModal && (
            <div className="fixed inset-0 z-300 flex items-center justify-center p-4">
              <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setShowMapModal(false)} className="absolute inset-0 bg-stone-900/60 backdrop-blur-sm" />
              <motion.div initial={{ scale: 0.9, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.9, opacity: 0 }} className="relative w-full max-w-4xl bg-white rounded-sm overflow-hidden shadow-2xl h-[85vh] flex flex-col">
                 <div className="p-6 border-b border-stone-100 flex justify-between items-center bg-stone-50">
                    <h3 className="text-lg font-medium text-stone-800">Pin Your Exact Location</h3>
                    <button onClick={() => setShowMapModal(false)} className="w-10 h-10 rounded-full bg-white flex items-center justify-center border border-stone-100 hover:bg-stone-50 transition-colors shadow-sm"><X className="w-5 h-5" /></button>
                 </div>
                 <div className="flex-1 relative">
                    <LocationPickerMap 
                      onLocationSelect={(lat, lng, addrObj) => {
                        setEditingAddress(prev => ({ 
                          ...prev, 
                          latitude: lat, 
                          longitude: lng,
                          houseNo: addrObj.houseNo || addrObj.houseNumber || prev.houseNo,
                          street: addrObj.street || prev.street,
                          barangay: addrObj.barangay || prev.barangay,
                          city: addrObj.city || prev.city,
                          province: addrObj.province || prev.province,
                          region: addrObj.region || prev.region,
                          postalCode: addrObj.postalCode || prev.postalCode
                        }));
                        setShowMapModal(false);
                      }}
                    />
                 </div>
                 <div className="p-4 bg-stone-800 text-center">
                    <p className="text-[11px] text-stone-400 font-medium uppercase tracking-widest">Move the pin to match your doorstep for precise delivery</p>
                 </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <div className="flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div className="relative w-full sm:w-80 group">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-300 group-focus-within:text-(--rust) transition-colors" />
          <input 
            type="text" 
            placeholder="Search address..." 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-11 pr-4 py-3 bg-stone-50 border border-stone-100 rounded-sm text-sm focus:bg-white focus:border-stone-300 outline-none transition-all placeholder-stone-300 font-medium" 
          />
        </div>
        <button 
          onClick={handleAddNew}
          className="w-full sm:w-auto flex items-center justify-center gap-2 bg-(--rust) text-white px-8 py-3 rounded-sm text-xs font-bold uppercase tracking-widest shadow-lg hover:shadow-(--rust)/20 transition-all active:scale-[0.98]"
        >
          <Plus className="w-4 h-4" /> Add New Address
        </button>
      </div>

      <div className="grid grid-cols-1 gap-4">
        {loading && addresses.length === 0 ? (
          <div className="py-20 text-center space-y-4">
            <Loader2 className="w-10 h-10 animate-spin text-stone-200 mx-auto" />
            <p className="text-sm text-stone-400 font-serif italic tracking-wide">Loading your address book...</p>
          </div>
        ) : filteredAddresses.length > 0 ? (
          filteredAddresses.map(addr => (
            <motion.div 
              key={addr.id}
              initial={{ opacity: 0, y: 5 }}
              animate={{ opacity: 1, y: 0 }}
              className={`group relative bg-white border rounded-sm p-6 transition-all hover:border-stone-300 ${addr.isDefault ? 'border-(--rust) shadow-sm' : 'border-stone-100'}`}
            >
              <div className="flex flex-col md:flex-row justify-between gap-6">
                <div className="flex gap-6">
                  {/* Map Preview Icon */}
                  <div className="hidden sm:flex w-24 h-24 bg-stone-50 rounded-sm items-center justify-center shrink-0 border border-stone-100 relative overflow-hidden group-hover:bg-stone-100 transition-colors">
                    {addr.latitude ? (
                      <div className="w-full h-full opacity-40 grayscale group-hover:grayscale-0 transition-all duration-700">
                        <LocationPickerMap 
                          initialLat={addr.latitude} 
                          initialLng={addr.longitude}
                          readOnly={true}
                        />
                      </div>
                    ) : (
                      <MapPin className="w-8 h-8 text-stone-200 group-hover:text-stone-300 transition-colors" />
                    )}
                    <div className="absolute inset-0 border border-black/5" />
                  </div>

                  <div className="space-y-2">
                    <div className="flex items-center gap-3">
                      <span className="text-sm font-bold text-stone-800">{addr.recipientName}</span>
                      <div className="h-4 w-px bg-stone-200" />
                      <span className="text-sm text-stone-500">{addr.phone}</span>
                    </div>
                    <p className="text-sm text-stone-500 leading-relaxed max-w-xl">
                      {addr.houseNo} {addr.street}, {addr.barangay}, {addr.city}, {addr.province}, {addr.region} {addr.postalCode}
                    </p>
                    <div className="flex items-center gap-2 pt-1">
                      {addr.isDefault && (
                        <span className="px-2 py-0.5 border border-(--rust) text-[10px] font-bold text-(--rust) uppercase tracking-widest rounded-sm">Default</span>
                      )}
                      {addr.label && (
                        <span className="px-2 py-0.5 border border-stone-200 text-[10px] font-bold text-stone-400 uppercase tracking-widest rounded-sm">{addr.label}</span>
                      )}
                    </div>
                  </div>
                </div>

                <div className="flex md:flex-col items-center md:items-end justify-between md:justify-start gap-4 shrink-0">
                  <div className="flex gap-4">
                    <button onClick={() => handleEdit(addr)} className="text-sm font-medium text-stone-800 hover:text-(--rust) transition-colors flex items-center gap-1">
                      <Pencil className="w-3.5 h-3.5" /> Edit
                    </button>
                    <button onClick={() => handleDelete(addr.id)} className="text-sm font-medium text-stone-400 hover:text-red-500 transition-colors flex items-center gap-1">
                      <Trash2 className="w-3.5 h-3.5" /> Delete
                    </button>
                  </div>
                  {!addr.isDefault && (
                    <button 
                      onClick={async () => {
                        try {
                          await api.patch(`/addresses/${addr.id}/set-default`);
                          fetchAddresses();
                          if (onUpdate) onUpdate();
                        } catch (e) { alert("Failed to set default."); }
                      }}
                      className="text-xs font-bold text-stone-400 hover:text-stone-800 border-b border-stone-200 hover:border-stone-800 transition-all pb-0.5"
                    >
                      Set as default
                    </button>
                  )}
                </div>
              </div>
            </motion.div>
          ))
        ) : (
          <div className="py-20 text-center border-2 border-dashed border-stone-100 rounded-sm">
            <MapPin className="w-12 h-12 text-stone-100 mx-auto mb-4" />
            <h3 className="text-stone-800 font-medium">No addresses found</h3>
            <p className="text-sm text-stone-400 mt-1">Start by adding a new delivery registry node.</p>
          </div>
        )}
      </div>
    </div>
  );
}
