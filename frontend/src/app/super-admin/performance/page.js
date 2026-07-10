"use client";
import React, { useState, useEffect, useCallback } from "react";
import Image from "next/image";
import SuperAdminLayout from "@/components/SuperAdminLayout";
import {
  Store,
  DollarSign,
  TrendingUp,
  Percent,
  Calendar,
  Download,
  Loader2,
  AlertCircle,
} from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";

export default function SuperAdminPerformancePage() {
  const [mounted, setMounted] = useState(false);
  const [loading, setLoading] = useState(true);
  const [dateFilter, setDateFilter] = useState("month");
  const [performanceData, setPerformanceData] = useState([]);
  const [commissionRate, setCommissionRate] = useState(10.0);
  const [error, setError] = useState(null);

  const fetchPerformance = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get(`/super-admin/seller-performance?range=${dateFilter}`);
      if (res.data && Array.isArray(res.data.performance)) {
        setPerformanceData(res.data.performance);
        setCommissionRate(res.data.commissionRate ?? 10.0);
      } else if (Array.isArray(res.data)) {
        setPerformanceData(res.data);
      }
    } catch (err) {
      console.error("Failed to fetch seller performance", err);
      setError("Unable to load performance reports. Please try again.");
    } finally {
      setLoading(false);
    }
  }, [dateFilter]);

  useEffect(() => { setMounted(true); }, []);
  useEffect(() => { if (mounted) fetchPerformance(); }, [mounted, dateFilter, fetchPerformance]);

  const totalSales = performanceData.reduce((sum, item) => sum + (item.totalSales || 0), 0);
  const totalCommission = performanceData.reduce((sum, item) => sum + (item.commissionPaid || 0), 0);
  const totalNetProfit = performanceData.reduce((sum, item) => sum + (item.netProfit || 0), 0);

  const handleDownloadCSV = () => {
    try {
      let csv = "Shop Name,Owner Email,Joined Date,Completed Orders,Total Sales (PHP),Commission Paid (PHP),Net Sales (PHP),Production Cost (PHP),Artisan Net Profit (PHP)\n";
      performanceData.forEach((item) => {
        const joinedDate = new Date(item.joinedAt).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
        csv += `"${item.shopName}","${item.email}","${joinedDate}",${item.orderCount},${(item.totalSales || 0).toFixed(2)},${(item.commissionPaid || 0).toFixed(2)},${(item.netSales || 0).toFixed(2)},${(item.totalCost || 0).toFixed(2)},${(item.netProfit || 0).toFixed(2)}\n`;
      });
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.setAttribute("href", url);
      link.setAttribute("download", `shop_performance_${dateFilter}_${Date.now()}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (err) {
      console.error("CSV generation failed", err);
    }
  };

  const resolveImage = (path) => {
    if (!path) return `https://ui-avatars.com/api/?background=2d2d5e&color=818cf8&size=128`;
    if (path.startsWith("http")) return path;
    const backendUrl = process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000";
    return `${backendUrl}${path.startsWith("/") ? "" : "/"}${path}`;
  };

  const statCards = [
    {
      label: "Total Platform Sales",
      value: `₱${totalSales.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
      icon: <DollarSign className="w-6 h-6" />,
      color: "#818cf8",
      bg: "rgba(129,140,248,0.12)",
    },
    {
      label: `Commissions Earned (${commissionRate}%)`,
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
  ];

  return (
    <SuperAdminLayout>
      <div className="space-y-10">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div className="flex items-center gap-1.5 text-xs font-bold tracking-widest uppercase mb-1" style={{ color: "#818cf8" }}>
              <Store className="w-3.5 h-3.5" /> Financial Monitoring
            </div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-white">
              Shop{" "}
              <span className="italic" style={{ color: "#818cf8" }}>
                Performance
              </span>{" "}
              &amp; Commissions
            </h1>
          </div>

          <div className="flex items-center gap-3">
            <div className="relative">
              <select
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
                className="pl-10 pr-6 py-3.5 rounded-2xl text-xs font-bold appearance-none cursor-pointer focus:outline-none uppercase tracking-widest border"
                style={{
                  background: "rgba(26,26,46,0.9)",
                  borderColor: "rgba(129,140,248,0.3)",
                  color: "#818cf8",
                }}
              >
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
                <option value="all">All Time</option>
              </select>
              <Calendar className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style={{ color: "#818cf8" }} />
            </div>

            <button
              onClick={handleDownloadCSV}
              disabled={performanceData.length === 0 || loading}
              className="flex items-center gap-2 py-3.5 px-6 text-xs font-bold uppercase tracking-widest rounded-2xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8", border: "1px solid rgba(129,140,248,0.3)" }}
            >
              <Download className="w-4 h-4" /> Export CSV
            </button>
          </div>
        </div>

        {/* KPI Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {statCards.map((card, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.07 }}
              className="p-6 rounded-3xl border flex items-center justify-between"
              style={{ background: "rgba(26,26,46,0.8)", borderColor: "rgba(45,45,94,0.8)" }}
            >
              <div className="space-y-1">
                <div className="text-[10px] font-black uppercase tracking-widest text-slate-500">{card.label}</div>
                <div className="text-2xl font-serif font-black text-white">{card.value}</div>
              </div>
              <div
                className="w-12 h-12 rounded-2xl flex items-center justify-center"
                style={{ background: card.bg, color: card.color }}
              >
                {card.icon}
              </div>
            </motion.div>
          ))}
        </div>

        {/* Table */}
        <div className="rounded-3xl border overflow-hidden" style={{ background: "rgba(26,26,46,0.7)", borderColor: "rgba(45,45,94,0.8)" }}>
          <div className="p-6 border-b flex items-center justify-between" style={{ borderColor: "rgba(45,45,94,0.8)", background: "rgba(15,15,30,0.5)" }}>
            <h3 className="text-xs font-bold uppercase tracking-widest text-slate-300">
              Artisan Registry Financials
            </h3>
            <span
              className="text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full border"
              style={{ background: "rgba(129,140,248,0.1)", borderColor: "rgba(129,140,248,0.2)", color: "#818cf8" }}
            >
              {performanceData.length} Shops Registered
            </span>
          </div>

          <div className="overflow-x-auto">
            {loading ? (
              <div className="py-24 flex flex-col items-center justify-center gap-3">
                <Loader2 className="w-8 h-8 animate-spin" style={{ color: "#818cf8" }} />
                <p className="text-[10px] font-black uppercase tracking-widest text-slate-500">
                  Consolidating ledger reports...
                </p>
              </div>
            ) : error ? (
              <div className="p-12 text-center flex flex-col items-center gap-3">
                <AlertCircle className="w-10 h-10 text-red-400" />
                <p className="text-sm font-bold text-slate-300">{error}</p>
                <button
                  onClick={fetchPerformance}
                  className="py-2 px-4 text-xs font-bold uppercase tracking-widest rounded-xl"
                  style={{ background: "rgba(129,140,248,0.15)", color: "#818cf8" }}
                >
                  Retry
                </button>
              </div>
            ) : performanceData.length === 0 ? (
              <div className="p-16 text-center text-[10px] uppercase font-bold text-slate-500">
                No performance data recorded for this time range.
              </div>
            ) : (
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr
                    className="border-b text-[9px] font-black uppercase tracking-widest text-slate-500"
                    style={{ borderColor: "rgba(45,45,94,0.8)", background: "rgba(15,15,30,0.3)" }}
                  >
                    <th className="py-5 px-6">Artisan Shop</th>
                    <th className="py-5 px-4 text-center">Orders</th>
                    <th className="py-5 px-4 text-right">Gross Sales</th>
                    <th className="py-5 px-4 text-right">Commission</th>
                    <th className="py-5 px-4 text-right">Net Sales</th>
                    <th className="py-5 px-4 text-right">Production Cost</th>
                    <th className="py-5 px-6 text-right">Net Profit</th>
                  </tr>
                </thead>
                <tbody>
                  {performanceData.map((item, idx) => {
                    const joinedDate = new Date(item.joinedAt).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
                    return (
                      <tr
                        key={idx}
                        className="transition-colors group border-b"
                        style={{ borderColor: "rgba(45,45,94,0.5)" }}
                        onMouseEnter={(e) => e.currentTarget.style.background = "rgba(129,140,248,0.04)"}
                        onMouseLeave={(e) => e.currentTarget.style.background = ""}
                      >
                        <td className="py-5 px-6">
                          <div className="flex items-center gap-3">
                            <div
                              className="relative w-10 h-10 rounded-xl border overflow-hidden shrink-0"
                              style={{ borderColor: "rgba(45,45,94,0.8)", background: "rgba(45,45,94,0.5)" }}
                            >
                              <Image
                                src={resolveImage(item.profilePhoto)}
                                alt={item.shopName}
                                fill
                                sizes="40px"
                                className="object-cover"
                                unoptimized
                                onError={(e) => {
                                  e.currentTarget.src = `https://ui-avatars.com/api/?name=${item.shopName}&background=2d2d5e&color=818cf8`;
                                }}
                              />
                            </div>
                            <div>
                              <div className="text-sm font-bold text-slate-200 group-hover:text-[#818cf8] transition-colors">
                                {item.shopName}
                              </div>
                              <div className="text-[9px] text-slate-500 font-bold uppercase tracking-widest leading-none mt-0.5">
                                Member Since {joinedDate}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="py-5 px-4 text-center text-xs font-bold text-slate-300">{item.orderCount}</td>
                        <td className="py-5 px-4 text-right text-xs font-bold text-slate-300">
                          ₱{(item.totalSales || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td className="py-5 px-4 text-right text-xs font-bold" style={{ color: "#f472b6" }}>
                          ₱{(item.commissionPaid || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td className="py-5 px-4 text-right text-xs font-bold text-slate-300">
                          ₱{(item.netSales || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td className="py-5 px-4 text-right text-xs font-bold text-slate-500">
                          ₱{(item.totalCost || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td className="py-5 px-6 text-right text-xs font-serif font-black" style={{ color: "#34d399" }}>
                          ₱{(item.netProfit || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </div>
    </SuperAdminLayout>
  );
}
