"use client";

import React, { createContext, useContext, useEffect, useState } from 'react';
import { io } from 'socket.io-client';
import { BACKEND_URL } from '@/lib/api';
import { initializePushNotifications } from '@/lib/pushNotifications';

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

  useEffect(() => {
    const socketInstance = io(BACKEND_URL, {
      withCredentials: true,
      transports: ['websocket', 'polling'],
    });

    // Initialize Push Notifications for Native Mobile
    initializePushNotifications().catch(console.error);

    socketInstance.on('connect', () => {
      console.log('Socket connected:', socketInstance.id);
      setIsConnected(true);

      // Join user-specific room if logged in
      try {
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (user && user.id) {
          socketInstance.emit('join_room', `user_${user.id}`);
          console.log(`Joined user room: user_${user.id}`);
          
          if (user.role === 'admin') {
            socketInstance.emit('join_room', 'admin');
            console.log('Joined admin room');
          }
        }
      } catch (e) {
        console.error('Failed to parse user for socket room joining', e);
      }
    });

    socketInstance.on('disconnect', () => {
      console.log('Socket disconnected');
      setIsConnected(false);
    });

    setSocket(socketInstance);

    return () => {
      socketInstance.disconnect();
    };
  }, []);

  const value = React.useMemo(() => ({ socket, isConnected }), [socket, isConnected]);
  
  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  );
};
