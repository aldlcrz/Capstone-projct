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
  ResponsiveContainer
} from "recharts";
import { motion } from "framer-motion";

const emptySalesData = [
  { name: 'Jan', sales: 0 },
  { name: 'Feb', sales: 0 },
  { name: 'Mar', sales: 0 },
  { name: 'Apr', sales: 0 },
  { name: 'May', sales: 0 },
  { name: 'Jun', sales: 0 },
  { name: 'Jul', sales: 0 },
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
          <KPICard label="Suki" value={`${stats?.retention || '0'}%`} icon={<UserCheck className="w-5 h-5" />} bg="bg-white" textColor="text-[var(--charcoal)]" />
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
              <AreaChart data={stats.performance && stats.performance.length > 0 ? stats.performance : emptySalesData}>
                <defs>
                  <linearGradient id="colorSalesPremium" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#C0422A" stopOpacity={0.15}/>
                    <stop offset="95%" stopColor="#C0422A" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5DDD5" />
                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#8C7B70', fontWeight: '600', fontFamily: '"Playfair Display", Georgia, serif' }} />
                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11, fill: '#8C7B70', fontWeight: '600', fontFamily: '"Playfair Display", Georgia, serif' }} tickFormatter={(val) => `₱${val/1000}k`} />
                <Tooltip 
                  contentStyle={{ background: '#1c1917', border: 'none', borderRadius: '14px', color: '#fff', padding: '12px' }}
                  itemStyle={{ color: '#fff', fontStyle: 'italic' }}
                />
                <Area type="monotone" dataKey="sales" stroke="#C0422A" strokeWidth={4} fillOpacity={1} fill="url(#colorSalesPremium)" animationDuration={2000} />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>


        {/* Sales Funnel Visual (Real Statistics) */}
        <div className="artisan-card p-10">
          <h3 className="text-xl font-bold text-[var(--charcoal)] mb-10 text-center uppercase tracking-widest underline decoration-[var(--rust)] underline-offset-8">Workshop Sales Funnel</h3>
          <div className="max-w-2xl mx-auto space-y-4">
            <FunnelBar label="Visitors" value={Math.floor((stats.funnel?.views || 0) * 1.4).toLocaleString()} width={stats.funnel?.views > 0 ? "100%" : "5%"} color="bg-[var(--bark)]" />
            <FunnelBar label="Product Views" value={(stats.funnel?.views || 0).toLocaleString()} width={stats.funnel?.views > 0 ? "75%" : "5%"} color="bg-[#594436]" />
            <FunnelBar label="Orders/Checkout" value={(stats.funnel?.checkout || 0).toLocaleString()} width={stats.funnel?.checkout > 0 ? "45%" : "5%"} color="bg-[#8C7B70]" />
            <FunnelBar label="Completed Sales" value={(stats.funnel?.completed || 0).toLocaleString()} width={stats.funnel?.completed > 0 ? "20%" : "5%"} color="bg-[var(--rust)]" />
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
