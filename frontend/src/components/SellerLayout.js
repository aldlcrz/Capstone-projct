"use client";
import React, { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Package,
  ReceiptText,
  Store,
  MessageCircle,
  Bell,
  Search,
  LogOut,
  Menu,
  PlusCircle,
  X
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

const sidebarData = [
  {
    group: "MY SHOP", items: [
      { label: "Dashboard", icon: <LayoutDashboard className="w-5 h-5" />, path: "/seller/dashboard" },
      { label: "Products", icon: <Package className="w-5 h-5" />, path: "/seller/inventory" },
    ]
  },
  {
    group: "SALES", items: [
      { label: "My Orders", icon: <ReceiptText className="w-5 h-5" />, path: "/seller/orders" },
      { label: "Customers", icon: <Store className="w-5 h-5" />, path: "/seller/customer" },
    ]
  },
  {
    group: "MESSAGES", items: [
      { label: "Customer Chat", icon: <MessageCircle className="w-5 h-5" />, path: "/seller/messages" },
    ]
  },
];

const mobileNavItems = [
  { label: "Dashboard", icon: <LayoutDashboard />, path: "/seller/dashboard" },
  { label: "Inventory", icon: <Package />, path: "/seller/inventory" },
  { label: "Orders", icon: <ReceiptText />, path: "/seller/orders" },
  { label: "Messages", icon: <MessageCircle />, path: "/seller/messages" },
];

import { useSocket } from "@/context/SocketContext";
import { api } from "@/lib/api";
import { normalizeNotification } from "@/lib/notifications";
import MobileBottomNav from "./MobileBottomNav";

export default function SellerLayout({ children }) {
  const pathname = usePathname();
  const [user, setUser] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isPopoutOpen, setIsPopoutOpen] = useState(false);
  const { socket } = useSocket();

  const fetchNotifications = React.useCallback(async () => {
    const token = localStorage.getItem("token");
    if (!token || token === "null" || token === "undefined") return;
    try {
      const res = await api.get("/notifications");
      const data = Array.isArray(res.data) ? res.data : [];
      setNotifications(data);
      setUnreadCount(data.filter(n => !n.read).length);
    } catch (error) {
      console.error("Failed to fetch seller notifications");
    }
  }, []);

  React.useEffect(() => {
    const storedUser = JSON.parse(localStorage.getItem("user") || "{}");
    setUser(storedUser);

    // Safety redirect: If not seller or admin, bounce to home
    if (storedUser && storedUser.role && storedUser.role !== 'seller' && storedUser.role !== 'admin') {
      window.location.href = "/";
      return;
    }

    fetchNotifications();

    if (socket && storedUser?.id) {
      socket.on('new_notification', (incoming) => {
        setNotifications(prev => [incoming, ...prev]);
        setUnreadCount(prev => prev + 1);
      });
      socket.on('notification_count_update', fetchNotifications);
    }

    return () => {
      if (socket) {
        socket.off('new_notification');
        socket.off('notification_count_update');
      }
    };
  }, [socket, fetchNotifications]);

  const markAsRead = async (id) => {
    try {
      await api.put(`/notifications/${id}/read`);
      setNotifications(prev => prev.map(n => n.id === id ? { ...n, read: true } : n));
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error("Failed to mark notification as read");
    }
  };

  const isVerified = user?.isVerified;

  return (
    <div className="flex h-screen bg-[#F4ECE3] overflow-hidden">
      {/* Sidebar Desktop */}
      <motion.aside
        initial={{ x: -280 }}
        animate={{ x: 0 }}
        className="hidden lg:flex flex-col w-[280px] h-full bg-white border-r border-[var(--border)] overflow-y-auto"
      >
        <div className="p-10 flex flex-col h-full">
          <div className="mb-12">
            <Link href="/seller/dashboard" className="font-serif text-2xl font-bold text-[var(--charcoal)] tracking-tighter">
              LUMBARONG
            </Link>
            <div className="flex items-center gap-1.5 mt-2 px-1 text-[var(--rust)] font-bold tracking-widest text-[10px]">
              <Store className="w-3 h-3" /> SELLER SIDE
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
                    const disabled = !isVerified && item.path !== "/seller/profile" && item.path !== "/seller/dashboard";
                    return (
                      <Link
                        key={i}
                        href={disabled ? "#" : item.path}
                        className={`flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium ${active ? 'bg-[rgba(192,66,42,0.08)] text-[var(--rust)] border-l-4 border-[var(--rust)]' : 'text-[var(--charcoal)] hover:bg-[var(--cream)] hover:text-[var(--rust)]'} ${disabled ? 'opacity-30 cursor-not-allowed grayscale' : ''}`}
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
              onClick={() => { localStorage.clear(); window.location.href = "/"; }}
              className="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase"
            >
              <LogOut className="w-4 h-4" /> End Session
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
              {/* Search logic disabled based on UI polish request */}
            </div>



            <div className="flex items-center gap-2 md:gap-6">
              <div className="relative">
                <button 
                  onClick={() => setIsPopoutOpen(!isPopoutOpen)}
                  className={`relative w-10 h-10 flex items-center justify-center rounded-xl transition-all ${isPopoutOpen ? 'bg-[var(--rust)] text-white shadow-lg' : 'bg-[var(--cream)] text-[var(--charcoal)] hover:text-[var(--rust)]'}`}
                >
                  <Bell className="w-5 h-5" />
                  {unreadCount > 0 && (
                    <span className="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 bg-[var(--rust)] text-white text-[10px] font-black flex items-center justify-center rounded-full border-2 border-white shadow-sm scale-110">
                      {unreadCount}
                    </span>
                  )}
                </button>

                {/* Notifications Popout */}
                <AnimatePresence>
                  {isPopoutOpen && (
                    <>
                      <motion.div 
                        initial={{ opacity: 0 }} 
                        animate={{ opacity: 1 }} 
                        exit={{ opacity: 0 }}
                        onClick={() => setIsPopoutOpen(false)}
                        className="fixed inset-0 z-40 bg-black/5 backdrop-blur-[2px]"
                      />
                      <motion.div
                        initial={{ opacity: 0, y: 10, scale: 0.95 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: 10, scale: 0.95 }}
                        className="absolute right-0 mt-3 w-80 md:w-96 max-h-[500px] bg-white rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden z-50 flex flex-col"
                      >
                        <div className="p-5 border-b border-[var(--border)] flex items-center justify-between bg-stone-50/50">
                          <h3 className="font-serif font-bold text-[var(--charcoal)]">Artisan Alerts</h3>
                          <span className="text-[10px] font-black tracking-widest text-[var(--muted)] opacity-50 uppercase">{unreadCount} New</span>
                        </div>
                        <div className="flex-1 overflow-y-auto custom-scrollbar p-2">
                          {notifications.length === 0 ? (
                            <div className="py-20 text-center space-y-3">
                              <Bell className="w-8 h-8 mx-auto text-[var(--muted)] opacity-20" />
                              <div className="text-sm font-medium text-[var(--muted)]">All quiet in the workshop.</div>
                            </div>
                          ) : (
                            <div className="space-y-1">
                              {notifications.map((notif) => {
                                const n = normalizeNotification(notif);
                                return (
                                  <div 
                                    key={notif.id} 
                                    className={`relative p-4 rounded-xl transition-all group ${!n.read ? 'bg-[var(--cream)]/40 hover:bg-[var(--cream)]/60' : 'hover:bg-stone-50'}`}
                                  >
                                    {!n.read && <div className="absolute left-2 top-1/2 -translate-y-1/2 w-1.5 h-1.5 bg-[var(--rust)] rounded-full" />}
                                    <div className="pl-3 space-y-1">
                                      <div className="flex items-start justify-between gap-2">
                                        <div className="text-xs font-bold text-[var(--charcoal)] leading-tight">{n.title}</div>
                                        <div className="text-[9px] font-bold text-[var(--muted)] opacity-50 whitespace-nowrap pt-0.5 uppercase tracking-tighter">
                                          {formatNotificationTime(n.createdAt)}
                                        </div>
                                      </div>
                                      <p className="text-[11px] text-[var(--muted)] leading-relaxed line-clamp-2">{n.message}</p>
                                      
                                      <div className="flex items-center gap-3 pt-2">
                                        {n.link && (
                                          <Link 
                                            href={n.link} 
                                            onClick={() => setIsPopoutOpen(false)}
                                            className="text-[10px] font-bold text-[var(--rust)] hover:underline flex items-center gap-1"
                                          >
                                            View Details <ArrowRight className="w-3 h-3" />
                                          </Link>
                                        )}
                                        {!n.read && (
                                          <button 
                                            onClick={() => markAsRead(notif.id)}
                                            className="text-[10px] font-bold text-[var(--muted)] hover:text-[var(--rust)] transition-colors ml-auto"
                                          >
                                            Mark as read
                                          </button>
                                        )}
                                      </div>
                                    </div>
                                  </div>
                                );
                              })}
                            </div>
                          )}
                        </div>
                        {notifications.length > 0 && (
                          <div className="p-4 bg-stone-50 border-t border-[var(--border)] text-center">
                             <button 
                               onClick={() => setIsPopoutOpen(false)}
                               className="text-[10px] font-bold uppercase tracking-widest text-[var(--muted)] hover:text-[var(--rust)] transition-colors"
                             >
                               Close Alerts
                             </button>
                          </div>
                        )}
                      </motion.div>
                    </>
                  )}
                </AnimatePresence>
              </div>
              <div className="hidden lg:block w-[1px] h-8 bg-[var(--border)]" />
              <Link href="/seller/profile" className="flex items-center gap-3 group">
                <div className="hidden lg:block text-right">
                  <div className="text-sm font-bold text-[var(--charcoal)] group-hover:text-[var(--rust)] transition-colors">{user?.name || "Artisan Shop"}</div>
                  <div className={`text-[10px] font-bold uppercase tracking-widest ${isVerified ? 'text-green-600' : 'text-amber-600 animate-pulse'}`}>
                    {isVerified ? "VERIFIED" : "PENDING VERIFICATION"}
                  </div>
                </div>
                <div className="w-10 h-10 rounded-xl bg-[var(--bark)] border-2 border-white shadow-md flex items-center justify-center text-white font-serif text-lg font-bold transition-transform active:scale-95">
                  {user?.name ? user.name[0] : "A"}
                </div>
              </Link>
            </div>
          </div>
        </header>



        {/* Scrollable Content */}
        <main className="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-[100px] lg:pb-10">
          <div className="max-w-[1400px] mx-auto">
            {!isVerified && pathname !== "/seller/profile" && pathname !== "/seller/dashboard" ? (
              <div className="artisan-card p-20 text-center space-y-6 animate-fade-up">
                <div className="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto scale-110 shadow-lg">
                  <ShieldCheck className="w-8 h-8" />
                </div>
                <h2 className="font-serif text-3xl font-bold">Lumban Community Verification</h2>
                <p className="max-w-md mx-auto text-[var(--muted)] leading-relaxed">Your application is currently being reviewed by our heritage administrators. Access to shop operations will be granted once your documents are verified.</p>
                <div className="text-[10px] font-bold text-amber-700 bg-amber-50 px-4 py-2 rounded-full inline-block border border-amber-200">EXPECTED WINDOW: 24-48 HOURS</div>
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

function ShieldCheck(props) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z" />
      <path d="m9 12 2 2 4-4" />
    </svg>
  );
}
