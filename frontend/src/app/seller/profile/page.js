"use client";
import React, { useState, useEffect } from "react";
import SellerLayout from "@/components/SellerLayout";
import { User, Mail, Phone, Calendar, ShieldCheck, MapPin, Building, Trash2 } from "lucide-react";
import { motion } from "framer-motion";
import axios from "axios";
import { api } from "@/lib/api";

export default function SellerProfile() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState({ name: '', mobileNumber: '', facebookLink: '', instagramLink: '', gcashNumber: ''});
  const [qrFile, setQrFile] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        const { data } = await api.get('/users/profile');
        setUser(data.user);
        localStorage.setItem("user", JSON.stringify(data.user));
      } catch (err) {
        console.error("Failed to load profile", err);
      } finally {
        setLoading(false);
      }
    };
    fetchProfile();
  }, []);

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    let qrUrl = null;
    try {
      if (qrFile) {
        const formDataObj = new FormData();
        formDataObj.append('image', qrFile);
        const uploadRes = await api.post('/upload', formDataObj, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        qrUrl = uploadRes.data.url;
      }

      const updatePayload = { ...formData };
      if (qrUrl) updatePayload.gcashQrCode = qrUrl;

      const { data } = await api.put('/users/profile', updatePayload);
      
      setUser(data.user);
      localStorage.setItem("user", JSON.stringify(data.user));
      setIsEditing(false);
    } catch (err) {
      console.error("Failed to update profile", err);
      alert("Error updating profile. Please ensure image size is optimized.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <SellerLayout>
      <div className="max-w-4xl mx-auto space-y-12 mb-20">
        <div>
          <div className="eyebrow">Artisan Metadata</div>
          <h1 className="font-serif text-4xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
            Workshop <span className="text-[var(--rust)] italic lowercase">Profile</span>
          </h1>
        </div>

        {loading ? (
             <div className="artisan-card p-24 text-center text-[var(--muted)] animate-pulse italic">Connecting to heritage registry...</div>
        ) : !user ? (
             <div className="artisan-card p-20 text-center text-[var(--muted)]">Session expired. Please sign in to the platform.</div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-10">
            {/* Left: Identity Card */}
            <div className="md:col-span-1 space-y-6">
               <div className="artisan-card p-10 flex flex-col items-center text-center group hover:scale-[1.02] transition-all shadow-2xl relative overflow-hidden bg-white">
                  <div className="absolute top-0 right-0 w-32 h-32 bg-[var(--rust)] opacity-5 -translate-y-1/2 translate-x-1/2 rounded-full ring-4 ring-white" />
                  <div className="w-24 h-24 bg-[var(--bark)] rounded-3xl flex items-center justify-center text-white font-serif text-4xl font-bold shadow-2xl ring-4 ring-white group-hover:rotate-6 transition-transform">
                    {user.name[0]}
                  </div>
                  <div className="mt-8 space-y-2 relative z-10">
                    <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)]">{user.name}</h3>
                    <div className="text-[10px] uppercase font-bold tracking-[0.2em] text-[var(--muted)] opacity-60">Verified Artisan Shop</div>
                  </div>
                  <div className={`mt-6 px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest border shadow-sm ${user.isVerified ? 'bg-green-50 text-green-700 border-green-200 ring-2 ring-white' : 'bg-amber-50 text-amber-700 border-amber-200 ring-2 ring-white animate-pulse'}`}>
                     {user.isVerified ? "Status: Verified" : "Review in Progress"}
                  </div>
               </div>

               <div className="artisan-card p-8 bg-[var(--bark)] text-white shadow-xl relative group">
                  <div className="space-y-4">
                     <div className="text-[10px] font-bold text-white/40 uppercase tracking-widest">Platform Role</div>
                     <div className="font-serif text-xl font-bold flex items-center gap-3"><ShieldCheck className="w-6 h-6 text-[var(--rust)]" /> MASTER ARTISAN</div>
                     <p className="text-xs text-white/50 leading-relaxed italic border-t border-white/10 pt-4 group-hover:text-white/80 transition-colors">You represent the heritage of Lumban. Your works are registered with global authenticity trackers.</p>
                  </div>
               </div>
            </div>

            {/* Right: Detailed Info */}
            <div className="md:col-span-2 space-y-8 animate-fade-in">
               <div className="artisan-card p-12 space-y-10 shadow-2xl bg-white/50 backdrop-blur-md">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-10">
                     <ProfileItem label="Registry Name" value={user.name} icon={<User />} />
                     <ProfileItem label="Heritage Email" value={user.email} icon={<Mail />} />
                     <ProfileItem label="Mobile Connection" value={user.mobile || '+63 9xx xxx xxxx'} icon={<Phone />} />
                     <ProfileItem label="Session Locality" value="Lumban, Laguna, PH" icon={<MapPin />} />
                  </div>

                  <div className="pt-10 border-t border-[var(--border)] grid grid-cols-1 sm:grid-cols-2 gap-10">
                     <ProfileItem label="Established On" value="March 2026" icon={<Calendar />} />
                     <ProfileItem label="Indigency Status" value="Active Support Level" icon={<Building />} />
                  </div>

                  <div className="pt-10 border-t border-[var(--border)] grid grid-cols-1 sm:grid-cols-2 gap-10">
                     <ProfileItem label="Facebook Network" value={user.facebookLink || 'Unlinked'} icon={<User />} />
                     <ProfileItem label="Instagram Portfolio" value={user.instagramLink || 'Unlinked'} icon={<User />} />
                  </div>

                  <div className="pt-10 border-t border-[var(--border)] grid grid-cols-1 sm:grid-cols-2 gap-10">
                     <div className="space-y-2">
                        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2">
                           <Phone className="w-3.5 h-3.5" /> GCash Direct Pay
                        </div>
                        <div className="text-sm font-bold text-[var(--charcoal)] pb-1">{user.gcashNumber || 'Not Configured'}</div>
                     </div>
                     <div className="space-y-4">
                        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2">
                           <ShieldCheck className="w-3.5 h-3.5" /> GCash QR Evidence
                        </div>
                        {user.gcashQrCode ? (
                           <div className="w-24 h-24 rounded-2xl overflow-hidden border border-[var(--border)] shadow-sm bg-[var(--input-bg)] flex items-center justify-center p-1">
                              <img src={user.gcashQrCode.startsWith('http') ? user.gcashQrCode : `http://localhost:5000/uploads/seller_documents/${user.gcashQrCode.split('/').pop()}`} alt="GCash QR Evidence" className="w-full h-full object-cover rounded-xl" onError={(e) => e.target.src = '/images/placeholder.png'} />
                           </div>
                        ) : (
                           <div className="text-xs font-bold text-[var(--muted)] opacity-50 uppercase tracking-widest">No Digital QR Scan</div>
                        )}
                     </div>
                  </div>

                  <div className="pt-10 border-t border-[var(--border)] flex flex-col md:flex-row gap-4">
                     <button 
                       onClick={() => {
                         setFormData({
                           name: user.name || '',
                           mobileNumber: user.mobileNumber || user.mobile || '',
                           facebookLink: user.facebookLink || '',
                           instagramLink: user.instagramLink || '',
                           gcashNumber: user.gcashNumber || ''
                         });
                         setQrFile(null);
                         setIsEditing(true);
                       }}
                       className="flex-1 btn-primary py-4 text-xs font-bold uppercase tracking-widest shadow-xl">
                       Update Platform Identity
                     </button>
                     <button className="flex-1 py-4 px-6 border-2 border-[var(--border)] rounded-xl text-xs font-bold uppercase tracking-widest text-red-500 hover:bg-red-50 hover:border-red-200 transition-all flex items-center justify-center gap-2 group"><Trash2 className="w-4 h-4 group-hover:scale-110" /> Deactivate Workshop</button>
                  </div>
               </div>

               <div className="p-8 bg-amber-50 border border-amber-100 rounded-3xl flex items-center gap-6 shadow-sm">
                  <div className="w-12 h-12 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center shadow-inner"><ShieldCheck className="w-6 h-6" /></div>
                  <div>
                    <div className="text-xs font-bold text-amber-800 uppercase tracking-widest mb-1">Security Recommendation</div>
                    <div className="text-xs text-amber-700/70 font-medium leading-relaxed">Artisan accounts should maintain multi-factor authentication for higher transaction security during heritage auctions.</div>
                  </div>
               </div>
            </div>
          </div>
        )}
      </div>

      {isEditing && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
          <div className="bg-white rounded-3xl w-full max-w-3xl max-h-[90vh] overflow-y-auto p-8 md:p-12 shadow-2xl relative block">
            <button onClick={() => setIsEditing(false)} className="absolute top-8 right-8 text-[var(--muted)] hover:text-black transition-colors bg-gray-100 p-2 rounded-full">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <h3 className="font-serif text-3xl font-bold mb-8 text-[var(--charcoal)]">Edit <span className="text-[var(--rust)] italic">Profile</span></h3>
            
            <form onSubmit={handleUpdateProfile} className="space-y-6">
               <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-2">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Registry Name</label>
                     <input required type="text" value={formData.name} onChange={e => setFormData({...formData, name: e.target.value})} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                  </div>
                  <div className="space-y-2">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Mobile Connection</label>
                     <input type="text" value={formData.mobileNumber} onChange={e => setFormData({...formData, mobileNumber: e.target.value})} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                  </div>
                  <div className="space-y-2">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Facebook (Optional)</label>
                     <input type="text" value={formData.facebookLink} onChange={e => setFormData({...formData, facebookLink: e.target.value})} placeholder="e.g. facebook.com/shop" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                  </div>
                  <div className="space-y-2">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Instagram (Optional)</label>
                     <input type="text" value={formData.instagramLink} onChange={e => setFormData({...formData, instagramLink: e.target.value})} placeholder="e.g. instagram.com/shop" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                  </div>
                  <div className="space-y-2">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">GCash Direct Pay Number</label>
                     <input type="text" value={formData.gcashNumber} onChange={e => setFormData({...formData, gcashNumber: e.target.value})} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                  </div>
                  <div className="space-y-2">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">New GCash QR Code (Image)</label>
                     <input type="file" accept="image/*" onChange={e => setQrFile(e.target.files[0])} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm flex items-center focus:outline-none" />
                  </div>
               </div>
               <div className="pt-4 mt-8 border-t border-[var(--border)]">
                 <button disabled={isSubmitting} type="submit" className="w-full btn-primary py-4 text-xs font-bold uppercase tracking-widest shadow-xl flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed">
                    {isSubmitting ? (
                      <span className="flex items-center gap-2">
                        <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Pushing to Registry...
                      </span>
                    ) : (
                      "Save Identity Metadata"
                    )}
                 </button>
               </div>
            </form>
          </div>
        </div>
      )}
    </SellerLayout>
  );
}

function ProfileItem({ label, value, icon }) {
  return (
    <div className="space-y-2 group">
      <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2 group-hover:text-[var(--rust)] transition-colors">
        {React.cloneElement(icon, { className: "w-3.5 h-3.5" })} {label}
      </div>
      <div className="text-sm font-bold text-[var(--charcoal)] border-b border-transparent group-hover:border-[var(--border)] pb-1 transition-all">
        {value}
      </div>
    </div>
  );
}
