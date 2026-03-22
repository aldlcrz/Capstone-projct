"use client";
import React, { useState, useEffect } from "react";
import AdminLayout from "@/components/AdminLayout";
import { Clock, UserCheck, ShoppingBag, Store, ShieldCheck, Mail, ArrowRight, Activity, MapPin } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { BACKEND_URL } from "@/lib/api";
import { io } from "socket.io-client";

export default function AdminActivity() {
  const [activities, setActivities] = useState([
    { id: 1, type: 'order', label: 'New Commission Placed', user: 'Maria Santos', desc: 'Pina-Silk Lumban Barong', time: '2 mins ago', color: 'bg-green-500' },
    { id: 2, type: 'seller', label: 'New Artisan Application', user: 'Jose Artisans', desc: 'Awaiting Document Review', time: '15 mins ago', color: 'bg-[var(--rust)]' },
    { id: 3, type: 'user', label: 'New Patron Registration', user: 'Ricardo Dalisay', desc: 'Customer from Laguna', time: '1 hour ago', color: 'bg-blue-500' },
  ]);

  useEffect(() => {
    const socket = io(BACKEND_URL);
    
    socket.on('stats_update', (data) => {
        const newAct = {
            id: Date.now(),
            type: data?.type || 'platform',
            label: data?.type === 'order' ? 'Commission Update' : 'Workshop Activity',
            user: 'Community Member',
            desc: 'Real-time platform synchronization detected.',
            time: 'Just now',
            color: 'bg-[var(--bark)]'
        };
        setActivities(prev => [newAct, ...prev].slice(0, 15));
    });

    socket.on('new_order', (order) => {
        const newAct = {
            id: Date.now(),
            type: 'order',
            label: 'New Heritage Commission',
            user: 'Lumban Patron',
            desc: `Created order #LB-${order.id.toString().padStart(6, '0')}`,
            time: 'Just now',
            color: 'bg-green-500 font-bold'
        };
        setActivities(prev => [newAct, ...prev].slice(0, 15));
    });

    return () => socket.disconnect();
  }, []);

  return (
    <AdminLayout>
      <div className="max-w-5xl mx-auto space-y-12 mb-20">
        <div className="flex items-center justify-between">
          <div>
            <div className="eyebrow">Real-Time Platform Log</div>
            <h1 className="font-serif text-4xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              Operational <span className="text-[var(--rust)] italic lowercase">Activity</span>
            </h1>
          </div>
          <div className="hidden md:flex items-center gap-4 px-6 py-3 bg-white border border-[var(--border)] rounded-2xl shadow-sm text-xs font-bold uppercase tracking-widest text-[var(--muted)]">
             <div className="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse ring-4 ring-green-100" /> LIVE SYNCHRONIZER ACTIVE
          </div>
        </div>

        <div className="artisan-card p-0 overflow-hidden shadow-2xl relative">
          <div className="absolute top-0 left-0 bottom-0 w-2 bg-[var(--rust)] opacity-10" />
          <div className="p-8 border-b border-[var(--border)] flex items-center justify-between bg-gray-50/50">
             <div className="flex items-center gap-3 font-bold text-sm text-[var(--charcoal)] tracking-tight">
                <Activity className="w-5 h-5 text-[var(--rust)]" /> FEED REFRESH: REAL-TIME SECURE
             </div>
             <button className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-widest hover:underline decoration-2 underline-offset-4">Download Activity Report (.csv)</button>
          </div>

          <div className="divide-y divide-[var(--border)]">
            <AnimatePresence initial={false}>
              {activities.map((act) => (
                <motion.div 
                  key={act.id}
                  initial={{ opacity: 0, height: 0, x: -20 }}
                  animate={{ opacity: 1, height: 'auto', x: 0 }}
                  exit={{ opacity: 0, x: 20 }}
                  className="p-8 hover:bg-white transition-all group flex items-start gap-8"
                >
                  <div className={`w-12 h-12 rounded-2xl shadow-lg ring-4 ring-white flex items-center justify-center text-white shrink-0 scale-110 rotate-3 group-hover:rotate-0 transition-transform ${act.color}`}>
                    {act.type === 'order' ? <ShoppingBag className="w-6 h-6" /> : act.type === 'seller' ? <Store className="w-6 h-6" /> : <UserCheck className="w-6 h-6" />}
                  </div>

                  <div className="flex-1 space-y-2">
                    <div className="flex justify-between items-start">
                      <div className="space-y-1">
                        <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest flex items-center gap-1.5 ring-1 ring-[var(--border)] px-2 py-0.5 rounded-full w-fit bg-white">
                           {act.label}
                        </div>
                        <h3 className="font-serif text-xl font-bold text-[var(--charcoal)]">{act.user}</h3>
                      </div>
                      <div className="text-[10px] font-bold text-[var(--muted)] opacity-60 flex items-center gap-1.5 uppercase tracking-[0.1em]"><Clock className="w-3.5 h-3.5" /> {act.time}</div>
                    </div>
                    <div className="flex items-center gap-4 text-sm font-medium text-[var(--muted)] leading-relaxed italic border-l-4 border-[var(--border)] pl-4 py-1">
                      {act.desc}
                    </div>
                    <div className="pt-3 flex gap-4">
                       <button className="flex items-center gap-2 text-[10px] font-bold text-[var(--rust)] uppercase tracking-widest hover:translate-x-1 transition-transform">Audit Record <ArrowRight className="w-3 h-3" /></button>
                       {act.type === 'seller' && <button className="flex items-center gap-2 text-[10px] font-bold text-[var(--charcoal)] uppercase tracking-widest hover:text-[var(--rust)] transition-all">Review Documents <ShieldCheck className="w-3 h-3" /></button>}
                    </div>
                  </div>
                </motion.div>
              ))}
            </AnimatePresence>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
           <div className="artisan-card p-10 bg-[var(--bark)] text-white relative group overflow-hidden shadow-2xl">
              <div className="absolute top-0 right-0 p-8 opacity-20 group-hover:scale-125 transition-transform"><MapPin className="w-20 h-20" /></div>
              <div className="space-y-6 relative z-10">
                 <h3 className="font-serif text-2xl font-bold italic tracking-tight">Geographic Hubs</h3>
                 <div className="space-y-3">
                    <GeoItem label="Lumban, Laguna" count="124 Patrons" />
                    <GeoItem label="Metro Manila" count="86 Patrons" />
                    <GeoItem label="Global Units" count="12 Commissions" />
                 </div>
              </div>
           </div>
           <div className="artisan-card p-10 bg-white/50 backdrop-blur-md shadow-2xl relative overflow-hidden group">
              <div className="absolute bottom-0 right-0 p-8 opacity-10 group-hover:scale-125 transition-transform"><Mail className="w-20 h-20" /></div>
              <div className="space-y-6 relative z-10">
                 <h3 className="font-serif text-2xl font-bold text-[var(--charcoal)]">Community Outreach</h3>
                 <p className="text-sm text-[var(--muted)] leading-relaxed">Broadcast heritage updates to all registered artisans and patrons across the platform securely.</p>
                 <button className="w-full py-4 bg-[var(--rust)] text-white rounded-xl text-xs font-bold font-sans uppercase tracking-[0.2em] shadow-xl hover:shadow-2xl transition-all">Send Global Announcement</button>
              </div>
           </div>
        </div>
      </div>
    </AdminLayout>
  );
}

function GeoItem({ label, count }) {
  return (
    <div className="flex items-center justify-between border-b border-white/10 pb-3 group/geo">
       <span className="text-xs font-bold text-white/70 group-hover/geo:text-white transition-colors">{label}</span>
       <span className="text-[10px] font-sans font-bold text-[var(--rust)] bg-white/10 px-2 py-0.5 rounded-full ring-1 ring-white/20">{count}</span>
    </div>
  );
}
