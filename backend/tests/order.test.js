const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../.env') });
const request = require('supertest');
const express = require('express');
const bodyParser = require('body-parser');
const orderRoutes = require('../src/routes/orderRoutes');
const { User, Product, Order, OrderItem, Notification, Address, Wishlist, ReturnRequest } = require('../src/models');
const sequelize = require('../src/config/db');
const jwt = require('jsonwebtoken');

const TEST_SECRET = process.env.JWT_SECRET || 'lumbarong_secret_key_2026';

const app = express();
app.use(bodyParser.json());
app.use('/api/orders', orderRoutes);

describe('Order System API', () => {
    let customerToken, sellerToken, productId;

    beforeAll(async () => {
        await Notification.sync({ force: true });
        await sequelize.sync({ force: true });

        const customer = await User.create({
            name: 'Test Customer',
            email: 'customer@test.com',
            password: 'password123',
            role: 'customer'
        });
        customerToken = jwt.sign({ id: customer.id, role: 'customer' }, TEST_SECRET);

        const seller = await User.create({
            name: 'Test Seller',
            email: 'seller@test.com',
            password: 'password123',
            role: 'seller'
        });
        sellerToken = jwt.sign({ id: seller.id, role: 'seller' }, TEST_SECRET);

        const product = await Product.create({
            name: 'Test Barong',
            description: 'Beautiful Barong',
            price: 5000,
            stock: 10,
            sellerId: seller.id,
            category: 'Barong Tagalog'
        });
        productId = product.id;
    });

    afterAll(async () => {
        await sequelize.close();
    });

    it('should create an order as customer', async () => {
        const res = await request(app)
            .post('/api/orders')
            .set('Authorization', `Bearer ${customerToken}`)
            .send({
                items: [{ product: productId, quantity: 2, price: 5000 }],
                totalAmount: 10000,
                paymentMethod: 'Cash on Delivery',
                shippingAddress: { street: '123 Test St', city: 'Lumban', province: 'Laguna' }
            });

        expect(res.statusCode).toEqual(201);
        expect(res.body).toHaveProperty('id');

        const product = await Product.findByPk(productId);
        expect(product.stock).toBe(8);
    });

    it('should get orders for customer', async () => {
        const res = await request(app)
            .get('/api/orders/my-orders')
            .set('Authorization', `Bearer ${customerToken}`);

        expect(res.statusCode).toEqual(200);
        expect(Array.isArray(res.body)).toBeTruthy();
        expect(res.body.length).toBeGreaterThan(0);
    });

    it('should get orders for seller if it contains their product', async () => {
        const res = await request(app)
            .get('/api/orders/seller-orders')
            .set('Authorization', `Bearer ${sellerToken}`);

        expect(res.statusCode).toEqual(200);
        expect(Array.isArray(res.body)).toBeTruthy();
    });
});
