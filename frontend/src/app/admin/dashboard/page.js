"use client";
import React, { useState, useEffect, useCallback } from "react";
import AdminLayout from "@/components/AdminLayout";
import {
  DollarSign,
  ShoppingBag,
  Users,
  Package,
  Activity,
  CloudRain,
  RefreshCw,
  Download,
  CheckCircle,
  Clock,
  Truck,
  XCircle,
} from "lucide-react";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  AreaChart,
  Area,
} from "recharts";
import { motion } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

// ─── Fallback data (shown while loading) ────────────────────────────────────
const EMPTY_WEEK = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"].map(n => ({ name: n, revenue: 0 }));
const EMPTY_MONTHS = ["Jan","Feb","Mar","Apr","May","Jun"].map(n => ({ name: n, hits: 0 }));

// ─── Status color map ────────────────────────────────────────────────────────
const STATUS_META = {
  Pending:    { color: "bg-amber-100 text-amber-700",  icon: <Clock className="w-4 h-4 text-amber-500" /> },
  Processing: { color: "bg-blue-100 text-blue-700",    icon: <RefreshCw className="w-4 h-4 text-blue-500" /> },
  Shipped:    { color: "bg-indigo-100 text-indigo-700",icon: <Truck className="w-4 h-4 text-indigo-500" /> },
  Completed:  { color: "bg-green-100 text-green-700",  icon: <CheckCircle className="w-4 h-4 text-green-500" /> },
  Delivered:  { color: "bg-green-100 text-green-700",  icon: <CheckCircle className="w-4 h-4 text-green-500" /> },
  Cancelled:  { color: "bg-red-100 text-red-700",      icon: <XCircle className="w-4 h-4 text-red-500" /> },
};

export default function AdminDashboard() {
  const [mounted, setMounted] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [dateFilter, setDateFilter] = useState("week");

  const [stats, setStats] = useState({
    totalSales: "—",
    totalOrders: "—",
    activeCustomers: "—",
    liveProducts: "—",
  });

  const [analytics, setAnalytics] = useState({
    revenueSeries: EMPTY_WEEK,
    monthlySignups: EMPTY_MONTHS,
    recentActivity: [],
    topLocations: [],
    orderStatusBreakdown: { pending: 0, processing: 0, shipped: 0, completed: 0, cancelled: 0 },
  });

  const { socket } = useSocket();

  const fetchStats = useCallback(async () => {
    try {
      const res = await api.get(`/admin/stats?range=${dateFilter}`);
      setStats(res.data);
    } catch (err) {
      console.warn("Stats fetch failed", err.message);
    }
  }, [dateFilter]);

  const fetchAnalytics = useCallback(async () => {
    try {
      const res = await api.get(`/admin/analytics?range=${dateFilter}`);
      const d = res.data;
      setAnalytics({
        revenueSeries: d.revenueSeries || [],
        monthlySignups: d.monthlySignups || EMPTY_MONTHS,
        recentActivity: d.recentActivity || [],
        topLocations: d.topLocations || [],
        orderStatusBreakdown: d.orderStatusBreakdown || { pending: 0, processing: 0, shipped: 0, completed: 0, cancelled: 0 },
      });
    } catch (err) {
      console.warn("Analytics fetch failed", err.message);
    }
  }, [dateFilter]);

  const refresh = useCallback(async () => {
    setRefreshing(true);
    await Promise.all([fetchStats(), fetchAnalytics()]);
    setRefreshing(false);
  }, [fetchStats, fetchAnalytics]);

  useEffect(() => {
    setMounted(true);
  }, []);

  useEffect(() => {
    if (mounted) refresh();
  }, [mounted, dateFilter, refresh]);

  // Real-time socket updates
  useEffect(() => {
    if (!socket) return;
    const handler = () => refresh();
    socket.on("stats_update", handler);
    socket.on("order_created", handler);
    socket.on("order_updated", handler);
    socket.on("user_updated", handler);
    return () => {
      socket.off("stats_update", handler);
      socket.off("order_created", handler);
      socket.off("order_updated", handler);
      socket.off("user_updated", handler);
    };
  }, [socket, refresh]);

  const handleDownloadReport = () => {
    const rows = [
      ["Metric", "Value"],
      ["Total Sales", stats.totalSales],
      ["Total Orders", stats.totalOrders],
      ["Active Customers", stats.activeCustomers],
      ["Live Products", stats.liveProducts],
    ];
    const csv = rows.map(r => r.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `lumbarong_report_${new Date().toLocaleDateString("en-CA")}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  };



  if (!mounted) return null;

  return (
    <AdminLayout>
      <div className="space-y-10 mb-20 animate-fade-down">
        {/* Page Heading */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">Enterprise Overview</div>
            <h1 className="font-serif text-charcoal text-4xl font-bold tracking-tight">
              Dashboard <span className="text-[var(--muted)] opacity-40 font-light italic">Insights</span>
            </h1>
          </div>
          <div className="flex items-center gap-3">
            {/* Filter Tabs */}
            <div className="flex items-center gap-1 p-1 bg-white border border-[var(--border)] rounded-xl shadow-sm">
              {["today", "week", "month", "year"].map(f => (
                <button
                  key={f}
                  onClick={() => setDateFilter(f)}
                  className={`px-4 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all ${dateFilter === f ? "bg-[var(--rust)] text-white shadow" : "text-[var(--muted)] hover:text-[var(--rust)]"}`}
                >
                  {f}
                </button>
              ))}
            </div>
            <button
              onClick={refresh}
              disabled={refreshing}
              className="p-2.5 bg-white border border-[var(--border)] rounded-xl hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all shadow-sm"
              title="Refresh data"
            >
              <RefreshCw className={`w-4 h-4 ${refreshing ? "animate-spin text-[var(--rust)]" : ""}`} />
            </button>
            <button
              onClick={handleDownloadReport}
              className="flex items-center gap-2 px-5 py-2.5 bg-[var(--bark)] text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-[var(--rust)] transition-all"
            >
              <Download className="w-4 h-4" /> Download Report
            </button>
          </div>
        </div>

        {/* 4 Stat Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard label="Total Sales"       value={stats.totalSales}       icon={<DollarSign className="w-5 h-5" />} color="rust"  loading={refreshing} />
          <StatCard label="Total Orders"      value={stats.totalOrders}      icon={<ShoppingBag className="w-5 h-5" />} color="blue"  loading={refreshing} />
          <StatCard label="Active Customers"  value={stats.activeCustomers}  icon={<Users className="w-5 h-5" />}     color="green" loading={refreshing} />
          <StatCard label="Live Products"     value={stats.liveProducts}     icon={<Package className="w-5 h-5" />}   color="amber" loading={refreshing} />
        </div>

        {/* Chart Row */}
        <div className="artisan-card min-h-[450px] flex flex-col">
          <div className="flex items-center justify-between mb-8">
            <div>
              <h3 className="text-lg font-bold text-[var(--charcoal)] mb-1">Earning Statistics</h3>
              <div className="text-xs text-[var(--muted)] tracking-wider flex items-center gap-2">
                Revenue for this {dateFilter}
                <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block" />
                <span className="text-green-600 font-bold">Live</span>
              </div>
            </div>
            <Activity className="w-5 h-5 text-[var(--muted)]" />
          </div>
          <div className="flex-1 w-full relative">
            {refreshing && (
              <div className="absolute inset-0 z-10 bg-white/50 backdrop-blur-[2px] flex items-center justify-center rounded-xl">
                <RefreshCw className="w-8 h-8 text-[var(--rust)] animate-spin" />
              </div>
            )}
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={analytics.revenueSeries} margin={{ top: 20, right: 30, left: 10, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5DDD5" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: "#8C7B70", fontWeight: "600", fontFamily: '"Playfair Display", Georgia, serif' }} dy={12} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: "#8C7B70", fontWeight: "600", fontFamily: '"Playfair Display", Georgia, serif' }} tickFormatter={v => v >= 1000 ? `₱${(v/1000).toFixed(0)}k` : `₱${v}`} />
                <Tooltip
                  cursor={{ fill: "rgba(192,66,42,0.04)" }}
                  content={({ active, payload }) => {
                    if (active && payload?.length) return (
                      <div className="bg-[#1C1917] text-white p-3 rounded-xl shadow-2xl border border-white/10">
                        <div className="text-[10px] font-bold text-white/50 uppercase tracking-widest mb-1">{payload[0].payload.name}</div>
                        <div className="text-lg font-serif font-bold">₱{payload[0].value.toLocaleString()}</div>
                      </div>
                    );
                    return null;
                  }}
                />
                <Bar dataKey="revenue" fill="#C0422A" radius={[10, 10, 4, 4]} barSize={dateFilter === 'today' ? 15 : dateFilter === 'month' ? 12 : 45} animationDuration={1200} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Bottom Charts Row */}
        <div className="grid grid-cols-1 xl:grid-cols-2 gap-8">
          {/* User Signups Area Chart */}
          <div className="artisan-card min-h-[400px]">
            <div className="flex items-center justify-between mb-8">
              <div>
                <h3 className="text-lg font-bold text-[var(--charcoal)] mb-1">User Growth</h3>
                <div className="text-xs text-[var(--muted)] tracking-wider">New registrations per month</div>
              </div>
              <CloudRain className="w-5 h-5 text-[var(--rust)]" />
            </div>
            <div className="h-[280px]">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={analytics.monthlySignups}>
                  <defs>
                    <linearGradient id="colorHits" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%"  stopColor="#C0422A" stopOpacity={0.2} />
                      <stop offset="95%" stopColor="#C0422A" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: "#8C7B70", fontWeight: "600" }} />
                  <Tooltip contentStyle={{ background: "#fff", borderRadius: "14px", border: "1px solid #E5DDD5", fontSize: "12px" }} labelStyle={{ fontWeight: "bold" }} />
                  <Area type="monotone" dataKey="hits" stroke="#C0422A" strokeWidth={3} fillOpacity={1} fill="url(#colorHits)" animationDuration={1200} />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Top Locations */}
          <div className="artisan-card min-h-[400px] flex flex-col">
            <h3 className="text-lg font-bold text-[var(--charcoal)] mb-6 underline decoration-[var(--border)] decoration-4 underline-offset-8">
              Orders by Location
            </h3>
            {analytics.topLocations.length === 0 ? (
              <div className="flex-1 flex items-center justify-center text-[var(--muted)] text-sm italic opacity-50">
                No location data yet
              </div>
            ) : (
              <div className="space-y-6 flex-1 flex flex-col justify-center">
                {analytics.topLocations.map((loc, i) => {
                  const maxCount = Math.max(...analytics.topLocations.map(l => l.count), 1);
                  return (
                    <div key={i} className="space-y-2">
                      <div className="flex justify-between text-xs font-bold tracking-widest">
                        <span>{loc.city.toUpperCase()}</span>
                        <span className="text-[var(--muted)] opacity-60">{loc.count} orders</span>
                      </div>
                      <div className="h-2 w-full bg-[var(--cream)] rounded-full overflow-hidden">
                        <motion.div
                          initial={{ width: 0 }}
                          whileInView={{ width: `${(loc.count / maxCount) * 100}%` }}
                          transition={{ duration: 1, ease: "easeOut", delay: i * 0.1 }}
                          className="h-full bg-[var(--rust)] rounded-full"
                        />
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>

        {/* Order Status Breakdown */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          {Object.entries(analytics.orderStatusBreakdown).map(([status, count]) => {
            const label = status.charAt(0).toUpperCase() + status.slice(1);
            const meta = STATUS_META[label] || STATUS_META.Pending;
            return (
              <div key={status} className="artisan-card p-5 flex flex-col items-center gap-2 text-center">
                <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${meta.color}`}>
                  {meta.icon}
                </div>
                <div className="text-2xl font-serif font-bold text-[var(--charcoal)]">{count}</div>
                <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-widest">{label}</div>
              </div>
            );
          })}
        </div>


      </div>
    </AdminLayout>
  );
}

function StatCard({ label, value, icon, color, loading }) {
  const colorMap = {
    rust:  "bg-[rgba(192,66,42,0.1)] text-[var(--rust)]",
    blue:  "bg-blue-50 text-blue-600",
    green: "bg-green-50 text-green-600",
    amber: "bg-amber-50 text-amber-600",
  };
  return (
    <motion.div whileHover={{ y: -4 }} className="artisan-card group cursor-pointer relative overflow-hidden">
      <div className="flex items-center justify-between mb-5">
        <div className={`w-12 h-12 rounded-2xl flex items-center justify-center transition-all ${colorMap[color]}`}>
          {icon}
        </div>
        <span className="w-2 h-2 rounded-full bg-green-400 animate-pulse" title="Live" />
      </div>
      {loading ? (
        <div className="h-8 w-24 bg-[var(--cream)] animate-pulse rounded-lg mb-1" />
      ) : (
        <div className="text-2xl font-serif font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors mb-1">
          {value}
        </div>
      )}
      <div className="text-[10px] font-bold text-[var(--muted)] opacity-60 uppercase tracking-widest">
        {label}
      </div>
    </motion.div>
  );
}


