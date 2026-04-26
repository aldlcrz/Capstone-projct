"use client";
import React, { useState, useEffect } from "react";
import SellerLayout from "@/components/SellerLayout";
import { User, Mail, Phone, Calendar, ShieldCheck, MapPin, Building, Trash2, Facebook, Instagram, Youtube, Music, Link as LinkIcon, Plus, X, Edit2, Home } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import axios from "axios";
import { api, getApiErrorMessage, setStoredUserForRole } from "@/lib/api";
import { resolveBackendImageUrl } from "@/lib/productImages";
import dynamic from 'next/dynamic';
import { INPUT_LIMITS, sanitizePersonNameInput, sanitizePhoneInput, validatePhilippineMobileNumber } from "@/lib/inputValidation";
import { validateImageFile } from "@/lib/imageUploadValidation";
import { CheckCircle, XCircle } from "lucide-react";

const LocationPicker = dynamic(
  () => import('@/components/LocationPicker'),
  { ssr: false, loading: () => <div className="h-52 w-full bg-slate-100 animate-pulse rounded-xl flex items-center justify-center text-[var(--muted)] text-xs font-bold uppercase tracking-widest">Loading Map...</div> }
);

export default function SellerProfile() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isEditing, setIsEditing] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    mobileNumber: '',
    facebookLink: '',
    instagramLink: '',
    tiktokLink: '',
    youtubeLink: '',
    socialLinks: [],
    gcashNumber: '',
    gcashName: '',
    mayaNumber: '',
    mayaName: ''
  });
  const [qrFile, setQrFile] = useState(null);
  const [mayaQrFile, setMayaQrFile] = useState(null);
  const [profileFile, setProfileFile] = useState(null);
  const [profilePreview, setProfilePreview] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showLocationModal, setShowLocationModal] = useState(false);
  const [showMapPicker, setShowMapPicker] = useState(false);
  const [locationForm, setLocationForm] = useState({
    shopHouseNo: '',
    shopStreet: '',
    shopBarangay: '',
    shopCity: '',
    shopProvince: '',
    shopPostalCode: '',
    shopLatitude: null,
    shopLongitude: null
  });
  const [isSavingLocation, setIsSavingLocation] = useState(false);
  const [toast, setToast] = useState(null);

  const showToast = (type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 4000);
  };

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        const { data } = await api.get('/users/profile');
        setUser(data.user);
        setStoredUserForRole("seller", data.user);
      } catch (err) {
        console.error("Failed to load profile", err);
      } finally {
        setLoading(false);
      }
    };
    fetchProfile();
  }, []);

  const handleProfileChange = async (e) => {
    const file = e.target.files[0];
    if (file) {
      const validationError = await validateImageFile(file, "Profile photo");
      if (validationError) {
        showToast("error", validationError);
        e.target.value = "";
        return;
      }
      setProfileFile(file);
      setProfilePreview(URL.createObjectURL(file));
    }
  };

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    if (!formData.name.trim()) {
      showToast("error", "Shop name is required.");
      setIsSubmitting(false);
      return;
    }

    try {
      validatePhilippineMobileNumber(formData.mobileNumber, "Contact number", { required: true });
      if (formData.gcashNumber) validatePhilippineMobileNumber(formData.gcashNumber, "GCash number");
      if (formData.mayaNumber) validatePhilippineMobileNumber(formData.mayaNumber, "Maya number");
      if (profileFile) {
        const validationError = await validateImageFile(profileFile, "Profile photo");
        if (validationError) throw new Error(validationError);
      }
      if (qrFile) {
        const validationError = await validateImageFile(qrFile, "GCash QR image");
        if (validationError) throw new Error(validationError);
      }
      if (mayaQrFile) {
        const validationError = await validateImageFile(mayaQrFile, "Maya QR image");
        if (validationError) throw new Error(validationError);
      }
    } catch (err) {
      showToast("error", err.message);
      setIsSubmitting(false);
      return;
    }

    try {
      let gcashQrUrl = null;
      if (qrFile) {
        const gcashFormData = new FormData();
        gcashFormData.append('image', qrFile);
        const uploadRes = await api.post('/upload', gcashFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        gcashQrUrl = uploadRes.data.url;
      }

      let profileUrl = null;
      if (profileFile) {
        const profileFormData = new FormData();
        profileFormData.append('image', profileFile);
        const uploadRes = await api.post('/upload', profileFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        profileUrl = uploadRes.data.url;
      }

      let mayaQrUrl = null;
      if (mayaQrFile) {
        const mayaFormData = new FormData();
        mayaFormData.append('image', mayaQrFile);
        const uploadRes = await api.post('/upload', mayaFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        mayaQrUrl = uploadRes.data.url;
      }

      const updatePayload = { ...formData };
      if (profileUrl) updatePayload.profilePhoto = profileUrl;
      if (gcashQrUrl) updatePayload.gcashQrCode = gcashQrUrl;
      if (mayaQrUrl) updatePayload.mayaQrCode = mayaQrUrl;

      const { data } = await api.put('/users/profile', updatePayload);

      setUser(data.user);
      setStoredUserForRole("seller", data.user);
      setIsEditing(false);
      showToast("success", "Profile updated successfully!");
    } catch (err) {
      console.error("Failed to update profile", err);
      showToast("error", getApiErrorMessage(err, "Error updating profile."));
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSaveLocation = async (e) => {
    e.preventDefault();
    if (!locationForm.shopCity || !locationForm.shopProvince) {
      showToast("error", "City and Province are required.");
      setIsSavingLocation(false);
      return;
    }

    try {
      const { data } = await api.put('/users/profile', locationForm);
      setUser(data.user);
      setStoredUserForRole("seller", data.user);
      setShowLocationModal(false);
      setShowMapPicker(false);
      showToast("success", "Location saved successfully!");
    } catch (err) {
      console.error('Failed to save location', err);
      showToast("error", 'Error saving location.');
    } finally {
      setIsSavingLocation(false);
    }
  };

  return (
    <SellerLayout>
      <div className="max-w-4xl mx-auto space-y-12 mb-20 relative">
        {/* Toast Notification */}
        <AnimatePresence>
          {toast && (
            <motion.div
              initial={{ opacity: 0, y: -20, x: "-50%" }}
              animate={{ opacity: 1, y: 20, x: "-50%" }}
              exit={{ opacity: 0, y: -20, x: "-50%" }}
              className={`fixed top-4 left-1/2 z-[100] px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border backdrop-blur-md ${
                toast.type === "success" 
                  ? "bg-green-50/90 border-green-200 text-green-800" 
                  : "bg-red-50/90 border-red-200 text-red-800"
              }`}
            >
              {toast.type === "success" ? <CheckCircle className="w-5 h-5" /> : <XCircle className="w-5 h-5" />}
              <span className="text-sm font-bold">{toast.msg}</span>
            </motion.div>
          )}
        </AnimatePresence>

        <div>
          <div className="text-[10px] sm:text-xs uppercase tracking-wider text-[var(--muted)] mb-1 font-bold">Profile</div>
          <h1 className="font-serif text-lg sm:text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
            Workshop <span className="text-[var(--rust)] italic lowercase">Profile</span>
          </h1>
        </div>

        {loading ? (
          <div className="artisan-card p-24 text-center text-[var(--muted)] animate-pulse italic">Connecting to heritage registry...</div>
        ) : !user ? (
          <div className="artisan-card p-20 text-center text-[var(--muted)]">Session expired. Please sign in to the platform.</div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            {/* Left: Identity Card */}
            <div className="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
              <div className="artisan-card p-0 flex flex-col items-center text-center group hover:-translate-y-1 transition-all duration-500 shadow-xl hover:shadow-2xl bg-white relative border-none overflow-hidden">
                {/* Dynamic Header Background */}
                <div className="absolute top-0 left-0 w-full h-24 sm:h-32 bg-gradient-to-br from-[var(--rust)] via-[var(--charcoal)] to-[#2A2421] opacity-95" />
                
                {/* Avatar Container */}
                <div className="relative mt-10 sm:mt-16 z-10 mb-4 sm:mb-6 group-hover:scale-105 transition-transform duration-500">
                  <div className="w-32 h-32 bg-[var(--cream)] rounded-full flex items-center justify-center text-[var(--charcoal)] font-serif text-3xl font-bold shadow-2xl ring-4 ring-white overflow-hidden">
                    {user.profilePhoto ? (
                      <img src={resolveBackendImageUrl(user.profilePhoto, '/images/placeholder.png')} alt={user.name} className="w-full h-full object-cover" onError={(e) => e.target.src = '/images/placeholder.png'} />
                    ) : (
                      user.name ? user.name[0] : "L"
                    )}
                  </div>
                  {user.isVerified && (
                    <div className="absolute bottom-1 right-1 w-8 h-8 bg-green-500 rounded-full border-4 border-white flex items-center justify-center shadow-lg">
                      <CheckCircle className="w-4 h-4 text-white" />
                    </div>
                  )}
                </div>

                <div className="px-8 pb-10 w-full flex flex-col items-center relative z-10">
                  <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)] mb-1">{user.name}</h3>
                  <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-[0.2em] mb-6">Master Artisan</p>
                  
                  <div className={`px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-inner w-full text-center transition-all ${user.isVerified ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse'}`}>
                    {user.isVerified ? "Verified Workshop" : "Review in Progress"}
                  </div>

                  {/* Dynamic Social Links Row */}
                  <div className="mt-6 pt-6 sm:mt-8 sm:pt-8 border-t border-[var(--border)] w-full flex items-center justify-center gap-3 flex-wrap">
                    {(() => {
                      let links = user.socialLinks;
                      if (typeof links === 'string') { try { links = JSON.parse(links); } catch { links = []; } }
                      if (!Array.isArray(links)) links = [];
                      const filtered = links.filter(l => l.url && l.url.trim());
                      return filtered.length > 0 ? (
                        filtered.map((link, idx) => (
                          <SocialIcon key={idx} href={link.url.trim()} tooltip={link.label || link.url} />
                        ))
                      ) : (
                        <div className="text-[9px] font-bold text-[var(--muted)] uppercase tracking-widest opacity-50 flex items-center gap-2">
                          <LinkIcon className="w-3 h-3" /> No Social Links
                        </div>
                      );
                    })()}
                  </div>
                </div>
              </div>
            </div>

            {/* Right: Detailed Info */}
            <div className="lg:col-span-8 space-y-6 animate-fade-in">
              <div className="bg-white rounded-[32px] p-6 sm:p-8 md:p-12 shadow-xl border border-[var(--border)] relative overflow-hidden group/main">
                {/* Ambient Background Gradient */}
                <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-gradient-to-bl from-[var(--cream)] to-transparent opacity-50 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none transition-transform duration-1000 group-hover/main:scale-110" />
                
                <div className="relative z-10 space-y-10">
                  
                  {/* Basic Info Grid */}
                  <div>
                    <h3 className="text-sm font-black text-[var(--charcoal)] uppercase tracking-widest mb-6 flex items-center gap-3">
                      <User className="w-4 h-4 text-[var(--rust)]" /> Contact Information
                    </h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <ProfileItem label="Shop Name" value={user.name} icon={<Building />} />
                      <ProfileItem label="Email Address" value={user.email} icon={<Mail />} />
                      <ProfileItem label="Contact Number" value={user.mobileNumber || user.mobile || 'Not set'} icon={<Phone />} />
                      <ProfileItem 
                        label="Shop Location" 
                        value={user.shopCity ? `${user.shopCity}, ${user.shopProvince}` : 'Not set'} 
                        icon={<MapPin />} 
                        onClick={() => {
                          setLocationForm({
                            shopHouseNo: user.shopHouseNo || '',
                            shopStreet: user.shopStreet || '',
                            shopBarangay: user.shopBarangay || '',
                            shopCity: user.shopCity || '',
                            shopProvince: user.shopProvince || '',
                            shopPostalCode: user.shopPostalCode || '',
                            shopLatitude: user.shopLatitude || null,
                            shopLongitude: user.shopLongitude || null,
                          });
                          setShowMapPicker(Boolean(user.shopLatitude && user.shopLongitude));
                          setShowLocationModal(true);
                        }}
                        actionIcon={<Edit2 />}
                      />
                    </div>
                  </div>

                  {/* Date */}
                  <div className="grid grid-cols-1 gap-4">
                    <ProfileItem 
                      label="Established On" 
                      value={user.createdAt ? new Date(user.createdAt).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : "March 2026"} 
                      icon={<Calendar />} 
                    />
                  </div>

                  {/* Payment Sections */}
                  <div className="pt-8 border-t border-[var(--border)]">
                    <h3 className="text-sm font-black text-[var(--charcoal)] uppercase tracking-widest mb-6 flex items-center gap-3">
                      <svg className="w-4 h-4 text-[var(--rust)]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                      Payment Methods
                    </h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                      {/* GCash */}
                      <div className="p-6 rounded-3xl border border-blue-100 bg-gradient-to-br from-blue-50/80 to-transparent hover:shadow-lg transition-all group relative overflow-hidden">
                        <div className="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all" />
                        <div className="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-4 relative z-10">
                          <div className="w-2 h-2 rounded-full bg-blue-500" /> GCash Account
                        </div>
                        <div className="text-base sm:text-lg font-serif font-bold text-[var(--charcoal)] tracking-tight mb-1 relative z-10">
                          {user.gcashNumber || 'Not set'}
                        </div>
                        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-wider mb-6 relative z-10">
                          Recipient: <span className="text-[var(--charcoal)]">{user.gcashName || user.name}</span>
                        </div>
                        {user.gcashQrCode ? (
                          <div className="w-full aspect-square max-w-[160px] mx-auto rounded-2xl overflow-hidden border-4 border-white shadow-xl bg-white relative z-10 group-hover:scale-105 transition-transform">
                            <img src={resolveBackendImageUrl(user.gcashQrCode, '/images/placeholder.png')} alt="GCash QR" className="w-full h-full object-cover" />
                          </div>
                        ) : (
                          <div className="w-full aspect-square max-w-[160px] mx-auto rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/50 flex flex-col items-center justify-center text-blue-400 relative z-10">
                            <span className="text-[10px] font-bold uppercase tracking-widest opacity-60">No QR</span>
                          </div>
                        )}
                      </div>
                      
                      {/* Maya */}
                      <div className="p-6 rounded-3xl border border-green-100 bg-gradient-to-br from-green-50/80 to-transparent hover:shadow-lg transition-all group relative overflow-hidden">
                        <div className="absolute -right-4 -top-4 w-24 h-24 bg-green-500/10 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all" />
                        <div className="text-[10px] font-black text-green-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-4 relative z-10">
                          <div className="w-2 h-2 rounded-full bg-green-500" /> Maya Account
                        </div>
                        <div className="text-base sm:text-lg font-serif font-bold text-[var(--charcoal)] tracking-tight mb-1 relative z-10">
                          {user.mayaNumber || 'Not set'}
                        </div>
                        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-wider mb-6 relative z-10">
                          Recipient: <span className="text-[var(--charcoal)]">{user.mayaName || user.name}</span>
                        </div>
                        {user.mayaQrCode ? (
                          <div className="w-full aspect-square max-w-[160px] mx-auto rounded-2xl overflow-hidden border-4 border-white shadow-xl bg-white relative z-10 group-hover:scale-105 transition-transform">
                            <img src={resolveBackendImageUrl(user.mayaQrCode, '/images/placeholder.png')} alt="Maya QR" className="w-full h-full object-cover" />
                          </div>
                        ) : (
                          <div className="w-full aspect-square max-w-[160px] mx-auto rounded-2xl border-2 border-dashed border-green-200 bg-green-50/50 flex flex-col items-center justify-center text-green-400 relative z-10">
                            <span className="text-[10px] font-bold uppercase tracking-widest opacity-60">No QR</span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Action Buttons */}
                  <div className="pt-10 border-t border-[var(--border)] flex flex-col sm:flex-row gap-4">
                    <button
                      onClick={() => {
                        setFormData({
                          name: user.name || '',
                          mobileNumber: user.mobileNumber || user.mobile || '',
                          facebookLink: user.facebookLink || '',
                          instagramLink: user.instagramLink || '',
                          tiktokLink: user.tiktokLink || '',
                          youtubeLink: user.youtubeLink || '',
                          socialLinks: (() => { let v = user.socialLinks; if (typeof v === 'string') { try { v = JSON.parse(v); } catch { v = []; } } return Array.isArray(v) ? v : []; })(),
                          gcashNumber: user.gcashNumber || '',
                          gcashName: user.gcashName || '',
                          mayaNumber: user.mayaNumber || '',
                          mayaName: user.mayaName || ''
                        });
                        setQrFile(null);
                        setMayaQrFile(null);
                        setIsEditing(true);
                      }}
                      className="flex-1 bg-[var(--charcoal)] text-white hover:bg-[#2A2421] hover:shadow-xl transition-all duration-300 py-4 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 group"
                    >
                      <Edit2 className="w-4 h-4 group-hover:-translate-y-0.5 transition-transform" /> Edit Profile Details
                    </button>
                    <button className="flex-1 py-4 px-6 border-2 border-red-100 rounded-xl text-xs font-bold uppercase tracking-widest text-red-500 bg-red-50/50 hover:bg-red-50 hover:border-red-200 transition-all flex items-center justify-center gap-2 group">
                      <Trash2 className="w-4 h-4 group-hover:scale-110 transition-transform" /> Deactivate Workshop
                    </button>
                  </div>
                </div>
              </div>

              {/* Security Rec */}
              <div className="p-6 bg-amber-50/80 border border-amber-200/50 rounded-3xl flex flex-col sm:flex-row items-center gap-6 shadow-sm hover:shadow-md transition-shadow">
                <div className="w-14 h-14 shrink-0 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center shadow-inner relative">
                  <div className="absolute inset-0 bg-amber-400 rounded-full animate-ping opacity-20" />
                  <ShieldCheck className="w-7 h-7 relative z-10" />
                </div>
                <div className="text-center sm:text-left">
                  <div className="text-xs font-black text-amber-800 uppercase tracking-widest mb-1.5">Security Recommendation</div>
                  <div className="text-xs text-amber-700/80 font-medium leading-relaxed max-w-md">Artisan accounts should maintain multi-factor authentication for higher transaction security during heritage auctions.</div>
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
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18M6 6l12 12" /></svg>
            </button>
            <h3 className="font-serif text-2xl font-bold mb-8 text-[var(--charcoal)]">Edit <span className="text-[var(--rust)] italic">Profile</span></h3>

            <form onSubmit={handleUpdateProfile} className="space-y-6">
              {/* Profile Photo Upload */}
              <div className="flex flex-col items-center gap-4 mb-8">
                <div className="relative group/photo cursor-pointer" onClick={() => document.getElementById('profileUpload').click()}>
                  <div className="w-28 h-28 rounded-3xl border-2 border-dashed border-[var(--border)] bg-[var(--input-bg)] flex items-center justify-center overflow-hidden transition-all group-hover/photo:border-[var(--rust)]">
                    {profilePreview || user.profilePhoto ? (
                      <img src={profilePreview || resolveBackendImageUrl(user.profilePhoto, '/images/placeholder.png')} className="w-full h-full object-cover" />
                    ) : (
                      <User className="w-8 h-8 text-[var(--muted)]" />
                    )}
                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover/photo:opacity-100 flex items-center justify-center transition-opacity">
                      <Plus className="w-6 h-6 text-white" />
                    </div>
                  </div>
                  <input id="profileUpload" type="file" accept="image/jpeg,image/png" className="hidden" onChange={handleProfileChange} />
                </div>
                <div className="text-center">
                  <h4 className="text-[10px] font-black uppercase tracking-widest text-[var(--charcoal)]">Shop Profile Photo</h4>
                  <p className="text-[9px] text-[var(--muted)]">Click to upload your workshop avatar</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Shop Name</label>
                  <input required type="text" value={formData.name} maxLength={INPUT_LIMITS.personName} onChange={e => setFormData({ ...formData, name: sanitizePersonNameInput(e.target.value) })} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Mobile Number</label>
                  <input type="text" value={formData.mobileNumber} inputMode="numeric" maxLength={INPUT_LIMITS.mobileNumber} onChange={e => setFormData({ ...formData, mobileNumber: sanitizePhoneInput(e.target.value) })} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                </div>
                <div className="col-span-full pt-6 border-t border-[var(--border)]">
                  <div className="flex items-center justify-between mb-6">
                    <div>
                      <h4 className="text-xs font-black uppercase tracking-widest text-[var(--charcoal)]">Social Links</h4>
                      <p className="text-[10px] text-[var(--muted)]">social media links</p>
                    </div>
                    <button
                      type="button"
                      onClick={() => setFormData({ ...formData, socialLinks: [...formData.socialLinks, { label: '', url: '' }] })}
                      className="flex items-center gap-1.5 px-3 py-1.5 bg-[var(--rust)] text-white text-[10px] font-bold uppercase tracking-widest rounded-lg shadow-sm hover:shadow-md transition-all active:scale-95"
                    >
                      <Plus className="w-3.5 h-3.5" /> Add Link
                    </button>
                  </div>

                  <div className="space-y-4">
                    {formData.socialLinks.map((link, idx) => (
                      <div key={idx} className="p-4 border border-[var(--border)] rounded-2xl bg-[var(--cream)]/10 space-y-4 relative group/link">
                        <button
                          type="button"
                          onClick={() => {
                            setFormData(prev => ({
                              ...prev,
                              socialLinks: prev.socialLinks.filter((_, i) => i !== idx)
                            }));
                          }}
                          className="absolute -top-2 -right-2 p-1.5 bg-red-500 text-white rounded-full shadow-lg opacity-0 group-hover/link:opacity-100 transition-opacity"
                        >
                          <X className="w-3 h-3" />
                        </button>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <div className="space-y-2">
                            <label className="text-[9px] font-bold text-[var(--muted)] uppercase tracking-widest">Link Description</label>
                            <input
                              type="text"
                              placeholder="e.g. My Portfolio"
                              className="w-full p-3 border border-[var(--border)] rounded-xl bg-white text-xs text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]"
                              value={link.label}
                              onChange={(e) => {
                                const val = e.target.value;
                                setFormData(prev => ({
                                  ...prev,
                                  socialLinks: prev.socialLinks.map((l, i) =>
                                    i === idx ? { ...l, label: val } : l
                                  )
                                }));
                              }}
                            />
                          </div>
                          <div className="space-y-2">
                            <label className="text-[9px] font-bold text-[var(--muted)] uppercase tracking-widest">Link URL</label>
                            <input
                              type="text"
                              placeholder="e.g. instagram.com/shop"
                              className="w-full p-3 border border-[var(--border)] rounded-xl bg-white text-xs text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]"
                              value={link.url}
                              onChange={(e) => {
                                const val = e.target.value;
                                setFormData(prev => ({
                                  ...prev,
                                  socialLinks: prev.socialLinks.map((l, i) =>
                                    i === idx ? { ...l, url: val } : l
                                  )
                                }));
                              }}
                            />
                          </div>
                        </div>
                      </div>
                    ))}
                    {formData.socialLinks.length === 0 && (
                      <div className="py-10 text-center border-2 border-dashed border-[var(--border)] rounded-2xl">
                        <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest opacity-50">No Dynamic Links Yet</p>
                      </div>
                    )}
                  </div>
                </div>



                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">GCash Number</label>
                  <input type="text" value={formData.gcashNumber} inputMode="numeric" maxLength={INPUT_LIMITS.mobileNumber} onChange={e => setFormData({ ...formData, gcashNumber: sanitizePhoneInput(e.target.value) })} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">GCash Account Name</label>
                  <input type="text" value={formData.gcashName} onChange={e => setFormData({ ...formData, gcashName: e.target.value })} placeholder="Account Holder Name" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">GCash QR Code</label>
                  <input type="file" accept="image/jpeg,image/png" onChange={async (e) => {
                    const file = e.target.files[0];
                    if (!file) {
                      setQrFile(null);
                      return;
                    }
                    const validationError = await validateImageFile(file, "GCash QR image");
                    if (validationError) {
                      showToast("error", validationError);
                      e.target.value = "";
                      setQrFile(null);
                      return;
                    }
                    setQrFile(file);
                  }} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm flex items-center focus:outline-none" />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Maya Number</label>
                  <input type="text" value={formData.mayaNumber} inputMode="numeric" maxLength={INPUT_LIMITS.mobileNumber} onChange={e => setFormData({ ...formData, mayaNumber: sanitizePhoneInput(e.target.value) })} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Maya Account Name</label>
                  <input type="text" value={formData.mayaName} onChange={e => setFormData({ ...formData, mayaName: e.target.value })} placeholder="Account Holder Name" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)] focus:ring-1 focus:ring-[var(--rust)]" />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">Maya QR Code</label>
                  <input type="file" accept="image/jpeg,image/png" onChange={async (e) => {
                    const file = e.target.files[0];
                    if (!file) {
                      setMayaQrFile(null);
                      return;
                    }
                    const validationError = await validateImageFile(file, "Maya QR image");
                    if (validationError) {
                      showToast("error", validationError);
                      e.target.value = "";
                      setMayaQrFile(null);
                      return;
                    }
                    setMayaQrFile(file);
                  }} className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm flex items-center focus:outline-none" />
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
                    "Save"
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Location Modal */}
      <AnimatePresence>
        {showLocationModal && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowLocationModal(false)}
              className="absolute inset-0 bg-black/50 backdrop-blur-sm"
            />
            <motion.div
              initial={{ scale: 0.92, y: 20, opacity: 0 }}
              animate={{ scale: 1, y: 0, opacity: 1 }}
              exit={{ scale: 0.92, y: 20, opacity: 0 }}
              className="relative z-10 w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl max-h-[92vh]"
            >
              <div className="p-10">
                <div className="flex justify-between items-start mb-8">
                  <div>
                    <h2 className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tight uppercase">
                      Shop <span className="text-[var(--rust)] italic lowercase">Location</span>
                    </h2>
                    <p className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest mt-1">Set your workshop address</p>
                  </div>
                  <button onClick={() => setShowLocationModal(false)} className="p-2 bg-gray-100 rounded-xl text-[var(--muted)] hover:text-red-500 transition-colors">
                    <X className="w-5 h-5" />
                  </button>
                </div>

                <form onSubmit={handleSaveLocation} className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div className="space-y-2">
                      <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2"><Home className="w-3.5 h-3.5" /> House no, or landmark</label>
                      <input type="text" value={locationForm.shopHouseNo} onChange={e => setLocationForm(p => ({ ...p, shopHouseNo: e.target.value }))} placeholder="e.g. 123" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2"><MapPin className="w-3.5 h-3.5" /> Street</label>
                      <input type="text" value={locationForm.shopStreet} onChange={e => setLocationForm(p => ({ ...p, shopStreet: e.target.value }))} placeholder="e.g. M.H. Del Pilar St." className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2"><MapPin className="w-3.5 h-3.5" /> Barangay</label>
                      <input type="text" value={locationForm.shopBarangay} onChange={e => setLocationForm(p => ({ ...p, shopBarangay: e.target.value }))} placeholder="e.g. Poblacion" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2"><MapPin className="w-3.5 h-3.5" /> City / Municipality</label>
                      <input type="text" value={locationForm.shopCity} onChange={e => setLocationForm(p => ({ ...p, shopCity: e.target.value }))} placeholder="e.g. Lumban" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2"><MapPin className="w-3.5 h-3.5" /> Province</label>
                      <input type="text" value={locationForm.shopProvince} onChange={e => setLocationForm(p => ({ ...p, shopProvince: e.target.value }))} placeholder="e.g. Laguna" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2"><MapPin className="w-3.5 h-3.5" /> Postal Code</label>
                      <input type="text" value={locationForm.shopPostalCode} onChange={e => setLocationForm(p => ({ ...p, shopPostalCode: e.target.value }))} placeholder="e.g. 4014" className="w-full p-4 border border-[var(--border)] rounded-xl bg-[var(--input-bg)] text-sm text-[var(--charcoal)] focus:outline-none focus:border-[var(--rust)]" />
                    </div>
                  </div>

                  <div className="flex items-center justify-between border-t border-[var(--border)] pt-5">
                    <button
                      type="button"
                      onClick={() => setShowMapPicker(v => !v)}
                      className={`flex items-center gap-2 px-5 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all ${
                        showMapPicker
                          ? 'bg-[var(--rust)] text-white'
                          : locationForm.shopLatitude
                          ? 'bg-green-50 text-green-700 border-2 border-green-200'
                          : 'bg-[var(--input-bg)] text-[var(--muted)] hover:bg-[var(--rust)] hover:text-white'
                      }`}
                    >
                      <MapPin className="w-4 h-4" />
                      {showMapPicker ? 'Hide Map' : locationForm.shopLatitude ? `Show Pin (${locationForm.shopLatitude.toFixed(4)})` : 'Drop Pin on Map'}
                    </button>

                    <button
                      type="button"
                      onClick={() => {
                        if ('geolocation' in navigator) {
                          navigator.geolocation.getCurrentPosition(
                            async (pos) => {
                              const lat = pos.coords.latitude;
                              const lng = pos.coords.longitude;
                              setLocationForm(p => ({ ...p, shopLatitude: lat, shopLongitude: lng }));
                              setShowMapPicker(true);
                              
                              // Auto-fill address from GPS
                              try {
                                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`, {
                                  headers: { 'User-Agent': 'LumbaRongApp/1.0' }
                                });
                                const data = await res.json();
                                if (data && data.address) {
                                  const addr = data.address;
                                  setLocationForm(p => ({
                                    ...p,
                                    shopHouseNo: addr.house_number || p.shopHouseNo,
                                    shopStreet: addr.road || addr.pedestrian || p.shopStreet,
                                    shopBarangay: addr.suburb || addr.quarter || addr.village || p.shopBarangay,
                                    shopCity: addr.city || addr.town || addr.municipality || p.shopCity,
                                    shopProvince: addr.state || addr.region || p.shopProvince,
                                    shopPostalCode: addr.postcode || p.shopPostalCode,
                                  }));
                                }
                              } catch (e) {
                                console.error("Reverse geocoding failed", e);
                              }
                            },
                            () => alert('Unable to get your location.')
                          );
                        }
                      }}
                      className="flex items-center gap-2 px-5 py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest bg-[var(--input-bg)] text-[var(--muted)] hover:bg-green-50 hover:text-green-700 transition-all"
                    >
                      <MapPin className="w-4 h-4" /> Use My Location
                    </button>
                  </div>

                  <AnimatePresence>
                    {showMapPicker && (
                      <motion.div
                        initial={{ opacity: 0, height: 0 }}
                        animate={{ opacity: 1, height: 'auto' }}
                        exit={{ opacity: 0, height: 0 }}
                        className="overflow-hidden"
                      >
                        <div className="rounded-2xl overflow-hidden border border-[var(--border)]">
                          <LocationPicker
                            onLocationFound={({ lat, lng, address }) => {
                              setLocationForm(p => ({
                                ...p,
                                shopLatitude: lat,
                                shopLongitude: lng,
                                shopHouseNo: address.houseNumber || p.shopHouseNo,
                                shopStreet: address.street || p.shopStreet,
                                shopBarangay: address.barangay || p.shopBarangay,
                                shopCity: address.city || p.shopCity,
                                shopProvince: address.province || p.shopProvince,
                                shopPostalCode: address.postalCode || p.shopPostalCode,
                              }));
                            }}
                            initialLat={locationForm.shopLatitude || 14.2952}
                            initialLng={locationForm.shopLongitude || 121.4647}
                            autoLocate={!locationForm.shopLatitude || !locationForm.shopLongitude}
                          />
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>

                  <button
                    type="submit"
                    disabled={isSavingLocation}
                    className="w-full btn-primary py-4 text-xs font-bold uppercase tracking-widest shadow-xl flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {isSavingLocation ? 'Saving...' : 'Save Location'}
                  </button>
                </form>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </SellerLayout>
  );
}

function SocialIcon({ href, tooltip }) {
  if (!href || !href.trim()) return null;
  const url = href.startsWith('http') ? href : `https://${href}`;

  const getPlatform = (url) => {
    const lower = url.toLowerCase();
    if (lower.includes('facebook.com')) return { icon: <Facebook />, color: "#1877F2" };
    if (lower.includes('instagram.com')) return { icon: <Instagram />, color: "#E4405F" };
    if (lower.includes('tiktok.com')) return { icon: <Music />, color: "#010101" };
    if (lower.includes('youtube.com')) return { icon: <Youtube />, color: "#FF0000" };
    return { icon: <LinkIcon />, color: "var(--rust)" };
  };

  const { icon, color } = getPlatform(url);

  return (
    <a
      href={url}
      target="_blank"
      rel="noreferrer"
      title={tooltip}
      className="p-2.5 rounded-xl border border-[var(--border)] hover:border-transparent transition-all hover:scale-110 shadow-sm hover:shadow-md group"
    >
      {React.cloneElement(icon, {
        className: "w-4 h-4 transition-colors",
        style: { color: color }
      })}
    </a>
  );
}

function ProfileItem({ label, value, icon, onClick, actionIcon }) {
  return (
    <div 
      onClick={onClick}
      className={`p-4 sm:p-5 rounded-2xl border border-[var(--border)] bg-[var(--cream)]/10 hover:bg-[var(--cream)]/30 transition-all duration-300 group ${onClick ? 'cursor-pointer hover:shadow-md hover:border-[var(--rust)]/30' : ''}`}
    >
      <div className="flex items-center justify-between mb-3">
        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-2 group-hover:text-[var(--rust)] transition-colors">
          {React.cloneElement(icon, { className: "w-4 h-4" })} {label}
        </div>
        {actionIcon && (
          <div className="text-[var(--muted)] group-hover:text-[var(--rust)] transition-colors p-1 bg-white rounded-md shadow-sm border border-[var(--border)]">
            {React.cloneElement(actionIcon, { className: "w-3 h-3" })}
          </div>
        )}
      </div>
      <div className="text-sm font-bold text-[var(--charcoal)] truncate">
        {value}
      </div>
    </div>
  );
}
