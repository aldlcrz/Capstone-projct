const { Order, OrderItem, ReturnRequest } = require('../src/models/Order');
const { Product, ProductRating, ProductImage } = require('../src/models/Product');
const User = require('../src/models/User');
const sequelize = require('../src/config/database');

async function testQuery() {
    try {
        console.log('Testing connection...');
        await sequelize.authenticate();
        console.log('Connection established.');

        // Get a sample order ID
        const sampleOrder = await Order.findOne({ attributes: ['id'] });
        if (!sampleOrder) {
            console.log('No orders found in database to test with.');
            return;
        }
        const orderId = sampleOrder.id;

        console.log(`Testing query for order: ${orderId}`);
        const order = await Order.findByPk(orderId, {
            include: [
                { model: User, as: 'customer', attributes: ['name', 'email', 'address'] },
                {
                    model: OrderItem,
                    as: 'items',
                    include: [{
                        model: Product,
                        as: 'product',
                        attributes: ['name', 'price', 'id', 'sellerId'],
                        include: [{ model: ProductImage, as: 'images' }]
                    }]
                },
                { model: ReturnRequest, as: 'returnRequest' },
                {
                    model: ProductRating,
                    as: 'ratings',
                    include: [{ model: User, as: 'reviewer', attributes: ['name', 'profileImage'] }]
                }
            ]
        });
        console.log('Query successful!');
    } catch (error) {
        console.error('QUERY FAILED!');
        console.error(error);
        if (error.sql) {
            console.log('SQL Attempted:', error.sql);
        }
    } finally {
        await sequelize.close();
    }
}

testQuery();
