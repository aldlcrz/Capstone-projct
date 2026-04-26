const Notification = require('../models/Notification');
const User = require('../models/User');
const socketUtility = require('./socketUtility');
const pushHelper = require('./pushHelper');

const sendNotification = async (userId, title, message, type = 'system', link = null, targetRole = 'customer') => {
    try {
        const resolvedTitle = title || 'Notification';
        const resolvedMessage = message || title || '';
        const notification = await Notification.create({
            userId,
            title: resolvedTitle,
            message: resolvedMessage,
            type,
            link,
            read: false,
            targetRole,
        });

        // Real-time emission using centralized utility
        socketUtility.emitToUser(userId, 'new_notification', notification);
        const unreadCount = await Notification.count({
            where: { userId, read: false },
        });
        socketUtility.emitNotificationCountUpdated(userId, unreadCount);

        // Native Push Notification
        const user = await User.findByPk(userId);
        if (user && user.fcmToken) {
            pushHelper.sendPush(user.fcmToken, resolvedTitle, resolvedMessage, { type, link });
        }

        console.log(`Notification sent to ${userId}: ${resolvedTitle}`);
        return notification;
    } catch (error) {
        console.error('Error sending notification:', error);
        return null;
    }
};

const notifyAdmins = async (title, message, type = 'system', link = null) => {
    try {
        const admins = await User.findAll({ where: { role: 'admin' } });
        for (const admin of admins) {
            await sendNotification(admin.id, title, message, type, link, 'admin');
        }
    } catch (error) {
        console.error('Error notifying admins:', error);
    }
};

module.exports = { sendNotification, notifyAdmins };
