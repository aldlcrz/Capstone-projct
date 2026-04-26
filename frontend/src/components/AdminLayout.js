"use client";
import React, { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
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
  X,
  Plus,
  LayoutDashboard,
  ShieldAlert
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, clearSession, ACCOUNT_STATUS_EVENT, getStoredUserForRole, getTokenForRole, SESSION_SYNC_EVENT } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import ConfirmationModal from "./ConfirmationModal";
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
  ]},
  { group: "USER REGISTRY", items: [
    { label: "Users", icon: <Users className="w-5 h-5" />, path: "/admin/users" },
    { label: "Shop Requests", icon: <Store className="w-5 h-5" />, path: "/admin/sellers" },
  ]},
  { group: "SYSTEM GOVERNANCE", items: [
    { label: "Reports", icon: <ShieldAlert className="w-5 h-5" />, path: "/admin/reports" },
  ]},
  { group: "PRODUCT CONTROL", items: [
    { label: "All Products", icon: <ShoppingBag className="w-5 h-5" />, path: "/admin/products" },
  ]},
  { group: "APP SETTINGS", items: [
    { label: "Settings", icon: <Settings className="w-5 h-5" />, path: "/admin/settings" },
  ] },
];

const mobileNavItems = [
  { label: "Dashboard", icon: <BarChart3 />, path: "/admin/dashboard" },
  { label: "Users", icon: <Users />, path: "/admin/users" },
  { label: "Products", icon: <ShoppingBag />, path: "/admin/products" },
  { label: "Sellers", icon: <Store />, path: "/admin/sellers" },
];

import MobileBottomNav from "./MobileBottomNav";

export default function AdminLayout({ children }) {
  const pathname = usePathname();
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  React.useEffect(() => {
    const checkAuth = () => {
      // Only use role-specific keys — no generic fallback to prevent cross-tab contamination
      const storedUser = getStoredUserForRole("admin");
      const token = getTokenForRole("admin");

      if (!token || !storedUser || storedUser.role !== 'admin') {
        if (!window.location.pathname.includes("/login")) {
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
    window.addEventListener(SESSION_SYNC_EVENT, checkAuth);
    return () => {
      window.removeEventListener('storage', checkAuth);
      window.removeEventListener(SESSION_SYNC_EVENT, checkAuth);
    };
  }, []);

  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [notificationsLoading, setNotificationsLoading] = useState(false);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  const [dismissedNotificationIds, setDismissedNotificationIds] = useState([]);
  const [violationAlert, setViolationAlert] = useState(null);

  const { socket, unreadCount, isDuplicateEvent, setUnreadCount } = useSocket();

  const fetchNotifications = React.useCallback(async ({ silent = false } = {}) => {
    const token = getTokenForRole("admin");
    if (!token) return;
    if (!silent) setNotificationsLoading(true);
    try {
      const res = await api.get("/notifications", { params: { role: "admin" } });
      const nextNotifications = Array.isArray(res.data) ? res.data.map(normalizeNotification) : [];
      setNotifications(nextNotifications);
      setUnreadCount(nextNotifications.filter((notification) => !notification.read).length);
    } catch (error) {
      const msg = error.response?.data?.message || error.message || "Unknown error";
      const status = error.response?.status;
      const data = error.response?.data;

      if (status === 401 && (msg.toLowerCase().includes('violation') || msg.toLowerCase().includes('user not found') || msg.toLowerCase().includes('account'))) {
        setViolationAlert({
          status: data?.status || 'suspended',
          reason: data?.reason || msg
        });
        return;
      } else if (status === 401 && msg.toLowerCase().includes('token')) {
        clearSession("admin");
        window.location.href = "/login?error=session_invalid";
      }
    } finally {
      if (!silent) setNotificationsLoading(false);
    }
  }, [setUnreadCount]);

  React.useEffect(() => {
    fetchNotifications({ silent: true });
    if (socket) {
      socket.on('new_notification', (incoming) => {
        if (isDuplicateEvent(incoming.id)) return;
        if (incoming.targetRole !== 'admin') return;
        const nextNotification = normalizeNotification(incoming);
        setNotifications((prev) => [nextNotification, ...prev.filter((item) => item.id !== nextNotification.id)]);
      });
    }
    return () => { if (socket) socket.off('new_notification'); };
  }, [socket, fetchNotifications, isDuplicateEvent]);

  React.useEffect(() => {
    const handleAccountStatus = (event) => {
      const detail = event?.detail;
      if (!detail || (detail.role && detail.role !== "admin")) return;

      setViolationAlert({
        status: detail.status || 'suspended',
        reason: detail.reason || detail.message || 'Your administrative access has been revoked.'
      });
    };

    window.addEventListener(ACCOUNT_STATUS_EVENT, handleAccountStatus);
    return () => window.removeEventListener(ACCOUNT_STATUS_EVENT, handleAccountStatus);
  }, []);

  // unreadCount is now provided by useSocket()

  const handleNotificationClick = async (notification) => {
    if (!notification.read) {
      setNotifications(prev => prev.map(n => n.id === notification.id ? { ...n, read: true } : n));
      try {
        await api.put(`/notifications/${notification.id}/read`, {});
      } catch (e) {
        setNotifications(prev => prev.map(n => n.id === notification.id ? { ...n, read: false } : n));
      }
    }
    setNotificationsOpen(false);
    if (notification.link) {
      router.push(notification.link);
    }
  };

  const markAllAsRead = async () => {
    try {
      await api.put('/notifications/read-all', {}, { params: { role: 'admin' } });
      setNotifications(prev => prev.map(n => ({ ...n, read: true })));
    } catch (e) {
      console.error('Failed to mark all as read', e);
    }
  };

  const handleLogout = () => {
    setShowLogoutConfirm(true);
  };

  const confirmLogout = () => {
    clearSession('admin');
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
            <Link href="/admin/dashboard" className="font-serif text-lg font-bold text-[var(--charcoal)] tracking-tighter">
              LUMBARONG
            </Link>
            <div className="flex items-center gap-1.5 mt-2 px-1 text-[var(--rust)] font-bold tracking-widest text-[10px]">
              <ShieldCheck className="w-3 h-3" /> CONTROL PANEL
            </div>
          </div>

          <nav className="flex-1 space-y-10">
            {sidebarData.filter(g => g.items.length > 0).map((group, idx) => (
              <div key={idx} className="space-y-4">
                <div className="text-[9px] font-bold text-[var(--muted)] opacity-60 tracking-widest uppercase px-3">
                  {group.group}
                </div>
                <div className="space-y-1.5">
                  {group.items.map((item, i) => {
                    const active = pathname === item.path || (item.path !== '/admin/dashboard' && pathname.startsWith(item.path));
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
          <div className="container mx-auto px-5 lg:px-10 flex items-center justify-between">
            {/* Mobile Header: Logo (Left) */}
            <div className="flex items-center lg:hidden">
              <Link href="/admin/dashboard" className="font-serif text-sm font-bold tracking-tighter text-[var(--charcoal)]">
                LUMBARONG
              </Link>
            </div>

            <div className="hidden lg:flex items-center gap-4 lg:flex-1">
              {/* Search logic disabled based on UI polish request */}
            </div>

            <div className="flex items-center gap-4">

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
                      <div className="p-4 border-b border-[var(--border)] bg-gray-50 flex items-center justify-between">
                        <div className="font-bold text-xs uppercase tracking-widest text-[var(--rust)]">Notifications</div>
                        <button onClick={markAllAsRead} className="text-[9px] font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors">
                          Mark all read
                        </button>
                      </div>
                      <div className="max-h-96 overflow-y-auto">
                        {notificationsLoading ? (
                          <div className="p-8 flex justify-center"><Loader2 className="w-6 h-6 animate-spin text-[var(--rust)]" /></div>
                        ) : notifications.filter(n => !dismissedNotificationIds.includes(n.id)).length === 0 ? (
                          <div className="p-8 text-center text-[10px] uppercase font-bold text-[var(--muted)]">No alerts detected</div>
                        ) : (
                          notifications
                            .filter(n => !dismissedNotificationIds.includes(n.id))
                            .slice(0, 10)
                            .map(n => (
                             <div key={n.id} className="relative group">
                               <button 
                                onClick={() => handleNotificationClick(n)} 
                                className={`w-full p-4 text-left border-b border-[var(--border)] hover:bg-gray-50 flex flex-col gap-1 ${n.read ? 'opacity-60' : 'bg-red-50/20'}`}
                               >
                                 <div className="text-[10px] font-bold text-[var(--rust)] uppercase">{n.title}</div>
                                 <div className="text-[11px] font-medium text-[var(--charcoal)] line-clamp-2">{n.message}</div>
                                 <div className="text-[8px] text-[var(--muted)] font-bold">{formatNotificationTime(n.createdAt)}</div>
                               </button>
                               <button
                                 onClick={(e) => {
                                   e.stopPropagation();
                                   setDismissedNotificationIds(prev => [...prev, n.id]);
                                 }}
                                 className="absolute right-2 top-2 p-1 text-[var(--muted)] hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                               >
                                 <X className="w-3 h-3" />
                               </button>
                             </div>
                           ))
                        )}
                      </div>
                      <div className="p-3 border-t border-[var(--border)] bg-gray-50 text-center">
                        <Link href="/admin/notifications" onClick={() => setNotificationsOpen(false)} className="text-[10px] font-bold uppercase tracking-widest text-[var(--rust)] hover:underline">
                          View all notifications
                        </Link>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>
              <div className="hidden sm:block text-sm font-bold ml-2">
                {new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
              </div>

              {/* Mobile Actions (Settings + Logout) */}
              <Link href="/admin/settings" className="lg:hidden shrink-0 w-10 h-10 rounded-xl bg-[var(--bark)] border-2 border-white shadow-md flex items-center justify-center text-white overflow-hidden transition-transform active:scale-95">
                <Settings className="w-5 h-5 text-white" />
              </Link>
              
              <button
                onClick={handleLogout}
                className="lg:hidden ml-1 flex items-center justify-center w-10 h-10 bg-red-50 text-red-600 rounded-xl transition-colors hover:bg-red-100"
                aria-label="Logout"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          </div>
        </header>

        {/* Scrollable Content */}
        <main className="flex-1 overflow-y-auto p-5 lg:p-10 pb-[135px] lg:pb-10 custom-scrollbar animate-fade-up">
          <div className="max-w-[1200px] mx-auto">
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

        {/* Mobile Bottom Nav handles navigation */}

      </div>

      <ConfirmationModal
        isOpen={showLogoutConfirm}
        onClose={() => setShowLogoutConfirm(false)}
        onConfirm={confirmLogout}
        title="Sign Out"
        message="Are you sure you want to log out?"
        confirmText="Yes"
        cancelText="Cancel"
        type="danger"
      />
      <AnimatePresence>
        {violationAlert && (
          <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-[var(--charcoal)]/80 backdrop-blur-xl">
             <motion.div 
               initial={{ opacity: 0, scale: 0.9, y: 20 }}
               animate={{ opacity: 1, scale: 1, y: 0 }}
               className="w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-red-100"
             >
                <div className="p-8 text-center space-y-6">
                   <div className="w-20 h-20 bg-red-50 text-red-600 rounded-3xl flex items-center justify-center mx-auto ring-8 ring-red-50/50">
                      <ShieldAlert className="w-10 h-10" />
                   </div>
                   <div className="space-y-2">
                      <h2 className="text-2xl font-serif font-black text-[var(--charcoal)] uppercase tracking-tighter">
                        Admin <span className="text-[var(--rust)] italic lowercase">Suspended</span>
                      </h2>
                      <p className="text-[10px] font-black text-red-600 uppercase tracking-widest opacity-50 italic">System Governance Action</p>
                   </div>
                   
                   <div className="bg-red-50/50 p-6 rounded-2xl border border-red-100/50 relative overflow-hidden">
                      <div className="absolute top-0 left-0 w-1 h-full bg-red-600" />
                      <p className="text-xs font-bold text-red-900 leading-relaxed italic text-left">
                        &ldquo;{violationAlert.reason || 'Your administrative access has been revoked.'}&rdquo;
                      </p>
                   </div>

                   <p className="text-[9px] text-[var(--muted)] font-bold uppercase tracking-widest leading-tight px-4">
                     You will be automatically logged out after acknowledging this notice.
                   </p>

                   <button 
                     onClick={() => {
                        clearSession("admin");
                        window.location.href = "/login?error=account_suspended";
                     }}
                     className="w-full py-4 bg-[var(--charcoal)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-red-600 transition-all shadow-xl active:scale-[0.98]"
                   >
                     Acknowledge & Exit
                   </button>
                </div>
                <div className="h-2 bg-red-600 w-full animate-[shimmer_2s_infinite]" style={{ backgroundSize: '200% 100%' }} />
             </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
