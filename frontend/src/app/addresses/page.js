"use client";
import React, { useState, useEffect } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { 
  Plus, 
  MapPin, 
  Edit2, 
  Trash2, 
  CheckCircle2, 
  Home, 
  Briefcase, 
  Navigation,
  Loader2,
  X,
  Phone,
  User
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import dynamic from 'next/dynamic';

const LocationPicker = dynamic(
  () => import('@/components/LocationPicker'),
  { ssr: false, loading: () => <div className="h-64 w-full bg-slate-100 animate-pulse rounded-xl border border-[var(--border)] flex items-center justify-center text-[var(--muted)] text-xs font-bold uppercase tracking-widest leading-relaxed text-center">Initializing <br/> Satellite Imagery...</div> }
);

export default function AddressesPage() {
  const [addresses, setAddresses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editingAddress, setEditingAddress] = useState(null);
  const [formData, setFormData] = useState({
    recipientName: "",
    phone: "",
    houseNo: "",
    street: "",
    barangay: "",
    city: "",
    province: "",
    postalCode: "",
    isDefault: false,
    latitude: null,
    longitude: null
  });

  const fetchAddresses = async () => {
    try {
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

  const handleOpenModal = (address = null) => {
    if (address) {
      setEditingAddress(address);
      setFormData(address);
    } else {
      setEditingAddress(null);
      setFormData({
        recipientName: "",
        phone: "",
        houseNo: "",
        street: "",
        barangay: "",
        city: "",
        province: "",
        postalCode: "",
        isDefault: false,
        latitude: null,
        longitude: null
      });
    }
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (editingAddress) {
        await api.put(`/addresses/${editingAddress.id}`, formData);
      } else {
        await api.post("/addresses", formData);
      }
      setShowModal(false);
      fetchAddresses();
    } catch (error) {
      alert("Failed to save address.");
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this address?")) return;
    try {
      await api.delete(`/addresses/${id}`);
      fetchAddresses();
    } catch (error) {
      alert("Failed to delete address.");
    }
  };

  const handleSetDefault = async (id) => {
    try {
      await api.patch(`/addresses/${id}/set-default`, {});
      fetchAddresses();
    } catch (error) {
      alert("Failed to update default address.");
    }
  };

  const handlePinLocation = () => {
    // Legacy generic pin replaced by interactive map
    setShowMapPicker(!showMapPicker);
  };

  const handleLocationFound = ({ lat, lng, address }) => {
    setFormData(prev => ({
      ...prev,
      latitude: lat,
      longitude: lng,
      street: address.street || prev.street,
      barangay: address.barangay || prev.barangay,
      city: address.city || prev.city,
      province: address.province || prev.province,
      postalCode: address.postalCode || prev.postalCode,
    }));
  };

  const [showMapPicker, setShowMapPicker] = useState(false);

  const handlePinLocationInCard = async (id) => {
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(async (position) => {
        try {
          await api.put(`/addresses/${id}`, {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          }, {
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
          });
          alert("📍 Location pinned to this address successfully!");
          fetchAddresses();
        } catch (e) {
          alert("Failed to update coordinates.");
        }
      }, () => {
        alert("Unable to get location.");
      });
    }
  };

  return (
    <CustomerLayout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex justify-between items-center mb-12">
          <div>
            <h1 className="text-5xl font-serif font-bold tracking-tighter text-[var(--charcoal)] uppercase">
              MY <span className="text-[var(--rust)] italic lowercase font-normal">address book</span>
            </h1>
            <p className="text-[var(--muted)] mt-2 font-medium">Manage your delivery registry for seamless heritage dispatch.</p>
          </div>
          
          <button 
            onClick={() => handleOpenModal()}
            className="flex items-center gap-3 bg-[var(--rust)] text-white px-8 py-4 rounded-xl font-bold uppercase text-[10px] tracking-widest shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            <Plus className="w-4 h-4" /> Add New Address
          </button>
        </div>

        {loading && addresses.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-24 gap-4">
            <Loader2 className="w-12 h-12 animate-spin text-[var(--rust)]" />
            <p className="font-serif italic text-xl text-[var(--muted)]">Consulting the registry...</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <AnimatePresence>
              {addresses.map((addr) => (
                <motion.div 
                  key={addr.id}
                  initial={{ opacity: 0, scale: 0.95 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.95 }}
                  className={`relative group bg-[#FDFBF9] border-2 rounded-[2rem] p-8 transition-all duration-500 overflow-hidden shadow-sm hover:shadow-2xl ${addr.isDefault ? 'border-[var(--rust)] ring-4 ring-[var(--rust)]/5' : 'border-[var(--border)] hover:border-[var(--rust)]/30'}`}
                >
                  {/* Default Badge */}
                  <div className="flex justify-between items-start mb-6">
                    {addr.isDefault ? (
                      <div className="bg-[var(--rust)] text-white px-3 py-1 rounded-lg text-[8px] font-extrabold uppercase tracking-widest">Default</div>
                    ) : (
                      <button 
                        onClick={() => handleSetDefault(addr.id)}
                        className="text-[8px] font-extrabold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors"
                      >
                        Set as Default
                      </button>
                    )}
                    
                    <div className="flex items-center gap-4">
                      <button 
                        onClick={() => handlePinLocationInCard(addr.id)}
                        className="p-2 bg-[var(--rust)]/5 rounded-lg text-[var(--rust)] hover:bg-[var(--rust)] hover:text-white transition-all shadow-sm"
                        title="Pin Precise Location"
                      >
                         <MapPin className="w-4 h-4" />
                      </button>
                      <button 
                        onClick={() => handleOpenModal(addr)}
                        className="text-[10px] font-bold uppercase tracking-widest text-[var(--rust)] hover:underline flex items-center gap-1"
                      >
                         Edit
                      </button>
                      <button 
                         onClick={() => handleDelete(addr.id)}
                         className="text-[var(--muted)] hover:text-red-500 transition-colors"
                      >
                         <Trash2 className="w-4 h-4" />
                      </button>
                    </div>
                  </div>

                  <div className="space-y-4 relative z-10">
                    <div>
                      <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tight">{addr.recipientName}</h3>
                      <p className="text-xs font-bold text-[var(--muted)] mt-1">{addr.phone}</p>
                    </div>

                    <div className="text-sm text-[var(--muted)] leading-relaxed font-medium">
                      {addr.houseNo} {addr.street},<br />
                      Brgy. {addr.barangay}, {addr.city},<br />
                      {addr.province}, {addr.postalCode}
                    </div>

                    {addr.latitude && (
                      <div className="flex items-center gap-2 text-[10px] font-bold text-[var(--rust)] uppercase tracking-widest bg-[var(--rust)]/5 py-2 px-3 rounded-lg w-fit">
                        <MapPin className="w-3 h-3" /> Pinned: {addr.latitude.toFixed(4)}, {addr.longitude.toFixed(4)}
                      </div>
                    )}
                  </div>

                  {/* Corner Map Icon Decoration/Action */}
                  <div className="absolute -bottom-4 -right-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <Navigation className="w-32 h-32 rotate-12" />
                  </div>
                </motion.div>
              ))}
            </AnimatePresence>

            {addresses.length === 0 && !loading && (
              <div className="col-span-full py-24 text-center bg-white/50 backdrop-blur-xl rounded-[3rem] border-2 border-dashed border-[var(--border)]">
                 <div className="bg-[var(--cream)] w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <MapPin className="w-8 h-8 text-[var(--muted)]" />
                 </div>
                 <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)]">Registry Empty</h3>
                 <p className="text-[var(--muted)] text-sm mb-8">No delivery destinations found in your heritage library.</p>
                 <button 
                   onClick={() => handleOpenModal()}
                   className="bg-[var(--rust)] text-white px-10 py-4 rounded-xl font-bold uppercase text-[10px] tracking-widest shadow-xl"
                 >
                   Establish New Address
                 </button>
              </div>
            )}
          </div>
        )}

        {/* Add/Edit Modal */}
        <AnimatePresence>
          {showModal && (
            <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
               <motion.div 
                 initial={{ opacity: 0 }} 
                 animate={{ opacity: 1 }} 
                 exit={{ opacity: 0 }} 
                 onClick={() => setShowModal(false)}
                 className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-md"
               />
               <motion.div 
                 initial={{ scale: 0.9, y: 20, opacity: 0 }}
                 animate={{ scale: 1, y: 0, opacity: 1 }}
                 exit={{ scale: 0.9, y: 20, opacity: 0 }}
                 className="relative bg-white w-full max-w-2xl rounded-[3rem] shadow-[0_40px_100px_rgba(0,0,0,0.3)] overflow-hidden"
               >
                  <div className="p-12">
                    <div className="flex justify-between items-start mb-10">
                      <div>
                        <h2 className="font-serif text-4xl font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                          {editingAddress ? 'Update' : 'Establish'} <span className="text-[var(--rust)] italic lowercase">Address</span>
                        </h2>
                        <p className="text-[var(--muted)] text-[10px] font-bold uppercase tracking-widest mt-2">{editingAddress ? 'Revise existing registry' : 'Add a new fulfillment node'}</p>
                      </div>
                      <button onClick={() => setShowModal(false)} className="p-3 bg-[var(--input-bg)] rounded-2xl text-[var(--muted)] hover:text-red-500 transition-colors">
                        <X className="w-6 h-6" />
                      </button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                       <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <InputGroup label="Recipient Full Name" value={formData.recipientName} onChange={(val) => setFormData({...formData, recipientName: val})} icon={<User className="w-4 h-4" />} placeholder="e.g. Juan Dela Cruz" />
                          <InputGroup label="Phone Number" value={formData.phone} onChange={(val) => setFormData({...formData, phone: val})} icon={<Phone className="w-4 h-4" />} placeholder="0912 345 6789" />
                          
                          <InputGroup label="House No. / Building" value={formData.houseNo} onChange={(val) => setFormData({...formData, houseNo: val})} icon={<Home className="w-4 h-4" />} placeholder="House 123" />
                          <InputGroup label="Street" value={formData.street} onChange={(val) => setFormData({...formData, street: val})} icon={<MapPin className="w-4 h-4" />} placeholder="M.H. Del Pilar St." />
                          
                          <InputGroup label="Barangay" value={formData.barangay} onChange={(val) => setFormData({...formData, barangay: val})} icon={<MapPin className="w-4 h-4" />} placeholder="Poblacion" />
                          <InputGroup label="City / Municipality" value={formData.city} onChange={(val) => setFormData({...formData, city: val})} icon={<MapPin className="w-4 h-4" />} placeholder="Lumban" />
                          
                          <InputGroup label="Province" value={formData.province} onChange={(val) => setFormData({...formData, province: val})} icon={<MapPin className="w-4 h-4" />} placeholder="Laguna" />
                          <InputGroup label="Postal Code" value={formData.postalCode} onChange={(val) => setFormData({...formData, postalCode: val})} icon={<MapPin className="w-4 h-4" />} placeholder="4014" />
                       </div>

                       <div className="flex flex-col sm:flex-row gap-4 items-center justify-between border-t border-[var(--border)] pt-8">
                           <button 
                             type="button"
                             onClick={() => setShowMapPicker(!showMapPicker)}
                             className={`flex items-center gap-3 px-6 py-4 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all ${formData.latitude ? 'bg-green-50 text-green-700 border-2 border-green-200 shadow-sm' : (showMapPicker ? 'bg-[var(--rust)] text-[white]' : 'bg-[var(--input-bg)] text-[var(--muted)] hover:bg-[var(--rust)] hover:text-white')}`}
                           >
                              <MapPin className="w-4 h-4" /> {formData.latitude ? `Location Pinned (${formData.latitude.toFixed(4)})` : (showMapPicker ? 'Hide Interactive Map' : 'Drop Precise Map Pin')}
                           </button>

                          <label className="flex items-center gap-3 cursor-pointer group">
                             <div 
                               onClick={() => setFormData({...formData, isDefault: !formData.isDefault})}
                               className={`w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all ${formData.isDefault ? 'bg-[var(--rust)] border-[var(--rust)]' : 'border-[var(--border)] group-hover:border-[var(--rust)]'}`}
                             >
                                {formData.isDefault && <CheckCircle2 className="w-4 h-4 text-white" />}
                             </div>
                             <span className="text-[10px] font-bold uppercase tracking-widest text-[var(--charcoal)] opacity-80">Set as default destination</span>
                          </label>
                       </div>

                        <AnimatePresence>
                          {showMapPicker && (
                            <motion.div
                              initial={{ opacity: 0, height: 0 }}
                              animate={{ opacity: 1, height: 'auto' }}
                              exit={{ opacity: 0, height: 0 }}
                              className="overflow-hidden"
                            >
                              <div className="pt-6 border-t border-[var(--border)]">
                                <label className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[var(--charcoal)] opacity-70 block mb-4">Interactive Heritage Map</label>
                                <LocationPicker 
                                  onLocationFound={handleLocationFound}
                                  initialLat={formData.latitude || 14.2952}
                                  initialLng={formData.longitude || 121.4647}
                                />
                              </div>
                            </motion.div>
                          )}
                        </AnimatePresence>

                       <button 
                         type="submit" 
                         disabled={loading}
                         className="w-full bg-[var(--rust)] text-white py-6 rounded-2xl font-bold uppercase text-xs tracking-[0.3em] shadow-[0_20px_40px_rgba(var(--rust-rgb),0.3)] hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3"
                       >
                         {loading ? <Loader2 className="w-6 h-6 animate-spin" /> : (editingAddress ? 'Update Registry' : 'Confirm New Address')}
                       </button>
                    </form>
                  </div>
               </motion.div>
            </div>
          )}
        </AnimatePresence>
      </div>
    </CustomerLayout>
  );
}

function InputGroup({ label, value, onChange, icon, placeholder }) {
  return (
    <div className="space-y-3">
      <label className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[var(--charcoal)] ml-1 opacity-70">{label}</label>
      <div className="relative group">
        <div className="absolute top-1/2 -translate-y-1/2 left-5 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors">
          {icon}
        </div>
        <input 
          type="text" 
          required
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          className="w-full bg-[var(--input-bg)] text-[var(--charcoal)] placeholder:text-[var(--muted)]/50 border-2 border-transparent outline-none focus:border-[var(--rust)] focus:bg-white transition-all pl-14 pr-6 py-5 rounded-2xl text-xs font-bold shadow-inner"
        />
      </div>
    </div>
  );
}
