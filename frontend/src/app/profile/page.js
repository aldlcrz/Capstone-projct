"use client";
import React, { useState, useEffect, useCallback, useRef } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { 
  User, Mail, Phone, MapPin, Package, Edit, Lock, Eye, EyeOff, Check, 
  Loader2, X, TrendingUp, Calendar, DollarSign, Plus, Trash2, Home, 
  Navigation, CreditCard, Bell, Ticket, Coins, Shield, Settings, History, Camera
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getStoredUserForRole, setStoredUserForRole, getTokenForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { useSearchParams, useRouter } from "next/navigation";
import AddressManager from "@/components/AddressManager";
import OrdersManagement from "@/components/OrdersManagement";
import { INPUT_LIMITS, sanitizePersonNameInput, sanitizePhoneInput } from "@/lib/inputValidation";
import { validateImageFile } from "@/lib/imageUploadValidation";
import { resolveBackendImageUrl } from "@/lib/productImages";

export default function CustomerProfile() {
  const [mounted, setMounted] = useState(false);
  const router = useRouter();
  useEffect(() => setMounted(true), []);

  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const searchParams = useSearchParams();
  const tabParam = searchParams.get("tab");
  
  const [activeTab, setActiveTab] = useState(
    tabParam === "address" ? "Addresses" : 
    tabParam === "password" ? "Change Password" : 
    tabParam === "orders" ? "My Purchase" :
    "Profile"
  );

  const [editForm, setEditForm] = useState({ 
    name: "", 
    username: "", 
    email: "", 
    mobileNumber: "", 
    gender: "other",
    birthday: { day: "1", month: "January", year: "2000" }
  });

  const [isSaving, setIsSaving] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [profilePreview, setProfilePreview] = useState(null);
  const fileInputRef = useRef(null);

  const fetchProfile = useCallback(async (isBackground = false) => {
    const token = getTokenForRole("customer");
    if (!token) return window.location.replace("/login");

    try {
      if (!isBackground) setLoading(true);
      const res = await api.get("/users/profile");
      const u = res.data.user;
      setUser(u);
      
      let bDay = { day: "1", month: "January", year: "2000" };
      if (u.birthday) {
        const date = new Date(u.birthday);
        bDay = {
          day: String(date.getDate()),
          month: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"][date.getMonth()],
          year: String(date.getFullYear())
        };
      }

      setEditForm({
        name: u.name || "",
        username: u.username || "",
        email: u.email || "",
        mobileNumber: u.mobileNumber || u.mobile || "",
        gender: u.gender || "other",
        birthday: bDay
      });
      setStoredUserForRole("customer", u);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchProfile();
  }, [fetchProfile]);

  const handleSaveProfile = async () => {
    try {
      setIsSaving(true);
      const monthIndex = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"].indexOf(editForm.birthday.month);
      const birthdayStr = `${editForm.birthday.year}-${String(monthIndex + 1).padStart(2, '0')}-${String(editForm.birthday.day).padStart(2, '0')}`;
      
      const res = await api.put("/users/profile", {
        name: editForm.name,
        username: editForm.username,
        mobileNumber: editForm.mobileNumber,
        gender: editForm.gender,
        birthday: birthdayStr
      });
      setUser(res.data.user);
      setStoredUserForRole("customer", res.data.user);
      alert("Profile updated successfully!");
    } catch (error) {
      alert(error.response?.data?.message || "Failed to update profile");
    } finally {
      setIsSaving(false);
    }
  };

  const handlePhotoChange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    try {
      const validationError = await validateImageFile(file, "Profile photo");
      if (validationError) return alert(validationError);

      setIsUploading(true);
      const formData = new FormData();
      formData.append("image", file);
      const uploadRes = await api.post("/upload", formData, {
        headers: { "Content-Type": "multipart/form-data" }
      });

      const photoUrl = uploadRes.data.url;
      const updateRes = await api.put("/users/profile", { profilePhoto: photoUrl });
      setUser(updateRes.data.user);
      setStoredUserForRole("customer", updateRes.data.user);
      setProfilePreview(photoUrl);
    } catch (error) {
      alert("Upload failed.");
    } finally {
      setIsUploading(false);
    }
  };

  const sidebarGroups = [
    {
      title: "My Account",
      icon: <User className="w-5 h-5 text-blue-500" />,
      items: [
        { label: "Profile", key: "Profile" },
        { label: "Addresses", key: "Addresses" },
        { label: "Change Password", key: "Change Password" },
      ]
    },
    { label: "My Purchase", key: "My Purchase", icon: <History className="w-5 h-5 text-red-500" /> },
  ];

  if (!mounted || loading) return <CustomerLayout><div className="p-20 text-center animate-pulse">Loading heritage profile...</div></CustomerLayout>;

  return (
    <CustomerLayout>
      <div className="max-w-6xl mx-auto flex flex-col md:flex-row gap-8 mb-20 px-4 md:px-0">
        {/* Sidebar */}
        <aside className="w-full md:w-64 space-y-8">
          <div className="flex items-center gap-3 px-2">
            <div className="w-12 h-12 rounded-full overflow-hidden border border-stone-200">
              <img src={resolveBackendImageUrl(user?.profilePhoto, "https://ui-avatars.com/api/?name=" + (user?.name || "U"))} className="w-full h-full object-cover" />
            </div>
            <div>
              <div className="text-sm font-bold text-stone-800 truncate max-w-[120px]">{user?.username || user?.name}</div>
              <button onClick={() => setActiveTab("Profile")} className="text-[11px] text-stone-400 flex items-center gap-1 hover:text-stone-600 transition-colors">
                <Edit className="w-3 h-3" /> Edit Profile
              </button>
            </div>
          </div>

          <nav className="space-y-4">
            {sidebarGroups.map((group, i) => (
              <div key={i} className="space-y-2">
                {group.items ? (
                  <>
                    <div className="flex items-center gap-3 px-3 py-2 cursor-default">
                      {group.icon}
                      <span className="text-sm font-bold text-stone-700">{group.title}</span>
                    </div>
                    <div className="pl-11 space-y-2">
                      {group.items.map((item, j) => (
                        <button
                          key={j}
                          onClick={() => setActiveTab(item.key)}
                          className={`block text-[13px] transition-colors ${activeTab === item.key ? 'text-[var(--rust)] font-bold' : 'text-stone-500 hover:text-[var(--rust)]'}`}
                        >
                          {item.label}
                        </button>
                      ))}
                    </div>
                  </>
                ) : (
                  <button
                    onClick={() => setActiveTab(group.key)}
                    className="flex items-center gap-3 px-3 py-2 w-full hover:bg-stone-50 rounded-lg transition-colors group"
                  >
                    {group.icon}
                    <span className={`text-sm font-bold transition-colors ${activeTab === group.key ? 'text-[var(--rust)]' : 'text-stone-700 group-hover:text-[var(--rust)]'}`}>
                      {group.label}
                    </span>
                  </button>
                )}
              </div>
            ))}
          </nav>
        </aside>

        {/* Main Content Area */}
        <main className="flex-1 bg-white rounded-sm shadow-sm border border-stone-200 min-h-[600px] overflow-hidden">
          {activeTab === "Profile" && (
            <div className="p-8">
              <div className="border-b border-stone-100 pb-5 mb-10">
                <h1 className="text-lg font-medium text-stone-800">My Profile</h1>
                <p className="text-sm text-stone-500 mt-1">Manage and protect your account</p>
              </div>

              <div className="flex flex-col lg:flex-row gap-12">
                {/* Form Side */}
                <div className="flex-1 space-y-8">
                  {/* Username */}
                  <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-0">
                    <label className="w-32 text-sm text-stone-500 sm:text-right pr-6">Username</label>
                    <div className="flex-1 max-w-md">
                      <input 
                        type="text" 
                        value={editForm.username} 
                        onChange={e => setEditForm({...editForm, username: e.target.value})}
                        className="w-full px-3 py-2 border border-stone-200 rounded-sm text-sm focus:outline-none focus:border-stone-400"
                      />
                      <p className="text-[11px] text-stone-400 mt-1">Username can only be changed once.</p>
                    </div>
                  </div>

                  {/* Name */}
                  <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-0">
                    <label className="w-32 text-sm text-stone-500 sm:text-right pr-6">Name</label>
                    <div className="flex-1 max-w-md">
                      <input 
                        type="text" 
                        value={editForm.name} 
                        onChange={e => setEditForm({...editForm, name: sanitizePersonNameInput(e.target.value)})}
                        className="w-full px-3 py-2 border border-stone-200 rounded-sm text-sm focus:outline-none focus:border-stone-400"
                      />
                    </div>
                  </div>

                  {/* Email */}
                  <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-0">
                    <label className="w-32 text-sm text-stone-500 sm:text-right pr-6">Email</label>
                    <div className="flex-1 text-sm text-stone-800 flex items-center gap-2">
                      {editForm.email.replace(/(.{2}).*(@.*)/, "$1******$2")}
                      <button className="text-blue-500 hover:underline text-xs">Change</button>
                    </div>
                  </div>

                  {/* Phone */}
                  <div className="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-0">
                    <label className="w-32 text-sm text-stone-500 sm:text-right pr-6">Phone Number</label>
                    <div className="flex-1 text-sm text-stone-800 flex items-center gap-2">
                      {editForm.mobileNumber.replace(/.*(\d{2})$/, "*********$1")}
                      <button className="text-blue-500 hover:underline text-xs">Change</button>
                    </div>
                  </div>

                  {/* Save Button */}
                  <div className="flex pt-6">
                    <div className="w-32 hidden sm:block"></div>
                    <button 
                      onClick={handleSaveProfile}
                      disabled={isSaving}
                      className="px-8 py-2.5 bg-[var(--rust)] text-white text-sm font-medium rounded-sm shadow-sm hover:opacity-90 transition-opacity disabled:opacity-50"
                    >
                      {isSaving ? "Saving..." : "Save"}
                    </button>
                  </div>
                </div>

                {/* Avatar Side */}
                <div className="w-full lg:w-72 lg:border-l border-stone-100 flex flex-col items-center gap-6 lg:pl-12">
                  <div className="w-24 h-24 rounded-full overflow-hidden border border-stone-100 shadow-inner">
                    <img src={profilePreview || resolveBackendImageUrl(user?.profilePhoto, "https://ui-avatars.com/api/?name=" + (user?.name || "U"))} className="w-full h-full object-cover" />
                  </div>
                  <div className="space-y-4 w-full flex flex-col items-center">
                    <button 
                      onClick={() => fileInputRef.current?.click()}
                      disabled={isUploading}
                      className="px-4 py-2 border border-stone-200 text-stone-600 text-sm rounded-sm hover:bg-stone-50 transition-colors shadow-sm"
                    >
                      {isUploading ? "Uploading..." : "Select Image"}
                    </button>
                    <input type="file" ref={fileInputRef} onChange={handlePhotoChange} className="hidden" accept=".jpeg,.jpg,.png" />
                    <div className="text-center space-y-1">
                      <p className="text-xs text-stone-400">File size: maximum 1 MB</p>
                      <p className="text-xs text-stone-400">File extension: .JPEG, .PNG</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === "Addresses" && (
            <div className="p-8">
               <div className="border-b border-stone-100 pb-5 mb-8">
                <h1 className="text-lg font-medium text-stone-800">My Addresses</h1>
                <p className="text-sm text-stone-500 mt-1">Manage your delivery registry</p>
              </div>
              <AddressManager onUpdate={() => fetchProfile(true)} />
            </div>
          )}

          {activeTab === "My Purchase" && (
            <div className="p-8">
              <OrdersManagement />
            </div>
          )}

          {activeTab === "Change Password" && (
            <div className="p-8 max-w-lg">
               <div className="border-b border-stone-100 pb-5 mb-8">
                <h1 className="text-lg font-medium text-stone-800">Change Password</h1>
                <p className="text-sm text-stone-500 mt-1">For your account's security, do not share your password with others</p>
              </div>
              {/* Reuse password logic from previous implementation if needed */}
              <div className="space-y-6">
                 <p className="text-xs text-stone-500">Feature coming soon in this layout style.</p>
              </div>
            </div>
          )}

          {/* Placeholder for other tabs */}
          {["Banks & Cards", "Privacy", "NotifSettings", "Notifications", "Vouchers", "Coins"].includes(activeTab) && (
            <div className="p-20 text-center">
              <div className="w-16 h-16 bg-stone-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <Settings className="w-8 h-8 text-stone-300 animate-spin-slow" />
              </div>
              <h3 className="text-stone-800 font-bold">{activeTab}</h3>
              <p className="text-xs text-stone-400 mt-1 italic">This module is currently being optimized for the heritage registry.</p>
            </div>
          )}
        </main>
      </div>
    </CustomerLayout>
  );
}
