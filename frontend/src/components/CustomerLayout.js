"use client";
import React, { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { 
  Home, 
  ShoppingBag, 
  MessageCircle, 
  Bell, 
  User, 
  Search, 
  ShoppingCart, 
  LogOut, 
  Menu, 
  X,
  History,
  MapPin,
  Package,
  ChevronRight,
  Loader2
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import {
  formatNotificationTime,
  getNotificationHref,
  getNotificationTypeKey,
  getNotificationTypeLabel,
  normalizeNotification,
} from "@/lib/notifications";
import MobileBottomNav from "./MobileBottomNav";

const sidebarData = [
  { group: "MARKETPLACE", items: [
    { label: "Heritage Shop", icon: <Home className="w-5 h-5" />, path: "/home" },
  ]},
  { group: "ACCOUNT & ORDERS", items: [
    { label: "Orders", icon: <History className="w-5 h-5" />, path: "/orders" },
    { label: "My Messages", icon: <MessageCircle className="w-5 h-5" />, path: "/messages" },
    { label: "Addressbook", icon: <MapPin className="w-5 h-5" />, path: "/addresses" },
  ]},
  { group: "PREFERENCES", items: [
    { label: "Profile Settings", icon: <User className="w-5 h-5" />, path: "/profile" },
  ]},
];

const mobileNavItems = [
  { label: "Home", icon: <Home />, path: "/home" },
  { label: "Messages", icon: <MessageCircle />, path: "/messages" },
  { label: "Orders", icon: <History />, path: "/orders" },
  { label: "Profile", icon: <User />, path: "/profile" },
];

export default function CustomerLayout({ children }) {
  const pathname = usePathname();
  const router = useRouter();
  const [mobileMenu, setMobileMenu] = useState(false);
  const [showMobileSearch, setShowMobileSearch] = useState(false);
  const [user, setUser] = useState(null);
  const [cartCount, setCartCount] = useState(0);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [notificationsLoading, setNotificationsLoading] = useState(false);
  const [activeNotificationId, setActiveNotificationId] = useState(null);
  const notificationPanelRef = React.useRef(null);

  const fetchNotifications = React.useCallback(async ({ silent = false } = {}) => {
    const token = localStorage.getItem("token");
    if (!token || token === "null" || token === "undefined") {
      setNotifications([]);
      return;
    }

    if (!silent) setNotificationsLoading(true);
    try {
      const res = await api.get("/notifications");
      const nextNotifications = Array.isArray(res.data) ? res.data.map(normalizeNotification) : [];
      setNotifications(nextNotifications);
    } catch (error) {
      const msg = error.response?.data?.message || error.message || "Unknown error";
      console.error(`Failed to fetch notifications (${error.response?.status || 'network'}):`, msg);
    } finally {
      if (!silent) setNotificationsLoading(false);
    }
  }, []);

  const { socket } = useSocket();

  React.useEffect(() => {
    let storedUser = null;
    try {
      storedUser = JSON.parse(localStorage.getItem("user") || "null");
    } catch (error) {}
    setUser(storedUser);

    const updateCartCount = () => {
      const cart = JSON.parse(localStorage.getItem("cart") || "[]");
      setCartCount(cart.reduce((sum, item) => sum + item.quantity, 0));
    };

    updateCartCount();
    fetchNotifications({ silent: true });

    if (socket && storedUser?.id) {
      socket.on('new_notification', (incoming) => {
        const nextNotification = normalizeNotification(incoming);
        setNotifications((prev) => [nextNotification, ...prev.filter((item) => item.id !== nextNotification.id)]);
      });
      socket.on('notification_count_update', () => {
        fetchNotifications({ silent: true });
      });
    }

    window.addEventListener('storage', updateCartCount);
    const interval = setInterval(updateCartCount, 1000); // Poll for changes
    return () => {
        window.removeEventListener('storage', updateCartCount);
        clearInterval(interval);
        if (socket) {
          socket.off('new_notification');
          socket.off('notification_count_update');
        }
    };
  }, [fetchNotifications, socket]);

  React.useEffect(() => {
    setNotificationsOpen(false);
  }, [pathname]);

  React.useEffect(() => {
    if (!notificationsOpen) return;

    const handleOutsideClick = (event) => {
      if (notificationPanelRef.current && !notificationPanelRef.current.contains(event.target)) {
        setNotificationsOpen(false);
      }
    };

    document.addEventListener("mousedown", handleOutsideClick);
    return () => document.removeEventListener("mousedown", handleOutsideClick);
  }, [notificationsOpen]);

  const handleLogout = () => {
    localStorage.clear();
    window.location.href = "/login";
  };

  const unreadCount = notifications.filter((notification) => !notification.read).length;

  const toggleNotifications = async () => {
    const nextOpen = !notificationsOpen;
    setNotificationsOpen(nextOpen);
    if (nextOpen) {
      await fetchNotifications();
    }
  };

  const handleNotificationClick = async (notification) => {
    const href = getNotificationHref(notification);
    const token = localStorage.getItem("token");

    if (!notification.read && token) {
      setActiveNotificationId(notification.id);
      setNotifications((prev) =>
        prev.map((item) => (item.id === notification.id ? { ...item, read: true } : item))
      );

      try {
        await api.put(
          `/notifications/${notification.id}/read`,
          {}
        );
      } catch (error) {
        console.error("Failed to mark notification as read", error.response?.data || error.message);
      } finally {
        setActiveNotificationId(null);
      }
    }

    setNotificationsOpen(false);
    if (/^https?:\/\//i.test(href)) {
      window.location.href = href;
      return;
    }

    router.push(href);
  };

  const renderNotificationIcon = (notification) => {
    switch (getNotificationTypeKey(notification)) {
      case "message":
        return (
          <div className="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 border border-[var(--border)] bg-[var(--cream)] text-[var(--charcoal)]">
            <MessageCircle className="w-5 h-5" />
          </div>
        );
      case "order":
        return (
          <div className="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 border border-red-100 bg-red-50 text-[var(--rust)]">
            <Package className="w-5 h-5" />
          </div>
        );
      default:
        return (
          <div className="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 border border-[var(--border)] bg-white text-[var(--muted)]">
            <Bell className="w-5 h-5" />
          </div>
        );
    }
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
            <Link href="/home" className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tighter">
              LUMBARONG
            </Link>
            <div className="flex items-center gap-1.5 mt-2 px-1 text-[var(--rust)] font-bold tracking-widest text-[10px]">
              <ShoppingBag className="w-3 h-3" /> CUSTOMER SIDE
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
            <button 
              onClick={handleLogout}
              className="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase"
            >
              <LogOut className="w-4 h-4" /> Sign Out
            </button>
          </div>
        </div>
      </motion.aside>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col h-full relative overflow-hidden">
        {/* Top Sticky Header */}
        <header className="sticky top-0 z-40 bg-white border-b border-[var(--border)] h-[72px] flex items-center shrink-0">
          <div className="container mx-auto px-4 lg:px-10 flex items-center justify-between">
            <div className="flex items-center gap-4 lg:flex-1">
              <button onClick={() => setMobileMenu(true)} className="lg:hidden p-2 hover:bg-[var(--cream)] rounded-lg">
                <Menu className="w-5 h-5 text-[var(--muted)]" />
              </button>
              
              {/* Desktop Search */}
              <div className="hidden lg:flex items-center bg-[var(--input-bg)] w-full max-w-lg rounded-xl px-4 py-2.5 border border-transparent focus-within:border-[var(--rust)] focus-within:bg-white transition-all">
                <Search className="w-4 h-4 text-[var(--muted)] mr-3" />
                <input type="text" placeholder="Search for hand embroidered barongs..." className="bg-transparent w-full text-sm outline-none" />
              </div>

              {/* Mobile Search Toggle */}
              <button 
                onClick={() => setShowMobileSearch(true)}
                className="lg:hidden p-2 hover:bg-[var(--cream)] rounded-lg text-[var(--muted)]"
              >
                <Search className="w-5 h-5" />
              </button>
            </div>

            {/* Centered Logo on Mobile */}
            <div className="absolute left-1/2 -translate-x-1/2 lg:static lg:translate-x-0 lg:hidden xl:block">
              <Link href="/home" className="font-serif text-xl font-bold text-[var(--charcoal)] tracking-tighter">
                LUMBARONG
              </Link>
            </div>

            <div className="flex items-center gap-2 md:gap-6">
              <div className="relative" ref={notificationPanelRef}>
                <button
                  onClick={toggleNotifications}
                  className="relative w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--cream)] text-[var(--charcoal)] hover:text-[var(--rust)] transition-colors"
                >
                  <Bell className="w-5 h-5" />
                  {unreadCount > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 bg-[var(--rust)] text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                      {unreadCount > 9 ? "9+" : unreadCount}
                    </span>
                  )}
                </button>

                <AnimatePresence>
                  {notificationsOpen && (
                    <motion.div
                      initial={{ opacity: 0, y: -12, scale: 0.98 }}
                      animate={{ opacity: 1, y: 0, scale: 1 }}
                      exit={{ opacity: 0, y: -12, scale: 0.98 }}
                      transition={{ duration: 0.18 }}
                      className="absolute right-0 top-[calc(100%+16px)] z-50 w-[360px] max-w-[calc(100vw-2rem)] rounded-[2rem] border border-[var(--border)] bg-white shadow-[0_24px_80px_rgba(0,0,0,0.16)] overflow-hidden"
                    >
                      <div className="px-6 py-5 border-b border-[var(--border)] bg-[var(--cream)]/60">
                        <div className="flex items-center justify-between gap-4">
                          <div>
                            <div className="text-[10px] font-bold uppercase tracking-[0.25em] text-[var(--rust)]">Notifications</div>
                            <div className="text-sm font-semibold text-[var(--charcoal)]">
                              {unreadCount} unread {unreadCount === 1 ? "alert" : "alerts"}
                            </div>
                          </div>
                          <Link
                            href="/notifications"
                            onClick={() => setNotificationsOpen(false)}
                            className="text-[10px] font-bold uppercase tracking-[0.2em] text-[var(--muted)] hover:text-[var(--rust)]"
                          >
                            View All
                          </Link>
                        </div>
                      </div>

                      <div className="max-h-[26rem] overflow-y-auto">
                        {notificationsLoading ? (
                          <div className="px-6 py-12 flex flex-col items-center gap-3 text-[var(--muted)]">
                            <Loader2 className="w-6 h-6 animate-spin" />
                            <span className="text-[11px] font-bold uppercase tracking-[0.2em]">Loading Alerts</span>
                          </div>
                        ) : notifications.length === 0 ? (
                          <div className="px-6 py-12 text-center space-y-3 text-[var(--muted)]">
                            <Bell className="w-10 h-10 mx-auto opacity-20" />
                            <div className="text-sm font-semibold text-[var(--charcoal)]">No notifications yet</div>
                            <p className="text-xs italic">Order updates and message alerts will show up here.</p>
                          </div>
                        ) : (
                          notifications.slice(0, 6).map((notification) => (
                            <button
                              key={notification.id}
                              onClick={() => handleNotificationClick(notification)}
                              className={`w-full px-6 py-4 text-left flex items-start gap-4 border-b border-[var(--border)] transition-colors hover:bg-[var(--cream)]/60 ${
                                notification.read ? "bg-white" : "bg-[rgba(192,66,42,0.04)]"
                              }`}
                            >
                              {renderNotificationIcon(notification)}
                              <div className="flex-1 min-w-0 space-y-1.5">
                                <div className="flex items-start justify-between gap-3">
                                  <span className="text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--muted)]">
                                    {getNotificationTypeLabel(notification)}
                                  </span>
                                  <span className="text-[10px] text-[var(--muted)] whitespace-nowrap">
                                    {formatNotificationTime(notification.createdAt)}
                                  </span>
                                </div>
                                <div className="text-sm font-bold text-[var(--charcoal)] line-clamp-1">
                                  {notification.title}
                                </div>
                                <div className="text-xs text-[var(--muted)] leading-relaxed line-clamp-2">
                                  {notification.message}
                                </div>
                              </div>
                              <div className="pt-1 text-[var(--muted)]">
                                {activeNotificationId === notification.id ? (
                                  <Loader2 className="w-4 h-4 animate-spin" />
                                ) : (
                                  <ChevronRight className="w-4 h-4" />
                                )}
                              </div>
                            </button>
                          ))
                        )}
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>

              <Link href="/cart" className="relative w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--cream)] text-[var(--charcoal)] hover:text-[var(--rust)]">
                <ShoppingCart className="w-5 h-5" />
                {cartCount > 0 && (
                   <span className="absolute -top-1.5 -right-1.5 w-5 h-5 bg-[var(--rust)] text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                     {cartCount}
                   </span>
                )}
              </Link>
              <div className="hidden lg:block w-[1px] h-8 bg-[var(--border)]" />
              <Link href="/profile" className="flex items-center gap-3 group">
                <div className="hidden lg:block text-right">
                  <div className="text-sm font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">{user?.name || "Patron"}</div>
                  <div className="text-[10px] text-[var(--muted)] font-bold uppercase tracking-widest">LOYAL CUSTOMER</div>
                </div>
                <div className="w-10 h-10 rounded-xl bg-[var(--sand)] border-2 border-white shadow-md flex items-center justify-center text-white font-serif text-lg font-bold uppercase transition-transform active:scale-95">
                  {user?.name ? user.name[0] : "P"}
                </div>
              </Link>
            </div>
          </div>
        </header>

        {/* Mobile Search Overlay */}
        <AnimatePresence>
          {showMobileSearch && (
            <motion.div 
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -20 }}
              className="lg:hidden fixed inset-x-0 top-0 z-[100] bg-white p-4 flex items-center gap-3 shadow-2xl"
            >
              <div className="flex-1 flex items-center bg-[var(--cream)] rounded-xl px-4 py-3 border border-[var(--border)]">
                <Search className="w-5 h-5 text-[var(--rust)] mr-3" />
                <input 
                  autoFocus
                  type="text" 
                  placeholder="Search products..." 
                  className="bg-transparent w-full text-base outline-none font-medium" 
                />
              </div>
              <button 
                onClick={() => setShowMobileSearch(false)}
                className="text-sm font-bold text-[var(--muted)] uppercase tracking-widest px-2"
              >
                Cancel
              </button>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Scrollable Content */}
        <main className="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-[100px] lg:pb-10">
          <div className="max-w-[1400px] mx-auto">
            {children}
          </div>
        </main>
        
        <MobileBottomNav items={mobileNavItems} />
      </div>

      <AnimatePresence>
        {mobileMenu && (
          <>
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setMobileMenu(false)} className="fixed inset-0 z-50 bg-black/40 lg:hidden" />
            <motion.aside initial={{ x: -280 }} animate={{ x: 0 }} exit={{ x: -280 }} className="fixed left-0 top-0 bottom-0 z-50 w-[300px] max-w-[85vw] bg-white lg:hidden overflow-y-auto border-r border-[var(--border)] shadow-2xl">
              <div className="p-8 flex flex-col min-h-full">
                <div className="flex items-center justify-between mb-8">
                  <Link href="/home" onClick={() => setMobileMenu(false)} className="text-2xl font-serif font-bold tracking-tighter text-[var(--charcoal)]">
                    LUMBARONG
                  </Link>
                  <button onClick={() => setMobileMenu(false)} className="w-10 h-10 rounded-xl border border-[var(--border)] bg-[var(--cream)] text-[var(--muted)] hover:text-[var(--rust)] transition-colors flex items-center justify-center">
                    <X className="w-5 h-5" />
                  </button>
                </div>

                <Link href="/profile" onClick={() => setMobileMenu(false)} className="flex items-center gap-4 p-4 rounded-2xl bg-[var(--cream)] border border-[var(--border)] mb-8">
                  <div className="w-12 h-12 rounded-xl bg-[var(--sand)] border-2 border-white shadow-md flex items-center justify-center text-white font-serif text-lg font-bold uppercase shrink-0">
                    {user?.name ? user.name[0] : "P"}
                  </div>
                  <div className="min-w-0">
                    <div className="text-sm font-bold text-[var(--charcoal)] truncate">{user?.name || "Patron"}</div>
                    <div className="text-[10px] text-[var(--muted)] font-bold uppercase tracking-widest">Loyal Customer</div>
                  </div>
                </Link>

                <nav className="flex-1 space-y-8">
                  {sidebarData.map((group, idx) => (
                    <div key={idx} className="space-y-3">
                      <div className="text-[10px] font-bold text-[var(--muted)] opacity-60 tracking-widest uppercase px-2">
                        {group.group}
                      </div>
                      <div className="space-y-1.5">
                        {group.items.map((item, i) => {
                          const active = pathname === item.path;
                          return (
                            <Link
                              key={i}
                              href={item.path}
                              onClick={() => setMobileMenu(false)}
                              className={`flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 text-sm font-medium ${active ? 'bg-[rgba(192,66,42,0.08)] text-[var(--rust)] border-l-4 border-[var(--rust)]' : 'text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)]'}`}
                            >
                              <span className={active ? 'text-[var(--rust)]' : 'text-[var(--muted)]'}>
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

                <div className="mt-8 pt-6 border-t border-[var(--border)] space-y-4">
                  <Link href="/cart" onClick={() => setMobileMenu(false)} className="flex items-center justify-between px-4 py-3.5 rounded-xl bg-[var(--cream)] text-[var(--charcoal)] hover:text-[var(--rust)] transition-colors">
                    <span className="flex items-center gap-3 text-sm font-medium">
                      <ShoppingCart className="w-5 h-5 text-[var(--muted)]" />
                      Cart
                    </span>
                    <span className="min-w-5 h-5 px-1.5 bg-[var(--rust)] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                      {cartCount}
                    </span>
                  </Link>

                  <button 
                    onClick={handleLogout}
                    className="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase"
                  >
                    <LogOut className="w-4 h-4" /> Sign Out
                  </button>
                </div>
              </div>
            </motion.aside>
          </>
        )}
      </AnimatePresence>
    </div>
  );
}
