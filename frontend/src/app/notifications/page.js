"use client";
import React, { useState } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { useRouter } from "next/navigation";
import { Bell, ShoppingBag, MessageSquare, Shield, Clock, ArrowRight, Mail, Loader2, CheckCheck } from "lucide-react";
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

export default function NotificationsPage() {
  const router = useRouter();
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeNotificationId, setActiveNotificationId] = useState(null);

  const fetchNotifications = React.useCallback(async () => {
    try {
      const res = await api.get("/notifications?role=customer");
      setNotifications(Array.isArray(res.data) ? res.data.map(normalizeNotification) : []);
    } catch (error) {
      console.error("Failed to fetch notifications", error.response?.data || error.message);
    } finally {
      setLoading(false);
    }
  }, []);

  const { socket, isDuplicateEvent, unreadCount: globalUnreadCount } = useSocket();

  React.useEffect(() => {
    fetchNotifications();

    if (!socket) return;

    const handleNewNotification = (incoming) => {
      if (isDuplicateEvent(incoming.id)) return;
      const next = normalizeNotification(incoming);
      setNotifications((prev) => [next, ...prev.filter((n) => n.id !== next.id)]);
    };

    socket.on('new_notification', handleNewNotification);

    return () => {
      socket.off('new_notification', handleNewNotification);
    };
  }, [fetchNotifications, socket, isDuplicateEvent]);

  const markAllAsRead = async () => {
    setNotifications((prev) => prev.map((notification) => ({ ...notification, read: true })));
    try {
      await api.put("/notifications/read-all?role=customer");
    } catch (error) {
      console.error("Failed to mark notifications as read", error.response?.data || error.message);
      fetchNotifications();
    }
  };

  const handleNotificationClick = async (notification) => {
    const href = getNotificationHref(notification, 'customer');

    if (!notification.read) {
      setActiveNotificationId(notification.id);
      setNotifications((prev) =>
        prev.map((item) => (item.id === notification.id ? { ...item, read: true } : item))
      );

      try {
        await api.put(`/notifications/${notification.id}/read`, {});
      } catch (error) {
        console.error("Failed to mark notification as read", error.response?.data || error.message);
      } finally {
        setActiveNotificationId(null);
      }
    }

    if (/^https?:\/\//i.test(href)) {
      window.location.href = href;
      return;
    }

    router.push(href);
  };

  const unreadCount = notifications.filter(n => !n.read).length;

  const renderNotificationIcon = (notification) => {
    switch (getNotificationTypeKey(notification)) {
      case "order":
        return (
          <div className="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-[#F4A8A3] bg-[#FEF2F1] text-[#B94232]">
            <ShoppingBag strokeWidth={2} className="w-5 h-5" />
          </div>
        );
      case "message":
        return (
          <div className="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-[#DBD4CC] bg-[#F8F5F1] text-[#2A2A2A]">
            <MessageSquare strokeWidth={2} className="w-5 h-5" />
          </div>
        );
      default:
        return (
          <div className="w-12 h-12 rounded-[1.2rem] flex items-center justify-center shrink-0 border border-[#A1D4B1] bg-[#EAF7ED] text-[#2A6D3A]">
            <Shield strokeWidth={2} className="w-5 h-5" />
          </div>
        );
    }
  };

  return (
    <CustomerLayout>
      <div className="max-w-2xl mx-auto space-y-6 mb-20 px-4 md:px-0">
        
        {/* Header Block */}
        <div className="flex flex-col md:flex-row md:items-start justify-between gap-4 pb-1">
          <div className="space-y-3">
             <div className="flex items-center gap-3">
               <span className="w-6 h-0.5 bg-[#B94232]"></span>
               <span className="text-[9px] font-extrabold uppercase tracking-[0.2em] text-[#B94232]">NOTIFICATIONS</span>
             </div>
             <h1 className="font-serif text-[28px] font-bold tracking-tight text-[#2A2A2A] leading-none">
               Alerts & Updates
             </h1>
             <p className="text-xs font-bold text-[#B94232]">{unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}</p>
          </div>
          <button 
            onClick={markAllAsRead} 
            className="self-start md:mt-2 flex items-center gap-1.5 px-4 py-2 bg-white border border-[var(--border)] rounded-xl text-[10px] font-bold text-[var(--muted)] hover:text-[#2A2A2A] hover:bg-[#FDFCFB] hover:shadow-sm transition-all shadow-sm"
          >
            <CheckCheck className="w-3.5 h-3.5" /> Mark All Read
          </button>
        </div>

        {/* Notifications List Container */}
        <div className="bg-white rounded-3xl shadow-sm overflow-hidden border border-[var(--border)] divide-y divide-[var(--border)] relative">
           {/* The red borderline representing unread borders */}
           <div className="absolute top-0 bottom-0 left-0 w-1 bg-transparent pointer-events-none" />

           <AnimatePresence initial={false}>
             {loading ? (
                <div className="p-16 text-center space-y-3">
                   <Loader2 className="w-8 h-8 text-[var(--muted)] mx-auto animate-spin opacity-50" />
                   <p className="text-xs text-[var(--muted)] italic">Loading your latest updates...</p>
                </div>
             ) : notifications.length === 0 ? (
                <div className="p-16 text-center space-y-3">
                   <Bell className="w-8 h-8 text-[var(--muted)] mx-auto opacity-20" />
                   <h3 className="font-serif text-lg font-bold text-[#2A2A2A] opacity-80">You&apos;re all caught up!</h3>
                   <p className="text-xs text-[var(--muted)] italic">No new alerts or updates at this time.</p>
                </div>
             ) : (
                notifications.map((notif, idx) => (
                  <motion.div 
                    key={notif.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, height: 0, overflow: 'hidden' }}
                    transition={{ delay: idx * 0.05 }}
                    className={`p-5 sm:p-6 flex flex-col sm:flex-row items-start gap-4 relative group transition-colors ${!notif.read ? 'bg-white' : 'bg-[#FAFAFA]'}`}
                  >
                    {!notif.read && (
                      <div className="absolute left-0 top-0 bottom-0 w-1 bg-[#B94232]" />
                    )}
                    
                    {/* Icon Block */}
                    {renderNotificationIcon(notif)}

                    <div className="flex-1 w-full space-y-2.5 pt-0.5">
                      {/* Top Meta Row */}
                      <div className="flex justify-between items-start">
                         <div className="text-[9px] font-bold text-[var(--muted)] opacity-80 uppercase tracking-[0.2em]">{getNotificationTypeLabel(notif)}</div>
                         <div className="flex items-center gap-2">
                            <span className="text-[9px] sm:text-[10px] font-medium text-[var(--muted)] opacity-70 flex items-center gap-1"><Clock className="w-3 h-3 opacity-50" /> {formatNotificationTime(notif.createdAt)}</span>
                            {!notif.read && (
                               <span className="text-[8px] font-bold text-[#B94232] border border-[#F4A8A3] bg-[#FEF2F1] px-1.5 py-0.5 rounded-[4px] uppercase tracking-wider">NEW</span>
                            )}
                         </div>
                      </div>
                      
                      {/* Title & Description */}
                      <div className="space-y-1">
                         <h3 className="text-[15px] font-bold text-[#2A2A2A]">{notif.title}</h3>
                         <div className="flex">
                            <div className="w-0.5 bg-[var(--border)] rounded-full mr-2.5 shrink-0" />
                            <p className="text-[12px] font-medium text-[var(--muted)] leading-relaxed italic opacity-90">{notif.message}</p>
                         </div>
                      </div>
                      
                      {/* Action Links */}
                      <div className="pt-1.5 flex items-center gap-4">
                         <button onClick={() => handleNotificationClick(notif)} className="flex items-center gap-1 text-[10px] font-extrabold text-[var(--muted)] hover:text-[#2A2A2A] uppercase tracking-[0.1em] transition-colors group/view">
                            VIEW DETAILS <ArrowRight className="w-3 h-3 group-hover/view:translate-x-1 transition-transform ml-0.5" />
                         </button>
                         {activeNotificationId === notif.id && <Loader2 className="w-3.5 h-3.5 animate-spin text-[var(--muted)]" />}
                      </div>
                    </div>
                  </motion.div>
                ))
             )}
           </AnimatePresence>
        </div>
      </div>
    </CustomerLayout>
  );
}
