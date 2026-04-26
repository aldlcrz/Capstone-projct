const { DataTypes } = require('sequelize');
const sequelize = require('../config/db');

const RefundRequest = sequelize.define('RefundRequest', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true,
  },
  orderId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Orders',
      key: 'id',
    },
  },
  orderItemId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'OrderItems',
      key: 'id',
    },
  },
  customerId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Users',
      key: 'id',
    },
  },
  sellerId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Users',
      key: 'id',
    },
  },
  reason: {
    type: DataTypes.ENUM('Damaged Item', 'Wrong Size', 'Other'),
    allowNull: false,
  },
  message: {
    type: DataTypes.TEXT,
    allowNull: true, // Required only if reason is 'Other'
  },
  videoProof: {
    type: DataTypes.STRING, // URL to the uploaded video
    allowNull: false,
  },
  status: {
    type: DataTypes.ENUM('Pending', 'Approved', 'Rejected', 'Resolved'),
    defaultValue: 'Pending',
  },
  sellerComment: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
}, {
  timestamps: true,
});

module.exports = RefundRequest;
