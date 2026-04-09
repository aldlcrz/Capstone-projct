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
import { api, clearSession } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import {
  formatNotificationTime,
  getNotificationHref,
  getNotificationTypeKey,
  getNotificationTypeLabel,
  normalizeNotification,
} from "@/lib/notifications";
import { Loader2 } from "lucide-react";

const sidebarData = [
  { group: "SYSTEM STATUS", items: [
    { label: "Dashboard", icon: <BarChart3 className="w-5 h-5" />, path: "/admin/dashboard" },
    { label: "Recent Updates", icon: <ChevronRight className="w-5 h-5" />, path: "/admin/activity" },
  ]},
  { group: "USER MANAGER", items: [
    { label: "Customers", icon: <Users className="w-5 h-5" />, path: "/admin/users" },
    { label: "Seller Requests", icon: <Store className="w-5 h-5" />, path: "/admin/sellers" },
  ]},
  { group: "PRODUCT CONTROL", items: [
    { label: "All Products", icon: <ShoppingBag className="w-5 h-5" />, path: "/admin/products" },
  ]},
  { group: "APP SETTINGS", items: [
    { label: "Sales Goals", icon: <Settings className="w-5 h-5" />, path: "/admin/settings" },
  ]},
];

const mobileNavItems = [
  { label: "Dashboard", icon: <BarChart3 />, path: "/admin/dashboard" },
  { label: "Customers", icon: <Users />, path: "/admin/users" },
  { label: "Products", icon: <ShoppingBag />, path: "/admin/products" },
  { label: "Settings", icon: <Settings />, path: "/admin/settings" },
];

import MobileBottomNav from "./MobileBottomNav";

export default function AdminLayout({ children }) {
  const pathname = usePathname();
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);

  React.useEffect(() => {
    const checkAuth = () => {
      const storedUser = JSON.parse(localStorage.getItem("user") || "null");
      const token = localStorage.getItem("token");

      if (!token || !storedUser || storedUser.role !== 'admin') {
        // If we are already on the login page or similar, don't redirect again
        if (!window.location.pathname.includes("/login")) {
          console.warn("Unauthorized or session expired. Redirecting...");
          clearSession();
          window.location.href = "/login?error=admin_required";
        }
        return;
      }

      setUser(storedUser);
      setLoading(false);
    };

    checkAuth();

    // Listen for storage changes (e.g. logout in another tab)
    window.addEventListener('storage', checkAuth);
    return () => window.removeEventListener('storage', checkAuth);
  }, []);

  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [notificationsLoading, setNotificationsLoading] = useState(false);

  const { socket } = useSocket();

  const fetchNotifications = React.useCallback(async ({ silent = false } = {}) => {
    const token = localStorage.getItem("token");
    if (!token) return;
    if (!silent) setNotificationsLoading(true);
    try {
      const res = await api.get("/notifications");
      setNotifications(Array.isArray(res.data) ? res.data.map(normalizeNotification) : []);
    } catch (error) {
    } finally {
      if (!silent) setNotificationsLoading(false);
    }
  }, []);

  React.useEffect(() => {
    fetchNotifications({ silent: true });
    if (socket) {
      socket.on('new_notification', (incoming) => {
        setNotifications(prev => [normalizeNotification(incoming), ...prev]);
      });
    }
    return () => { if (socket) socket.off('new_notification'); };
  }, [socket, fetchNotifications]);

  const unreadCount = notifications.filter(n => !n.read).length;

  const handleNotificationClick = async (notification) => {
    if (!notification.read) {
      try { await api.put(`/notifications/${notification.id}/read`, {}); } catch (e) {}
    }
    setNotificationsOpen(false);
  };

  const handleLogout = () => {
    clearSession();
    window.location.href = "/";
  };

  return (
    <div data-panel="admin" className="flex h-screen bg-[#F7F3EE] overflow-hidden">
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
              <ShieldCheck className="w-3 h-3" /> CONTROL PANEL
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
              <div className="w-10 h-10 rounded-xl bg-[var(--bark)] border-2 border-white shadow-md flex items-center justify-center text-white font-serif text-lg font-bold overflow-hidden transition-transform active:scale-95">
                {user?.profilePhoto ? (
                  <img 
                    src={user.profilePhoto} 
                    alt={user.name} 
                    className="w-full h-full object-cover"
                    onError={(e) => {
                      e.target.onerror = null;
                      e.target.src = `https://ui-avatars.com/api/?name=${user?.name || "Admin"}&background=EBE5DE&color=2A2A2A`;
                    }}
                  />
                ) : (
                  user?.name ? user.name[0] : "A"
                )}
              </div>
              <div>
                <div className="text-sm font-bold text-[var(--charcoal)]">{user?.name || "Administrator"}</div>
                <div className="text-[10px] text-[var(--muted)] font-bold uppercase tracking-widest leading-none">SYSTEM LEVEL</div>
              </div>
            </div>
            <button 
              onClick={handleLogout}
              className="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase"
            >
              <LogOut className="w-4 h-4" /> LOG OUT
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
              {/* Search logic disabled based on UI polish request */}
            </div>

            <div className="flex items-center gap-4">
              <div className="hidden md:flex flex-col text-right">
                <div className="text-[10px] font-bold text-[var(--muted)] uppercase tracking-wider">Server Status</div>
                <div className="text-xs font-bold text-green-600 flex items-center gap-1.5 justify-end">
                  <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" /> ONLINE
                </div>
              </div>
              <div className="w-[1px] h-8 bg-[var(--border)] hidden md:block mx-2" />
              <div className="relative">
                <button 
                  onClick={() => { setNotificationsOpen(!notificationsOpen); if(!notificationsOpen) fetchNotifications(); }}
                  className="relative w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--cream)] text-[var(--charcoal)] hover:text-[var(--rust)] transition-all"
                >
                  <Bell className="w-5 h-5" />
                  {unreadCount > 0 && <span className="absolute top-2.5 right-2.5 w-2 h-2 bg-[var(--rust)] rounded-full border-2 border-white" />}
                </button>
                
                <AnimatePresence>
                  {notificationsOpen && (
                    <motion.div
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: 10 }}
                      className="absolute right-0 top-full mt-4 w-80 bg-white rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden z-50"
                    >
                      <div className="p-4 border-b border-[var(--border)] font-bold text-xs uppercase tracking-widest text-[var(--rust)] bg-gray-50">Notifications</div>
                      <div className="max-h-96 overflow-y-auto">
                        {notificationsLoading ? (
                          <div className="p-8 flex justify-center"><Loader2 className="w-6 h-6 animate-spin text-[var(--rust)]" /></div>
                        ) : notifications.length === 0 ? (
                          <div className="p-8 text-center text-[10px] uppercase font-bold text-[var(--muted)]">No alerts detected</div>
                        ) : (
                          notifications.slice(0, 5).map(n => (
                            <button key={n.id} onClick={() => handleNotificationClick(n)} className={`w-full p-4 text-left border-b border-[var(--border)] hover:bg-gray-50 flex flex-col gap-1 ${n.read ? 'opacity-60' : 'bg-red-50/20'}`}>
                              <div className="text-[10px] font-bold text-[var(--rust)] uppercase">{n.title}</div>
                              <div className="text-[11px] font-medium text-[var(--charcoal)] line-clamp-2">{n.message}</div>
                              <div className="text-[8px] text-[var(--muted)] font-bold">{formatNotificationTime(n.createdAt)}</div>
                            </button>
                          ))
                        )}
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
              <div className="text-sm font-bold ml-2">
                {new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
              </div>
            </div>
          </div>
        </header>

        {/* Scrollable Content */}
        <main className="flex-1 overflow-y-auto p-10 custom-scrollbar animate-fade-up">
          <div className="max-w-[1400px] mx-auto">
            {loading ? (
              <div className="flex items-center justify-center min-h-[400px]">
                <Loader2 className="w-10 h-10 animate-spin text-[var(--rust)]" />
              </div>
            ) : (
              children
            )}
          </div>
        </main>

        <MobileBottomNav items={mobileNavItems} />
      </div>

    </div>
  );
}
