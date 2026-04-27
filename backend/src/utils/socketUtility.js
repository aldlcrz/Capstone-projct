let io = null;

const SOCKET_EVENTS = {
  USER_UPDATED: 'user_updated',
  ORDER_CREATED: 'order_created',
  ORDER_UPDATED: 'order_updated',
  DASHBOARD_UPDATE: 'dashboard_update',
  INVENTORY_UPDATED: 'inventory_updated',
  REVIEW_UPDATED: 'review_updated',
  STATS_UPDATE: 'stats_update',
  NOTIFICATION_COUNT_UPDATE: 'notification_count_update',
  NEW_NOTIFICATION: 'new_notification',
  SETTINGS_UPDATED: 'settings_updated',
  FORCE_LOGOUT: 'force_logout'
};

const init = (ioInstance) => {
  io = ioInstance;
};

const configureSocketServer = (ioServer) => {
  init(ioServer);
  ioServer.on('connection', (socket) => {
    console.log(`Socket connected: ${socket.id}`);
    
    // Auth could be handled here or via middleware
    const token = socket.handshake.auth?.token;
    if (token) {
        try {
            const jwt = require('jsonwebtoken');
            const decoded = jwt.verify(token, process.env.JWT_SECRET || 'lumbarong_secret_key_2026');
            if (decoded && decoded.id) {
                // Join personal room based on user role and id
                socket.join(`user_${decoded.id}`);
                if (decoded.role === 'admin') {
                    socket.join('admin');
                }
            }
        } catch(e) {
            console.error('Socket auth error:', e);
        }
    }

    socket.on('disconnect', () => {
      console.log(`Socket disconnected: ${socket.id}`);
    });

    socket.on('join_room', (roomName) => {
      // Allows fallback join mechanism if handshakes aren't supplying tokens from frontend
      if (roomName) {
         socket.join(roomName);
         console.log(`Socket ${socket.id} joined room: ${roomName}`);
      }
    });

    // Token Validation Middleware for all incoming packets
    socket.use(async ([event, ...args], next) => {
      const currentToken = socket.handshake.auth?.token;
      if (currentToken) {
        try {
          const jwt = require('jsonwebtoken');
          jwt.verify(currentToken, process.env.JWT_SECRET || 'lumbarong_secret_key_2026');
        } catch (e) {
          console.error(`Socket ${socket.id} token expired, disconnecting.`);
          socket.disconnect(true);
          return next(new Error('Authentication expired'));
        }
      }
      next();
    });

    // Typing Indicators
    socket.on('typing', (data) => {
      // data: { receiverId, isTyping }
      if (data.receiverId) {
        ioServer.to(`user_${data.receiverId}`).emit('typing_status', {
          senderId: socket.user?.id,
          isTyping: data.isTyping
        });
      }
    });
  });
};

const emit = (event, data, room = null) => {
  if (!io) {
    console.warn('SocketUtility: io instance not initialized.');
    return;
  }
  if (room) {
    io.to(room).emit(event, data);
  } else {
    io.emit(event, data);
  }
};

const emitToUser = (userId, event, data) => {
  emit(event, data, `user_${userId}`);
};

const emitNotificationReceived = (notification) => {
  emitToUser(notification.userId, 'new_notification', notification);
};

const emitNotificationCountUpdated = (userId, count) => {
  emitToUser(userId, 'notification_count_update', { userId, unreadCount: count });
};

const emitInventoryUpdated = (product, metadata = {}) => {
  emit('inventory_updated', { product, ...metadata });
  // REAL-TIME: Update admin dashboard stats (liveProducts count)
  emitStatsUpdate({ type: 'inventory', productId: product.id, sellerId: product.sellerId });
};

const emitOrderCreated = (order) => {
  emitToUser(order.customerId, 'order_created', order);
  emitToUser(order.sellerId, 'new_order', order);
  emit('order_created', order, 'admin'); // Correctly target the 'admin' room
  emitStatsUpdate({ type: 'order', sellerId: order.sellerId });
};

const emitOrderUpdated = (order, metadata = {}) => {
  emitToUser(order.customerId, 'order_status_update', { orderId: order.id, status: order.status, ...metadata });
  emitToUser(order.sellerId, 'order_status_update', { orderId: order.id, status: order.status, ...metadata });
  emit('order_updated', { orderId: order.id, status: order.status, ...metadata }, 'admin'); // Correctly target the 'admin' room
  emitStatsUpdate({ type: 'order_status', sellerId: order.sellerId });
};

const emitUserUpdated = (user, metadata = {}) => {
  emitToUser(user.id, 'user_updated', { user, ...metadata });
  emit('user_updated', { user, ...metadata }, 'admin'); // Correctly target the 'admin' room
  emitStatsUpdate({ type: 'user' });
};

const emitDashboardUpdate = () => {
  emit('dashboard_update', { timestamp: new Date() });
  emitStatsUpdate({ type: 'dashboard' });
};

const emitStatsUpdate = (metadata = {}) => {
  emit('stats_update', { timestamp: new Date(), ...metadata });
};

const broadcast = (message, title = 'System Broadcast') => {
  emit('broadcast_message', { 
    title, 
    message, 
    timestamp: new Date(),
    type: 'system'
  });
};

const emitSettingsUpdated = (settings) => {
  emit(SOCKET_EVENTS.SETTINGS_UPDATED, settings);
};

/**
 * Push a force_logout event directly to a user's private socket room.
 * The frontend SocketContext listens for this and immediately clears
 * the session + redirects to /login without waiting for the next API call.
 */
const emitForceLogout = (userId, status, reason) => {
  emitToUser(userId, SOCKET_EVENTS.FORCE_LOGOUT, {
    status,
    reason: reason || null,
    message: status === 'blocked' ? 'Account Terminated' : 'Account Frozen',
  });
};

module.exports = {
  init,
  emit,
  emitToUser,
  emitNotificationReceived,
  emitNotificationCountUpdated,
  emitInventoryUpdated,
  emitOrderCreated,
  emitOrderUpdated,
  emitUserUpdated,
  emitDashboardUpdate,
  emitStatsUpdate,
  emitSettingsUpdated,
  emitForceLogout,
  broadcast,
  SOCKET_EVENTS,
  configureSocketServer
};
