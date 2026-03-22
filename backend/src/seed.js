const bcrypt = require('bcryptjs');
const sequelize = require('./config/db');
const { User, Category, Product } = require('./models');

const seedDatabase = async () => {
  try {
    await sequelize.authenticate();
    console.log('Database connected for seeding...');
    
    // Drop and re-sync tables
    await sequelize.sync({ force: true });
    console.log('Tables re-synced successfully');

    const hashedPassword = await bcrypt.hash('password123', 10);

    // Seed Users
    const admin = await User.create({
      name: 'Root Admin',
      email: 'admin@lumbarong.com',
      password: hashedPassword,
      role: 'admin',
      isVerified: true
    });

    const seller = await User.create({
      name: 'Jose Artisan Shop',
      email: 'seller@lumbarong.com',
      password: hashedPassword,
      role: 'seller',
      isVerified: true
    });

    const customer = await User.create({
      name: 'Juan Dela Cruz',
      email: 'customer@lumbarong.com',
      password: hashedPassword,
      role: 'customer'
    });

    // Seed Categories
    const categories = await Category.bulkCreate([
      { name: 'Formal', description: 'Traditional Barong Tagalog for weddings and formal events' },
      { name: 'Casual', description: 'Lightweight Barongs for semi-formal daily wear' },
      { name: 'Traditional', description: 'Classic designs with heritage patterns' },
      { name: 'Modern Elite', description: 'Contemporary cuts with artisan embroidery' }
    ]);

    // Seed Initial Product
    await Product.create({
      name: 'Pina-Silk Lumban Barong',
      description: 'Our signature Pina-Silk barong with hand-embroidered floral patterns.',
      price: 12500.00,
      sizes: ['S', 'M', 'L', 'XL'],
      category: 'Formal',
      stock: 12,
      sellerId: seller.id,
      image: ['/images/product1.png']
    });

    console.log('✅ Seeding completed successfully!');
    console.log('\n--- Default Accounts ---');
    console.log('Admin: admin@lumbarong.com / password123');
    console.log('Seller: seller@lumbarong.com / password123');
    console.log('Customer: customer@lumbarong.com / password123');
    
    process.exit(0);
  } catch (error) {
    console.error('❌ Seeding failed:', error);
    process.exit(1);
  }
};

seedDatabase();
