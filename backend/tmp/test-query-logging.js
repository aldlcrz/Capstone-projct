const { Order, OrderItem, ReturnRequest } = require('../src/models/Order');
const { Product, ProductRating, ProductImage } = require('../src/models/Product');
const User = require('../src/models/User');
const sequelize = require('../src/config/database');

// Enable logging
sequelize.options.logging = console.log;

async function testQuery() {
    try {
        console.log('Testing query structure for a sample order...');
        const order = await Order.findOne({
            include: [
                { model: User, as: 'customer', attributes: ['name', 'email'] },
                {
                    model: OrderItem,
                    as: 'items',
                    include: [{
                        model: Product,
                        as: 'product',
                        attributes: ['name', 'price', 'id', 'sellerId'],
                        include: [{ model: ProductImage, as: 'images', separate: true }]
                    }]
                }
            ]
        });

        if (order) {
            console.log('Query successful!');
        } else {
            console.log('No order found to test.');
        }
    } catch (error) {
        console.error('Query failed!', error);
    } finally {
        await sequelize.close();
    }
}

testQuery();
