const { Message, User } = require('../models');
const { Op } = require('sequelize');
const { sendNotification } = require('../utils/notificationHelper');
const { emitChatMessageReceived } = require('../utils/socketUtility');

exports.getChatThreads = async (req, res) => {
  try {
    const userId = req.user.id;
    // Find unique users that I've messaged or who messaged me
    const messages = await Message.findAll({
      where: {
        [Op.or]: [{ senderId: userId }, { receiverId: userId }]
      },
      attributes: ['senderId', 'receiverId'],
      group: ['senderId', 'receiverId']
    });

    const threadIds = new Set();
    messages.forEach(m => {
      if (m.senderId !== userId) threadIds.add(m.senderId);
      if (m.receiverId !== userId) threadIds.add(m.receiverId);
    });

    const threads = await User.findAll({
      where: { id: Array.from(threadIds) },
      attributes: ['id', 'name', 'role', 'profilePhoto']
    });

    res.status(200).json(threads);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.getMessages = async (req, res) => {
  try {
    const userId = req.user.id;
    const otherId = req.params.otherId;

    const chat = await Message.findAll({
      where: {
        [Op.or]: [
          { senderId: userId, receiverId: otherId },
          { senderId: otherId, receiverId: userId }
        ]
      },
      order: [['createdAt', 'ASC']]
    });

    res.status(200).json(chat);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.sendMessage = async (req, res) => {
  try {
    const { content } = req.body;
    const receiverId = req.body.receiverId || req.body.recipientId;
    const senderId = req.user.id;

    if (!receiverId || !content) {
      return res.status(400).json({ message: 'Receiver and content are required' });
    }

    const message = await Message.create({ senderId, receiverId, content });
    emitChatMessageReceived(message);

    const receiver = await User.findByPk(receiverId, {
      attributes: ['id', 'role'],
    });

    await sendNotification(
      receiverId,
      'New message',
      'You received a new message in your inbox.',
      'message',
      receiver?.role === 'seller' ? '/seller/messages' : '/messages'
    );

    res.status(201).json(message);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};
