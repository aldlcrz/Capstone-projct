const { DataTypes } = require('sequelize');
const sequelize = require('../config/db');

const Notification = sequelize.define('Notification', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true,
  },
  userId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Users',
      key: 'id',
    },
  },
  title: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  message: {
    type: DataTypes.TEXT,
    allowNull: false,
  },
  type: {
    type: DataTypes.ENUM('product_approved', 'product_rejected', 'order_update', 'account_verified', 'new_message', 'system'),
    defaultValue: 'system',
  },
  link: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  isRead: {
    type: DataTypes.BOOLEAN,
    defaultValue: false,
  },
  targetRole: {
    type: DataTypes.STRING, // 'customer' or 'seller'
    defaultValue: 'customer',
  },
}, {
  timestamps: true,
  indexes: [
    {
      fields: ['userId', 'createdAt'],
    },
    {
      fields: ['userId', 'isRead'],
    },
    {
      fields: ['userId', 'targetRole', 'createdAt'],
    },
    {
      fields: ['userId', 'targetRole', 'isRead'],
    },
  ],
});

module.exports = Notification;
