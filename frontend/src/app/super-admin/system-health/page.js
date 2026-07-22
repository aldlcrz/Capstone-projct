"use client";
import React, { useState, useEffect, useCallback } from "react";
import SuperAdminLayout from "@/components/SuperAdminLayout";
import { Activity, Database, Download, RefreshCw, Server, Cpu, HardDrive, Loader2, CheckCircle2 } from "lucide-react";
import { motion } from "framer-motion";
import { api, getTokenForRole } from "@/lib/api";

export default function SuperAdminSystemHealthPage() {
  const [loading, setLoading] = useState(true);
  const [health, setHealth] = useState(null);
  const [downloading, setDownloading] = useState(false);

  const fetchHealth = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.get("/super-admin/system-health");
      if (res.data && res.data.health) {
        setHealth(res.data.health);
      }
    } catch (err) {
      console.error("Failed to load system health diagnostics", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchHealth();
  }, [fetchHealth]);

  const handleDownloadBackup = async () => {
    setDownloading(true);
    try {
      const token = getTokenForRole("super_admin");
      const backendUrl = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";
      
      const response = await fetch(`${backendUrl}/api/v1/super-admin/backup-db`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) throw new Error("Failed to download database backup");

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `lumbarong_backup_${Date.now()}.sql`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (err) {
      console.error("Backup download failed", err);
    } finally {
      setDownloading(false);
    }
  };

  return (
    <SuperAdminLayout>
      <div className="space-y-8">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div className="flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase mb-1" style={{ color: "#818cf8" }}>
              <Activity className="w-3.5 h-3.5" /> Maintenance & Diagnostics
            </div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-white">
              System{" "}
              <span className="italic" style={{ color: "#818cf8" }}>
                Health & Backups
              </span>
            </h1>
            <p className="text-xs text-slate-400 mt-1 font-medium">
              Monitor MySQL database capacity, server runtime stats, and download raw SQL database backups.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={fetchHealth}
              disabled={loading || downloading}
              className="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-xs tracking-widest uppercase transition-all disabled:opacity-50"
              style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8", border: "1px solid rgba(129,140,248,0.3)" }}
            >
              <RefreshCw className={`w-4 h-4 ${loading ? "animate-spin" : ""}`} /> Refresh Stats
            </button>

            <button
              onClick={handleDownloadBackup}
              disabled={downloading}
              className="flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-xs tracking-widest uppercase transition-all shadow-xl disabled:opacity-50"
              style={{ background: "linear-gradient(135deg, #10b981, #059669)", color: "#ffffff" }}
            >
              {downloading ? <Loader2 className="w-4 h-4 animate-spin" /> : <Download className="w-4 h-4" />}
              {downloading ? "Exporting SQL..." : "Download SQL Backup"}
            </button>
          </div>
        </div>

        {/* Diagnostics Cards */}
        {loading ? (
          <div className="py-24 flex flex-col items-center justify-center gap-3">
            <Loader2 className="w-8 h-8 animate-spin" style={{ color: "#818cf8" }} />
            <p className="text-[10px] font-black uppercase tracking-widest text-slate-500">
              Diagnosing system metrics...
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-6 rounded-3xl border space-y-4" style={{ background: "rgba(26,26,46,0.8)", borderColor: "rgba(45,45,94,0.8)" }}>
              <div className="flex items-center justify-between">
                <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">MySQL Database</span>
                <Database className="w-5 h-5 text-indigo-400" />
              </div>
              <div>
                <div className="text-2xl font-serif font-black text-white">{health?.databaseName || "lumbarong"}</div>
                <div className="text-xs text-emerald-400 font-bold mt-1 flex items-center gap-1">
                  <CheckCircle2 className="w-3.5 h-3.5" /> Connection Active ({health?.databaseSize || "0 MB"})
                </div>
              </div>
            </div>

            <div className="p-6 rounded-3xl border space-y-4" style={{ background: "rgba(26,26,46,0.8)", borderColor: "rgba(45,45,94,0.8)" }}>
              <div className="flex items-center justify-between">
                <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">Backend Runtime</span>
                <Server className="w-5 h-5 text-purple-400" />
              </div>
              <div>
                <div className="text-2xl font-serif font-black text-white">PHP {health?.phpVersion || "8.2"}</div>
                <div className="text-xs text-slate-400 font-bold mt-1">Laravel {health?.laravelVersion}</div>
              </div>
            </div>

            <div className="p-6 rounded-3xl border space-y-4" style={{ background: "rgba(26,26,46,0.8)", borderColor: "rgba(45,45,94,0.8)" }}>
              <div className="flex items-center justify-between">
                <span className="text-[10px] font-black uppercase tracking-widest text-slate-500">Server Platform</span>
                <Cpu className="w-5 h-5 text-amber-400" />
              </div>
              <div>
                <div className="text-2xl font-serif font-black text-white">{health?.serverOS || "Windows"}</div>
                <div className="text-xs text-slate-400 font-bold mt-1">Development & Production</div>
              </div>
            </div>
          </div>
        )}

        {/* Database Record Counts */}
        {health?.counts && (
          <div className="rounded-3xl border p-8 space-y-6" style={{ background: "rgba(26,26,46,0.7)", borderColor: "rgba(45,45,94,0.8)" }}>
            <div className="flex items-center justify-between border-b pb-4" style={{ borderColor: "rgba(45,45,94,0.8)" }}>
              <h3 className="text-xs font-bold uppercase tracking-widest text-slate-300 flex items-center gap-2">
                <HardDrive className="w-4 h-4 text-[#818cf8]" /> Database Entity Volumes
              </h3>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-6 text-center">
              <div className="p-4 rounded-2xl bg-[#0f0f1a] border border-[#2d2d5e]">
                <div className="text-2xl font-black text-white">{health.counts.users}</div>
                <div className="text-[10px] font-bold uppercase tracking-widest text-slate-500 mt-1">Users</div>
              </div>
              <div className="p-4 rounded-2xl bg-[#0f0f1a] border border-[#2d2d5e]">
                <div className="text-2xl font-black text-white">{health.counts.products}</div>
                <div className="text-[10px] font-bold uppercase tracking-widest text-slate-500 mt-1">Products</div>
              </div>
              <div className="p-4 rounded-2xl bg-[#0f0f1a] border border-[#2d2d5e]">
                <div className="text-2xl font-black text-white">{health.counts.orders}</div>
                <div className="text-[10px] font-bold uppercase tracking-widest text-slate-500 mt-1">Orders</div>
              </div>
              <div className="p-4 rounded-2xl bg-[#0f0f1a] border border-[#2d2d5e]">
                <div className="text-2xl font-black text-white">{health.counts.auditLogs}</div>
                <div className="text-[10px] font-bold uppercase tracking-widest text-slate-500 mt-1">Audit Logs</div>
              </div>
            </div>
          </div>
        )}
      </div>
    </SuperAdminLayout>
  );
}
