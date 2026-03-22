const { Notification } = require('../models');
const { sendNotification } = require('../utils/notificationHelper');
const { emitNotificationCountUpdated } = require('../utils/socketUtility');

exports.getMyNotifications = async (req, res) => {
  try {
    const userId = req.user.id;
    const notifications = await Notification.findAll({
      where: { userId },
      order: [['createdAt', 'DESC']],
    });
    res.status(200).json(notifications);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.markAsRead = async (req, res) => {
  try {
    const { id } = req.params;
    const notification = await Notification.findByPk(id);
    if (!notification) return res.status(404).json({ message: 'Notification not found' });
    if (String(notification.userId) !== String(req.user.id)) {
      return res.status(403).json({ message: 'You can only update your own notifications' });
    }

    notification.read = true;
    await notification.save();

    const unreadCount = await Notification.count({
      where: { userId: req.user.id, read: false },
    });
    emitNotificationCountUpdated(req.user.id, unreadCount);

    res.status(200).json(notification);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.createPushNotification = async (userId, title, message, type = 'general', link = null) => {
  try {
    return await sendNotification(userId, title, message, type, link);
  } catch (error) {
    console.error('Failed to create notification:', error);
    return null;
  }
};
