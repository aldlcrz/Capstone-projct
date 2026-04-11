const { server } = require('./app');
const sequelize = require('./config/db');
const models = require('./models');
const { DataTypes } = require('sequelize');

const PORT = process.env.PORT || 5000;

const ensureDatabaseIntegrity = async () => {
  const queryInterface = sequelize.getQueryInterface();
  const tables = await queryInterface.showAllTables();
  
  // Helper to safely describe table only if it exists
  const safeDescribeTable = async (tableName) => {
    // queryInterface.showAllTables() might return strings or objects depending on dialect
    const tableNames = tables.map(t => (typeof t === 'string' ? t : t.tableName).toLowerCase());
    if (tableNames.includes(tableName.toLowerCase())) {
      return await queryInterface.describeTable(tableName);
    }
    return null;
  };

  // 1. Check Users table
  const userTableName = models.User.getTableName();
  const userTable = await safeDescribeTable(userTableName);
  if (userTable) {
    const userColumns = {
      profilePhoto: { type: DataTypes.STRING, allowNull: true },
      indigencyCertificate: { type: DataTypes.STRING, allowNull: true },
      validId: { type: DataTypes.STRING, allowNull: true },
      mobileNumber: { type: DataTypes.STRING, allowNull: true },
      gcashNumber: { type: DataTypes.STRING, allowNull: true },
      gcashQrCode: { type: DataTypes.STRING, allowNull: true },
      facebookLink: { type: DataTypes.STRING, allowNull: true },
      instagramLink: { type: DataTypes.STRING, allowNull: true },
      isAdult: { type: DataTypes.BOOLEAN, defaultValue: false },
      fcmToken: { type: DataTypes.STRING, allowNull: true },
      followers: { type: DataTypes.JSON, allowNull: true },
      following: { type: DataTypes.JSON, allowNull: true },
      resetPasswordToken: { type: DataTypes.STRING, allowNull: true },
      resetPasswordExpires: { type: DataTypes.DATE, allowNull: true },
      passwordChangedAt: { type: DataTypes.DATE, allowNull: true },
      status: { type: DataTypes.ENUM('active', 'frozen', 'blocked'), defaultValue: 'active' }
    };

    for (const [columnName, definition] of Object.entries(userColumns)) {
      if (!Object.keys(userTable).some(k => k.toLowerCase() === columnName.toLowerCase())) {
        try {
          console.log(`Adding missing column: ${columnName} to Users table`);
          await queryInterface.addColumn(userTableName, columnName, definition);
        } catch (err) { /* Ignore duplicate column errors */ }
      }
    }
  }

  // 2. Check Addresses table
  const addressTableName = models.Address.getTableName();
  const addressTable = await safeDescribeTable(addressTableName);
  if (addressTable) {
    const addressColumns = {
      recipientName: { type: DataTypes.STRING, allowNull: true },
      phone: { type: DataTypes.STRING, allowNull: true },
      houseNo: { type: DataTypes.STRING, allowNull: true },
      barangay: { type: DataTypes.STRING, allowNull: true },
      province: { type: DataTypes.STRING, allowNull: true },
      latitude: { type: DataTypes.FLOAT, allowNull: true },
      longitude: { type: DataTypes.FLOAT, allowNull: true }
    };

    for (const [columnName, definition] of Object.entries(addressColumns)) {
      if (!Object.keys(addressTable).some(k => k.toLowerCase() === columnName.toLowerCase())) {
        try {
          console.log(`Adding missing column: ${columnName} to Addresses table`);
          await queryInterface.addColumn(addressTableName, columnName, definition);
        } catch (err) { /* Ignore duplicate column errors */ }
      }
    }
  }

  // 3. Check Products table for shipping fields
  const productTableName = models.Product.getTableName();
  const productTable = await safeDescribeTable(productTableName);
  if (productTable) {
    const productColumns = {
      categories: { type: DataTypes.JSON, defaultValue: ["Formal"], allowNull: true },
      sizes: { type: DataTypes.JSON, defaultValue: [], allowNull: true },
      image: { type: DataTypes.JSON, defaultValue: [], allowNull: true },
      views: { type: DataTypes.INTEGER, defaultValue: 0, allowNull: true },
      shippingFee: { type: DataTypes.DECIMAL(10, 2), defaultValue: 0, allowNull: true },
      shippingDays: { type: DataTypes.INTEGER, defaultValue: 3, allowNull: true }
    };

    for (const [columnName, definition] of Object.entries(productColumns)) {
      if (!Object.keys(productTable).some(k => k.toLowerCase() === columnName.toLowerCase())) {
        try {
          console.log(`Adding missing column: ${columnName} to Products table`);
          await queryInterface.addColumn(productTableName, columnName, definition);
        } catch (err) { /* Ignore duplicate column errors */ }
      }
    }
  }

  // 4. Check ProductViews table for analytics
  const viewTableName = models.ProductView.getTableName();
  const viewTable = await safeDescribeTable(viewTableName);
  if (viewTable) {
    const viewColumns = {
      ipAddress: { type: DataTypes.STRING, allowNull: true }
    };

    for (const [columnName, definition] of Object.entries(viewColumns)) {
      if (!Object.keys(viewTable).some(k => k.toLowerCase() === columnName.toLowerCase())) {
        try {
          console.log(`Adding missing column: ${columnName} to ProductViews table`);
          await queryInterface.addColumn(viewTableName, columnName, definition);
        } catch (err) { /* Ignore duplicate column errors */ }
      }
    }
  }

  // 5. Check Notifications table
  const notifTableName = models.Notification.getTableName();
  const notifTable = await safeDescribeTable(notifTableName);
  if (notifTable) {
    const notifColumns = {
      targetRole: { type: DataTypes.STRING, defaultValue: 'customer', allowNull: true }
    };

    for (const [columnName, definition] of Object.entries(notifColumns)) {
      if (!Object.keys(notifTable).some(k => k.toLowerCase() === columnName.toLowerCase())) {
        try {
          console.log(`Adding missing column: ${columnName} to Notifications table`);
          await queryInterface.addColumn(notifTableName, columnName, definition);
        } catch (err) { /* Ignore duplicate column errors */ }
      }
    }
  }
};

const ensureOrderStatusSupportsCancellation = async () => {
  const queryInterface = sequelize.getQueryInterface();
  await queryInterface.changeColumn(models.Order.getTableName(), 'status', {
    type: DataTypes.ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'),
    allowNull: false,
    defaultValue: 'Pending',
  });
};

const startServer = async () => {
  try {
    // Create database if it doesn't exist
    const mysql = require('mysql2/promise');
    const connection = await mysql.createConnection({
        host: process.env.DB_HOST || '127.0.0.1',
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASS || '',
    });
    await connection.query(`CREATE DATABASE IF NOT EXISTS \`${process.env.DB_NAME || 'lumbarong'}\`;`);
    await connection.end();
    console.log('✅ Database checking/creation complete');

    // Authenticate and sync the models
    await sequelize.authenticate();
    console.log('Database connected successfully');

    // Ensure schema is complete (handles missing columns from previous versions)
    // Runs BEFORE sync to ensure columns exist before indexes are created
    await ensureDatabaseIntegrity();

    // { force: false } ensures we don't wipe the DB on every restart
    await sequelize.sync({ force: false });
    
    await ensureOrderStatusSupportsCancellation();
    
    console.log('Database synced successfully');

    const initCategories = require('./utils/initCategories');
    await initCategories();

    server.listen(PORT, () => {
      console.log(`Server is running on port ${PORT}`);
    });
  } catch (error) {
    if (error.code === 'ECONNREFUSED') {
      console.error('\n❌ DATABASE CONNECTION ERROR:');
      console.error('Could not connect to MySQL. Please ensure your XAMPP MySQL is STARTED.');
      console.error('If you changed the port, update the DB_HOST or add a DB_PORT in your .env file.\n');
    } else {
      console.error('Unable to start the server:', error);
    }
    process.exit(1);
  }
};

startServer();
