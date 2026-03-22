"use client";
import React, { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { 
  BarChart3, 
  Users, 
  Store, 
  ShoppingBag, 
  Settings, 
  Bell, 
  Search, 
  ChevronRight, 
  LogOut, 
  ShieldCheck, 
  Menu, 
  X 
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

const sidebarData = [
  { group: "PLATFORM MONITORING", items: [
    { label: "Dashboard", icon: <BarChart3 className="w-5 h-5" />, path: "/admin/dashboard" },
    { label: "Recent Activity", icon: <ChevronRight className="w-5 h-5" />, path: "/admin/activity" },
  ]},
  { group: "USER MANAGEMENT", items: [
    { label: "Customers", icon: <Users className="w-5 h-5" />, path: "/admin/users" },
    { label: "Seller Verification", icon: <Store className="w-5 h-5" />, path: "/admin/sellers" },
  ]},
  { group: "CONTENT MODERATION", items: [
    { label: "Global Products", icon: <ShoppingBag className="w-5 h-5" />, path: "/admin/products" },
  ]},
  { group: "SYSTEM SETTINGS", items: [
    { label: "Revenue Targets", icon: <Settings className="w-5 h-5" />, path: "/admin/settings" },
  ]},
];

export default function AdminLayout({ children }) {
  const pathname = usePathname();
  const [mobileMenu, setMobileMenu] = useState(false);
  const [user, setUser] = useState(null);

  React.useEffect(() => {
    const storedUser = JSON.parse(localStorage.getItem("user") || "{}");
    const token = localStorage.getItem("token");

    // Strict Admin verification
    if (!token || storedUser.role !== 'admin') {
      console.warn("Unauthorized access attempt to Admin Panel. Redirecting...");
      localStorage.clear();
      window.location.href = "/login?error=admin_required";
      return;
    }

    setUser(storedUser);
  }, []);

  const handleLogout = () => {
    localStorage.clear();
    window.location.href = "/login";
  };

  return (
    <div className="flex h-screen bg-[#F7F3EE] overflow-hidden">
      {/* Sidebar Desktop */}
      <motion.aside 
        initial={{ x: -280 }}
        animate={{ x: 0 }}
        className="hidden lg:flex flex-col w-[280px] h-full bg-white border-r border-[var(--border)] overflow-y-auto"
      >
        <div className="p-10 flex flex-col h-full">
          <div className="mb-12">
            <Link href="/admin/dashboard" className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tighter">
              LUMBARONG
            </Link>
            <div className="flex items-center gap-1.5 mt-2 px-1 text-[var(--rust)] font-bold tracking-widest text-[10px]">
              <ShieldCheck className="w-3 h-3" /> ADMINISTRATION
            </div>
          </div>

          <nav className="flex-1 space-y-10">
            {sidebarData.map((group, idx) => (
              <div key={idx} className="space-y-4">
                <div className="text-[10px] font-bold text-[var(--muted)] opacity-60 tracking-widest uppercase px-3">
                  {group.group}
                </div>
                <div className="space-y-1.5">
                  {group.items.map((item, i) => {
                    const active = pathname === item.path;
                    return (
                      <Link 
                        key={i} 
                        href={item.path} 
                        className={`flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium ${active ? 'bg-[rgba(192,66,42,0.08)] text-[var(--rust)] border-l-4 border-[var(--rust)]' : 'text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)]'}`}
                      >
                        <span className={`transition-colors ${active ? 'text-[var(--rust)]' : 'text-[var(--muted)] group-hover:text-[var(--rust)]'}`}>
                          {item.icon}
                        </span>
                        {item.label}
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </nav>

          <div className="mt-10 pt-8 border-t border-[var(--border)]">
            <div className="flex items-center gap-3 px-2 mb-6">
              <div className="w-10 h-10 rounded-xl bg-[var(--bark)] flex items-center justify-center text-white font-serif text-lg font-bold shadow-lg">
                {user?.name ? user.name[0] : "A"}
              </div>
              <div>
                <div className="text-sm font-bold text-[var(--charcoal)]">{user?.name || "Administrator"}</div>
                <div className="text-[10px] text-[var(--muted)] font-bold uppercase tracking-widest leading-none">SYSTEM LEVEL</div>
              </div>
            </div>
            <button 
              onClick={handleLogout}
              className="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest"
            >
              <LogOut className="w-4 h-4" /> LOGOUT SESSION
            </button>
          </div>
        </div>
      </motion.aside>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col h-full relative overflow-hidden">
        {/* Top Sticky Header */}
        <header className="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-[var(--border)] h-[72px] flex items-center shrink-0">
          <div className="container mx-auto px-10 flex items-center justify-between">
            <div className="flex items-center gap-4 lg:flex-1">
              <button 
                onClick={() => setMobileMenu(true)} 
                className="lg:hidden p-2 hover:bg-[var(--cream)] rounded-lg transition-all"
              >
                <Menu className="w-5 h-5 text-[var(--muted)]" />
              </button>
              
              <div className="hidden lg:flex items-center bg-[var(--input-bg)] w-full max-w-lg rounded-xl px-4 py-2.5 border border-transparent focus-within:border-[var(--rust)] focus-within:bg-white transition-all">
                <Search className="w-4 h-4 text-[var(--muted)] mr-3" />
                <input 
                  type="text" 
                  placeholder="Global system search..." 
                  className="bg-transparent w-full text-sm outline-none text-[var(--charcoal)]"
                />
              </div>
            </div>

            <div className="flex items-center gap-4">
              <div className="hidden md:flex flex-col text-right">
                <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-wider">Server Status</div>
                <div className="text-xs font-bold text-green-600 flex items-center gap-1.5 justify-end">
                  <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" /> ONLINE
                </div>
              </div>
              <div className="w-[1px] h-8 bg-[var(--border)] hidden md:block mx-2" />
              <button className="relative w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--cream)] text-[var(--charcoal)] hover:text-[var(--rust)] transition-all">
                <Bell className="w-5 h-5" />
                <span className="absolute top-2.5 right-2.5 w-2 h-2 bg-[var(--rust)] rounded-full border-2 border-white" />
              </button>
              <div className="text-sm font-bold ml-2">Mar 19, 2026</div>
            </div>
          </div>
        </header>

        {/* Scrollable Content */}
        <main className="flex-1 overflow-y-auto p-10 custom-scrollbar animate-fade-up">
          <div className="max-w-[1400px] mx-auto">
            {children}
          </div>
        </main>
      </div>

      {/* Mobile Sidebar Overlay */}
      <AnimatePresence>
        {mobileMenu && (
          <>
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMobileMenu(false)}
              className="fixed inset-0 z-50 bg-[#1C1917]/40 backdrop-blur-sm lg:hidden"
            />
            <motion.aside 
              initial={{ x: -280 }}
              animate={{ x: 0 }}
              exit={{ x: -280 }}
              className="fixed left-0 top-0 bottom-0 z-50 w-[280px] bg-white lg:hidden overflow-y-auto shadow-2xl"
            >
              {/* Similar sidebar content for mobile */}
              <div className="p-8">
                <div className="flex items-center justify-between mb-10">
                  <div className="font-serif text-2xl font-bold">LUMBARONG</div>
                  <button onClick={() => setMobileMenu(false)} className="p-2"><X className="w-5 h-5" /></button>
                </div>
                {/* Repurpose sidebar data mapping here */}
              </div>
            </motion.aside>
          </>
        )}
      </AnimatePresence>
    </div>
  );
}
