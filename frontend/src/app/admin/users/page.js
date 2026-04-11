"use client";
import React, { useState, useEffect, useCallback } from "react";
import AdminLayout from "@/components/AdminLayout";
import { Users, Search, Mail, Phone, Calendar, ShieldCheck, Trash2, Snowflake, CheckCircle, XCircle } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function AdminUsers() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [toast, setToast] = useState(null); // { type: 'success'|'error', msg }

  const { socket } = useSocket();

  const showToast = (type, msg) => {
    setToast({ type, msg });
    setTimeout(() => setToast(null), 3000);
  };

  const fetchUsers = useCallback(async () => {
    try {
      const res = await api.get("/admin/customers");
      setUsers(res.data);
    } catch (err) {
      console.error("Failed to fetch users", err.response?.data || err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchUsers();

    if (socket) {
      socket.on("stats_update", (data) => {
        if (data?.type === "user") fetchUsers();
      });
      socket.on("user_updated", (data) => {
        if (data?.user?.role === "customer") fetchUsers();
      });
    }

    return () => {
      if (socket) {
        socket.off("stats_update");
        socket.off("user_updated");
      }
    };
  }, [socket, fetchUsers]);

  const handleToggleStatus = async (id) => {
    try {
      await api.put(`/admin/customers/${id}/toggle-status`);
      await fetchUsers();
      showToast("success", "User status updated.");
    } catch (err) {
      showToast("error", "Failed to update user status.");
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
      <div className="space-y-10">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">User Management</div>
            <h1 className="font-serif text-4xl font-bold tracking-tight text-[var(--charcoal)]">
              Heritage <span className="text-[var(--rust)] italic lowercase">Customers</span>
            </h1>
          </div>
          <div className="relative w-full md:w-96">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
            <input
              type="text"
              placeholder="Search by name, email or mobile..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-12 pr-4 py-3 bg-white border border-[var(--border)] rounded-2xl outline-none focus:border-[var(--rust)] transition-all font-medium text-sm"
            />
          </div>
        </div>

        {/* Toast */}
        <AnimatePresence>
          {toast && (
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              className={`p-4 rounded-xl text-sm font-bold flex items-center gap-2 ${
                toast.type === "success"
                  ? "bg-green-50 border border-green-200 text-green-700"
                  : "bg-red-50 border border-red-200 text-red-700"
              }`}
            >
              {toast.type === "success" ? <CheckCircle className="w-4 h-4" /> : <XCircle className="w-4 h-4" />}
              {toast.msg}
            </motion.div>
          )}
        </AnimatePresence>

        {/* Table */}
        <div className="artisan-card p-0 overflow-hidden shadow-2xl">
          <div className="overflow-x-auto custom-scrollbar">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[var(--input-bg)] border-b border-[var(--border)]">
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Profile</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Contact Details</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)]">Status</th>
                  <th className="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]">
                {loading ? (
                  <tr>
                    <td colSpan="4" className="px-8 py-20 text-center text-[var(--muted)] italic animate-pulse">
                      Synchronizing community records...
                    </td>
                  </tr>
                ) : filtered.length === 0 ? (
                  <tr>
                    <td colSpan="4" className="px-8 py-20 text-center text-[var(--muted)] italic">
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
                        <td className="px-8 py-6">
                          <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-[var(--bark)] rounded-xl flex items-center justify-center text-white font-serif text-lg font-bold shadow-md">
                              {user.name ? user.name[0].toUpperCase() : "U"}
                            </div>
                            <div>
                              <div className="font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">
                                {user.name}
                              </div>
                              <div className="text-[10px] text-[var(--muted)] font-bold flex items-center gap-1 uppercase tracking-widest mt-0.5">
                                <Calendar className="w-3 h-3" />
                                Joined {new Date(user.createdAt).toLocaleDateString("en-US", { month: "short", year: "numeric" })}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="px-8 py-6">
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
                        <td className="px-8 py-6">
                          <div
                            className={`inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-full border italic shadow-sm ${
                              isFrozen
                                ? "bg-blue-50 text-blue-700 border-blue-100"
                                : "bg-green-50 text-green-700 border-green-100"
                            }`}
                          >
                            {isFrozen ? <Snowflake className="w-3 h-3" /> : <ShieldCheck className="w-3 h-3" />}
                            {isFrozen ? "Frozen" : "Active"}
                          </div>
                        </td>
                        <td className="px-8 py-6 text-right">
                          <div className="flex justify-end gap-2">
                            <button
                              onClick={() => handleToggleStatus(user.id)}
                              className={`p-2.5 rounded-xl transition-all ${
                                isFrozen
                                  ? "text-green-600 hover:bg-green-50 border border-transparent hover:border-green-100"
                                  : "text-blue-500 hover:bg-blue-50 border border-transparent hover:border-blue-100"
                              }`}
                              title={isFrozen ? "Unfreeze Account" : "Freeze Account"}
                            >
                              <Snowflake className="w-4 h-4" />
                            </button>
                            <button
                              onClick={() => handleDeleteUser(user.id)}
                              className="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all shadow-sm border border-transparent hover:border-red-100"
                              title="Delete Permanently"
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
        </div>

        {/* Footer count */}
        {!loading && (
          <div className="text-xs font-bold text-[var(--muted)] uppercase tracking-widest opacity-50">
            Showing {filtered.length} of {users.length} registered customers
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
