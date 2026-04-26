const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../.env') });
const request = require('supertest');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
jest.mock('../src/utils/emailService', () => ({
    sendPasswordResetEmail: jest.fn().mockResolvedValue({ provider: 'test' }),
}));
const emailService = require('../src/utils/emailService');
const { app } = require('../src/app');
const sequelize = require('../src/config/db');
const User = require('../src/models/User');
const TEST_SECRET = process.env.JWT_SECRET || 'lumbarong_secret_key_2026';

describe('Auth Endpoints', () => {
    beforeAll(async () => {
        // Use Sequelize (MySQL), NOT Mongoose/MongoDB
        await sequelize.authenticate();
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 0');
        await sequelize.sync({ force: true });
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 1');
    });

    afterAll(async () => {
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 0');
        await sequelize.drop();
        await sequelize.query('SET FOREIGN_KEY_CHECKS = 1');
        await sequelize.close();
    });

    it('should register a new user', async () => {
        const res = await request(app)
            .post('/api/v1/auth/register')
            .send({
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123'
            });
        expect(res.statusCode).toEqual(201);
        expect(res.body.user).toHaveProperty('email', 'test@example.com');
    });

    it('should reject registration names with numbers', async () => {
        const res = await request(app)
            .post('/api/v1/auth/register')
            .send({
                name: 'Test User 123',
                email: 'invalid-name@example.com',
                password: 'password123'
            });
        expect(res.statusCode).toEqual(400);
        expect(res.body.message).toMatch(/letters/i);
    });

    it('should not register user with existing email', async () => {
        const res = await request(app)
            .post('/api/v1/auth/register')
            .send({
                name: 'Test User',
                email: 'test@example.com',
                password: 'password123'
            });
        expect(res.statusCode).toEqual(400);
    });

    it('should login with valid credentials', async () => {
        const res = await request(app)
            .post('/api/v1/auth/login')
            .send({
                email: 'test@example.com',
                password: 'password123'
            });
        expect(res.statusCode).toEqual(200);
        expect(res.body).toHaveProperty('token');
        expect(res.body.user).toHaveProperty('role', 'customer');
    });

    it('should not login with wrong password', async () => {
        const res = await request(app)
            .post('/api/v1/auth/login')
            .send({
                email: 'test@example.com',
                password: 'wrongpassword'
            });
        expect(res.statusCode).toEqual(400);
    });

    it('should reject a seller rejection request without a reason', async () => {
        const adminPassword = await bcrypt.hash('password123', 10);
        const admin = await User.create({
            name: 'Admin User',
            email: 'admin@example.com',
            password: adminPassword,
            role: 'admin',
        });

        const pendingSellerPassword = await bcrypt.hash('password123', 10);
        const pendingSeller = await User.create({
            name: 'Pending Seller',
            email: 'pending-seller@example.com',
            password: pendingSellerPassword,
            role: 'seller',
        });

        const adminToken = jwt.sign({ id: admin.id, role: 'admin' }, TEST_SECRET);

        const res = await request(app)
            .put(`/api/v1/admin/reject-seller/${pendingSeller.id}`)
            .set('Authorization', `Bearer ${adminToken}`)
            .send({ reason: '   ' });

        expect(res.statusCode).toEqual(400);
        expect(res.body.message).toMatch(/reason is required/i);
    });

    it('should include the termination reason when a blocked user logs in', async () => {
        const blockedPassword = await bcrypt.hash('password123', 10);
        await User.create({
            name: 'Blocked User',
            email: 'blocked@example.com',
            password: blockedPassword,
            role: 'customer',
            status: 'blocked',
            violationReason: 'Repeated marketplace fraud.',
        });

        const res = await request(app)
            .post('/api/v1/auth/login')
            .send({
                email: 'blocked@example.com',
                password: 'password123'
            });

        expect(res.statusCode).toEqual(403);
        expect(res.body.status).toEqual('blocked');
        expect(res.body.reason).toMatch(/marketplace fraud/i);
    });

    it('should reject frozen users on protected routes', async () => {
        const frozenPassword = await bcrypt.hash('password123', 10);
        const frozenUser = await User.create({
            name: 'Frozen User',
            email: 'frozen@example.com',
            password: frozenPassword,
            role: 'customer',
            status: 'frozen',
            violationReason: 'Multiple policy violations.',
        });

        const frozenToken = jwt.sign({ id: frozenUser.id, role: 'customer' }, TEST_SECRET);

        const res = await request(app)
            .get('/api/v1/users/profile')
            .set('Authorization', `Bearer ${frozenToken}`);

        expect(res.statusCode).toEqual(401);
        expect(res.body.status).toEqual('frozen');
        expect(res.body.reason).toMatch(/policy violations/i);
    });

    it('should create a password reset token and send a reset link', async () => {
        const res = await request(app)
            .post('/api/v1/auth/forgot-password')
            .send({
                email: 'test@example.com'
            });

        expect(res.statusCode).toEqual(200);
        expect(res.body.message).toMatch(/If an account with that email exists/i);
        expect(emailService.sendPasswordResetEmail).toHaveBeenCalledTimes(1);

        const updatedUser = await User.findOne({ where: { email: 'test@example.com' } });
        expect(updatedUser.resetPasswordToken).toBeTruthy();
        expect(updatedUser.resetPasswordExpires).toBeTruthy();
    });

    it('should normalize forgot-password email casing before lookup', async () => {
        const initialUser = await User.findOne({ where: { email: 'test@example.com' } });
        const initialToken = initialUser.resetPasswordToken;

        const res = await request(app)
            .post('/api/v1/auth/forgot-password')
            .send({
                email: 'Test@Example.com'
            });

        expect(res.statusCode).toEqual(200);
        const updatedUser = await User.findOne({ where: { email: 'test@example.com' } });
        expect(updatedUser.resetPasswordToken).toBeTruthy();
        expect(updatedUser.resetPasswordToken).not.toEqual(initialToken);
    });

    it('should reset the password using the emailed token', async () => {
        const [{ resetUrl }] = emailService.sendPasswordResetEmail.mock.calls.at(-1);
        const token = new URL(resetUrl).searchParams.get('token');

        const resetResponse = await request(app)
            .post('/api/v1/auth/reset-password')
            .send({
                token,
                password: 'newpassword123'
            });

        expect(resetResponse.statusCode).toEqual(200);

        const updatedUser = await User.findOne({ where: { email: 'test@example.com' } });
        expect(updatedUser.resetPasswordToken).toBeNull();
        expect(updatedUser.resetPasswordExpires).toBeNull();
        expect(updatedUser.passwordChangedAt).toBeTruthy();

        const oldLoginResponse = await request(app)
            .post('/api/v1/auth/login')
            .send({
                email: 'test@example.com',
                password: 'password123'
            });
        expect(oldLoginResponse.statusCode).toEqual(400);

        const newLoginResponse = await request(app)
            .post('/api/v1/auth/login')
            .send({
                email: 'test@example.com',
                password: 'newpassword123'
            });
        expect(newLoginResponse.statusCode).toEqual(200);
        expect(newLoginResponse.body).toHaveProperty('token');
    });
});
