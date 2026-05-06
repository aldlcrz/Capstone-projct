const { DataTypes } = require('sequelize');
const sequelize = require('../config/db');

const Order = sequelize.define('Order', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true,
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
  totalAmount: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false,
  },
  status: {
    type: DataTypes.ENUM('pending', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'),
    defaultValue: 'pending',
  },
  paymentMethod: {
    type: DataTypes.STRING,
    defaultValue: 'GCash',
  },
  paymentReference: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  paymentProof: {
    type: DataTypes.STRING,
    allowNull: true, // URL to screenshot proof
  },
  paymentStatus: {
    type: DataTypes.ENUM('pending', 'paid', 'failed'),
    defaultValue: 'pending',
  },
  shippingAddress: {
    type: DataTypes.JSON, // {name, street, city, postalCode}
    allowNull: false,
  },
  cancellationReason: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
}, {
  timestamps: true,
  indexes: [
    {
      fields: ['customerId']
    },
    {
      fields: ['sellerId']
    }
  ]
});

module.exports = Order;
