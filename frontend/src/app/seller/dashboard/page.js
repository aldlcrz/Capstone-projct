"use client";
import React, { useState, useEffect } from "react";
import SellerLayout from "@/components/SellerLayout";
import { 
  DollarSign, 
  ShoppingBag, 
  Users, 
  MessageCircle, 
  FileDown, 
  Calendar,
  Filter,
  BarChart3,
  TrendingUp,
  UserCheck
} from "lucide-react";
import { 
  AreaChart, 
  Area, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer, 
  LineChart, 
  Line,
  Legend
} from "recharts";
import { motion } from "framer-motion";

const salesData = [
  { name: 'Jan', sales: 42000 },
  { name: 'Feb', sales: 38000 },
  { name: 'Mar', sales: 51000 },
  { name: 'Apr', sales: 46000 },
  { name: 'May', sales: 59000 },
  { name: 'Jun', sales: 74000 },
  { name: 'Jul', sales: 68000 },
];

const cohortData = [
  { name: 'Week 1', Jan: 4000, Mar: 6000 },
  { name: 'Week 2', Jan: 3000, Mar: 5500 },
  { name: 'Week 3', Jan: 5000, Mar: 7200 },
  { name: 'Week 4', Jan: 4500, Mar: 8100 },
];

import { api, BACKEND_URL } from "@/lib/api";
import { io } from "socket.io-client";

export default function SellerDashboard() {
  const [mounted, setMounted] = useState(false);
  const [stats, setStats] = useState({ revenue: 0, orders: 0, inquiries: 0, products: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setMounted(true);
    const fetchStats = async () => {
      try {
        const res = await api.get("/products/seller-stats", {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
        });
        setStats(res.data);
      } catch (err) {
        console.error("Failed to fetch seller stats");
      } finally {
        setLoading(false);
      }
    };
    fetchStats();

    const user = JSON.parse(localStorage.getItem("user") || "{}");
    const socket = io(BACKEND_URL);
    
    socket.emit('join_room', `user_${user.id}`);
    
    socket.on('stats_update', (data) => {
      if (!data || data.sellerId === user.id || !data.sellerId) {
        fetchStats();
      }
    });

    socket.on('new_order', () => {
      alert("New Heritage Order Received!");
      fetchStats();
    });

    return () => socket.disconnect();
  }, []);

  const handleExportReport = () => {
    const csvContent = "data:text/csv;charset=utf-8,Metric,Value\n" 
      + `Total Revenue,₱${(stats?.revenue || 0).toLocaleString()}\n`
      + `Total Orders,${stats?.orders || 0}\n`
      + `Active Inquiries,${stats?.inquiries || 0}\n`
      + `Total Products,${stats?.products || 0}`;
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `workshop_performance_${new Date().toLocaleDateString()}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  };

  if (!mounted) return null;

  return (
    <SellerLayout>
      <div className="space-y-10 mb-20">
        {/* Verification Pending Banner if shop not approved - placeholder */}
        {/* <div className="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center gap-4 text-amber-800 animate-fade-down">
          <div className="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center"><Filter className="w-5 h-5" /></div>
          <div><div className="font-bold text-sm">Shop Verification Pending</div><div className="text-xs opacity-70 tracking-wide">Our admins are reviewing your indigency certificates.</div></div>
        </div> */}

        {/* Dashboard Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">Artisan Performance</div>
            <h1 className="font-serif text-charcoal text-4xl font-bold tracking-tight uppercase">
              Workshop <span className="text-[var(--rust)] italic lowercase">Dashboard</span>
            </h1>
          </div>
          <div className="flex items-center gap-3">
            <button className="px-5 py-3 bg-white border border-[var(--border)] rounded-xl flex items-center gap-2 text-xs font-bold uppercase tracking-widest hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all">
              <Calendar className="w-4 h-4" /> This Month
            </button>
            <button onClick={handleExportReport} className="px-5 py-3 bg-[var(--bark)] text-white rounded-xl flex items-center gap-2 text-xs font-bold uppercase tracking-widest hover:bg-[var(--rust)] transition-all shadow-md">
              <FileDown className="w-4 h-4" /> Export (.xlsx)
            </button>
          </div>
        </div>

        {/* 4 KPI summary cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <KPICard label="Total Revenue" value={`₱${(stats?.revenue || 0).toLocaleString()}`} icon={<DollarSign className="w-5 h-5" />} bg="bg-[var(--rust)]" textColor="text-white" />
          <KPICard label="Shop Orders" value={stats?.orders || 0} icon={<ShoppingBag className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
          <KPICard label="Retention" value="48.2%" icon={<UserCheck className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
          <KPICard label="Inquiries" value={stats?.inquiries || 0} icon={<MessageCircle className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
        </div>

        {/* Main Sales Area Chart */}
        <div className="artisan-card min-h-[450px] flex flex-col group">
          <div className="flex items-center justify-between mb-10">
            <div>
              <h3 className="text-xl font-bold text-[var(--charcoal)] mb-1">Workshop Sales Trend</h3>
              <div className="text-xs text-[var(--muted)] tracking-wider">Revenue performance over selected date range</div>
            </div>
            <TrendingUp className="w-6 h-6 text-[var(--rust)]" />
          </div>

          <div className="flex-1">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={stats.performance && stats.performance.length > 0 ? stats.performance : salesData}>
                <defs>
                  <linearGradient id="colorSalesPremium" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#C0422A" stopOpacity={0.15}/>
                    <stop offset="95%" stopColor="#C0422A" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5DDD5" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#8C7B70', fontWeight: 'bold' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#8C7B70', fontWeight: 'bold' }} tickFormatter={(val) => `₱${val/1000}k`} />
                <Tooltip 
                  contentStyle={{ background: '#1c1917', border: 'none', borderRadius: '14px', color: '#fff', padding: '12px' }}
                  itemStyle={{ color: '#fff', fontStyle: 'italic' }}
                />
                <Area type="monotone" dataKey="sales" stroke="#C0422A" strokeWidth={4} fillOpacity={1} fill="url(#colorSalesPremium)" animationDuration={2000} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-2 gap-8">
          {/* Lifetime Revenue Line Chart (Cohort) */}
          <div className="artisan-card min-h-[400px]">
            <h3 className="text-lg font-bold text-[var(--charcoal)] mb-8 flex items-center gap-3">
              Avg. Lifetime Revenue <div className="text-[10px] font-bold text-white bg-[var(--rust)] px-2 py-0.5 rounded-full">COHORT</div>
            </h3>
            <div className="h-[280px]">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={cohortData}>
                  <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: '#8C7B70' }} />
                  <Tooltip 
                    contentStyle={{ borderRadius: '12px', border: '1px solid #E5DDD5' }}
                  />
                  <Legend iconType="circle" />
                  <Line type="monotone" dataKey="Jan" stroke="#D4B896" strokeWidth={3} dot={{ r: 4, strokeWidth: 2 }} />
                  <Line type="monotone" dataKey="Mar" stroke="#C0422A" strokeWidth={3} dot={{ r: 4, strokeWidth: 2 }} strokeDasharray="5 5" />
                </LineChart>
              </ResponsiveContainer>
            </div>
            <div className="mt-4 text-center text-xs text-[var(--muted)] italic">Comparing performance of users registered in Jan vs Mar 2026</div>
          </div>

          {/* Customer Retention Heatmap (Custom CSS) */}
          <div className="artisan-card h-[400px] flex flex-col">
            <h3 className="text-lg font-bold text-[var(--charcoal)] mb-8">Customer Retention (%)</h3>
            <div className="flex-1 grid grid-cols-6 grid-rows-6 gap-2">
              {[...Array(36)].map((_, i) => {
                const val = Math.floor(Math.random() * 100);
                const colors = ['bg-[#f3dad6]', 'bg-[#e8b5ac]', 'bg-[#d26a4e]', 'bg-[#c14a38]'];
                const color = colors[Math.floor(val/25)];
                return (
                  <motion.div 
                    key={i} 
                    whileHover={{ scale: 1.1, zIndex: 10 }}
                    className={`${color} rounded-md flex items-center justify-center text-[10px] font-bold text-white/80 shadow-sm`}
                  >
                    {val}%
                  </motion.div>
                );
              })}
            </div>
            <div className="flex items-center gap-6 mt-6 pt-6 border-t border-[var(--border)] text-[10px] uppercase font-bold tracking-widest text-[var(--muted)]">
               <div className="flex items-center gap-2"><div className="w-3 h-3 bg-[#f3dad6] rounded-sm" /> Low</div>
               <div className="flex items-center gap-2"><div className="w-3 h-3 bg-[#c14a38] rounded-sm" /> High</div>
            </div>
          </div>
        </div>

        {/* Sales Funnel Visual (Custom CSS Funnel) */}
        <div className="artisan-card p-10">
          <h3 className="text-xl font-bold text-[var(--charcoal)] mb-10 text-center uppercase tracking-widest underline decoration-[var(--rust)] underline-offset-8">Sales Performance Funnel</h3>
          <div className="max-w-2xl mx-auto space-y-4">
            <FunnelBar label="Visitors" value="12,500" width="100%" color="bg-[var(--bark)]" />
            <FunnelBar label="Product Views" value="8,400" width="85%" color="bg-[#594436]" />
            <FunnelBar label="Add to Cart" value="4,200" width="60%" color="bg-[#8C7B70]" />
            <FunnelBar label="Checkout" value="1,800" width="35%" color="bg-[var(--sand)]" />
            <FunnelBar label="Completed" value="1,240" width="20%" color="bg-[var(--rust)]" />
          </div>
        </div>
      </div>
    </SellerLayout>
  );
}

function KPICard({ label, value, icon, bg, textColor }) {
  return (
    <div className={`artisan-card p-8 flex flex-col justify-between h-[180px] group hover:scale-[1.02] ${bg}`}>
      <div className="flex justify-between items-start">
        <div className={`text-sm font-bold uppercase tracking-widest ${bg === 'bg-white' ? 'text-[var(--muted)]' : 'text-white/60'}`}>{label}</div>
        <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${bg === 'bg-white' ? 'bg-[var(--cream)]' : 'bg-white/20 text-white'}`}>
          {icon}
        </div>
      </div>
      <div className={`text-3xl font-serif font-bold ${textColor}`}>{value}</div>
    </div>
  );
}

function FunnelBar({ label, value, width, color }) {
  return (
    <div className="flex items-center gap-6 group">
      <div className="w-32 text-right text-[10px] font-bold tracking-widest text-[var(--muted)] uppercase">{label}</div>
      <div className="flex-1 h-14 bg-[var(--cream)] rounded-xl overflow-hidden relative">
        <motion.div 
          initial={{ width: 0 }}
          whileInView={{ width }}
          transition={{ duration: 1.5, ease: "circOut" }}
          className={`h-full ${color} rounded-r-xl shadow-lg flex items-center justify-end px-6`}
        >
          <span className="text-white font-bold text-sm tracking-tight">{value}</span>
        </motion.div>
      </div>
    </div>
  );
}
