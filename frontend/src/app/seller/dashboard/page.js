"use client";
import React, { useState, useEffect, useCallback, useRef } from "react";
import SellerLayout from "@/components/SellerLayout";
import {
  DollarSign,
  ShoppingBag,
  MessageCircle,
  FileDown,
  BarChart3,
  UserCheck,
  RefreshCw,
  TrendingUp,
} from "lucide-react";
import {
  BarChart,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  AreaChart,
  Area,
  PieChart,
  Pie,
  Cell,
} from "recharts";
import { motion, AnimatePresence } from "framer-motion";
import { api, getApiErrorMessage, getStoredUserForRole, getTokenForRole, SESSION_SYNC_EVENT } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

const FILTER_LABELS = { today: "Today", week: "This Week", month: "This Month", year: "This Year" };
const EMPTY_FUNNEL = { visitors: 0, views: 0, addedToCart: 0, contactLeadCustomers: 0, checkout: 0, completed: 0 };
const EMPTY_STATS = {
  revenue: 0,
  orders: 0,
  inquiries: 0,
  products: 0,
  topProducts: [],
  topCategories: [],
  performance: [],
  retention: 0,
  funnel: EMPTY_FUNNEL,
};

const getStoredSellerId = () => {
  if (typeof window === "undefined") return null;

  try {
    const seller = getStoredUserForRole("seller");
    return seller?.id || null;
  } catch (error) {
    return null;
  }
};

export default function SellerDashboard() {
  const [mounted, setMounted] = useState(false);
  const [showRevenueTrend, setShowRevenueTrend] = useState(false);
  const [dateFilter, setDateFilter] = useState("month");
  const [stats, setStats] = useState(EMPTY_STATS);
  const [loading, setLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [fetchError, setFetchError] = useState(null);
  const [newOrderAlert, setNewOrderAlert] = useState(false);
  const [filterKey, setFilterKey] = useState(0); // forces chart re-animation
  const sellerIdRef = useRef(null);
  const refreshTimeoutRef = useRef(null);
  const newOrderToastTimeoutRef = useRef(null);

  const { socket } = useSocket();

  const fetchStats = useCallback(async ({ background = false } = {}) => {
    const token = getTokenForRole("seller");
    if (!token) {
      setStats(EMPTY_STATS);
      setFetchError(null);
      setLoading(false);
      setIsRefreshing(false);
      return;
    }

    if (background) {
      setIsRefreshing(true);
    } else {
      setLoading(true);
    }
    try {
      const res = await api.get(`/products/seller-stats?range=${dateFilter}`);
      setStats(res.data);
      setFetchError(null);
      setFilterKey(k => k + 1); // trigger re-animation every refresh
    } catch (err) {
      if (err?.response?.status === 401) {
        setFetchError(null);
        setLoading(false);
        return;
      }
      const msg = getApiErrorMessage(err, "Failed to load stats");
      console.warn("Failed to fetch seller stats:", msg);
      setFetchError(msg);
    } finally {
      if (background) {
        setIsRefreshing(false);
      } else {
        setLoading(false);
      }
    }
  }, [dateFilter]);

  const scheduleRealtimeRefresh = useCallback(() => {
    if (!mounted) return;

    if (refreshTimeoutRef.current) {
      clearTimeout(refreshTimeoutRef.current);
    }

    refreshTimeoutRef.current = setTimeout(() => {
      fetchStats({ background: true });
    }, 180);
  }, [fetchStats, mounted]);

  useEffect(() => {
    setMounted(true);
    sellerIdRef.current = getStoredSellerId();

    return () => {
      if (refreshTimeoutRef.current) clearTimeout(refreshTimeoutRef.current);
      if (newOrderToastTimeoutRef.current) clearTimeout(newOrderToastTimeoutRef.current);
    };
  }, []);

  useEffect(() => {
    if (mounted) fetchStats();
  }, [mounted, dateFilter, fetchStats]);

  useEffect(() => {
    const handleSessionSync = () => {
      sellerIdRef.current = getStoredSellerId();

      if (!getTokenForRole("seller")) {
        setStats(EMPTY_STATS);
        setFetchError(null);
        setLoading(false);
        setIsRefreshing(false);
        return;
      }

      scheduleRealtimeRefresh();
    };

    window.addEventListener(SESSION_SYNC_EVENT, handleSessionSync);
    return () => window.removeEventListener(SESSION_SYNC_EVENT, handleSessionSync);
  }, [scheduleRealtimeRefresh]);

  useEffect(() => {
    if (!socket || !mounted) return;

    const matchesActiveSeller = (candidateSellerId) =>
      sellerIdRef.current && String(candidateSellerId) === String(sellerIdRef.current);

    const handleStatsUpdate = (data = {}) => {
      if (!data?.sellerId || !matchesActiveSeller(data.sellerId)) return;
      scheduleRealtimeRefresh();
    };

    const handleInventoryUpdated = (data = {}) => {
      if (!matchesActiveSeller(data?.product?.sellerId)) return;
      scheduleRealtimeRefresh();
    };

    const handleNewOrder = () => {
      setNewOrderAlert(true);
      if (newOrderToastTimeoutRef.current) clearTimeout(newOrderToastTimeoutRef.current);
      newOrderToastTimeoutRef.current = setTimeout(() => setNewOrderAlert(false), 5000);
      scheduleRealtimeRefresh();
    };

    const handleOrderStatusUpdate = () => {
      scheduleRealtimeRefresh();
    };

    const handleReceiveMessage = (message = {}) => {
      const currentSellerId = sellerIdRef.current;
      if (!currentSellerId) return;

      const isConversationForActiveSeller =
        String(message.receiverId) === String(currentSellerId) ||
        String(message.senderId) === String(currentSellerId);

      if (!isConversationForActiveSeller) return;
      scheduleRealtimeRefresh();
    };

    socket.on("stats_update", handleStatsUpdate);
    socket.on("inventory_updated", handleInventoryUpdated);
    socket.on("new_order", handleNewOrder);
    socket.on("order_status_update", handleOrderStatusUpdate);
    socket.on("receive_message", handleReceiveMessage);

    return () => {
      socket.off("stats_update", handleStatsUpdate);
      socket.off("inventory_updated", handleInventoryUpdated);
      socket.off("new_order", handleNewOrder);
      socket.off("order_status_update", handleOrderStatusUpdate);
      socket.off("receive_message", handleReceiveMessage);
    };
  }, [socket, mounted, scheduleRealtimeRefresh]);

  const handleExportReport = async () => {
    try {
      setLoading(true);
      const res = await api.get('/orders/export-report', { responseType: 'blob' });

      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `lumbarong_report_${new Date().getTime()}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error("Export failed:", err);
      alert("Failed to generate report. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  if (!mounted) return null;

  const topProducts = Array.isArray(stats.topProducts) ? stats.topProducts : [];
  const hasTopProducts = topProducts.length > 0;
  const isBusy = loading || isRefreshing;

  // Funnel data with proportional widths
  const funnel = stats.funnel || {};
  const funnelMax = Math.max(
    funnel.visitors || 0,
    funnel.views || 0,
    funnel.addedToCart || 0,
    funnel.contactLeadCustomers || 0,
    funnel.checkout || 0,
    funnel.completed || 0,
    1
  );
  const funnelSteps = [
    { label: "Visitors", value: funnel.visitors || 0, color: "var(--bark)" },
    { label: "Contacts", value: funnel.contacts || 0, color: "#594436" },
    { label: "Leads", value: funnel.leads || 0, color: "#7A6658" },
    { label: "Add to Cart", value: funnel.addedToCart || 0, color: "#A58E7C" },
    { label: "Checkout", value: funnel.checkout || 0, color: "#8C7B70" },
    { label: "Customers", value: funnel.completed || 0, color: "var(--rust)" },
  ];

  return (
    <SellerLayout>
      <div className="mb-20 space-y-6 md:space-y-10">

        {/* New Order Toast */}
        <AnimatePresence>
          {newOrderAlert && (
            <motion.div
              initial={{ opacity: 0, y: -20, scale: 0.95 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              exit={{ opacity: 0, y: -20 }}
              className="fixed left-4 right-4 top-4 z-50 flex items-center gap-3 rounded-2xl bg-green-600 p-3 text-white shadow-2xl sm:left-auto sm:right-6 sm:top-6 sm:max-w-sm sm:p-4"
            >
              <ShoppingBag className="w-5 h-5" />
              <div>
                <div className="font-bold text-sm">New Order (Heritage Sale)!</div>
                <div className="text-xs opacity-80">A customer just placed an order.</div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Fetch Error Banner */}
        {fetchError && (
          <div className="flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 p-3 text-xs font-bold text-red-700 md:gap-3 md:p-4 md:text-sm">
            <span>⚠️</span> Could not load stats: {fetchError}
          </div>
        )}

        {/* Dashboard Header */}
        <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end md:gap-6">
          <div>
            <div className="text-[10px] sm:text-xs uppercase tracking-wider text-[var(--muted)] mb-1 font-bold">Seller Performance</div>
            <h1 className="font-serif text-lg sm:text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              SELLER <span className="text-[var(--rust)] italic lowercase">dashboard</span>
            </h1>
            <p className="mt-1 text-[11px] font-medium text-[var(--muted)] sm:text-xs">
              Showing data for: <span className="text-[var(--rust)] font-bold">{FILTER_LABELS[dateFilter]}</span>
              {isBusy && <RefreshCw className="inline-block w-3 h-3 ml-2 animate-spin opacity-60" />}
            </p>
          </div>
          <div className="flex w-full flex-col-reverse gap-2 sm:flex-row sm:items-center md:w-auto md:gap-3">
            <div className="grid grid-cols-4 items-center gap-1 rounded-xl border border-[var(--border)] bg-white p-1 shadow-sm sm:flex">
              {["today", "week", "month", "year"].map(f => (
                <button
                  key={f}
                  type="button"
                  onClick={(e) => {
                    e.preventDefault();
                    setDateFilter(f);
                  }}
                  className={`min-w-0 rounded-lg px-2 py-2 text-[9px] font-bold uppercase tracking-[0.2em] transition-all sm:px-4 sm:py-1.5 sm:text-[10px] sm:tracking-widest ${dateFilter === f ? "bg-[var(--rust)] text-white shadow" : "text-[var(--muted)] hover:text-[var(--rust)]"}`}
                >
                  {f}
                </button>
              ))}
            </div>
            <button
              onClick={handleExportReport}
              className="flex w-fit self-end items-center justify-center gap-2 rounded-xl bg-[var(--bark)] px-4 py-2.5 text-[11px] font-bold uppercase tracking-[0.2em] text-white shadow-md transition-all hover:bg-[var(--rust)] sm:w-auto sm:px-5 sm:py-3 sm:text-xs sm:tracking-widest"
            >
              <FileDown className="w-4 h-4" /> Export (.csv)
            </button>
          </div>
        </div>

        {/* 4 KPI summary cards */}
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-4">
          <KPICard label="Total Revenue" value={loading ? "—" : `₱${(stats?.revenue || 0).toLocaleString()}`} icon={<DollarSign className="w-5 h-5" />} bg="bg-[var(--rust)]" textColor="text-white" />
          <KPICard label="Orders" value={loading ? "—" : (stats?.orders || 0)} icon={<ShoppingBag className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
          <KPICard label="Suki (Loyalty)" value={loading ? "—" : `${stats?.retention || '0'}%`} icon={<UserCheck className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
          <KPICard label="Messages" value={loading ? "—" : (stats?.inquiries || 0)} icon={<MessageCircle className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
        </div>



        {/* Revenue Trend Chart (Toggleable) */}
        <AnimatePresence>
          {showRevenueTrend && (
            <motion.div
              initial={{ opacity: 0, height: 0, marginBottom: 0 }}
              animate={{ opacity: 1, height: 'auto', marginBottom: 32 }}
              exit={{ opacity: 0, height: 0, marginBottom: 0, overflow: 'hidden' }}
              className="rounded-[20px] border border-[var(--border)] bg-white p-3 shadow-sm sm:p-4"
            >
              <div className="mb-3 flex items-center justify-between gap-2">
                <div>
                  <h3 className="mb-0.5 text-sm font-bold text-[var(--charcoal)]">Revenue trend</h3>
                  <div className="text-[10px] font-medium text-[var(--muted)]">
                    Financial performance · {FILTER_LABELS[dateFilter]}
                    {isBusy && <RefreshCw className="inline-block w-2.5 h-2.5 ml-1.5 animate-spin opacity-60" />}
                  </div>
                </div>
                <button
                  onClick={() => setShowRevenueTrend(false)}
                  className="rounded-lg border border-[var(--rust)] bg-[var(--rust)] p-1.5 text-white shadow-sm transition-all hover:border-[#a33520] hover:bg-[#a33520]"
                  title="Hide Revenue Trend"
                >
                  <TrendingUp className="pointer-events-none h-3.5 w-3.5" />
                </button>
              </div>

              <div className="mb-3 grid grid-cols-3 gap-2">
                <div className="rounded-lg bg-[#f7f6f2] p-2.5">
                  <div className="mb-1 text-[10px] font-semibold text-[var(--charcoal)] opacity-70">Total revenue</div>
                  <div className="flex items-baseline gap-1 text-sm font-bold text-[var(--charcoal)]">
                    ₱{(stats?.revenue || 0).toLocaleString()} <span className="text-[10px] font-medium text-[var(--muted)]">period</span>
                  </div>
                </div>
                <div className="rounded-lg bg-[#f7f6f2] p-2.5">
                  <div className="mb-1 text-[10px] font-semibold text-[var(--charcoal)] opacity-70">Units sold</div>
                  <div className="flex items-baseline gap-1 text-sm font-bold text-[var(--charcoal)]">
                    {stats?.topProducts?.reduce((sum, p) => sum + p.sales, 0) || 0} <span className="text-[10px] font-medium text-[var(--muted)]">top 5</span>
                  </div>
                </div>
                <div className="rounded-lg bg-[#f7f6f2] p-2.5">
                  <div className="mb-1 text-[10px] font-semibold text-[var(--charcoal)] opacity-70">Avg. order</div>
                  <div className="flex items-baseline gap-1 text-sm font-bold text-[var(--charcoal)]">
                    ₱{stats?.orders ? (stats.revenue / stats.orders).toFixed(1) : 0} <span className="text-[10px] font-medium text-[var(--muted)]">/order</span>
                  </div>
                </div>
              </div>

              <div className="relative h-[100px] w-full">
                {loading ? (
                  <div className="absolute inset-0 flex items-center justify-center">
                    <RefreshCw className="w-8 h-8 text-[var(--muted)] animate-spin opacity-30" />
                  </div>
                ) : (
                  <ResponsiveContainer key={`trend-${filterKey}`} width="100%" height="100%" minWidth={0}>
                    <AreaChart data={stats.performance || []} margin={{ top: 10, right: 0, left: -20, bottom: 0 }}>
                      <defs>
                        <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="5%" stopColor="#C0422A" stopOpacity={0.15} />
                          <stop offset="95%" stopColor="#C0422A" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5DDD5" />
                      <XAxis
                        dataKey="name"
                        axisLine={false}
                        tickLine={false}
                        tick={{ fontSize: 10, fill: "#8C7B70", fontWeight: "600" }}
                        dy={10}
                      />
                      <YAxis
                        axisLine={false}
                        tickLine={false}
                        tick={{ fontSize: 10, fill: "#8C7B70", fontWeight: "600" }}
                        tickFormatter={(v) => v === 0 ? "" : `₱${(v / 1000).toFixed(0)}k`}
                      />
                      <Tooltip
                        contentStyle={{ background: "#fff", border: "1px solid #E5DDD5", borderRadius: "12px", color: "#1c1917", padding: "10px 14px", boxShadow: "0 10px 25px -5px rgba(0, 0, 0, 0.1)" }}
                        itemStyle={{ color: "#C0422A", fontWeight: "bold" }}
                        labelStyle={{ fontWeight: "bold", fontSize: "11px", marginBottom: "4px", color: "#8c7b70" }}
                        formatter={(val) => [`₱${val.toLocaleString()}`, "Revenue"]}
                      />
                      <Area type="monotone" dataKey="sales" stroke="#C0422A" strokeWidth={2.5} fillOpacity={1} fill="url(#colorRevenue)" animationDuration={1000} />
                    </AreaChart>
                  </ResponsiveContainer>
                )}
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Top Products & Top Categories (Modern Side-by-Side) */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">

          {/* Top Products (Smaller Header) */}
          <div className="rounded-[24px] border border-[var(--border)] bg-white p-4 shadow-sm sm:p-5 md:p-8 lg:col-span-8">
            <div className="mb-5 flex items-start justify-between gap-3 md:mb-8">
              <div>
                <h3 className="mb-0.5 text-base font-bold text-[var(--charcoal)] md:text-lg">Top products</h3>
                <div className="text-[10px] font-medium text-[var(--muted)] md:text-[11px]">
                  Most sold products · {FILTER_LABELS[dateFilter]}
                </div>
              </div>
              <button
                onClick={() => setShowRevenueTrend(!showRevenueTrend)}
                className={`rounded-xl border p-2 shadow-sm transition-all md:p-2.5 ${showRevenueTrend ? 'bg-[var(--rust)] border-[var(--rust)] text-white' : 'bg-white border-[var(--border)] text-[var(--rust)] hover:bg-[#FAF9F7]'}`}
                title={showRevenueTrend ? "Hide Revenue Trend" : "Show Revenue Trend"}
              >
                <BarChart3 className="pointer-events-none h-4 w-4 md:h-5 md:w-5" />
              </button>
            </div>

            <div className="space-y-3 md:space-y-4">
              {/* Header row */}
              <div className="hidden grid-cols-12 gap-4 border-b border-[var(--border)] px-4 pb-2 text-[10px] font-semibold text-[var(--muted)] md:grid">
                <div className="col-span-3">Product name</div>
                <div className="col-span-2">category</div>
                <div className="col-span-3">Sales volume</div>
                <div className="col-span-2">Rating</div>
                <div className="col-span-2">Status</div>
              </div>

              {loading ? (
                <div className="flex items-center justify-center py-8 md:py-12">
                  <RefreshCw className="w-6 h-6 text-[var(--muted)] animate-spin opacity-30" />
                </div>
              ) : !hasTopProducts ? (
                <div className="py-8 text-center text-[11px] font-medium text-[var(--muted)] md:py-12">
                  No product sales yet in this period.
                </div>
              ) : (
                <div className="space-y-3">
                  <div className="grid gap-3 md:hidden">
                    {topProducts.map((prod, i) => (
                      <TopProductMobileCard key={`mobile-${prod.id || i}`} prod={prod} index={i} />
                    ))}
                  </div>
                  <div className="hidden space-y-3 md:block">
                    {topProducts.map((prod, i) => (
                      <TopProductDesktopRow key={prod.id || i} prod={prod} index={i} />
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Top Categories Chart */}
          <div className="rounded-[24px] border border-[var(--border)] bg-white p-4 shadow-sm sm:p-5 md:p-8 lg:col-span-4">
            <div className="mb-6">
              <h3 className="mb-0.5 text-base font-bold text-[var(--charcoal)] md:text-lg">Top Categories</h3>
              <div className="text-[10px] font-medium text-[var(--muted)] md:text-[11px]">Sales distribution</div>
            </div>

            <div className="relative flex h-[280px] w-full flex-col items-center justify-center">
              {loading ? (
                <RefreshCw className="w-6 h-6 text-[var(--muted)] animate-spin opacity-30" />
              ) : !stats.topCategories?.length ? (
                <div className="text-[11px] font-medium text-[var(--muted)]">No data yet.</div>
              ) : (
                <>
                  <ResponsiveContainer width="100%" height="100%" minWidth={0}>
                    <PieChart>
                      <Pie
                        data={stats.topCategories}
                        cx="50%"
                        cy="50%"
                        innerRadius={60}
                        outerRadius={85}
                        paddingAngle={5}
                        dataKey="revenue"
                        animationBegin={200}
                        animationDuration={1000}
                      >
                        {stats.topCategories.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={['#594436', '#C0422A', '#A38B78', '#D6CDC4', '#705C53'][index % 5]} />
                        ))}
                      </Pie>
                      <Tooltip
                        contentStyle={{ borderRadius: '12px', border: 'none', boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)', fontSize: '11px', fontWeight: 'bold' }}
                        formatter={(value) => [`₱${value.toLocaleString()}`, 'Revenue']}
                      />
                    </PieChart>
                  </ResponsiveContainer>

                  {/* Legend */}
                  <div className="mt-4 w-full space-y-2">
                    {stats.topCategories.map((entry, i) => (
                      <div key={i} className="flex items-center justify-between text-[10px] font-bold">
                        <div className="flex items-center gap-2">
                          <div className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: ['#594436', '#C0422A', '#A38B78', '#D6CDC4', '#705C53'][i % 5] }} />
                          <span className="text-[var(--muted)]">{entry.name}</span>
                        </div>
                        <span className="text-[var(--charcoal)]">₱{entry.revenue.toLocaleString()}</span>
                      </div>
                    ))}
                  </div>
                </>
              )}
            </div>
          </div>
        </div>

        <div className="mx-auto max-w-3xl rounded-[24px] border border-[var(--border)] bg-white p-4 shadow-sm sm:p-5 md:p-8" key={`funnel-card-${filterKey}`}>
          <div className="mb-6">
            <h3 className="mb-0.5 text-base font-bold text-[var(--charcoal)] md:text-lg">Sales Funnel Support</h3>
            <div className="text-[10px] font-medium text-[var(--muted)] md:text-[11px]">Lifecycle performance · {FILTER_LABELS[dateFilter]}</div>
          </div>
          <div className="max-w-2xl space-y-1.5 md:space-y-2" key={`funnel-${filterKey}`}>
            {funnelSteps.map((step, i) => {
              const basePct = funnelMax > 0 ? Math.max((step.value / funnelMax) * 100, 2) : 2;
              // Tiered width reduction to force the "Funnel" shape even for high values
              const funnelTierModifier = 1 - (i * 0.08);
              const displayPct = basePct * funnelTierModifier;

              return (
                <FunnelBar
                  key={step.label}
                  label={step.label}
                  value={loading ? "—" : step.value.toLocaleString()}
                  pct={loading ? 2 : displayPct}
                  color={step.color}
                  delay={i * 0.1}
                  index={i}
                />
              );
            })}
          </div>
        </div>

      </div>
    </SellerLayout>
  );
}

function KPICard({ label, value, icon, bg, textColor }) {
  return (
    <div className={`artisan-card relative flex h-[90px] flex-col justify-between overflow-hidden p-3.5 transition-all group hover:scale-[1.02] md:h-[100px] md:p-4 ${bg}`}>
      <div className="flex justify-between items-start relative z-10">
        <div className={`text-[10px] font-bold uppercase tracking-[0.15em] ${bg === "bg-white" ? "text-[var(--muted)]" : "text-white/60"}`}>{label}</div>
        <div className={`flex h-7 w-7 items-center justify-center rounded-lg ${bg === "bg-white" ? "bg-[var(--cream)]" : "bg-white/10 text-white"}`}>
          {icon}
        </div>
      </div>
      <div className="relative z-10 flex items-center justify-between">
        <div className={`text-base font-serif font-bold ${textColor}`}>{value}</div>
        <div className="flex items-center gap-1.5 rounded-full border border-white/5 bg-white/10 px-1.5 py-0.5">
          <span className="w-1 h-1 rounded-full bg-green-400 animate-pulse" />
          <span className="text-[6px] font-bold text-white/50 uppercase tracking-tighter">Live</span>
        </div>
      </div>
    </div>
  );
}

function getProductStatusColor(status) {
  if (status === "Top seller") return "bg-[#eaf5eb] text-[#3b8c4c]";
  if (status === "Trending") return "bg-[#fcf5e3] text-[#b88c35]";
  return "bg-[#fcedeb] text-[#c95a46]";
}

function TopProductMobileCard({ prod, index }) {
  const max = prod.maxSalesRef || 1;
  const pct = Math.max((prod.sales / max) * 100, 2);
  const statusColor = getProductStatusColor(prod.status);
  const reviewCount = Number(prod.reviewsCount || 0);
  const numericRating = Number(prod.rating || 0);
  const hasReviews = reviewCount > 0 && numericRating > 0;

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="rounded-2xl bg-[#f7f6f2] p-4 transition-all hover:bg-[#f1efe9]"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="text-sm font-bold leading-tight text-[var(--charcoal)]">{prod.name}</div>
          <div className="mt-1 truncate text-[11px] font-semibold lowercase text-[var(--charcoal)]">
            {prod.category || "uncategorized"}
          </div>
        </div>
        <span className={`shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-bold ${statusColor}`}>
          {prod.status || "Trending"}
        </span>
      </div>

      <div className="mt-4 space-y-3">
        <div>
          <div className="mb-1.5 flex h-1.5 w-full items-center gap-2 overflow-hidden rounded-full bg-[#e3dfd7]">
            <motion.div
              initial={{ width: 0 }}
              animate={{ width: `${pct}%` }}
              transition={{ duration: 1, delay: 0.2 + (index * 0.1) }}
              className="h-full rounded-full bg-[var(--rust)]"
            />
          </div>
          <div className="text-[11px] font-medium text-[var(--charcoal)]">
            {prod.sales} sold <span className="opacity-40">·</span> ₱{prod.revenue?.toLocaleString() || 0}
          </div>
        </div>

        <div className="flex items-center justify-between gap-3">
          <div className="min-w-0">
            <div className="flex items-center gap-1">
            <div className="flex">
                {[1, 2, 3, 4, 5].map((star) => (
                  <svg key={star} className={`h-3 w-3 ${star <= Math.round(numericRating) ? "fill-current text-[#e56d4b]" : "fill-current text-[#e3dfd7]"}`} viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                ))}
              </div>
              <span className="ml-1 text-sm font-bold text-[var(--charcoal)]">{hasReviews ? numericRating.toFixed(1) : "No reviews"}</span>
            </div>
            <div className="mt-1 text-[10px] text-[var(--muted)]">{hasReviews ? `${reviewCount} reviews` : "No reviews yet"}</div>
          </div>
        </div>
      </div>
    </motion.div>
  );
}

function TopProductDesktopRow({ prod, index }) {
  const max = prod.maxSalesRef || 1;
  const pct = Math.max((prod.sales / max) * 100, 2);
  const statusColor = getProductStatusColor(prod.status);
  const reviewCount = Number(prod.reviewsCount || 0);
  const numericRating = Number(prod.rating || 0);
  const hasReviews = reviewCount > 0 && numericRating > 0;

  return (
    <motion.div
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="grid grid-cols-12 items-center gap-4 rounded-xl bg-[#f7f6f2] p-4 transition-all hover:bg-[#f1efe9]"
    >
      <div className="col-span-3 flex flex-col justify-center pr-4">
        <div className="truncate text-[13px] font-bold leading-tight text-[var(--charcoal)]">{prod.name}</div>
      </div>

      <div className="col-span-2 flex items-center pr-4">
        <div className="truncate text-[12px] font-semibold lowercase text-[var(--charcoal)]">{prod.category || "uncategorized"}</div>
      </div>

      <div className="col-span-3 pr-4">
        <div className="mb-1.5 flex h-1.5 w-full items-center gap-2 overflow-hidden rounded-full bg-[#e3dfd7]">
          <motion.div
            initial={{ width: 0 }}
            animate={{ width: `${pct}%` }}
            transition={{ duration: 1, delay: 0.2 + (index * 0.1) }}
            className="h-full rounded-full bg-[var(--rust)]"
          />
        </div>
        <div className="text-[11px] font-medium text-[var(--charcoal)]">
          {prod.sales} sold <span className="opacity-40">·</span> ₱{prod.revenue?.toLocaleString() || 0}
        </div>
      </div>

      <div className="col-span-2 flex flex-col justify-center">
        <div className="mb-1 flex items-center gap-1">
          <div className="flex">
            {[1, 2, 3, 4, 5].map((star) => (
              <svg key={star} className={`h-3 w-3 ${star <= Math.round(numericRating) ? "fill-current text-[#e56d4b]" : "fill-current text-[#e3dfd7]"}`} viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            ))}
          </div>
          <span className="ml-1 text-[12px] font-bold text-[var(--charcoal)]">{hasReviews ? numericRating.toFixed(1) : "No reviews"}</span>
        </div>
        <div className="text-[10px] text-[var(--muted)]">{hasReviews ? `${reviewCount} reviews` : "No reviews yet"}</div>
      </div>

      <div className="col-span-2">
        <span className={`whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-bold ${statusColor}`}>
          {prod.status || "Trending"}
        </span>
      </div>
    </motion.div>
  );
}

function FunnelBar({ label, value, pct, color, delay = 0 }) {
  return (
    <div className="group flex items-center gap-3 md:gap-4 transition-all">
      <div className="w-16 shrink-0 text-[10px] font-bold text-[var(--charcoal)] opacity-70 md:w-24 md:text-[11px]">
        {label}
      </div>
      <div className="relative h-7 flex-1 rounded-full bg-[#F8F7F4] md:h-8">
        <motion.div
          initial={{ width: "0%" }}
          animate={{ width: `${pct}%` }}
          transition={{ duration: 1, ease: "circOut", delay }}
          style={{ backgroundColor: color }}
          className="relative flex h-full min-w-[2.5rem] items-center justify-end rounded-full px-3 shadow-sm"
        >
          <span className="text-[10px] font-bold text-white shadow-sm">{value}</span>
        </motion.div>
      </div>
    </div>
  );
}
