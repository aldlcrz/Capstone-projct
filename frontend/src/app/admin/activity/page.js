"use client";
import React, { useState, useEffect } from "react";
import AdminLayout from "@/components/AdminLayout";
import { Clock, UserCheck, ShoppingBag, Store, ShieldCheck, Mail, ArrowRight, Activity, MapPin } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { BACKEND_URL } from "@/lib/api";
import { io } from "socket.io-client";

export default function AdminActivity() {
  const [activities, setActivities] = useState([
    { id: 1, type: 'order', label: 'New Order Placed', user: 'Maria Santos', desc: 'Pina-Silk Lumban Barong', time: '2 mins ago', color: 'bg-green-500' },
    { id: 2, type: 'seller', label: 'New Seller Application', user: 'Jose Sellers', desc: 'Awaiting Document Review', time: '15 mins ago', color: 'bg-[var(--rust)]' },
    { id: 3, type: 'user', label: 'New Customer Registration', user: 'Ricardo Dalisay', desc: 'Customer from Laguna', time: '1 hour ago', color: 'bg-blue-500' },
  ]);

  useEffect(() => {
    const socket = io(BACKEND_URL);
    
    socket.on('stats_update', (data) => {
        const newAct = {
            id: Date.now(),
            type: data?.type || 'platform',
            label: data?.type === 'order' ? 'Order Update' : 'Workshop Activity',
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
            label: 'New Heritage Order',
            user: 'Lumban Customer',
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
      <div className="max-w-6xl mx-auto space-y-10 mb-20">
        <div className="flex items-center justify-between">
          <div className="space-y-1">
            <div className="eyebrow">Registry Log</div>
            <h1 className="text-3xl font-bold tracking-tight text-[var(--charcoal)]">
              System <span className="text-[var(--rust)] italic">Activity</span>
            </h1>
          </div>
          <div className="hidden md:flex items-center gap-3 px-4 py-2 bg-white border border-[var(--border)] rounded-lg shadow-sm text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">
             <div className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" /> SYSTEM STATUS: ONLINE
          </div>
        </div>

        <div className="bg-white rounded-xl border border-[var(--border)] overflow-hidden shadow-sm">
          <div className="px-6 py-4 border-b border-[var(--border)] flex items-center justify-between bg-[var(--warm-white)]">
             <div className="flex items-center gap-2 font-bold text-xs text-[var(--charcoal)] tracking-widest uppercase">
                <Activity className="w-4 h-4 text-[var(--rust)]" /> Latest Operations
             </div>
             <button className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-widest hover:text-[var(--rust-light)] transition-colors">Export Activity</button>
          </div>

          <div className="divide-y divide-[var(--border)]">
            <AnimatePresence initial={false}>
                {activities.map((act) => (
                <motion.div 
                  key={act.id}
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0 }}
                  className="px-6 py-5 hover:bg-[var(--warm-white)] transition-colors flex items-center gap-6"
                >
                  <div className={`w-10 h-10 rounded-lg flex items-center justify-center text-white shrink-0 ${act.color}`}>
                    {act.type === 'order' ? <ShoppingBag className="w-5 h-5" /> : act.type === 'seller' ? <Store className="w-5 h-5" /> : <UserCheck className="w-5 h-5" />}
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-center mb-0.5">
                      <div className="flex items-center gap-3">
                        <span className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-wider">{act.label}</span>
                        <span className="hidden md:inline w-1 h-1 bg-[var(--border)] rounded-full" />
                        <h4 className="text-sm font-semibold text-[var(--charcoal)] truncate">{act.user}</h4>
                      </div>
                      <span className="text-[10px] font-medium text-[var(--muted)] flex items-center gap-1.5 tabular-nums">
                        <Clock className="w-3 h-3" /> {act.time}
                      </span>
                    </div>
                    <p className="text-xs text-[var(--muted)] truncate max-w-2xl">{act.desc}</p>
                  </div>

                  <div className="flex gap-3">
                    <button 
                      onClick={() => alert(`Reviewing action #${act.id}`)}
                      className="p-2 text-[var(--muted)] hover:text-[var(--rust)] hover:bg-white rounded-md transition-all border border-transparent hover:border-[var(--border)]"
                      title="View Details"
                    >
                      <ArrowRight className="w-4 h-4" />
                    </button>
                  </div>
                </motion.div>
              ))}
            </AnimatePresence>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
           {/* User Locations Card - Refined and Lightened */}
           <div className="bg-white rounded-xl border border-[var(--border)] p-8 relative overflow-hidden group shadow-sm">
              <div className="absolute top-[-20px] right-[-20px] p-8 opacity-[0.03] group-hover:opacity-[0.06] transition-opacity">
                <MapPin className="w-32 h-32 text-[var(--charcoal)]" />
              </div>
              <div className="relative z-10 space-y-6">
                 <h3 className="text-xl font-bold text-[var(--charcoal)]">Regional Distribution</h3>
                 <div className="space-y-4">
                    <GeoItem label="Lumban, Laguna" count="124 Artisans" />
                    <GeoItem label="Metro Manila" count="86 Clients" />
                    <GeoItem label="Global Exports" count="12 Ships" />
                 </div>
              </div>
           </div>

           {/* Global Message Card - Refined */}
           <div className="bg-white rounded-xl border border-[var(--border)] p-8 relative overflow-hidden group shadow-sm">
              <div className="absolute top-[-20px] right-[-20px] p-8 opacity-[0.03] group-hover:opacity-[0.06] transition-opacity">
                <Mail className="w-32 h-32 text-[var(--charcoal)]" />
              </div>
              <div className="relative z-10 h-full flex flex-col justify-between">
                 <div>
                    <h3 className="text-xl font-bold text-[var(--charcoal)]">Broadcast System</h3>
                    <p className="mt-2 text-xs text-[var(--muted)] leading-relaxed">Broadcast an urgent notification to all registered users in the network.</p>
                 </div>
                  <button 
                    onClick={async () => {
                        const msg = prompt("Enter the broadcast message:");
                        if (msg) {
                          try {
                            const { api } = await import('@/lib/api');
                            await api.post('/admin/broadcast', { message: msg });
                            alert("Broadcast transmitted successfully.");
                          } catch (error) {
                            alert("Failed to send broadcast.");
                          }
                        }
                    }}
                    className="mt-6 w-full py-3.5 bg-[var(--charcoal)] text-white rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-[var(--rust)] transition-all shadow-sm"
                  >
                    Initiate Broadcast
                  </button>
              </div>
           </div>
        </div>
      </div>
    </AdminLayout>
  );
}

function GeoItem({ label, count }) {
  return (
    <div className="flex items-center justify-between border-b border-[var(--border)] pb-3 group/geo">
       <span className="text-xs font-bold text-[var(--charcoal)] group-hover/geo:text-[var(--rust)] transition-colors">{label}</span>
       <span className="text-[10px] font-sans font-bold text-[var(--rust)] bg-[var(--warm-white)] px-2 py-0.5 rounded-full ring-1 ring-[var(--border)]">{count}</span>
    </div>
  );
}
