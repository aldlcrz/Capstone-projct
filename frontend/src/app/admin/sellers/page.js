"use client";
import React, { useState, useEffect } from "react";
import AdminLayout from "@/components/AdminLayout";
import { Store, CheckCircle, XCircle, Eye, ShieldCheck, Search, X } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function AdminSellersPage() {
  const [sellers, setSellers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedSeller, setSelectedSeller] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const { socket } = useSocket();

  const fetchSellers = async () => {
    try {
      const res = await api.get("/admin/pending-sellers");
      setSellers(res.data);
    } catch (err) {
      console.error("Failed to fetch pending sellers");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSellers();

    if (socket) {
      socket.on('user_updated', (data) => {
        if (data.user.role === 'seller' || data.action === 'registered') {
          fetchSellers();
        }
      });
      socket.on('dashboard_update', fetchSellers);
    }

    return () => {
      if (socket) {
        socket.off('user_updated');
        socket.off('dashboard_update');
      }
    };
  }, [socket]);

  const verifySeller = async (id) => {
    if(!confirm("Verify this seller for the Lumban community?")) return;
    setError(null); setSuccess(null);
    try {
      await api.put(`/admin/verify-seller/${id}`, {}, {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
      });
      setSuccess("Seller verified successfully!");
      fetchSellers();
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err.response?.data?.message || "Verification failed.");
      setTimeout(() => setError(null), 3000);
    }
  };

  return (
    <AdminLayout>
      <div className="space-y-10">
        <div className="flex items-end justify-between">
          <div>
            <div className="eyebrow">User Management</div>
            <h1 className="font-serif text-4xl font-bold tracking-tight text-[var(--charcoal)]">
              Seller <span className="text-[var(--rust)] italic lowercase">Verification</span>
            </h1>
          </div>
          <div className="relative w-64">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
            <input type="text" placeholder="Search sellers..." className="w-full pl-10 pr-4 py-2 border border-[var(--border)] rounded-xl outline-none" />
          </div>
        </div>

        {/* Alerts */}
        <AnimatePresence>
          {error && (
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-bold flex items-center gap-2">
              <XCircle className="w-4 h-4" /> {error}
            </motion.div>
          )}
          {success && (
            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -10 }} className="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-bold flex items-center gap-2">
              <CheckCircle className="w-4 h-4" /> {success}
            </motion.div>
          )}
        </AnimatePresence>

        {loading ? (
          <div className="artisan-card p-20 text-center text-[var(--muted)] animate-pulse">Scanning community applications...</div>
        ) : sellers.length === 0 ? (
          <div className="artisan-card p-20 text-center space-y-4">
             <div className="w-16 h-16 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto"><CheckCircle className="w-8 h-8" /></div>
             <h3 className="text-xl font-bold">Queue is empty</h3>
             <p className="text-[var(--muted)]">All sellers are currently verified.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-6">
            {sellers.map((seller, idx) => (
              <motion.div 
                key={seller.id}
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: idx * 0.1 }}
                className="artisan-card p-8 flex flex-col md:flex-row items-center justify-between gap-8 group"
              >
                <div className="flex items-center gap-6">
                  <div className="w-16 h-16 bg-[var(--bark)] rounded-2xl flex items-center justify-center text-white font-serif text-2xl font-bold shadow-lg group-hover:scale-105 transition-transform">
                    {seller.name[0]}
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-[var(--charcoal)]">{seller.name}</h3>
                    <div className="text-sm text-[var(--muted)] italic mb-2">{seller.email}</div>
                    <div className="flex items-center gap-3">
                      <span className="text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-full font-bold tracking-widest uppercase">{seller.isVerified ? 'VERIFIED' : 'PENDING APPROVAL'}</span>
                      <span className="text-[10px] text-[var(--muted)] flex items-center gap-1">
                        <Clock className="w-3 h-3" /> 
                        Joined {new Date(seller.createdAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="flex items-center gap-3">
                  <button 
                    onClick={() => { setSelectedSeller(seller); setIsModalOpen(true); }}
                    className="px-5 py-3 border border-[var(--border)] rounded-xl flex items-center gap-2 text-xs font-bold uppercase tracking-widest hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all"
                  >
                    <Eye className="w-4 h-4" /> View Documents
                  </button>
                  <button 
                    onClick={() => verifySeller(seller.id)}
                    className="px-5 py-3 bg-[var(--bark)] text-white rounded-xl flex items-center gap-2 text-xs font-bold uppercase tracking-widest hover:bg-green-600 transition-all shadow-md group"
                  >
                    <ShieldCheck className="w-4 h-4 group-hover:rotate-12 transition-transform" /> Approve Seller
                  </button>
                </div>
              </motion.div>
            ))}
          </div>
        )}
      </div>

      <AnimatePresence>
        {isModalOpen && selectedSeller && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsModalOpen(false)}
              className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-md"
            />
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            >
              <div className="p-6 border-b border-[var(--border)] flex items-center justify-between bg-[var(--stone)]/30">
                <div>
                  <h2 className="text-2xl font-serif font-bold text-[var(--charcoal)]">Verification Documents</h2>
                  <p className="text-sm text-[var(--muted)]">Reviewing credentials for <span className="font-bold text-[var(--rust)]">{selectedSeller.name}</span></p>
                </div>
                <button 
                  onClick={() => setIsModalOpen(false)}
                  className="p-2 hover:bg-white rounded-full transition-colors text-[var(--muted)] hover:text-[var(--rust)]"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              <div className="p-8 overflow-y-auto custom-scrollbar">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                  <DocumentCard title="Indigency Certificate" src={selectedSeller.indigencyCertificate} />
                  <DocumentCard title="Valid ID" src={selectedSeller.validId} />
                  <DocumentCard title="GCash QR Code" src={selectedSeller.gcashQrCode} />
                </div>
              </div>

              <div className="p-6 border-t border-[var(--border)] bg-[var(--stone)]/30 flex justify-end gap-4">
                 <button 
                  onClick={() => setIsModalOpen(false)}
                  className="px-6 py-3 text-sm font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--charcoal)]"
                >
                  Back to Queue
                </button>
                  <button 
                    onClick={() => {
                      verifySeller(selectedSeller.id);
                      setIsModalOpen(false);
                    }}
                    className="px-8 py-3 bg-[var(--bark)] text-white rounded-xl text-sm font-bold uppercase tracking-widest hover:bg-green-600 transition-all shadow-lg"
                  >
                    Approve Seller
                  </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </AdminLayout>
  );
}

function DocumentCard({ title, src }) {
  return (
    <div className="space-y-3">
      <h4 className="text-xs font-bold uppercase tracking-widest text-[var(--muted)] px-1">{title}</h4>
      <div className="aspect-[3/4] rounded-2xl border-2 border-dashed border-[var(--border)] bg-[var(--stone)]/50 overflow-hidden group hover:border-[var(--rust)] transition-colors relative">
        {src ? (
          <>
            <img src={src} alt={title} className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
               <a href={src} target="_blank" rel="noopener noreferrer" className="px-4 py-2 bg-white/90 backdrop-blur rounded-lg text-xs font-bold uppercase tracking-wider shadow-xl transform translate-y-4 group-hover:translate-y-0 transition-transform">
                 Open Full Size
               </a>
            </div>
          </>
        ) : (
          <div className="w-full h-full flex flex-col items-center justify-center text-[var(--muted)] p-6 text-center italic">
            <XCircle className="w-8 h-8 mb-2 opacity-20" />
            <p className="text-[10px]">No document available</p>
          </div>
        )}
      </div>
    </div>
  );
}

function Clock(props) {
  return (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
  );
}
