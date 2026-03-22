const { User, Product, Order, OrderItem } = require('../src/models');
const { v4: uuidv4 } = require('uuid');

async function seed() {
    try {
        const seller = await User.findOne({ where: { email: 'seller@lumbarong.com' } });
        const customer = await User.findOne({ where: { email: 'customer@lumbarong.com' } });
        const product = await Product.findOne();

        if (!seller || !customer || !product) {
            console.log('Missing seller, customer or product');
            process.exit(1);
        }

        const statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];
        
        for (let i = 0; i < statuses.length; i++) {
            const order = await Order.create({
                id: uuidv4(),
                customerId: customer.id,
                sellerId: seller.id,
                totalAmount: (Math.random() * 1000 + 500).toFixed(2),
                status: statuses[i],
                paymentMethod: i % 2 === 0 ? 'GCash' : 'Cash on Delivery',
                paymentReference: i % 2 === 0 ? 'REF123' + i : null,
                shippingAddress: { name: customer.name, street: '123 Lumban St', city: 'Lumban', postalCode: '4014' }
            });

            await OrderItem.create({
                orderId: order.id,
                productId: product.id,
                quantity: 1,
                price: order.totalAmount,
                size: 'M',
                variation: 'Original'
            });
            console.log(`Created ${statuses[i]} order: ${order.id}`);
        }

        console.log('Done seeding orders');
        process.exit(0);
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}

seed();
