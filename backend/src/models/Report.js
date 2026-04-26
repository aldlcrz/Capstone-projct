const { DataTypes } = require('sequelize');
const sequelize = require('../config/db');

const Report = sequelize.define('Report', {
  id: {
    type: DataTypes.UUID,
    defaultValue: DataTypes.UUIDV4,
    primaryKey: true,
  },
  reporterId: {
    type: DataTypes.UUID,
    allowNull: false,
  },
  reportedId: {
    type: DataTypes.UUID,
    allowNull: false,
  },
  type: {
    type: DataTypes.ENUM('CustomerReportingSeller', 'SellerReportingCustomer'),
    allowNull: false,
  },
  referenceId: {
    type: DataTypes.UUID,
    allowNull: true,
  },
  reason: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  description: {
    type: DataTypes.TEXT,
    allowNull: false,
  },
  evidence: {
    type: DataTypes.TEXT, // Stored as a JSON string of URLs
    allowNull: true,
  },
  status: {
    type: DataTypes.ENUM('Pending', 'In Review', 'Resolved', 'Dismissed'),
    defaultValue: 'Pending',
  },
  adminNotes: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  actionTaken: {
    type: DataTypes.ENUM('None', 'Warning', 'Restricted', 'Suspended'),
    allowNull: true,
  }
}, {
  timestamps: true,
});

module.exports = Report;
