"use client";
import React, { useState, useEffect } from "react";
import AdminLayout from "@/components/AdminLayout";
import { 
  DollarSign, 
  ShoppingBag, 
  Users, 
  Package, 
  ArrowUpRight, 
  ArrowDownRight, 
  Activity, 
  TrendingUp, 
  Database, 
  CloudRain, 
  UserPlus 
} from "lucide-react";
import { 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer, 
  PieChart, 
  Pie, 
  Cell, 
  LineChart, 
  Line, 
  AreaChart, 
  Area,
  Legend
} from "recharts";
import { motion } from "framer-motion";

const barData = [
  { name: 'Mon', revenue: 4200 },
  { name: 'Tue', revenue: 3800 },
  { name: 'Wed', revenue: 5100 },
  { name: 'Thu', revenue: 4600 },
  { name: 'Fri', revenue: 5900 },
  { name: 'Sat', revenue: 6400 },
  { name: 'Sun', revenue: 5200 },
];

const targetData = [
  { name: 'Filled', value: 78, color: '#C0422A' },
  { name: 'Empty', value: 22, color: '#f3f4f6' },
];

const hitRateData = [
  { name: 'Jan', hits: 1200 },
  { name: 'Feb', hits: 1500 },
  { name: 'Mar', hits: 1400 },
  { name: 'Apr', hits: 1900 },
  { name: 'May', hits: 2300 },
  { name: 'Jun', hits: 2100 },
];

const locationData = [
  { city: 'Manila', count: 420 },
  { city: 'Quezon City', count: 310 },
  { city: 'Lumban', count: 280 },
  { city: 'Makati', count: 190 },
  { city: 'Cebu City', count: 150 },
];

import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function AdminDashboard() {
  const [mounted, setMounted] = useState(false);
  const [stats, setStats] = useState({
    totalSales: "₱1,420,500",
    totalOrders: "8,420",
    activeCustomers: "12,500",
    liveProducts: "2,400"
  });

  const { socket } = useSocket();

  useEffect(() => {
    setMounted(true);
    
    // Initial Fetch
    const fetchStats = async () => {
      try {
        const response = await api.get("/admin/stats");
        setStats(response.data);
      } catch (err) {}
    };
    fetchStats();

    // Socket Setup
    if (socket) {
        socket.on("stats_update", (newData) => {
          setStats(prev => ({ ...prev, ...newData }));
        });
    }

    return () => {
        if (socket) {
            socket.off("stats_update");
        }
    };
  }, [socket]);

  const handleDownloadReport = () => {
    const csvContent = "data:text/csv;charset=utf-8,Metric,Value\n" 
      + Object.entries(stats).map(e => `${e[0]},${e[1]}`).join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `lumba_rong_report_${new Date().toLocaleDateString()}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
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
            <button className="px-5 py-2.5 bg-white border border-[var(--border)] rounded-xl text-xs font-bold uppercase tracking-widest hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all">
              Filter: Today
            </button>
            <button onClick={handleDownloadReport} className="px-5 py-2.5 bg-[var(--bark)] text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-[var(--rust)] transition-all">
              Download Report
            </button>
          </div>
        </div>

        {/* 4 Stat Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard label="Total Sales" value={stats.totalSales} change="+12.4%" icon={<DollarSign className="w-5 h-5 text-rust" />} color="rust" />
          <StatCard label="Total Orders" value={stats.totalOrders} change="+8.2%" icon={<ShoppingBag className="w-5 h-5 text-blue" />} color="blue" />
          <StatCard label="Active Customers" value={stats.activeCustomers} change="+15.5%" icon={<Users className="w-5 h-5 text-green" />} color="green" />
          <StatCard label="Live Products" value={stats.liveProducts} change="-2.1%" icon={<Package className="w-5 h-5 text-amber" />} color="amber" />
        </div>

        {/* Chart Grid */}
        <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
          {/* Main Earner Bar Chart */}
          <div className="xl:col-span-2 artisan-card min-h-[450px] flex flex-col">
            <div className="flex items-center justify-between mb-8">
              <div>
                <h3 className="text-lg font-bold text-[var(--charcoal)] mb-1">Earning Statistics</h3>
                <div className="text-xs text-[var(--muted)] tracking-wider">Revenue across current week cycle</div>
              </div>
              <Activity className="w-5 h-5 text-[var(--muted)]" />
            </div>
            
            <div className="flex-1 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={barData} margin={{ top: 20, right: 30, left: 10, bottom: 0 }}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5DDD5" />
                  <XAxis 
                    dataKey="name" 
                    axisLine={false} 
                    tickLine={false} 
                    tick={{ fontSize: 11, fill: '#8C7B70', fontWeight: '600', fontFamily: '"Playfair Display", Georgia, serif' }} 
                    dy={12}
                  />
                  <YAxis 
                    axisLine={false} 
                    tickLine={false} 
                    tick={{ fontSize: 11, fill: '#8C7B70', fontWeight: '600', fontFamily: '"Playfair Display", Georgia, serif' }}
                    tickFormatter={(val) => `₱${val/1000}k`}
                  />
                  <Tooltip 
                    cursor={{ fill: 'rgba(192,66,42,0.03)' }}
                    content={({ active, payload }) => {
                      if (active && payload && payload.length) {
                        return (
                          <div className="bg-[#1C1917] text-white p-3 rounded-xl shadow-2xl border border-white/10">
                            <div className="text-[10px] font-bold text-white/50 uppercase tracking-widest mb-1">{payload[0].payload.name}</div>
                            <div className="text-lg font-serif font-bold">₱{payload[0].value.toLocaleString()}</div>
                          </div>
                        );
                      }
                      return null;
                    }}
                  />
                  <Bar 
                    dataKey="revenue" 
                    fill="#C0422A" 
                    radius={[10, 10, 4, 4]} 
                    barSize={45} 
                    animationDuration={2000}
                  />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Sales Target Pie Chart */}
          <div className="artisan-card flex flex-col">
            <div className="mb-8">
              <h3 className="text-lg font-bold text-[var(--charcoal)] mb-1">Sales Target</h3>
              <div className="text-xs text-[var(--muted)] tracking-wider">Daily reach index</div>
            </div>

            <div className="flex-1 flex flex-col items-center justify-center relative">
              <div className="w-[200px] h-[200px]">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={targetData}
                      cx="50%"
                      cy="50%"
                      innerRadius={70}
                      outerRadius={95}
                      paddingAngle={5}
                      dataKey="value"
                      stroke="none"
                      animationDuration={1500}
                    >
                      {targetData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                  </PieChart>
                </ResponsiveContainer>
              </div>
              
              {/* Inner Text for Donut */}
              <div className="absolute inset-x-0 top-1/2 -translate-y-2 text-center pointer-events-none">
                <div className="text-3xl font-serif font-bold text-[var(--charcoal)] tracking-tighter">78%</div>
                <div className="text-[10px] text-[var(--muted)] font-bold tracking-widest uppercase">Target Meta</div>
              </div>

              <div className="mt-8 w-full space-y-3">
                <TargetRow label="Daily Goal" amount="₱45,000" color="rust" />
                <TargetRow label="Weekly Forecast" amount="₱320,000" color="sand" />
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 xl:grid-cols-2 gap-8">
          {/* User Hit Rate Line Chart */}
          <div className="artisan-card min-h-[400px]">
            <div className="flex items-center justify-between mb-8">
              <div>
                <h3 className="text-lg font-bold text-[var(--charcoal)] mb-1">User Hit Rate</h3>
                <div className="text-xs text-[var(--muted)] tracking-wider">Site engagement & session trends</div>
              </div>
              <CloudRain className="w-5 h-5 text-[var(--rust)]" />
            </div>

            <div className="h-[280px]">
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={hitRateData}>
                  <defs>
                    <linearGradient id="colorHits" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#C0422A" stopOpacity={0.2}/>
                      <stop offset="95%" stopColor="#C0422A" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="name" hide />
                  <Tooltip 
                    contentStyle={{ background: '#fff', borderRadius: '14px', border: '1px solid #E5DDD5', fontSize: '12px' }}
                    labelStyle={{ fontWeight: 'bold' }}
                  />
                  <Area 
                    type="monotone" 
                    dataKey="hits" 
                    stroke="#C0422A" 
                    strokeWidth={3}
                    fillOpacity={1} 
                    fill="url(#colorHits)" 
                  />
                </AreaChart>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Location Bar Grid */}
          <div className="artisan-card min-h-[400px] flex flex-col">
            <h3 className="text-lg font-bold text-[var(--charcoal)] mb-6 underline decoration-[var(--border)] decoration-4 underline-offset-8">Customers by Location</h3>
            <div className="space-y-6 flex-1 flex flex-col justify-center">
              {locationData.map((loc, i) => (
                <div key={i} className="space-y-2">
                  <div className="flex justify-between text-xs font-bold tracking-widest">
                    <span>{loc.city.toUpperCase()}</span>
                    <span className="text-[var(--muted)] opacity-60">{loc.count}</span>
                  </div>
                  <div className="h-2 w-full bg-[var(--cream)] rounded-full overflow-hidden">
                    <motion.div 
                      initial={{ width: 0 }}
                      whileInView={{ width: `${(loc.count / 450) * 100}%` }}
                      transition={{ duration: 1, ease: "easeOut", delay: i * 0.1 }}
                      className="h-full bg-[var(--rust)] rounded-full"
                    />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Recent Activity Feed */}
        <div className="artisan-card">
          <h3 className="text-xl font-serif font-bold mb-8">Recent Platforms <span className="text-[var(--rust)] italic">Activity</span></h3>
          <div className="space-y-6">
            <ActivityItem 
              icon={<UserPlus className="w-4 h-4 text-green-600" />} 
              bg="bg-green-100" 
              title="New Seller Registered" 
              desc="A new user applied for seller verification" 
              time="2 min ago" 
            />
            <ActivityItem 
              icon={<Database className="w-4 h-4 text-blue-600" />} 
              bg="bg-blue-100" 
              title="Global Stats Refreshed" 
              desc="Platform-wide synchronization completed successfully" 
              time="12 min ago" 
            />
            <ActivityItem 
              icon={<TrendingUp className="w-4 h-4 text-rust" />} 
              bg="bg-[rgba(192,66,42,0.1)]" 
              title="Daily Target Met" 
              desc="System reached 100% of daily revenue goal" 
              time="1 hour ago" 
            />
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}

function StatCard({ label, value, change, icon, color }) {
  const isUp = change.startsWith('+');
  return (
    <motion.div 
      whileHover={{ y: -5 }}
      className="artisan-card group cursor-pointer"
    >
      <div className="flex items-center justify-between mb-5">
        <div className={`w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 ${color === 'rust' ? 'bg-[rgba(192,66,42,0.1)]' : 'bg-[var(--cream)] group-hover:bg-white'}`}>
          {icon}
        </div>
        <div className={`text-[10px] font-bold px-2 py-1 rounded-full ${isUp ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50'}`}>
          {isUp ? <ArrowUpRight className="inline w-3 h-3 mr-0.5" /> : <ArrowDownRight className="inline w-3 h-3 mr-0.5" />}
          {change}
        </div>
      </div>
      <div className="text-2xl font-serif font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors mb-1">
        {value}
      </div>
      <div className="text-[10px] font-bold text-[var(--muted)] opacity-60 uppercase tracking-widest">
        {label}
      </div>
    </motion.div>
  );
}

function TargetRow({ label, amount, color }) {
  return (
    <div className="flex items-center justify-between p-3.5 bg-[var(--input-bg)] rounded-xl border border-[var(--border)]">
      <div className="flex items-center gap-3">
        <div className={`w-2.5 h-2.5 rounded-full ${color === 'rust' ? 'bg-[var(--rust)]' : 'bg-[var(--sand)]'}`} />
        <span className="text-xs font-bold text-[var(--muted)] opacity-60 tracking-wider font-sans uppercase">{label}</span>
      </div>
      <span className="font-serif font-bold text-[var(--charcoal)]">{amount}</span>
    </div>
  );
}

function ActivityItem({ icon, bg, title, desc, time }) {
  return (
    <div className="flex items-start gap-5 group">
      <div className={`w-10 h-10 rounded-xl shrink-0 flex items-center justify-center ${bg} shadow-sm group-hover:scale-110 transition-transform`}>
        {icon}
      </div>
      <div className="flex-1 pb-6 border-b border-[var(--border)] group-last:border-none">
        <div className="flex justify-between mb-1">
          <h4 className="text-sm font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">{title}</h4>
          <span className="text-[10px] font-bold text-[var(--muted)] opacity-50 uppercase tracking-wider">{time}</span>
        </div>
        <p className="text-sm text-[var(--muted)] leading-relaxed">{desc}</p>
      </div>
    </div>
  );
}
