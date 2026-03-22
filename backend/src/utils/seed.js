const { Sequelize } = require('sequelize');
const mysql = require('mysql2/promise');
const User = require('../models/User');
const { Product, ProductImage, ProductSize, ProductRating } = require('../models/Product');
const { Order, OrderItem, ReturnRequest } = require('../models/Order');
const Message = require('../models/Message');
const Notification = require('../models/Notification');
const bcrypt = require('bcryptjs');
require('dotenv').config();

const seedData = async () => {
    try {
        // First, connect to MySQL without a database to create it if it doesn't exist
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST,
            user: process.env.DB_USER,
            password: process.env.DB_PASS,
        });
        await connection.query(`CREATE DATABASE IF NOT EXISTS \`${process.env.DB_NAME}\`;`);
        await connection.end();

        const sequelize = require('../config/database');
        await sequelize.authenticate();
        console.log('Connected to MySQL via Sequelize for seeding...');

        // Force sync to clear and recreate tables
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 0');
        await sequelize.sync({ force: true });
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 1');
        console.log('Database tables recreated.');

        // Hash passwords
        const salt = await bcrypt.genSalt(10);
        const sellerPassword = await bcrypt.hash('seller123', salt);
        const adminPassword = await bcrypt.hash('admin123', salt);
        const customerPassword = await bcrypt.hash('customer123', salt);

        // Create a seller
        const seller = await User.create({
            name: 'Maria Clara',
            email: 'artisan@lumban.ph',
            password: sellerPassword,
            role: 'seller',
            shopName: 'Heritage Embroideries',
            shopDescription: 'Exquisite hand-embroidered Barongs from Lumban, Laguna.',
            gcashNumber: '0917-123-4567',
            isVerified: true
        });

        // Create an admin
        const admin = await User.create({
            name: 'Admin User',
            email: 'admin@lumbarong.ph',
            password: adminPassword,
            role: 'admin',
            isVerified: true
        });

        // Create a customer
        const customer = await User.create({
            name: 'Juan Dela Cruz',
            email: 'juan@example.ph',
            password: customerPassword,
            role: 'customer',
            isVerified: true
        });

        // Create products with associations — minimum 3 images each
        const product1 = await Product.create({
            name: 'Classic Piña Barong',
            description: 'Authentic Piña fabric with intricate hand embroidery.',
            price: 8500,
            stock: 5,
            category: 'Barong Tagalog',
            sellerId: seller.id
        });
        await ProductImage.bulkCreate([
            { url: 'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=800&q=80', ProductId: product1.id },
            { url: 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80', ProductId: product1.id },
            { url: 'https://images.unsplash.com/photo-1519222970733-f546218fa6d7?auto=format&fit=crop&w=800&q=80', ProductId: product1.id }
        ]);
        await ProductSize.bulkCreate([
            { size: 'M', ProductId: product1.id },
            { size: 'L', ProductId: product1.id },
            { size: 'XL', ProductId: product1.id }
        ]);

        const product2 = await Product.create({
            name: 'Modern Filipiniana Gown',
            description: 'Traditional silhouette with modern floral motifs.',
            price: 12000,
            stock: 3,
            category: 'Filipiniana Dresses',
            sellerId: seller.id
        });
        await ProductImage.bulkCreate([
            { url: 'https://images.unsplash.com/photo-1595777457583-95e059eb59c0?auto=format&fit=crop&w=500&q=60', ProductId: product2.id },
            { url: 'https://images.unsplash.com/photo-1594938298603-c8148c4b4a30?auto=format&fit=crop&w=500&q=60', ProductId: product2.id },
            { url: 'https://images.unsplash.com/photo-1618375531912-867984bdfd87?auto=format&fit=crop&w=500&q=60', ProductId: product2.id }
        ]);
        await ProductSize.bulkCreate([
            { size: 'S', ProductId: product2.id },
            { size: 'M', ProductId: product2.id }
        ]);

        const product3 = await Product.create({
            name: 'Abaca Handbag',
            description: 'Eco-friendly stylish handbag made from local abaca fibers.',
            price: 2500,
            stock: 15,
            category: 'Accessories',
            sellerId: seller.id
        });
        await ProductImage.bulkCreate([
            { url: 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=500&q=60', ProductId: product3.id },
            { url: 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=500&q=60', ProductId: product3.id },
            { url: 'https://images.unsplash.com/photo-1566150905458-1bf1fc113f0d?auto=format&fit=crop&w=500&q=60', ProductId: product3.id }
        ]);
        await ProductSize.create({ size: 'One Size', ProductId: product3.id });

        console.log('✅ Data seeded successfully!');
        console.log('');
        console.log('--- Login Credentials ---');
        console.log('Admin:    admin@lumbarong.ph  / admin123');
        console.log('Seller:   artisan@lumban.ph   / seller123');
        console.log('Customer: juan@example.ph      / customer123');
        process.exit();
    } catch (error) {
        console.error('Error seeding data:', error);
        process.exit(1);
    }
};

seedData();
