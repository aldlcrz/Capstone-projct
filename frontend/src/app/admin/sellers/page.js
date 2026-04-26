"use client";
import React, { useState, useEffect, useCallback } from "react";
import AdminLayout from "@/components/AdminLayout";
import { Store, CheckCircle, XCircle, Eye, ShieldCheck, Search, X, Star } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function AdminSellersPage() {

  const [sellers, setSellers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [selectedSeller, setSelectedSeller] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isReviewsOpen, setIsReviewsOpen] = useState(false);
  const [sellerReviews, setSellerReviews] = useState([]);
  const [reviewsLoading, setReviewsLoading] = useState(false);

  const { socket } = useSocket();

  const fetchSellers = useCallback(async () => {
    try {
      setLoading(true);
      const res = await api.get("/admin/pending-sellers");
      setSellers(res.data);
    } catch (err) {
      console.error("Failed to fetch pending sellers");
    } finally {
      setLoading(false);
    }
  }, []);



  const fetchSellerReviews = async (sellerId) => {
    try {
      setReviewsLoading(true);
      const res = await api.get(`/reviews/seller/${sellerId}`);
      setSellerReviews(res.data);
      setIsReviewsOpen(true);
    } catch (err) {
      console.error("Failed to fetch seller reviews");
      alert("Could not retrieve feedback records.");
    } finally {
      setReviewsLoading(false);
    }
  };

  useEffect(() => {
    fetchSellers();

    if (socket) {
      const handler = () => {
        fetchSellers();
      };
      
      socket.on('stats_update', handler);
      socket.on('user_updated', handler);
      socket.on('dashboard_update', handler);

      return () => {
        socket.off('stats_update', handler);
        socket.off('user_updated', handler);
        socket.off('dashboard_update', handler);
      };
    }
  }, [socket, fetchSellers]);

  const verifySeller = async (id) => {
    if(!confirm("Verify this seller for the Lumban community?")) return;
    setError(null); setSuccess(null);
    try {
      await api.put(`/admin/verify-seller/${id}`);
      setSuccess("Seller verified successfully!");
      fetchSellers();
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err.response?.data?.message || "Verification failed.");
      setTimeout(() => setError(null), 3000);
    }
  };

  const rejectSeller = async (id) => {
    if(!confirm("Are you sure you want to reject this seller's application?")) return;
    setError(null); setSuccess(null);
    try {
      await api.put(`/admin/reject-seller/${id}`);
      setSuccess("Seller application removed.");
      fetchSellers();
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err.response?.data?.message || "Operation failed.");
      setTimeout(() => setError(null), 3000);
    }
  };

  return (
    <AdminLayout>
      <div className="space-y-6 mb-16 sm:mb-20">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">Enterprise Management</div>
            <h1 className="font-serif text-xl font-bold tracking-tight text-[var(--charcoal)]">
              Artisan <span className="text-[var(--rust)] italic lowercase">Shops</span>
            </h1>
          </div>
          
          <div className="flex items-center gap-3">
             <div className="flex items-center gap-1 p-1 bg-white border border-[var(--border)] rounded-xl shadow-sm">
                <div className="px-4 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest bg-[var(--rust)] text-white shadow">
                  Requests ({sellers.length})
                </div>
             </div>
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
          <div className="artisan-card p-20 text-center text-[var(--muted)] animate-pulse">Scanning community requests...</div>
        ) : sellers.length === 0 ? (
          <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="artisan-card p-20 text-center space-y-4">
            <div className="w-16 h-16 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto"><CheckCircle className="w-8 h-8" /></div>
            <h3 className="text-xl font-bold">Queue is empty</h3>
            <p className="text-xs text-[var(--muted)] opacity-60">All artisan requests have been processed.</p>
          </motion.div>
        ) : (
          <div className="grid grid-cols-1 gap-6">
            {sellers.map((seller, idx) => (
              <motion.div 
                key={seller.id}
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ delay: idx * 0.1 }}
                className="artisan-card p-5 lg:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 group"
              >
                <div className="flex items-center gap-4 sm:gap-6 w-full md:w-auto">
                  <div className="w-14 sm:w-16 h-14 sm:h-16 shrink-0 bg-[var(--bark)] rounded-2xl flex items-center justify-center text-white font-serif text-xl sm:text-2xl font-bold shadow-lg group-hover:scale-105 transition-transform">
                    {seller.name[0]}
                  </div>
                  <div className="min-w-0">
                    <h3 className="text-lg sm:text-xl font-bold text-[var(--charcoal)] truncate">{seller.name}</h3>
                    <div className="text-xs sm:text-sm text-[var(--muted)] italic mb-2 truncate">{seller.email}</div>
                    <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                      <span className="text-[9px] sm:text-[10px] bg-amber-100 text-amber-700 px-3 py-1 rounded-full font-bold tracking-widest uppercase whitespace-nowrap">PENDING APPROVAL</span>
                      <span className="text-[9px] sm:text-[10px] text-[var(--muted)] flex items-center gap-1 whitespace-nowrap">
                        <Clock className="w-3 h-3 shrink-0" /> 
                        Applied {new Date(seller.createdAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full md:w-auto">
                  <button 
                    onClick={() => { setSelectedSeller(seller); setIsModalOpen(true); }}
                    className="flex-1 justify-center px-4 py-2.5 border border-[var(--border)] rounded-xl flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all"
                  >
                    <Eye className="w-4 h-4" /> View Docs
                  </button>
                  <div className="flex items-center gap-2 sm:gap-3 flex-1">
                    <button 
                      onClick={() => rejectSeller(seller.id)}
                      className="flex-1 justify-center px-4 py-2.5 border border-red-200 bg-red-50 text-red-600 rounded-xl flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest hover:bg-red-100 transition-all"
                    >
                      <XCircle className="w-4 h-4" /> Reject
                    </button>
                    <button 
                      onClick={() => verifySeller(seller.id)}
                      className="flex-1 justify-center px-4 py-2.5 bg-[var(--bark)] text-white rounded-xl flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest hover:bg-green-600 transition-all shadow-md group"
                    >
                      <ShieldCheck className="w-4 h-4 group-hover:rotate-12 transition-transform" /> Approve
                    </button>
                  </div>
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
                      rejectSeller(selectedSeller.id);
                      setIsModalOpen(false);
                    }}
                    className="px-8 py-3 bg-red-100 text-red-700 rounded-xl text-sm font-bold uppercase tracking-widest hover:bg-red-200 transition-all"
                  >
                    Reject Application
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

        {isReviewsOpen && selectedSeller && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsReviewsOpen(false)}
              className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-md"
            />
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 20 }}
              className="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]"
            >
              <div className="p-6 border-b border-[var(--border)] flex items-center justify-between bg-[var(--stone)]/30">
                <div className="flex items-center gap-4">
                   <div className="w-12 h-12 bg-[var(--rust)] rounded-xl flex items-center justify-center text-white">
                      <Star className="w-6 h-6 fill-current" />
                   </div>
                   <div>
                      <h2 className="text-xl font-serif font-bold text-[var(--charcoal)]">Artisan Feedback</h2>
                      <p className="text-xs text-[var(--muted)]">Monitoring customer satisfaction for <span className="font-bold text-[var(--rust)]">{selectedSeller.name}</span></p>
                   </div>
                </div>
                <button 
                  onClick={() => setIsReviewsOpen(false)}
                  className="p-2 hover:bg-white rounded-full transition-colors text-[var(--muted)]"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              <div className="p-8 overflow-y-auto artisan-scrollbar bg-[#FAFAFA]">
                {reviewsLoading ? (
                  <div className="py-12 text-center text-[var(--muted)] italic animate-pulse">Syncing feedback registry...</div>
                ) : sellerReviews.length === 0 ? (
                  <div className="py-12 px-6 text-center text-[var(--muted)] italic border-2 border-dashed border-[var(--border)] rounded-2xl mx-10">
                    No customer feedback has been recorded for this workshop yet.
                  </div>
                ) : (
                  <div className="space-y-6">
                    {sellerReviews.map((review) => (
                      <div key={review.id} className="bg-white p-5 rounded-2xl border border-[var(--border)] shadow-sm">
                        <div className="flex justify-between items-start mb-3">
                          <div className="flex items-center gap-3">
                             <div className="w-8 h-8 rounded-full bg-[var(--stone)] flex items-center justify-center text-[var(--rust)] font-bold text-xs overflow-hidden">
                                {review.customer?.profilePhoto ? (
                                  <img src={review.customer.profilePhoto} alt="" className="w-full h-full object-cover" />
                                ) : (
                                  review.customer?.name?.[0] || 'C'
                                )}
                             </div>
                             <div>
                                <div className="text-xs font-bold text-[var(--charcoal)]">{review.customer?.name}</div>
                                <div className="flex text-amber-400">
                                   {[...Array(5)].map((_, i) => (
                                     <Star key={i} className={`w-2.5 h-2.5 ${i < review.rating ? 'fill-current' : 'opacity-20'}`} />
                                   ))}
                                </div>
                             </div>
                          </div>
                          <div className="text-[10px] text-[var(--muted)] font-bold bg-[var(--stone)]/30 px-2 py-1 rounded">
                             {new Date(review.createdAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}
                          </div>
                        </div>
                        <div className="text-[11px] font-bold text-[var(--rust)] bg-[var(--rust)]/5 px-2 py-1 rounded w-fit mb-2">
                           Product: {review.product?.name}
                        </div>
                        <p className="text-xs text-[var(--charcoal)]/80 leading-relaxed italic">&ldquo;{review.comment}&rdquo;</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="p-6 border-t border-[var(--border)] bg-[var(--stone)]/30 flex justify-end">
                  <button 
                    onClick={() => setIsReviewsOpen(false)}
                    className="px-8 py-3 bg-[var(--charcoal)] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[var(--rust)] transition-all"
                  >
                    Close Review Panel
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
