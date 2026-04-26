"use client";

import React, { createContext, useContext, useEffect, useState } from 'react';
import { io } from 'socket.io-client';
import { usePathname } from 'next/navigation';
import { api, BACKEND_URL, resolveRoleFromPath, getStoredUserForRole, getTokenForRole } from '@/lib/api';
const SocketContext = createContext(null);

export const useSocket = () => {
  const context = useContext(SocketContext);
  if (!context) {
    throw new Error('useSocket must be used within a SocketProvider');
  }
  return context;
};

export const SocketProvider = ({ children }) => {
  const [socket, setSocket] = useState(null);
  const [isConnected, setIsConnected] = useState(false);
  const [currentBroadcast, setCurrentBroadcast] = useState(null);
  const [unreadCount, setUnreadCount] = useState(0);
  const [processedEventIds] = useState(() => new Set());

  const clearBroadcast = React.useCallback(() => setCurrentBroadcast(null), []);

  const isDuplicateEvent = React.useCallback((eventId) => {
    if (!eventId) return false;
    if (processedEventIds.has(eventId)) return true;
    
    processedEventIds.add(eventId);
    // Keep the set size manageable
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

  useEffect(() => {
    const { defineCustomElements } = require('@ionic/pwa-elements/loader');
    defineCustomElements(window);
  }, []);

  useEffect(() => {
    const getToken = () => {
      try {
        return getTokenForRole(activeRole);
      } catch (e) {
        return null;
      }
    };

    const socketInstance = io(BACKEND_URL, {
      withCredentials: true,
      transports: ['websocket', 'polling'],
      auth: { token: getToken() }
    });

    socketInstance.on('connect', () => {
      console.log('Socket connected:', socketInstance.id, 'Role:', activeRole);
      setIsConnected(true);
      refreshUnreadCount(activeRole);

      // Strictly role-specific room joining — only join rooms for the ACTIVE role
      try {
        const user = getStoredUserForRole(activeRole);
        
        if (user && user.id) {
          socketInstance.emit('join_room', `user_${user.id}`);
          console.log(`Joined active user room: user_${user.id}`);
          
          if (activeRole === 'admin') {
            socketInstance.emit('join_room', 'admin');
            console.log('Joined admin group room');
          }
        }
      } catch (e) {
        console.error('Failed to parse user for socket room joining', e);
      }
    });

    socketInstance.io.on('reconnect', () => {
      console.log('Socket reconnected:', socketInstance.id);
      socketInstance.auth = { token: getToken() };
    });

    socketInstance.on('disconnect', () => {
      console.log('Socket disconnected');
      setIsConnected(false);
    });

    socketInstance.on('notification_count_update', (data) => {
      if (data && typeof data.unreadCount === 'number') {
        setUnreadCount(data.unreadCount);
        return;
      }

      refreshUnreadCount(activeRole);
    });

    socketInstance.on('broadcast_message', (data) => {
      setCurrentBroadcast({
        id: Date.now(),
        title: data.title || 'System Broadcast',
        message: data.message,
        timestamp: data.timestamp || new Date(),
        type: data.type || 'system'
      });
    });

    queueMicrotask(() => {
      setSocket(socketInstance);
    });

    return () => {
      socketInstance.disconnect();
    };
  }, [activeRole, refreshUnreadCount]);

  const value = React.useMemo(() => ({ 
    socket, 
    isConnected, 
    currentBroadcast, 
    unreadCount,
    setUnreadCount,
    refreshUnreadCount,
    clearBroadcast,
    isDuplicateEvent
  }), [socket, isConnected, currentBroadcast, unreadCount, refreshUnreadCount, clearBroadcast, isDuplicateEvent]);
  
  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  );
};
