"use client";
import React, { useState, useEffect, useCallback } from "react";
import SuperAdminLayout from "@/components/SuperAdminLayout";
import { FileText, RefreshCw, Loader2, ShieldAlert } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";

export default function SuperAdminAuditLogsPage() {
  const [loading, setLoading] = useState(true);
  const [logs, setLogs] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [page, setPage] = useState(1);

  const fetchLogs = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get(`/super-admin/audit-logs?page=${page}`);
      if (res.data && res.data.logs) {
        setLogs(res.data.logs.data || []);
        setPagination({
          currentPage: res.data.logs.current_page,
          lastPage: res.data.logs.last_page,
          total: res.data.logs.total,
        });
      }
    } catch (err) {
      console.error("Failed to load audit logs", err);
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  const getActionBadgeColor = (action) => {
    if (action.includes("DELETE") || action.includes("REJECT") || action.includes("REVOKE")) {
      return "bg-red-500/10 text-red-400 border-red-500/30";
    }
    if (action.includes("APPROVE") || action.includes("VERIFY") || action.includes("ASSIGN")) {
      return "bg-emerald-500/10 text-emerald-400 border-emerald-500/30";
    }
    return "bg-indigo-500/10 text-indigo-400 border-indigo-500/30";
  };

  return (
    <SuperAdminLayout>
      <div className="space-y-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div className="flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase mb-1" style={{ color: "#818cf8" }}>
              <ShieldAlert className="w-3.5 h-3.5" /> Security & Accountability
            </div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-white">
              System{" "}
              <span className="italic" style={{ color: "#818cf8" }}>
                Audit Logs
              </span>
            </h1>
            <p className="text-xs text-slate-400 mt-1 font-medium">
              Real-time immutable log of administrative actions, moderation decisions, and security events.
            </p>
          </div>

          <button
            onClick={fetchLogs}
            disabled={loading}
            className="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-xs tracking-widest uppercase transition-all disabled:opacity-50"
            style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8", border: "1px solid rgba(129,140,248,0.3)" }}
          >
            <RefreshCw className={`w-4 h-4 ${loading ? "animate-spin" : ""}`} /> Refresh Logs
          </button>
        </div>

        {/* Logs Table */}
        <div className="rounded-3xl border overflow-hidden" style={{ background: "rgba(26,26,46,0.7)", borderColor: "rgba(45,45,94,0.8)" }}>
          <div className="p-6 border-b flex items-center justify-between" style={{ borderColor: "rgba(45,45,94,0.8)", background: "rgba(15,15,30,0.5)" }}>
            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-300">Activity Trail</h3>
            <span className="text-[10px] font-bold uppercase tracking-widest text-slate-400">
              Total {pagination?.total || 0} Events Recorded
            </span>
          </div>

          <div className="overflow-x-auto">
            {loading ? (
              <div className="py-24 flex flex-col items-center justify-center gap-3">
                <Loader2 className="w-8 h-8 animate-spin" style={{ color: "#818cf8" }} />
                <p className="text-[10px] font-black uppercase tracking-widest text-slate-500">
                  Retrieving security logs...
                </p>
              </div>
            ) : logs.length === 0 ? (
              <div className="p-16 text-center text-xs font-bold uppercase tracking-widest text-slate-500">
                No system audit logs recorded yet.
              </div>
            ) : (
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr
                    className="border-b text-[9px] font-black uppercase tracking-widest text-slate-500"
                    style={{ borderColor: "rgba(45,45,94,0.8)", background: "rgba(15,15,30,0.3)" }}
                  >
                    <th className="py-4 px-6">Timestamp</th>
                    <th className="py-4 px-4">Admin User</th>
                    <th className="py-4 px-4">Action</th>
                    <th className="py-4 px-4">Target Entity</th>
                    <th className="py-4 px-6">IP Address</th>
                  </tr>
                </thead>
                <tbody>
                  {logs.map((log) => {
                    const formattedDate = new Date(log.created_at).toLocaleString("en-US", {
                      month: "short",
                      day: "numeric",
                      year: "numeric",
                      hour: "2-digit",
                      minute: "2-digit",
                      second: "2-digit",
                    });
                    return (
                      <tr key={log.id} className="border-b border-[#2d2d5e]/40 hover:bg-white/2">
                        <td className="py-4 px-6 text-xs text-slate-400 font-medium whitespace-nowrap">{formattedDate}</td>
                        <td className="py-4 px-4">
                          <div className="text-xs font-bold text-slate-200">{log.user?.name || "System"}</div>
                          <div className="text-[9px] text-slate-500 font-bold uppercase">{log.user?.email || "Automated"}</div>
                        </td>
                        <td className="py-4 px-4">
                          <span className={`px-2.5 py-1 rounded-full border text-[9px] font-black uppercase tracking-widest ${getActionBadgeColor(log.action)}`}>
                            {log.action}
                          </span>
                        </td>
                        <td className="py-4 px-4 text-xs font-bold text-slate-300">
                          {log.entity_type ? `${log.entity_type} (${log.entity_id || "N/A"})` : "—"}
                        </td>
                        <td className="py-4 px-6 text-xs font-mono text-slate-400">{log.ip_address || "127.0.0.1"}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            )}
          </div>

          {/* Pagination Controls */}
          {pagination && pagination.lastPage > 1 && (
            <div className="p-4 border-t border-[#2d2d5e] flex items-center justify-between text-xs font-bold text-slate-400">
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                className="px-4 py-2 rounded-xl bg-[#1a1a2e] border border-[#2d2d5e] disabled:opacity-50"
              >
                Previous
              </button>
              <span>
                Page {pagination.currentPage} of {pagination.lastPage}
              </span>
              <button
                disabled={page >= pagination.lastPage}
                onClick={() => setPage((p) => p + 1)}
                className="px-4 py-2 rounded-xl bg-[#1a1a2e] border border-[#2d2d5e] disabled:opacity-50"
              >
                Next
              </button>
            </div>
          )}
        </div>
      </div>
    </SuperAdminLayout>
  );
}
