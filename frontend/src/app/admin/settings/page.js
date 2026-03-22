"use client";
import React, { useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { Settings, TrendingUp, Target, DollarSign, Users, Store, ShieldCheck, Mail, Save, Clock, Trash2, ArrowRight } from "lucide-react";
import { motion } from "framer-motion";

import { api, getApiErrorMessage } from "@/lib/api";
import { Loader2, AlertCircle } from "lucide-react";

export default function AdminSettings() {
  const [revenueTarget, setRevenueTarget] = useState(500000);
  const [commissionRate, setCommissionRate] = useState(10);
  const [verificationRequired, setVerificationRequired] = useState(true);
  const [automatedSupport, setAutomatedSupport] = useState(false);
  const [publicLedger, setPublicLedger] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  React.useEffect(() => {
    const fetchSettings = async () => {
      try {
        setError(null);
        const res = await api.get("/admin/settings");
        const s = res.data;
        if (s.revenueTarget) setRevenueTarget(s.revenueTarget);
        if (s.commissionRate) setCommissionRate(s.commissionRate);
        if (s.verificationRequired !== undefined) setVerificationRequired(s.verificationRequired);
        if (s.automatedSupport !== undefined) setAutomatedSupport(s.automatedSupport);
        if (s.publicLedger !== undefined) setPublicLedger(s.publicLedger);
      } catch (err) {
        console.error("Failed to fetch settings", err);
        setError(getApiErrorMessage(err, "Failed to load platform parameters."));
      } finally {
        setIsLoading(false);
      }
    };
    fetchSettings();
  }, []);

  const handleSaveSettings = async () => {
    try {
      setIsSaving(true);
      setError(null);
      await api.put("/admin/settings", {
        revenueTarget,
        commissionRate,
        verificationRequired,
        automatedSupport,
        publicLedger
      });
      alert("Platform parameters synchronized successfully!");
    } catch (err) {
      setError(getApiErrorMessage(err, "Failed to sync governance."));
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <AdminLayout>
      <div className="max-w-6xl mx-auto space-y-12 mb-20 animate-fade-in">
        <div>
          <div className="eyebrow">Enterprise Governance</div>
          <h1 className="font-serif text-4xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
            Platform <span className="text-[var(--rust)] italic lowercase">Parameters</span>
          </h1>
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-3 gap-10 items-start">
          {/* Main Controls */}
          <div className="xl:col-span-2 space-y-10">
            {/* Revenue Targets Section */}
            <div className="artisan-card p-12 space-y-10 shadow-2xl relative overflow-hidden bg-white">
               <div className="absolute top-0 right-0 p-10 opacity-5 group-hover:scale-110 transition-transform"><Target className="w-24 h-24" /></div>
               <div>
                  <h3 className="text-xl font-bold text-[var(--charcoal)] mb-2 flex items-center gap-3"><TrendingUp className="w-6 h-6 text-[var(--rust)]" /> Fiscal Performance Goals</h3>
                  <p className="text-xs text-[var(--muted)] tracking-wider uppercase font-bold opacity-60">Set quarterly revenue milestones for the Lumban heritage marketplace.</p>
               </div>

               <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
                  <div className="space-y-4">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-[0.2em] flex items-center gap-2"><DollarSign className="w-3.5 h-3.5" /> Growth Target (PHP)</label>
                     <div className="relative group">
                        <input 
                           type="number" 
                           value={revenueTarget}
                           onChange={(e) => setRevenueTarget(e.target.value)}
                           className="w-full pl-10 pr-4 py-4 bg-[var(--input-bg)] border-2 border-transparent focus:border-[var(--rust)] focus:bg-white rounded-2xl outline-none font-bold text-lg transition-all"
                        />
                        <div className="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--muted)] group-focus-within:text-[var(--rust)] font-serif font-bold transition-all">₱</div>
                     </div>
                  </div>
                  <div className="space-y-4">
                     <label className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-[0.2em] flex items-center gap-2">Artisan Support Rate (%)</label>
                     <div className="relative group">
                        <input 
                           type="number" 
                           value={commissionRate}
                           onChange={(e) => setCommissionRate(e.target.value)}
                           className="w-full pl-6 pr-10 py-4 bg-[var(--input-bg)] border-2 border-transparent focus:border-[var(--rust)] focus:bg-white rounded-2xl outline-none font-bold text-lg transition-all"
                        />
                        <div className="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--muted)] group-focus-within:text-[var(--rust)] font-serif font-bold transition-all">%</div>
                     </div>
                  </div>
               </div>
                <div className="pt-8 border-t border-[var(--border)] flex flex-col md:flex-row gap-4 items-center justify-between">
                   <div className="text-xs text-[var(--muted)] italic">Settings will propagate across all artisan workshops in real-time.</div>
                   <button 
                     onClick={handleSaveSettings}
                     disabled={isSaving}
                     className="btn-primary px-10 py-4 shadow-xl flex items-center gap-2"
                   >
                      {isSaving ? <Loader2 className="w-4.5 h-4.5 animate-spin" /> : <Save className="w-4.5 h-4.5" />}
                      {isSaving ? "Synchronizing..." : "Sync Global Governance"}
                   </button>
                </div>

                {error && (
                   <div className="p-4 bg-red-50 border border-red-100 rounded-xl flex items-center gap-3 text-red-600 text-xs font-bold animate-shake">
                      <AlertCircle className="w-4 h-4" />
                      {error}
                   </div>
                )}
            </div>

            {/* Platform Policy Sliders */}
            <div className="artisan-card p-10 space-y-8 bg-white/50 backdrop-blur-md shadow-2xl">
               <h3 className="text-xl font-bold flex items-center gap-3"><ShieldCheck className="w-6 h-6 text-[var(--rust)]" /> Security & Policy Matrix</h3>
               
               <div className="divide-y divide-[var(--border)]">
                  <PolicySwitch 
                    label="Artisan Verification Wall" 
                    desc="Require manual document audit for all new workshop registrations."
                    active={verificationRequired}
                    toggle={() => setVerificationRequired(!verificationRequired)}
                  />
                   <PolicySwitch 
                     label="Automated Support Threads" 
                     desc="Enable heritage-AI assisted initial responses for common inquiries."
                     active={automatedSupport}
                     toggle={() => setAutomatedSupport(!automatedSupport)}
                   />
                   <PolicySwitch 
                     label="Public Revenue Ledger" 
                     desc="Display global earnings statistics on the public landing page hero."
                     active={publicLedger}
                     toggle={() => setPublicLedger(!publicLedger)}
                   />
               </div>
            </div>
          </div>

          {/* Right: Side Actions */}
          <div className="space-y-8">
             <div className="artisan-card p-8 bg-[var(--bark)] text-white shadow-2xl relative overflow-hidden group">
                <div className="absolute top-0 right-0 p-8 opacity-10 group-hover:rotate-12 transition-transform"><Clock className="w-20 h-20" /></div>
                <div className="space-y-6 relative z-10">
                   <h3 className="font-serif text-xl font-bold italic tracking-tight underline decoration-[var(--rust)] underline-offset-8">Audit Log Snapshot</h3>
                   <div className="space-y-4">
                      <AuditItem user="Root Admin" action="Updated verification flag" time="2h ago" />
                      <AuditItem user="System" action="Monthly stats broadcast" time="5h ago" />
                      <AuditItem user="Moderator" action="Revoked artisan access #88" time="1d ago" />
                   </div>
                   <button className="w-full mt-4 flex items-center justify-center gap-2 text-[10px] font-bold uppercase tracking-widest text-white/60 hover:text-white transition-all underline decoration-1">View Enterprise Compliance Feed <ArrowRight className="w-3.5 h-3.5" /></button>
                </div>
             </div>

             <div className="artisan-card p-8 border-2 border-red-100 bg-red-50/50 space-y-6 shadow-sm">
                <div className="text-xs font-bold text-red-600 uppercase tracking-widest flex items-center gap-2 border-b border-red-100 pb-2"><Trash2 className="w-4 h-4" /> Termination Zone</div>
                <p className="text-[10px] text-red-700/60 leading-relaxed font-bold">This section is for high-level database purges and infrastructure resets. Procedural caution is mandatory.</p>
                <button className="w-full py-3 bg-red-600 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:bg-red-700 transition-all">Wipe Platform Temporary Caches</button>
             </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}

function PolicySwitch({ label, desc, active, toggle }) {
  return (
    <div className="py-6 flex items-center justify-between group">
       <div className="space-y-1">
          <div className="text-sm font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">{label}</div>
          <div className="text-[10px] text-[var(--muted)] font-bold tracking-tight max-w-[280px]">{desc}</div>
       </div>
       <button 
          onClick={toggle}
          className={`w-14 h-8 rounded-full relative transition-all duration-500 shadow-inner ${active ? 'bg-[var(--rust)] ring-4 ring-red-50' : 'bg-[var(--border)]'}`}
        >
          <motion.div 
             animate={{ x: active ? 28 : 4 }}
             className="absolute top-1 w-6 h-6 bg-white rounded-full shadow-lg border-2 border-transparent" 
          />
       </button>
    </div>
  );
}

function AuditItem({ user, action, time }) {
  return (
    <div className="flex items-center gap-4 text-xs font-medium border-l-2 border-white/10 pl-4 py-1">
       <div className="flex-1">
          <span className="text-white font-bold">{user}</span> <span className="text-white/40 italic">{action}</span>
       </div>
       <div className="text-[9px] font-bold text-white/30 uppercase tracking-tighter shrink-0">{time}</div>
    </div>
  );
}
