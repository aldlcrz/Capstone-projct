"use client";
import React, { useState, useEffect, useCallback } from "react";
import SuperAdminLayout from "@/components/SuperAdminLayout";
import {
  DollarSign,
  TrendingUp,
  Store,
  ShoppingBag,
  Percent,
  RefreshCw,
  ArrowUpRight,
  Code2,
} from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";
import Link from "next/link";

export default function SuperAdminDashboard() {
  const [mounted, setMounted] = useState(false);
  const [loading, setLoading] = useState(true);
  const [performance, setPerformance] = useState([]);
  const [commissionRate, setCommissionRate] = useState(10);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    setRefreshing(true);
    try {
      const res = await api.get("/super-admin/seller-performance?range=all");
      if (res.data && Array.isArray(res.data.performance)) {
        setPerformance(res.data.performance);
        setCommissionRate(res.data.commissionRate ?? 10);
      } else if (Array.isArray(res.data)) {
        setPerformance(res.data);
      }
    } catch (err) {
      console.error("Failed to load performance data:", err);
    } finally {
      setRefreshing(false);
      setLoading(false);
    }
  }, []);

  useEffect(() => { setMounted(true); }, []);
  useEffect(() => { if (mounted) fetchData(); }, [mounted, fetchData]);

  const totalSales = performance.reduce((s, i) => s + (i.totalSales || 0), 0);
  const totalCommission = performance.reduce((s, i) => s + (i.commissionPaid || 0), 0);
  const totalNetProfit = performance.reduce((s, i) => s + (i.netProfit || 0), 0);
  const totalOrders = performance.reduce((s, i) => s + (i.orderCount || 0), 0);

  const kpis = [
    {
      label: "Platform Gross Sales",
      value: `₱${totalSales.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: <DollarSign className="w-6 h-6" />,
      color: "#818cf8",
      bg: "rgba(129,140,248,0.12)",
    },
    {
      label: `Platform Commission (${commissionRate}%)`,
      value: `₱${totalCommission.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: <Percent className="w-6 h-6" />,
      color: "#f472b6",
      bg: "rgba(244,114,182,0.12)",
    },
    {
      label: "Artisans' Net Profits",
      value: `₱${totalNetProfit.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: <TrendingUp className="w-6 h-6" />,
      color: "#34d399",
      bg: "rgba(52,211,153,0.12)",
    },
    {
      label: "Total Completed Orders",
      value: totalOrders.toLocaleString("en-US"),
      icon: <ShoppingBag className="w-6 h-6" />,
      color: "#fb923c",
      bg: "rgba(251,146,60,0.12)",
    },
  ];

  return (
    <SuperAdminLayout>
      <div className="space-y-10">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div className="flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase mb-1" style={{ color: "#818cf8" }}>
              <Code2 className="w-3.5 h-3.5" /> Super Admin Console
            </div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-white">
              Platform{" "}
              <span className="italic" style={{ color: "#818cf8" }}>
                Overview
              </span>
            </h1>
            <p className="text-xs text-slate-400 mt-1 font-medium">
              All-time platform health metrics for developer visibility.
            </p>
          </div>
          <button
            onClick={fetchData}
            disabled={refreshing || loading}
            className="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-xs tracking-widest uppercase transition-all disabled:opacity-50"
            style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8", border: "1px solid rgba(129,140,248,0.3)" }}
          >
            <RefreshCw className={`w-4 h-4 ${refreshing ? "animate-spin" : ""}`} /> Refresh
          </button>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
          {kpis.map((kpi, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.07 }}
              className="p-6 rounded-3xl border flex items-center justify-between"
              style={{
                background: "rgba(26,26,46,0.8)",
                borderColor: "rgba(45,45,94,0.8)",
              }}
            >
              <div className="space-y-1">
                <div className="text-[10px] font-black uppercase tracking-widest text-slate-500">
                  {kpi.label}
                </div>
                <div className="text-2xl font-serif font-black text-white">
                  {loading ? "—" : kpi.value}
                </div>
              </div>
              <div
                className="w-12 h-12 rounded-2xl flex items-center justify-center"
                style={{ background: kpi.bg, color: kpi.color }}
              >
                {kpi.icon}
              </div>
            </motion.div>
          ))}
        </div>

        {/* Quick links */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Link href="/super-admin/performance">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.3 }}
              className="p-8 rounded-3xl border cursor-pointer transition-all hover:scale-[1.01] group"
              style={{
                background: "rgba(129,140,248,0.08)",
                borderColor: "rgba(129,140,248,0.2)",
              }}
            >
              <div className="flex items-center justify-between mb-4">
                <div
                  className="w-12 h-12 rounded-2xl flex items-center justify-center"
                  style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8" }}
                >
                  <TrendingUp className="w-6 h-6" />
                </div>
                <ArrowUpRight className="w-5 h-5 text-slate-500 group-hover:text-[#818cf8] transition-colors" />
              </div>
              <div className="text-sm font-black uppercase tracking-widest text-white mb-1">
                Shop Performance
              </div>
              <div className="text-xs text-slate-400">
                Full financial ledger per artisan shop — sales, commissions, and net profits.
              </div>
            </motion.div>
          </Link>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.35 }}
            className="p-8 rounded-3xl border"
            style={{
              background: "rgba(26,26,46,0.5)",
              borderColor: "rgba(45,45,94,0.8)",
            }}
          >
            <div className="flex items-center gap-3 mb-4">
              <div
                className="w-12 h-12 rounded-2xl flex items-center justify-center"
                style={{ background: "rgba(244,114,182,0.12)", color: "#f472b6" }}
              >
                <Store className="w-6 h-6" />
              </div>
              <div>
                <div className="text-sm font-black uppercase tracking-widest text-white">
                  {loading ? "—" : performance.length}
                </div>
                <div className="text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                  Registered Shops
                </div>
              </div>
            </div>
            <p className="text-xs text-slate-400">
              Total artisan shops registered across the platform, contributing to sales this period.
            </p>
          </motion.div>
        </div>
      </div>
    </SuperAdminLayout>
  );
}
