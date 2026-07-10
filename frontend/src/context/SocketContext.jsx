"use client";

import React, { createContext, useContext, useEffect, useState } from 'react';
import Pusher from 'pusher-js';
import { usePathname } from 'next/navigation';
import { api, BACKEND_URL, resolveRoleFromPath, getStoredUserForRole, getTokenForRole, removeSessionKeys } from '@/lib/api';

const SocketContext = createContext(null);

export const useSocket = () => {
  const context = useContext(SocketContext);
  if (!context) {
    throw new Error('useSocket must be used within a SocketProvider');
  }
  return context;
};

class PusherSocketWrapper {
  constructor(pusher, activeRole, user) {
    this.pusher = pusher;
    this.activeRole = activeRole;
    this.user = user;
    this.listeners = {};
    this.subscribedChannels = {};

    this.subscribeToChannel('public');
    if (this.user && this.user.id) {
      this.subscribeToChannel(`user_${this.user.id}`);
      if (this.activeRole === 'admin') {
        this.subscribeToChannel('admin');
      }
    }
  }

  getChannelsForEvent(eventName) {
    const userEvents = new Set([
      'new_notification',
      'notification_count_update',
      'force_logout',
      'order_created',
      'new_order',
      'order_status_update',
      'user_updated',
      'receive_message',
      'typing_status'
    ]);
    const adminEvents = new Set([
      'order_created',
      'order_updated',
      'user_updated',
      'dashboard_update'
    ]);

    const channels = ['public'];
    if (userEvents.has(eventName) && this.user?.id) {
      channels.push(`user_${this.user.id}`);
    }
    if (adminEvents.has(eventName) && this.activeRole === 'admin') {
      channels.push('admin');
    }
    return channels;
  }

  subscribeToChannel(channelName) {
    if (this.subscribedChannels[channelName]) return;

    const channel = this.pusher.subscribe(channelName);
    this.subscribedChannels[channelName] = channel;

    const knownEvents = [
      'new_notification', 'notification_count_update', 'broadcast_message', 'force_logout',
      'settings_updated', 'review_updated', 'inventory_updated', 'stats_update',
      'order_created', 'new_order', 'order_status_update', 'user_updated', 'dashboard_update',
      'receive_message', 'typing_status', 'order_updated'
    ];

    knownEvents.forEach(evt => {
      channel.bind(evt, (data) => {
        if (this.listeners[evt]) {
          this.listeners[evt].forEach(handler => {
            try {
              handler(data);
            } catch (err) {
              console.error(`Error in event listener for ${evt}:`, err);
            }
          });
        }
      });
    });
  }

  on(eventName, handler) {
    if (!this.listeners[eventName]) {
      this.listeners[eventName] = [];
    }
    this.listeners[eventName].push(handler);

    const channels = this.getChannelsForEvent(eventName);
    channels.forEach(ch => this.subscribeToChannel(ch));
  }

  off(eventName, handler) {
    if (!this.listeners[eventName]) return;
    if (!handler) {
      delete this.listeners[eventName];
      return;
    }
    this.listeners[eventName] = this.listeners[eventName].filter(h => h !== handler);
  }

  emit(eventName, data) {
    console.log(`Mock socket emit: ${eventName}`, data);
  }

  disconnect() {
    this.pusher.disconnect();
    Object.keys(this.subscribedChannels).forEach(ch => {
      this.pusher.unsubscribe(ch);
    });
    this.subscribedChannels = {};
    this.listeners = {};
  }
}

export const SocketProvider = ({ children }) => {
  const [socket, setSocket] = useState(null);
  const [isConnected, setIsConnected] = useState(false);
  const [currentBroadcast, setCurrentBroadcast] = useState(null);
  const [unreadCount, setUnreadCount] = useState(0);
  const [processedEventIds] = useState(() => new Set());
  const [publicSettings, setPublicSettings] = useState({ maintenanceMode: false });

  const clearBroadcast = React.useCallback(() => setCurrentBroadcast(null), []);

  const isDuplicateEvent = React.useCallback((eventId) => {
    if (!eventId) return false;
    if (processedEventIds.has(eventId)) return true;
    
    processedEventIds.add(eventId);
    if (processedEventIds.size > 100) {
      const first = processedEventIds.values().next().value;
      processedEventIds.delete(first);
    }
    return false;
  }, [processedEventIds]);

  const pathname = usePathname();
  const activeRole = React.useMemo(() => resolveRoleFromPath(pathname || ''), [pathname]);

  const refreshUnreadCount = React.useCallback(async (role = activeRole) => {
    try {
      const token = getTokenForRole(role);

      if (!token || token === "null" || token === "undefined") {
        setUnreadCount(0);
        return;
      }

      const res = await api.get("/notifications/unread-count", { params: { role } });
      setUnreadCount(Number(res.data?.unreadCount || 0));
    } catch (error) {
      if (error?.response?.status === 401) {
        setUnreadCount(0);
        return;
      }
      console.warn("Unable to refresh unread notification count", error?.response?.data || error?.message || error);
    }
  }, [activeRole]);

  const fetchPublicSettings = React.useCallback(async () => {
    try {
      const res = await api.get("/admin/public-settings");
      setPublicSettings({
        maintenanceMode: res.data.maintenanceMode === true || res.data.maintenanceMode === "true"
      });
    } catch (e) {
      console.error("Failed to fetch public settings");
    }
  }, []);

  useEffect(() => {
    const { defineCustomElements } = require('@ionic/pwa-elements/loader');
    defineCustomElements(window);
  }, []);

  useEffect(() => {
    const PUSHER_KEY = process.env.NEXT_PUBLIC_PUSHER_APP_KEY || 'lumbarong_key';
    const PUSHER_CLUSTER = process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER || 'mt1';

    const options = {
      cluster: PUSHER_CLUSTER,
      forceTLS: false,
    };

    if (BACKEND_URL.includes('localhost') || BACKEND_URL.includes('127.0.0.1')) {
      options.wsHost = '127.0.0.1';
      options.wsPort = 6001;
      options.wssPort = 6001;
      options.enabledTransports = ['ws', 'wss'];
    } else {
      options.forceTLS = true;
    }

    const pusherInstance = new Pusher(PUSHER_KEY, options);
    
    pusherInstance.connection.bind('connected', () => {
      console.log('Pusher connected. Cluster:', PUSHER_CLUSTER, 'Role:', activeRole);
      setIsConnected(true);
      refreshUnreadCount(activeRole);
    });

    pusherInstance.connection.bind('disconnected', () => {
      console.log('Pusher disconnected');
      setIsConnected(false);
    });

    const user = getStoredUserForRole(activeRole);
    const wrapperInstance = new PusherSocketWrapper(pusherInstance, activeRole, user);

    wrapperInstance.on('notification_count_update', (data) => {
      if (data && typeof data.unreadCount === 'number') {
        setUnreadCount(data.unreadCount);
        return;
      }
      refreshUnreadCount(activeRole);
    });

    wrapperInstance.on('broadcast_message', (data) => {
      setCurrentBroadcast({
        id: Date.now(),
        title: data.title || 'System Broadcast',
        message: data.message,
        timestamp: data.timestamp || new Date(),
        type: data.type || 'system'
      });
    });

    wrapperInstance.on('force_logout', (data) => {
      console.warn('FORCE LOGOUT RECEIVED:', data);
      if (typeof window !== "undefined") {
        try {
          sessionStorage.setItem(
            "lumbarong_account_restriction",
            JSON.stringify({
              status: data.status,
              reason: data.reason,
              message: data.message
            })
          );
        } catch (_) {}

        const role = resolveRoleFromPath(window.location.pathname);
        removeSessionKeys(role);
        window.location.replace("/login");
      }
    });

    wrapperInstance.on('settings_updated', (data) => {
      if (data.maintenanceMode !== undefined) {
        setPublicSettings(prev => ({
          ...prev,
          maintenanceMode: data.maintenanceMode === true || data.maintenanceMode === "true"
        }));
      }
    });

    queueMicrotask(() => {
      setSocket(wrapperInstance);
    });

    return () => {
      wrapperInstance.disconnect();
    };
  }, [activeRole, refreshUnreadCount]);

  useEffect(() => {
    queueMicrotask(() => {
      fetchPublicSettings();
    });
  }, [fetchPublicSettings]);

  const value = React.useMemo(() => ({ 
    socket, 
    isConnected, 
    currentBroadcast, 
    unreadCount,
    setUnreadCount,
    refreshUnreadCount,
    clearBroadcast,
    isDuplicateEvent,
    publicSettings,
    fetchPublicSettings
  }), [socket, isConnected, currentBroadcast, unreadCount, refreshUnreadCount, clearBroadcast, isDuplicateEvent, publicSettings, fetchPublicSettings]);
  
  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  );
};
