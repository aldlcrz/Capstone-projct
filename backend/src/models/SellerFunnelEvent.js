const { DataTypes } = require('sequelize');
const sequelize = require('../config/db');

const SellerFunnelEvent = sequelize.define('SellerFunnelEvent', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true,
  },
  sellerId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Users',
      key: 'id',
    },
  },
  productId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Products',
      key: 'id',
    },
  },
  customerId: {
    type: DataTypes.UUID,
    allowNull: true,
    references: {
      model: 'Users',
      key: 'id',
    },
  },
  visitorSessionId: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  ipAddress: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  eventType: {
    type: DataTypes.ENUM('add_to_cart'),
    allowNull: false,
  },
}, {
  timestamps: true,
  updatedAt: false,
  indexes: [
    { fields: ['sellerId'] },
    { fields: ['productId'] },
    { fields: ['customerId'] },
    { fields: ['visitorSessionId'] },
    { fields: ['eventType'] },
    { fields: ['createdAt'] },
  ],
});

module.exports = SellerFunnelEvent;
