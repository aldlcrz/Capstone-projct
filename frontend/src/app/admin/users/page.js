"use client";
import React, { useState, useEffect, useCallback } from "react";
import AdminLayout from "@/components/AdminLayout";
import ConfirmationModal from "@/components/ConfirmationModal";
import { Users, Search, Mail, Phone, Calendar, ShieldCheck, Trash2, Snowflake, CheckCircle, XCircle } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function AdminUsers() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [activeTab, setActiveTab] = useState("customers");
  const [toast, setToast] = useState(null); // { type: 'success'|'error', msg }
  const [showTerminateConfirm, setShowTerminateConfirm] = useState(false);
  const [terminateTarget, setTerminateTarget] = useState(null);
  const [terminationReason, setTerminationReason] = useState("");
  const [showFreezeModal, setShowFreezeModal] = useState(false);
  const [freezeTarget, setFreezeTarget] = useState(null);
  const [freezeReason, setFreezeReason] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { socket } = useSocket();

  const showToast = (type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 3000);
  };

  const fetchUsers = useCallback(async () => {
    try {
      setLoading(true);
      const endpoint = activeTab === "customers" ? "/admin/customers" : "/admin/sellers";
      const res = await api.get(endpoint);
      setUsers(res.data);
    } catch (err) {
      console.error("Failed to fetch users", err.response?.data || err.message);
    } finally {
      setLoading(false);
    }
  }, [activeTab]);

  useEffect(() => {
    fetchUsers();

    if (!socket) return;

    const handleStatsUpdate = (data) => {
      if (data?.type === "user") fetchUsers();
    };

    const handleUserUpdated = (data) => {
      if (data?.user?.role === "customer" || data?.user?.role === "seller") fetchUsers();
    };

    socket.on("stats_update", handleStatsUpdate);
    socket.on("user_updated", handleUserUpdated);
    socket.on("dashboard_update", handleStatsUpdate);

    return () => {
      socket.off("stats_update", handleStatsUpdate);
      socket.off("user_updated", handleUserUpdated);
      socket.off("dashboard_update", handleStatsUpdate);
    };
  }, [socket, fetchUsers]);

  const handleToggleStatus = async (id) => {
    try {
      await api.put(`/admin/${activeTab}/${id}/toggle-status`);
      await fetchUsers();
      showToast("success", "Account status updated.");
    } catch (err) {
      showToast("error", "Failed to update status.");
    }
  };

  const handleFreezeAccount = async (e) => {
    e.preventDefault();
    if (!freezeReason.trim()) return alert("Please provide a reason for freezing.");

    setIsSubmitting(true);
    try {
      await api.put(`/admin/${activeTab}/${freezeTarget.id}/freeze`, { reason: freezeReason });
      await fetchUsers();
      showToast("success", "Account frozen with reason.");
      setShowFreezeModal(false);
      setFreezeReason("");
    } catch (err) {
      showToast("error", err.response?.data?.message || "Freezing failed.");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleTerminateAccount = async () => {
    if (!terminateTarget) return;
    if (!terminationReason.trim()) {
      alert("Please provide a reason for termination.");
      return;
    }
    try {
      await api.delete(`/admin/${activeTab}/${terminateTarget.id}`, { data: { reason: terminationReason } });
      await fetchUsers();
      showToast("success", "Account terminated and purged.");
      setShowTerminateConfirm(false);
      setTerminationReason("");
    } catch (err) {
      showToast("error", "Termination failed.");
    }
  };

  const handleDeleteUser = async (id) => {
    if (!window.confirm("Are you sure you want to permanently delete this user?")) return;
    try {
      await api.delete(`/admin/customers/${id}`);
      await fetchUsers();
      showToast("success", "User deleted successfully.");
    } catch (err) {
      showToast("error", "Failed to delete user.");
    }
  };

  const filtered = users.filter(
    (u) =>
      u.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      u.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (u.mobileNumber || u.mobile || "").includes(searchTerm)
  );

  return (
    <AdminLayout>
      <div className="max-w-5xl mx-auto space-y-6 mb-20">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">Registry Management</div>
            <h1 className="font-serif text-xl font-bold tracking-tight text-[var(--charcoal)]">
              LumBarong <span className="text-[var(--rust)] italic lowercase">Users</span>
            </h1>
          </div>
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-1 p-1 bg-white/50 backdrop-blur-md border border-[var(--border)] rounded-2xl shadow-sm">
              <button
                onClick={() => setActiveTab("customers")}
                className={`px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${activeTab === "customers" ? "bg-[var(--rust)] text-white shadow-lg shadow-red-900/20" : "text-[var(--muted)] hover:text-[var(--rust)] hover:bg-white"}`}
              >
                Customers
              </button>
              <button
                onClick={() => setActiveTab("sellers")}
                className={`px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all ${activeTab === "sellers" ? "bg-[var(--rust)] text-white shadow-lg shadow-red-900/20" : "text-[var(--muted)] hover:text-[var(--rust)] hover:bg-white"}`}
              >
                Sellers
              </button>
            </div>
            <div className="relative w-full md:w-96 group">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)] group-focus-within:text-[var(--rust)] transition-colors" />
              <input
                type="text"
                placeholder="Search registry by name or email..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-12 pr-4 py-3.5 bg-white border border-[var(--border)] rounded-2xl outline-none focus:border-[var(--rust)] transition-all font-bold text-[11px] uppercase tracking-wider shadow-sm"
              />
            </div>
          </div>
        </div>

        {/* Toast */}
        <AnimatePresence>
          {toast && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className={`p-4 rounded-xl text-sm font-bold flex items-center gap-2 ${toast.type === "success"
                  ? "bg-green-50 border border-green-200 text-green-700"
                  : "bg-red-50 border border-red-200 text-red-700"
                }`}
            >
              {toast.type === "success" ? <CheckCircle className="w-4 h-4" /> : <XCircle className="w-4 h-4" />}
              {toast.msg}
            </motion.div>
          )}
        </AnimatePresence>

        {/* Registry Container */}
        <div className="artisan-card p-0 overflow-hidden shadow-xl border-none bg-white/80 backdrop-blur-xl">
          {/* Desktop Table View */}
          <div className="hidden lg:block overflow-x-auto custom-scrollbar">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[var(--cream)]/30 border-b border-[var(--border)]">
                  <th className="px-5 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Profile</th>
                  <th className="px-5 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Contact Details</th>
                  <th className="px-5 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Status</th>
                  {activeTab === 'sellers' && <th className="px-5 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Performance</th>}
                  <th className="px-5 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic text-right whitespace-nowrap">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]">
                {loading ? (
                  <tr>
                    <td colSpan="4" className="px-5 py-16 text-center text-[var(--muted)] italic animate-pulse">
                      Synchronizing community records...
                    </td>
                  </tr>
                ) : filtered.length === 0 ? (
                  <tr>
                    <td colSpan="4" className="px-5 py-16 text-center text-[var(--muted)] italic">
                      {searchTerm ? "No results found." : "No customer records found."}
                    </td>
                  </tr>
                ) : (
                  filtered.map((user, idx) => {
                    const isFrozen = user.status === "frozen";
                    return (
                      <motion.tr
                        key={user.id}
                        initial={{ opacity: 0, x: -10 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: idx * 0.04 }}
                        className="hover:bg-gray-50/50 transition-colors group"
                      >
                        <td className="px-5 py-4">
                          <div className="flex items-center gap-4">
                            <div className="w-10 h-10 bg-gradient-to-br from-[var(--charcoal)] to-[var(--bark)] rounded-xl flex items-center justify-center text-[var(--cream)] font-serif text-base font-bold shadow-sm">
                              {user.name ? user.name[0].toUpperCase() : "U"}
                            </div>
                            <div className="min-w-0">
                              <div className="font-black text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors truncate">
                                {user.name}
                              </div>
                              <div className="text-[8px] text-[var(--muted)] font-black flex items-center gap-1 uppercase tracking-widest mt-1 opacity-60">
                                <Calendar className="w-2.5 h-2.5" />
                                Est. {new Date(user.createdAt).toLocaleDateString("en-US", { month: "long", year: "numeric" })}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="px-5 py-4">
                          <div className="space-y-1.5">
                            <div className="text-xs font-medium text-[var(--charcoal)] flex items-center gap-2">
                              <Mail className="w-3.5 h-3.5 text-[var(--muted)]" /> {user.email}
                            </div>
                            <div className="text-xs font-medium text-[var(--charcoal)] flex items-center gap-2">
                              <Phone className="w-3.5 h-3.5 text-[var(--muted)]" />
                              {user.mobileNumber || user.mobile || "—"}
                            </div>
                          </div>
                        </td>
                        <td className="px-5 py-4">
                          <div
                            className={`inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-full border italic shadow-sm ${isFrozen
                                ? "bg-blue-50 text-blue-700 border-blue-100"
                                : "bg-green-50 text-green-700 border-green-100"
                              }`}
                          >
                            {isFrozen ? <Snowflake className="w-3 h-3" /> : <ShieldCheck className="w-3 h-3" />}
                            {isFrozen ? "Frozen" : "Active"}
                          </div>
                        </td>
                        {activeTab === 'sellers' && (
                          <td className="px-5 py-4">
                            {user.avgRating ? (
                              <div className={`inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-full border shadow-sm ${Number(user.avgRating) < 3 ? "bg-red-50 text-red-700 border-red-100" : "bg-green-50 text-green-700 border-green-100"
                                }`}>
                                {Number(user.avgRating) < 3 ? "⚠️ Low" : "✓ Clean"}
                                <span className="opacity-60 ml-1">({Number(user.avgRating).toFixed(1)}★)</span>
                              </div>
                            ) : (
                              <span className="text-[9px] font-black text-blue-600/70 uppercase tracking-widest italic bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100">Genesis Seller</span>
                            )}
                          </td>
                        )}
                        <td className="px-5 py-4 text-right">
                          <div className="flex justify-end gap-2">
                            <button
                              onClick={() => {
                                if (isFrozen) {
                                  handleToggleStatus(user.id);
                                } else {
                                  setFreezeTarget(user);
                                  setFreezeReason("");
                                  setShowFreezeModal(true);
                                }
                              }}
                              className={`p-2.5 rounded-xl transition-all ${isFrozen
                                  ? "text-green-600 hover:bg-green-50 border border-transparent hover:border-green-100"
                                  : "text-blue-500 hover:bg-blue-50 border border-transparent hover:border-blue-100"
                                }`}
                              title={isFrozen ? "Unfreeze Account" : "Freeze Account"}
                            >
                              <Snowflake className={`w-4 h-4 ${isFrozen ? "animate-pulse" : ""}`} />
                            </button>
                            <button
                              onClick={() => {
                                setTerminateTarget(user);
                                setShowTerminateConfirm(true);
                              }}
                              className="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all shadow-sm border border-transparent hover:border-red-100"
                              title="Terminate Account"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        </td>
                      </motion.tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>

          {/* Mobile Card View */}
          <div className="lg:hidden flex flex-col divide-y divide-[var(--border)]">
            {loading ? (
              <div className="px-8 py-20 text-center text-[var(--muted)] italic animate-pulse">
                Synchronizing community records...
              </div>
            ) : filtered.length === 0 ? (
              <div className="px-8 py-20 text-center text-[var(--muted)] italic">
                {searchTerm ? "No results found." : "No records identified."}
              </div>
            ) : (
              filtered.map((user, idx) => (
                <MobileUserCard
                  key={user.id}
                  user={user}
                  index={idx}
                  activeTab={activeTab}
                  onUnfreeze={() => handleToggleStatus(user.id)}
                  onFreeze={() => {
                    setFreezeTarget(user);
                    setFreezeReason("");
                    setShowFreezeModal(true);
                  }}
                  onTerminate={() => {
                    setTerminateTarget(user);
                    setShowTerminateConfirm(true);
                  }}
                />
              ))
            )}
          </div>
        </div>

        {/* Footer count */}
        {!loading && (
          <div className="text-xs font-bold text-[var(--muted)] uppercase tracking-widest opacity-50 px-2">
            Showing {filtered.length} of {users.length} registered {activeTab}
          </div>
        )}

        {/* Terminate Modal */}
        <ConfirmationModal
          isOpen={showTerminateConfirm}
          onClose={() => { setShowTerminateConfirm(false); setTerminationReason(""); }}
          onConfirm={handleTerminateAccount}
          title={`Terminate ${activeTab === 'customers' ? 'Customer' : 'Seller'}?`}
          message={`Are you sure you want to PERMANENTLY terminate ${terminateTarget?.name}'s account? This will purge all profile data${activeTab === 'sellers' ? ' and permanently delete all their listed products.' : '.'} This action is irreversible.`}
          confirmText="Terminate & Purge"
          cancelText="Cancel"
          type="danger"
        >
          <div className="mt-6 text-left">
            <label className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] ml-1">Reason for Termination</label>
            <textarea
              value={terminationReason}
              onChange={(e) => setTerminationReason(e.target.value)}
              placeholder="e.g. Terms of Service violation, Fraudulent activity..."
              className="w-full mt-2 p-4 bg-gray-50 border border-[var(--border)] rounded-2xl text-xs font-medium outline-none focus:border-red-500 transition-all min-h-[100px] resize-none"
            />
          </div>
        </ConfirmationModal>

        {/* Freeze Modal */}
        <AnimatePresence>
          {showFreezeModal && freezeTarget && (
            <div className="fixed inset-0 z-[110] flex items-center justify-center p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={() => setShowFreezeModal(false)}
                className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-sm"
              />
              <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                className="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden artisan-card p-0"
              >
                <div className="p-6 sm:p-8 space-y-6">
                  <div className="flex justify-between items-start">
                    <div>
                      <h3 className="font-serif text-xl font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                        Freeze <span className="text-blue-600 italic lowercase">Account</span>
                      </h3>
                      <p className="text-[9px] font-black text-[var(--muted)] opacity-50 uppercase tracking-widest italic mt-1">Suspending {freezeTarget.name}&apos;s access.</p>
                    </div>
                    <button onClick={() => setShowFreezeModal(false)} className="p-2 hover:bg-[var(--cream)] rounded-xl transition-all">
                      <XCircle className="w-5 h-5 text-[var(--muted)]" />
                    </button>
                  </div>

                  <form onSubmit={handleFreezeAccount} className="space-y-4">
                    <div className="space-y-2">
                      <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] ml-1">Violation Reason</label>
                      <textarea
                        autoFocus
                        placeholder="e.g. Multiple policy violations, Fraudulent activity detected..."
                        value={freezeReason}
                        onChange={(e) => setFreezeReason(e.target.value)}
                        rows="4"
                        className="w-full px-4 py-3 bg-blue-50/30 border border-blue-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 rounded-xl outline-none text-[11px] font-bold transition-all shadow-sm resize-none"
                      />
                      <p className="text-[8px] text-blue-600 font-bold uppercase tracking-wider ml-1 italic opacity-70">This reason will be shown to the user upon login attempt.</p>
                    </div>
                    <div className="flex gap-3 pt-2">
                      <button
                        type="button"
                        onClick={() => setShowFreezeModal(false)}
                        className="flex-1 py-4 bg-[var(--cream)] text-[var(--charcoal)] text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-[var(--border)] transition-all shadow-sm"
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        disabled={isSubmitting}
                        className="flex-[2] py-4 bg-blue-600 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-[var(--charcoal)] transition-all shadow-lg active:scale-[0.98] disabled:opacity-50"
                      >
                        {isSubmitting ? "Freezing..." : "Confirm Suspension"}
                      </button>
                    </div>
                  </form>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>

      </div>
    </AdminLayout>
  );
}
function MobileUserCard({ user, index, activeTab, onUnfreeze, onFreeze, onTerminate }) {
  const isFrozen = user.status === "frozen";
  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.04 }}
      className="p-3.5 space-y-2.5 active:bg-gray-50 transition-colors"
    >
      <div className="flex justify-between items-start">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 bg-gradient-to-br from-[var(--charcoal)] to-[var(--bark)] rounded-lg flex items-center justify-center text-[var(--cream)] font-serif font-bold shadow-md">
            {user.name ? user.name[0].toUpperCase() : "U"}
          </div>
          <div className="min-w-0">
            <div className="font-black text-[12px] text-[var(--charcoal)] truncate leading-tight">{user.name}</div>
            <div className="text-[7.5px] text-[var(--muted)] font-black uppercase tracking-widest opacity-60 mt-0.5">
              Est. {new Date(user.createdAt).toLocaleDateString("en-US", { month: "long", year: "numeric" })}
            </div>
          </div>
        </div>
        <div
          className={`px-2 py-0.5 text-[7px] font-black uppercase tracking-widest rounded-md border italic shadow-sm leading-none ${isFrozen ? "bg-blue-50 text-blue-700 border-blue-100" : "bg-green-50 text-green-700 border-green-100"
            }`}
        >
          {isFrozen ? "Frozen" : "Active"}
        </div>
      </div>

      <div className="flex items-center justify-between gap-4 pt-1">
        <div className="min-w-0 flex-1 space-y-1">
          <div className="text-[10px] font-bold text-[var(--charcoal)] flex items-center gap-1.5 truncate border-b border-[var(--border)]/20 pb-1">
            <Mail className="w-2.5 h-2.5 text-[var(--muted)] shrink-0" /> {user.email}
          </div>
          <div className="text-[9px] font-medium text-[var(--muted)] flex items-center gap-1.5">
            <Phone className="w-2.5 h-2.5 opacity-50 shrink-0" /> {user.mobileNumber || user.mobile || "—"}
            {activeTab === 'sellers' && user.avgRating && (
              <>
                <span className="opacity-20 mx-1">|</span>
                <span className="text-[var(--rust)] font-bold">{Number(user.avgRating).toFixed(1)}★</span>
              </>
            )}
          </div>
        </div>

        <div className="flex items-center gap-1.5">
          <button
            onClick={isFrozen ? onUnfreeze : onFreeze}
            className={`p-2 rounded-lg transition-all border ${isFrozen ? "bg-green-50 text-green-700 border-green-200" : "bg-blue-50 text-blue-700 border-blue-200"
              }`}
            title={isFrozen ? "Unfreeze" : "Freeze"}
          >
            <Snowflake className="w-3 h-3" />
          </button>
          <button
            onClick={onTerminate}
            className="p-2 bg-red-50 text-red-600 rounded-lg border border-red-200 transition-all hover:bg-red-100"
            title="Terminate"
          >
            <Trash2 className="w-3 h-3" />
          </button>
        </div>
      </div>
    </motion.div>
  );
}
