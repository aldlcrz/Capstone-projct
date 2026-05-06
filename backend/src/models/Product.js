const { DataTypes } = require('sequelize');
const sequelize = require('../config/db');

const Product = sequelize.define('Product', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true,
  },
  name: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  sku: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  fabric_type: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  collar_type: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  artisan_region: {
    type: DataTypes.STRING,
    allowNull: true,
  },
  price: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: false,
  },
  costPerPiece: {
    type: DataTypes.DECIMAL(10, 2),
    allowNull: true,
    defaultValue: 0,
  },
  sizes: {
    type: DataTypes.JSON, // Array of available sizes
    defaultValue: [],
  },
  categories: {
    type: DataTypes.JSON, // Array of Heritage Sectors
    defaultValue: ["Formal"],
  },
  image: {
    type: DataTypes.JSON, // Can store array of image URLs
    defaultValue: [],
  },
  stock: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
  shippingFee: {
    type: DataTypes.DECIMAL(10, 2),
    defaultValue: 0,
  },
  shippingDays: {
    type: DataTypes.INTEGER,
    defaultValue: 3,
  },
  sellerId: {
    type: DataTypes.UUID,
    allowNull: false,
    references: {
      model: 'Users',
      key: 'id',
    },
  },
  status: {
    type: DataTypes.ENUM('pending', 'approved', 'rejected'),
    defaultValue: 'pending',
  },
  rejectionReason: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  views: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
}, {
  timestamps: true,
});

module.exports = Product;
