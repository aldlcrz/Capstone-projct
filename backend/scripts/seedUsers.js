const bcrypt = require('bcryptjs');
const { User } = require('../src/models');
const sequelize = require('../src/config/db');

async function seed() {
  await sequelize.authenticate();
  
  const password = await bcrypt.hash('password123', 10);
  
  const users = [
    { email: 'admin@lumbarong.com', name: 'Admin LumbaRong', role: 'admin', password, isVerified: true },
    { email: 'seller@lumbarong.com', name: 'Artisan LumbaRong', role: 'seller', password, isVerified: true },
    { email: 'customer@lumbarong.com', name: 'Customer LumbaRong', role: 'customer', password, isVerified: true },
    { email: 'ailodleacruz@gmail.com', name: 'Ailod', role: 'customer', password, isVerified: true }
  ];

  for (const u of users) {
    const existing = await User.findOne({ where: { email: u.email } });
    if (!existing) {
      await User.create(u);
      console.log('Created user:', u.email);
    } else {
      console.log('User already exists:', u.email);
      await existing.update({ password });
    }
  }
  
  console.log('Done seeding default accounts!');
  process.exit(0);
}

seed().catch(err => {
  console.error(err);
  process.exit(1);
});
