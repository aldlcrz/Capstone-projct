const { app } = require('./src/app');
const sequelize = require('./src/config/database');
const http = require('http');
const { Server } = require('socket.io');
const { configureSocketServer } = require('./src/utils/socketUtility');

// Import ALL models so Sequelize knows about them before sync
require('./src/models/User');
require('./src/models/Product');
require('./src/models/Order');
require('./src/models/Message');
require('./src/models/Notification');
require('./src/models/Address');
require('./src/models/SystemSetting');

const server = http.createServer(app);
const allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:5000',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:3001',
    'http://127.0.0.1:5000',
    'http://172.28.167.168:3000',
    'http://172.28.167.168:3001',
    'http://172.28.167.168:5000',
].filter(Boolean);
const io = new Server(server, {
    cors: {
        origin: allowedOrigins.length ? allowedOrigins : true,
        methods: ['GET', 'POST'],
        credentials: true
    }
});

// Make io accessible in controllers
app.set('io', io);
configureSocketServer(io);

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
        console.log(`✅ Database checking/creation complete`);

        await sequelize.authenticate();
        console.log('✅ Connected to MySQL via Sequelize');

        await sequelize.sync();
        console.log('✅ Database synchronized');

        const initCategories = require('./src/utils/initCategories');
        await initCategories();
    } catch (err) {
        console.error('❌ Database connection/sync error:', err);
        console.log('⚠️ Server will continue to run, but DB-dependent features will fail.');
    }

    server.listen(PORT, () => {
        console.log(`🚀 Server is running on port ${PORT}`);
        console.log(`📡 API Health Check: http://localhost:${PORT}/api/v1/health`);
    });
};

startServer();
