const User = require('./backend/src/models/User');
const { Product } = require('./backend/src/models/Product');
const sequelize = require('./backend/src/config/database');

async function seed() {
    try {
        await sequelize.authenticate();
        console.log('DB Connected');

        // Check if we already have pending data
        const pendingSellers = await User.count({ where: { role: 'seller', isVerified: false } });
        if (pendingSellers === 0) {
            await User.create({
                name: 'Mateo Dela Cruz',
                email: 'mateo@lumban.ph',
                password: 'password123',
                role: 'seller',
                isVerified: false,
                shopName: 'Dela Cruz Barong Heritage',
                shopDescription: 'Master weavers since 1950.'
            });
            console.log('Seeded pending seller');
        }

        const pendingProducts = await Product.count({ where: { status: 'pending' } });
        if (pendingProducts === 0) {
            const seller = await User.findOne({ where: { role: 'seller' } });
            if (seller) {
                await Product.create({
                    name: 'Executive Semi-Calado Barong',
                    description: 'A premium hand-embroidered piece.',
                    price: 12500,
                    stock: 3,
                    sellerId: seller.id,
                    status: 'pending'
                });
                console.log('Seeded pending product');
            }
        }
    } catch (err) {
        console.error(err);
    } finally {
        process.exit();
    }
}

seed();
