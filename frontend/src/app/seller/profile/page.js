"use client";
import React, { useState, useEffect } from "react";
import SellerLayout from "@/components/SellerLayout";
import { User, Mail, Phone, Calendar, ShieldCheck, MapPin, Building, Trash2 } from "lucide-react";
import { motion } from "framer-motion";
import axios from "axios";

export default function SellerProfile() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        const storedUser = JSON.parse(localStorage.getItem("user") || "{}");
        // For real profile, we'd fetch from backend
        setUser(storedUser);
      } catch (err) {
        console.error("Failed to load profile");
      } finally {
        setLoading(false);
      }
    };
    fetchProfile();
  }, []);

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

                  <div className="pt-10 border-t border-[var(--border)] flex flex-col md:flex-row gap-4">
                     <button className="flex-1 btn-primary py-4 text-xs font-bold uppercase tracking-widest shadow-xl">Update Platform Identity</button>
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
