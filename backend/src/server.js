const { server } = require('./app');
const sequelize = require('./config/db');
const models = require('./models');
const { DataTypes } = require('sequelize');

const PORT = process.env.PORT || 5000;

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

    // { force: false } ensures we don't wipe the DB on every restart
    // Disable alter: true to prevent duplicate index issues. 
    // Use migrations for production or manual schema changes if needed.
    await sequelize.sync();

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