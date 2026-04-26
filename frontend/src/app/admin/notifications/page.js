"use client";
import React, { useState } from "react";
import AdminLayout from "@/components/AdminLayout";
import { useRouter } from "next/navigation";
import { Bell, ShoppingBag, MessageSquare, Shield, Clock, ArrowRight, Mail, Loader2, CheckCheck, Store, Package } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import {
  formatNotificationTime,
  getNotificationHref,
  getNotificationTypeLabel,
  normalizeNotification,
} from "@/lib/notifications";

export default function AdminNotificationsPage() {
  const router = useRouter();
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeNotificationId, setActiveNotificationId] = useState(null);

  const fetchNotifications = React.useCallback(async () => {
    try {
      const res = await api.get("/notifications?role=admin");
      setNotifications(Array.isArray(res.data) ? res.data.map(normalizeNotification) : []);
    } catch (error) {
      console.error("Failed to fetch admin notifications", error);
    } finally {
      setLoading(false);
    }
  }, []);

  const { socket } = useSocket();

  React.useEffect(() => {
    fetchNotifications();

    if (!socket) return;

    const handleNewNotification = (incoming) => {
      if (incoming.targetRole === 'admin') {
        const next = normalizeNotification(incoming);
        setNotifications((prev) => [next, ...prev.filter((n) => n.id !== next.id)]);
      }
    };

    socket.on('new_notification', handleNewNotification);

    return () => {
      socket.off('new_notification', handleNewNotification);
    };
  }, [fetchNotifications, socket]);

  const markAllAsRead = async () => {
    if (!notifications.some(n => !n.read)) return;

    setNotifications((prev) => prev.map((notification) => ({ ...notification, read: true })));
    try {
      await api.put("/notifications/read-all?role=admin");
    } catch (error) {
      console.error("Failed to mark notifications as read", error);
      fetchNotifications();
    }
  };

  const handleNotificationClick = async (notification) => {
    const href = notification.link || getNotificationHref(notification, 'admin');

    if (!notification.read) {
      setActiveNotificationId(notification.id);
      setNotifications((prev) =>
        prev.map((item) => (item.id === notification.id ? { ...item, read: true } : item))
      );

      try {
        await api.put(`/notifications/${notification.id}/read`);
      } catch (error) {
        console.error("Failed to mark notification as read", error);
      } finally {
        setActiveNotificationId(null);
      }
    }

    if (href) {
      router.push(href);
    }
  };

  const unreadCount = notifications.filter(n => !n.read).length;

  const renderNotificationIcon = (notification) => {
    const title = (notification.title || '').toLowerCase();
    if (title.includes('product')) {
      return (
        <div className="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-[#F4A8A3] bg-[#FEF2F1] text-[#B94232]">
          <Package strokeWidth={2} className="w-5 h-5" />
        </div>
      );
    }
    if (title.includes('seller')) {
      return (
        <div className="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border border-[#DBD4CC] bg-[#F8F5F1] text-[#2A2A2A]">
          <Store strokeWidth={2} className="w-5 h-5" />
        </div>
      );
    }
    return (
      <div className="w-12 h-12 rounded-[1.2rem] flex items-center justify-center shrink-0 border border-[#D1D5DB] bg-[#F3F4F6] text-[#374151]">
        <Shield strokeWidth={2} className="w-5 h-5" />
      </div>
    );
  };

  return (
    <AdminLayout>
      <div className="max-w-[700px] mx-auto space-y-6 mb-20 px-4 md:px-0">
        
        <div className="flex flex-col md:flex-row md:items-start justify-between gap-6 pb-2">
          <div className="space-y-4">
             <div className="flex items-center gap-3">
               <span className="w-8 h-0.5 bg-[var(--rust)]"></span>
               <span className="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[var(--rust)]">NOTIFICATIONS</span>
             </div>
             <h1 className="font-serif text-2xl font-bold tracking-tight text-[var(--charcoal)]">
               System <span className="text-[var(--rust)] italic lowercase">Notifications</span>
             </h1>
             <p className="text-sm font-bold text-[var(--rust)]">{unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}</p>
          </div>
          <button 
            onClick={markAllAsRead} 
            className="self-start md:mt-4 flex items-center gap-2 px-5 py-2.5 bg-white border border-[var(--border)] rounded-xl text-xs font-bold text-[var(--muted)] hover:text-[#2A2A2A] hover:bg-[#FDFCFB] hover:shadow-sm transition-all shadow-sm"
          >
            <CheckCheck className="w-4 h-4" /> Mark All Read
          </button>
        </div>

        <div className="bg-white rounded-[2rem] shadow-sm overflow-hidden border border-[var(--border)] divide-y divide-[var(--border)] relative">
           <AnimatePresence initial={false}>
             {loading ? (
                <div className="p-24 text-center space-y-4">
                   <Loader2 className="w-10 h-10 text-[var(--muted)] mx-auto animate-spin opacity-50" />
                   <p className="text-sm text-[var(--muted)] italic">Loading notifications...</p>
                </div>
             ) : notifications.length === 0 ? (
                <div className="p-24 text-center space-y-4">
                   <Bell className="w-12 h-12 text-[var(--muted)] mx-auto opacity-20" />
                   <h3 className="font-serif text-xl font-bold text-[var(--charcoal)] opacity-80">No notifications</h3>
                   <p className="text-sm text-[var(--muted)] italic">You have no new notifications right now.</p>
                </div>
             ) : (
                notifications.map((notif, idx) => (
                  <motion.div 
                    key={notif.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, height: 0, overflow: 'hidden' }}
                    transition={{ delay: idx * 0.05 }}
                    className={`p-5 sm:p-6 flex flex-col sm:flex-row items-start gap-5 relative group transition-colors ${!notif.read ? 'bg-white' : 'bg-[#FAFAFA]'}`}
                  >
                    {!notif.read && (
                      <div className="absolute left-0 top-0 bottom-0 w-1 bg-[var(--rust)]" />
                    )}
                    
                    {renderNotificationIcon(notif)}

                    <div className="flex-1 w-full space-y-3 pt-0.5">
                      <div className="flex justify-between items-start">
                         <div className="text-[10px] font-bold text-[var(--muted)] opacity-80 uppercase tracking-[0.2em]">{getNotificationTypeLabel(notif)}</div>
                         <div className="flex items-center gap-3">
                            <span className="text-[10px] sm:text-[11px] font-medium text-[var(--muted)] opacity-70 flex items-center gap-1.5"><Clock className="w-3.5 h-3.5 opacity-50" /> {formatNotificationTime(notif.createdAt)}</span>
                            {!notif.read && (
                               <span className="text-[9px] font-bold text-[var(--rust)] border border-[#F4A8A3] bg-[#FEF2F1] px-2 py-0.5 rounded-md uppercase tracking-wider">NEW</span>
                            )}
                         </div>
                      </div>
                      
                      <div className="space-y-1.5">
                         <h3 className="text-lg font-bold text-[var(--charcoal)]">{notif.title}</h3>
                         <div className="flex">
                            <div className="w-0.5 bg-[var(--border)] rounded-full mr-3 shrink-0" />
                            <p className="text-[15px] font-medium text-[var(--muted)] leading-relaxed italic opacity-90">{notif.message}</p>
                         </div>
                      </div>
                      
                      <div className="pt-2 flex items-center gap-6">
                         <button onClick={() => handleNotificationClick(notif)} className="flex items-center gap-1 text-[11px] font-extrabold text-[var(--muted)] hover:text-[#2A2A2A] uppercase tracking-[0.1em] transition-colors group/view">
                            VIEW <ArrowRight className="w-3.5 h-3.5 group-hover/view:translate-x-1 transition-transform ml-1" />
                         </button>
                         {activeNotificationId === notif.id && <Loader2 className="w-4 h-4 animate-spin text-[var(--muted)]" />}
                      </div>
                    </div>
                  </motion.div>
                ))
             )}
           </AnimatePresence>
        </div>

      </div>
    </AdminLayout>
  );
}
