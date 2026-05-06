"use client";
import React, { useState, useEffect, useRef } from "react";
import Link from "next/link";
import Image from "next/image";
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
  MapPin,
  Package,
  ChevronRight,
  ChevronDown,
  Loader2,
  Star
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, clearSession, getStoredUserForRole, getTokenForRole, SESSION_SYNC_EVENT, getApiErrorMessage } from "@/lib/api";
import ConfirmationModal from "./ConfirmationModal";
import { useSocket } from "@/context/SocketContext";
import {
  formatNotificationTime,
  getNotificationHref,
  getNotificationTypeKey,
  getNotificationTypeLabel,
  normalizeNotification,
} from "@/lib/notifications";
import MobileBottomNav from "./MobileBottomNav";

const mobileNavItems = [
  { label: "Home", icon: <Home />, path: "/" },
  { label: "Messages", icon: <MessageCircle />, path: "/messages" },
  { label: "Profile", icon: <User />, path: "/profile" },
];

const megaMenuData = [];

export default function CustomerLayout({ children, breadcrumbs, layoutBg = "bg-[#FAFAFA]", contentPadding = "px-4 py-8 lg:px-12 lg:py-12" }) {
  const pathname = usePathname();
  const router = useRouter();
  const { socket, unreadCount, setUnreadCount, refreshUnreadCount } = useSocket();
  const [user, setUser] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [notificationsLoading, setNotificationsLoading] = useState(false);
  const [activeNotificationId, setActiveNotificationId] = useState(null);
  const [dismissedNotificationIds, setDismissedNotificationIds] = useState([]);
  const [activeMegaMenu, setActiveMegaMenu] = useState(null);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [cartCount, setCartCount] = useState(0);
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);
  
  // Navigation State
  const [categories, setCategories] = useState([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [cartOpen, setCartOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);
  const [categoryMenuOpen, setCategoryMenuOpen] = useState(false);
  const [arrivalMenuOpen, setArrivalMenuOpen] = useState(false);
  const [isSellerHubOpen, setIsSellerHubOpen] = useState(false);
  const categoryRef = useRef(null);
  const arrivalRef = useRef(null);

  useEffect(() => {
    const loadCategories = async () => {
      // Simple static cache to avoid 429s on every mount
      if (window._categories_cache) {
        setCategories(window._categories_cache);
        return;
      }
      try {
        const response = await api.get("/categories");
        const data = response.data || [];
        setCategories(data);
        window._categories_cache = data;
      } catch (err) {
        console.error("Failed to fetch categories:", err);
      }
    };
    loadCategories();
  }, []);

  const handleSearch = (e) => {
    if (e.key === 'Enter' && searchQuery.trim()) {
      router.push(`/?search=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  const navigateToCategory = (category) => {
    router.push(`/?category=${encodeURIComponent(category)}`);
    setCategoryMenuOpen(false);
    setArrivalMenuOpen(false);
  };

  const navigateToNewArrivals = () => {
    router.push(`/?filter=new_arrivals`);
    setArrivalMenuOpen(false);
  };

  const notificationPanelRef = useRef(null);

  const syncUserSession = () => {
    const stored = getStoredUserForRole("customer");
    setUser(stored);
  };

  useEffect(() => {
    syncUserSession();
    window.addEventListener(SESSION_SYNC_EVENT, syncUserSession);
    return () => window.removeEventListener(SESSION_SYNC_EVENT, syncUserSession);
  }, []);

  const lastNotificationFetchRef = useRef(0);
  const fetchNotifications = React.useCallback(async () => {
    if (!user) return;
    const now = Date.now();
    // Throttle fetches to once every 1 minute if open, or 30s if just checking
    const waitTime = notificationsOpen ? 60000 : 30000;
    if (now - lastNotificationFetchRef.current < waitTime) return;

    setNotificationsLoading(true);
    try {
      const res = await api.get("/notifications");
      setNotifications(res.data);
      // Synchronize with socket context count
      const count = res.data.filter((n) => !n.read).length;
      setUnreadCount(count);
      lastNotificationFetchRef.current = now;
    } catch (err) {
      console.error("Failed to fetch notifications:", err);
    } finally {
      setNotificationsLoading(false);
    }
  }, [user, notificationsOpen, setUnreadCount]);

  // Real-time notification updates
  useEffect(() => {
    if (!socket || !user) return;

    const handleNewNotification = () => {
      // When a new notification arrives via socket, we only need to refresh the list 
      // if the panel is currently open. Otherwise, the unreadCount is already handled by SocketContext.
      if (notificationsOpen) {
        lastNotificationFetchRef.current = 0; // Force refresh
        fetchNotifications();
      }
    };

    socket.on('notification', handleNewNotification);
    socket.on('notification_received', handleNewNotification);

    return () => {
      socket.off('notification', handleNewNotification);
      socket.off('notification_received', handleNewNotification);
    };
  }, [socket, user, notificationsOpen, fetchNotifications]);

  const toggleNotifications = () => {
    if (!notificationsOpen) fetchNotifications();
    setNotificationsOpen(!notificationsOpen);
  };

  const handleNotificationClick = async (notification) => {
    setActiveNotificationId(notification.id);
    try {
      if (!notification.read) {
        await api.patch(`/notifications/${notification.id}/read`);
        setUnreadCount((prev) => Math.max(0, prev - 1));
      }
      setNotificationsOpen(false);
      const href = getNotificationHref(notification);
      router.push(href);
    } catch (err) {
      console.error("Notification action failed:", err);
    } finally {
      setActiveNotificationId(null);
    }
  };

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (categoryRef.current && !categoryRef.current.contains(event.target)) {
        setCategoryMenuOpen(false);
      }
      if (arrivalRef.current && !arrivalRef.current.contains(event.target)) {
        setArrivalMenuOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleLogout = () => setShowLogoutConfirm(true);

  const confirmLogout = async () => {
    try {
      await api.post("/auth/logout");
      clearSession("customer");
      router.replace("/login");
    } catch (err) {
      clearSession("customer");
      router.replace("/login");
    }
  };

  const renderNotificationIcon = (n) => {
    const key = getNotificationTypeKey(n);
    const commonClasses = "w-10 h-10 rounded-xl flex items-center justify-center shrink-0";
    switch (key) {
      case "ORDER":
        return <div className={`${commonClasses} bg-blue-50 text-blue-500`}><Package className="w-5 h-5" /></div>;
      case "CHAT":
        return <div className={`${commonClasses} bg-green-50 text-green-500`}><MessageCircle className="w-5 h-5" /></div>;
      default:
        return <div className={`${commonClasses} bg-gray-50 text-gray-400`}><Bell className="w-5 h-5" /></div>;
    }
  };

  return (
    <div data-panel="customer" className={`min-h-screen ${layoutBg}`}>
      <div className="flex flex-col min-h-screen">
        <header className="sticky top-0 z-40 bg-white flex flex-col w-full">
          {/* Top Row */}
          <div className="flex items-center justify-between px-4 lg:px-12 py-4 border-b border-gray-100 w-full">
            <div className="flex items-center gap-4 flex-1 md:flex-none">
              {!user && (
                <div 
                  className="relative"
                  onMouseEnter={() => setIsSellerHubOpen(true)}
                  onMouseLeave={() => setIsSellerHubOpen(false)}
                >
                  <Link 
                    href="/seller-register" 
                    className="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500 hover:text-black transition-all duration-300 py-2"
                  >
                    Start your shop now
                  </Link>

                  <AnimatePresence>
                    {isSellerHubOpen && (
                      <motion.div
                        initial={{ opacity: 0, y: -12, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -12, scale: 0.98 }}
                        transition={{ duration: 0.18 }}
                        className="absolute left-0 top-[calc(100%+12px)] z-50 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
                      >
                        <div className="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                          <div className="flex items-center justify-between gap-4">
                            <div>
                              <div className="text-[10px] font-bold uppercase tracking-[0.25em] text-gray-500">Seller Hub</div>
                              <div className="text-sm font-semibold text-black">Artisan Portal</div>
                            </div>
                            <Link
                              href="/seller-register"
                              className="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500 hover:text-black"
                            >
                              Join Now
                            </Link>
                          </div>
                        </div>
                        <div className="px-6 py-8 text-center space-y-5">
                          <div className="text-sm font-bold text-black leading-tight">Register as seller now to showcase your heritage</div>
                          <Link 
                            href="/seller-register" 
                            className="inline-block px-8 py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all shadow-lg shadow-black/10"
                          >
                            Get Started
                          </Link>
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              )}
            </div>
            
            <div className="flex items-center md:absolute md:left-1/2 md:-translate-x-1/2">
              <Link href="/" className="flex items-center gap-2">
                <div className="w-8 h-8 bg-black rounded-full text-white flex items-center justify-center font-serif font-bold text-lg">
                  L
                </div>
                <span className="font-serif text-xl font-bold tracking-tight text-black">
                  LumBarong
                </span>
              </Link>
            </div>

            <div className="flex items-center gap-4 md:gap-6 flex-1 md:flex-none justify-end">

              
              <div className="flex items-center gap-2 md:gap-3">
                {/* Notification Bell */}
                <div 
                  className="relative" 
                  ref={notificationPanelRef}
                  onMouseEnter={() => {
                    setNotificationsOpen(true);
                  }}
                  onMouseLeave={() => setNotificationsOpen(false)}
                >
                  <button
                    onClick={toggleNotifications}
                    className="relative w-12 h-12 flex items-center justify-center rounded-full border border-gray-200 text-gray-800 hover:border-gray-400 transition-colors bg-white"
                  >
                    <Bell className="w-5 h-5" />
                    {unreadCount > 0 && (
                      <span className="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
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
                        className="absolute right-0 top-[calc(100%+16px)] z-50 w-[min(24rem,calc(100vw-1rem))] max-w-[calc(100vw-1rem)] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
                      >
                        <div className="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                          <div className="flex items-center justify-between gap-4">
                            <div>
                              <div className="text-[10px] font-bold uppercase tracking-[0.25em] text-gray-500">Notifications</div>
                              <div className="text-sm font-semibold text-black">
                                {unreadCount} unread {unreadCount === 1 ? "alert" : "alerts"}
                              </div>
                            </div>
                            <Link
                              href="/notifications"
                              onClick={() => setNotificationsOpen(false)}
                              className="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500 hover:text-black"
                            >
                              View All
                            </Link>
                          </div>
                        </div>

                        <div className="max-h-96 overflow-y-auto">
                          {notificationsLoading ? (
                            <div className="px-6 py-12 flex flex-col items-center gap-3 text-gray-400">
                              <Loader2 className="w-6 h-6 animate-spin" />
                              <span className="text-[11px] font-bold uppercase tracking-[0.2em]">Loading Alerts</span>
                            </div>
                          ) : !user ? (
                            <div className="px-6 py-16 text-center space-y-6">
                              <div className="text-sm font-bold text-black">Sign in to stay updated</div>
                              <Link href="/login" className="inline-block px-8 py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all">
                                Login Now
                              </Link>
                            </div>
                          ) : (
                            <div className="divide-y divide-gray-50">
                               {notifications
                                  .filter(n => !dismissedNotificationIds.includes(n.id))
                                  .slice(0, 10)
                                  .map((notification) => (
                                    <button
                                      key={notification.id}
                                      onClick={() => handleNotificationClick(notification)}
                                      className={`w-full px-6 py-4 text-left flex items-start gap-4 transition-colors hover:bg-gray-50 ${notification.read ? "bg-white" : "bg-blue-50/30"}`}
                                    >
                                      {renderNotificationIcon(notification)}
                                      <div className="flex-1 min-w-0 space-y-1">
                                        <div className="flex items-start justify-between gap-3">
                                          <span className="text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500">
                                            {getNotificationTypeLabel(notification)}
                                          </span>
                                          <span className="text-[10px] text-gray-400 whitespace-nowrap">
                                            {formatNotificationTime(notification.createdAt)}
                                          </span>
                                        </div>
                                        <div className="text-sm font-bold text-black line-clamp-1">
                                          {notification.title}
                                        </div>
                                        <div className="text-xs text-gray-500 leading-relaxed line-clamp-2">
                                          {notification.message}
                                        </div>
                                      </div>
                                    </button>
                                  ))
                               }
                            </div>
                          )}
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>

                {/* Cart Icon */}
                <div 
                  className="relative"
                  onMouseEnter={() => setCartOpen(true)}
                  onMouseLeave={() => setCartOpen(false)}
                >
                  <Link href="/cart" className="relative w-12 h-12 flex items-center justify-center rounded-full border border-gray-200 text-gray-800 hover:border-gray-400 transition-colors bg-white">
                    <ShoppingBag className="w-5 h-5" />
                    {cartCount > 0 && (
                      <span className="absolute -top-1 -right-1 min-w-[20px] h-[20px] px-1 bg-black text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
                        {cartCount}
                      </span>
                    )}
                  </Link>
                  
                  <AnimatePresence>
                    {cartOpen && (
                      <motion.div
                        initial={{ opacity: 0, y: -12, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -12, scale: 0.98 }}
                        transition={{ duration: 0.15 }}
                        className="absolute right-0 top-[calc(100%+16px)] z-50 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl p-6 text-center"
                      >
                        <ShoppingBag className="w-8 h-8 mx-auto text-gray-200 mb-4" />
                        <div className="text-sm font-bold text-black mb-1">Your Shopping Bag</div>
                        <p className="text-[11px] text-gray-500 mb-6 uppercase tracking-widest">
                          {cartCount} {cartCount === 1 ? "Item" : "Items"} Total
                        </p>
                        <Link 
                          href="/cart"
                          className="block w-full py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all"
                        >
                          View Bag
                        </Link>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>

                {/* Profile icon */}
                <div 
                  className="relative"
                  onMouseEnter={() => setProfileOpen(true)}
                  onMouseLeave={() => setProfileOpen(false)}
                >
                  {user ? (
                    <Link href="/profile" className="w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center overflow-hidden hover:border-gray-400 transition-colors bg-white shrink-0">
                      {user?.profilePhoto ? (
                        <div className="relative w-full h-full">
                          <Image
                            src={user.profilePhoto}
                            alt={user.name || "User"}
                            fill
                            className="object-cover"
                            unoptimized
                          />
                        </div>
                      ) : (
                        <span className="text-base font-bold text-gray-700">{user?.name ? user.name[0].toUpperCase() : "U"}</span>
                      )}
                    </Link>
                  ) : (
                    <Link href="/login" className="w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center text-gray-800 hover:border-gray-400 transition-colors bg-white">
                      <User className="w-5 h-5" />
                    </Link>
                  )}

                  <AnimatePresence>
                    {profileOpen && (
                      <motion.div
                        initial={{ opacity: 0, y: -12, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        exit={{ opacity: 0, y: -12, scale: 0.98 }}
                        transition={{ duration: 0.15 }}
                        className="absolute right-0 top-[calc(100%+16px)] z-50 w-64 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
                      >
                        <div className="p-5 border-b border-gray-50 bg-gray-50/30">
                          <div className="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 mb-1">My Account</div>
                          <div className="text-sm font-bold text-black truncate">{user?.name || "Guest Traveler"}</div>
                        </div>
                        <div className="p-2">
                          {user ? (
                            <>
                              <Link href="/profile" className="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-600 hover:bg-gray-50 hover:text-black rounded-xl transition-all">
                                <User className="w-4 h-4" /> My Profile
                              </Link>
                              <Link href="/orders" className="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-600 hover:bg-gray-50 hover:text-black rounded-xl transition-all">
                                <Package className="w-4 h-4" /> My Orders
                              </Link>
                              <button onClick={handleLogout} className="w-full flex items-center gap-3 px-4 py-3 text-xs font-semibold text-red-500 hover:bg-red-50 rounded-xl transition-all mt-1">
                                <LogOut className="w-4 h-4" /> Sign Out
                              </button>
                            </>
                          ) : (
                            <>
                              <Link href="/login" className="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-600 hover:bg-gray-50 hover:text-black rounded-xl transition-all">
                                <LogOut className="w-4 h-4" /> Sign In
                              </Link>
                              <Link href="/register" className="flex items-center gap-3 px-4 py-3 text-xs font-semibold text-gray-600 hover:bg-gray-50 hover:text-black rounded-xl transition-all">
                                <User className="w-4 h-4" /> Create Account
                              </Link>
                            </>
                          )}
                        </div>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Row */}
          <div className="hidden lg:flex items-center gap-4 px-4 lg:px-12 py-3 border-b border-gray-100">
            <div className="flex items-center gap-2">
              <div className="relative" ref={categoryRef}>
                <button 
                  onClick={() => setCategoryMenuOpen(!categoryMenuOpen)}
                  className="flex items-center gap-2 px-5 py-2.5 rounded-full border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors bg-gray-50/50"
                >
                  Categories <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${categoryMenuOpen ? 'rotate-180' : ''}`} />
                </button>
                
                <AnimatePresence>
                  {categoryMenuOpen && (
                    <motion.div 
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: 10 }}
                      className="absolute left-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50"
                    >
                      <div className="py-2">
                        <button 
                          onClick={() => navigateToCategory('ALL')}
                          className="w-full text-left px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-black transition-colors"
                        >
                          All Categories
                        </button>
                        {categories.map((cat) => (
                          <button 
                            key={cat.id}
                            onClick={() => navigateToCategory(cat.name)}
                            className="w-full text-left px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-black transition-colors"
                          >
                            {cat.name}
                          </button>
                        ))}
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>

              <div className="relative" ref={arrivalRef}>
                <button 
                  onClick={() => setArrivalMenuOpen(!arrivalMenuOpen)}
                  className="flex items-center gap-2 px-5 py-2.5 rounded-full border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors bg-gray-50/50"
                >
                  New Arrival <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${arrivalMenuOpen ? 'rotate-180' : ''}`} />
                </button>

                <AnimatePresence>
                  {arrivalMenuOpen && (
                    <motion.div 
                      initial={{ opacity: 0, y: 10 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: 10 }}
                      className="absolute left-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50"
                    >
                      <div className="py-2">
                        <button 
                          onClick={navigateToNewArrivals}
                          className="w-full text-left px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-black transition-colors"
                        >
                          Shop All New
                        </button>
                        <button 
                          onClick={() => navigateToCategory('Trending')}
                          className="w-full text-left px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-black transition-colors"
                        >
                          Trending Now
                        </button>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>

              <div className="h-6 w-px bg-gray-200 mx-2" />

              {['Men', 'Women', 'Children'].map((filter) => (
                <button 
                  key={filter} 
                  onClick={() => navigateToCategory(filter)}
                  className="px-5 py-2.5 rounded-full border border-gray-200 text-sm font-medium text-gray-600 hover:border-gray-400 hover:text-black transition-colors"
                >
                  {filter}
                </button>
              ))}
            </div>
            
            <div className="flex-1 max-w-xl relative ml-4">
              <input 
                type="text" 
                placeholder="Search" 
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onKeyDown={handleSearch}
                className="w-full bg-gray-50/80 border border-gray-200 rounded-full py-2.5 pl-6 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-black/5 focus:border-gray-300 transition-all"
              />
              <Search 
                className="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer" 
                onClick={() => handleSearch({ key: 'Enter' })}
              />
            </div>
          </div>

          {/* Breadcrumbs Row */}
          {breadcrumbs && (
            <div className="hidden lg:block px-4 lg:px-12 py-2.5 border-b border-gray-100 bg-gray-50/30">
              {breadcrumbs}
            </div>
          )}
        </header>

        <main className="flex-1 overflow-visible flex flex-col">
          <div className={`flex-1 w-full max-w-[1440px] mx-auto ${contentPadding}`}>
            {children}
          </div>
        </main>

        <footer className="bg-white pt-16 pb-8 border-t border-gray-100 mt-auto">
          <div className="max-w-[1440px] mx-auto px-4 lg:px-12">
            <div className="grid grid-cols-2 md:grid-cols-3 gap-8 mb-16">
              <div className="lg:col-span-1">
                <Link href="/" className="flex items-center gap-2 mb-6">
                  <div className="w-8 h-8 bg-black rounded-full text-white flex items-center justify-center font-serif font-bold text-lg">
                    L
                  </div>
                  <span className="font-serif text-xl font-bold tracking-tight text-black">
                    LumBarong
                  </span>
                </Link>
                <p className="text-sm text-gray-500 leading-relaxed mb-8 max-w-sm">
                  We have clothes that suits your style and which you&apos;re proud to wear. From women to men.
                </p>
              </div>

              <div>
                <h4 className="text-xs font-bold text-black uppercase tracking-widest mb-6">Shop</h4>
                <ul className="space-y-4">
                  <li><Link href="/" className="text-sm text-gray-500 hover:text-black transition-colors">All Products</Link></li>
                  <li><Link href="/?filter=new_arrivals" className="text-sm text-gray-500 hover:text-black transition-colors">New Arrivals</Link></li>
                  <li><Link href="/heritage-guide" className="text-sm text-gray-500 hover:text-black transition-colors">Heritage Guide</Link></li>
                </ul>
              </div>

              <div>
                <h4 className="text-xs font-bold text-black uppercase tracking-widest mb-6">Support</h4>
                <ul className="space-y-4">
                  <li><Link href="/about" className="text-sm text-gray-500 hover:text-black transition-colors">About LumBarong</Link></li>
                  <li><Link href="/privacy-policy" className="text-sm text-gray-500 hover:text-black transition-colors">Privacy Policy</Link></li>
                  <li><Link href="/terms" className="text-sm text-gray-500 hover:text-black transition-colors">Terms & Conditions</Link></li>
                </ul>
              </div>
            </div>

            <div className="pt-8 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
              <div className="text-xs text-gray-400">
                LumBarong © 2024. All Rights Reserved
              </div>
              <div className="flex items-center gap-2">
                <div className="w-10 h-6 bg-[#007DFE] rounded border border-blue-400 flex items-center justify-center text-[8px] font-black text-white">GCash</div>
                <div className="w-10 h-6 bg-[#19D65E] rounded border border-green-400 flex items-center justify-center text-[8px] font-black text-white">Maya</div>
              </div>
            </div>
          </div>
        </footer>

        {/* Floating Message Bubble */}
        <Link 
          href="/messages"
          className="fixed bottom-6 right-6 md:bottom-10 md:right-10 w-16 h-16 bg-black text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-100 group border-4 border-white"
        >
          <MessageCircle className="w-8 h-8 group-hover:animate-pulse" />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 min-w-[24px] h-[24px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white">
              {unreadCount}
            </span>
          )}
        </Link>
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
    </div>
  );
}
