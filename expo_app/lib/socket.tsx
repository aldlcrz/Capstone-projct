/**
 * LumBarong Socket.IO Context — Mobile
 * Mirrors the web SocketContext.jsx, adapted for React Native (no window/DOM).
 */
import React, { createContext, useContext, useEffect, useState, useCallback, useMemo, useRef } from 'react';
import { io, Socket } from 'socket.io-client';
import { useAuthStore } from '@/store/authStore';
import { api } from '@/lib/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

interface Broadcast {
  id: number;
  title: string;
  message: string;
  timestamp: Date;
  type: string;
}

interface SocketContextValue {
  socket: Socket | null;
  isConnected: boolean;
  currentBroadcast: Broadcast | null;
  unreadCount: number;
  setUnreadCount: (count: number) => void;
  refreshUnreadCount: () => Promise<void>;
  clearBroadcast: () => void;
}

const SocketContext = createContext<SocketContextValue | null>(null);

export const useSocket = () => {
  const ctx = useContext(SocketContext);
  if (!ctx) throw new Error('useSocket must be used within SocketProvider');
  return ctx;
};

export const SocketProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [socket, setSocket] = useState<Socket | null>(null);
  const [isConnected, setIsConnected] = useState(false);
  const [currentBroadcast, setCurrentBroadcast] = useState<Broadcast | null>(null);
  const [unreadCount, setUnreadCount] = useState(0);
  const processedEventIds = useRef<Set<string>>(new Set());

  const { user, role, token, logout } = useAuthStore();

  const clearBroadcast = useCallback(() => setCurrentBroadcast(null), []);

  const refreshUnreadCount = useCallback(async () => {
    if (!token || !role) { setUnreadCount(0); return; }
    try {
      const res = await api.get('/notifications/unread-count', { params: { role } });
      setUnreadCount(Number(res.data?.unreadCount || 0));
    } catch {
      setUnreadCount(0);
    }
  }, [token, role]);

  useEffect(() => {
    if (!token || !user) {
      if (socket) { socket.disconnect(); setSocket(null); }
      return;
    }

    const connectSocket = async () => {
      const ip   = await AsyncStorage.getItem('backend_ip')   || '192.168.100.5';
      const port = await AsyncStorage.getItem('backend_port') || '5000';
      const backendUrl = `http://${ip}:${port}`;

      const socketInstance = io(backendUrl, {
        transports: ['websocket', 'polling'],
        auth: { token },
        reconnection: true,
        reconnectionDelay: 2000,
      });

      socketInstance.on('connect', () => {
        setIsConnected(true);
        refreshUnreadCount();

        // Join user-specific room
        if (user?.id) {
          socketInstance.emit('join_room', `user_${user.id}`);
          if (role === 'admin') socketInstance.emit('join_room', 'admin');
        }
      });

      socketInstance.on('disconnect', () => setIsConnected(false));

      socketInstance.on('notification_count_update', (data: any) => {
        if (typeof data?.unreadCount === 'number') {
          setUnreadCount(data.unreadCount);
        } else {
          refreshUnreadCount();
        }
      });

      socketInstance.on('broadcast_message', (data: any) => {
        setCurrentBroadcast({
          id: Date.now(),
          title: data.title || 'System Broadcast',
          message: data.message,
          timestamp: data.timestamp || new Date(),
          type: data.type || 'system',
        });
      });

      socketInstance.on('force_logout', async (data: any) => {
        console.warn('Force logout received:', data);
        await logout();
      });

      setSocket(socketInstance);
    };

    connectSocket();

    return () => {
      socket?.disconnect();
    };
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token, user?.id, role]);

  const value = useMemo(() => ({
    socket,
    isConnected,
    currentBroadcast,
    unreadCount,
    setUnreadCount,
    refreshUnreadCount,
    clearBroadcast,
  }), [socket, isConnected, currentBroadcast, unreadCount, refreshUnreadCount, clearBroadcast]);

  return <SocketContext.Provider value={value}>{children}</SocketContext.Provider>;
};
