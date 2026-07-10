const { User } = require('../backend/src/models');
const sequelize = require('../backend/src/config/db');

async function test() {
  try {
    await sequelize.authenticate();
    console.log('Connected to DB');

    const email = 'test_duplicate@gmail.com';
    
    // Cleanup
    await User.destroy({ where: { email } });

    console.log('Creating first user...');
    await User.create({
      name: 'User 1',
      email: email,
      password: 'password123',
      role: 'customer'
    });

    console.log('Creating second user (should fail)...');
    await User.create({
      name: 'User 2',
      email: email,
      password: 'password123',
      role: 'customer'
    });

    console.log('SUCCESS (Wait, this is bad! It should have failed)');
  } catch (error) {
    console.log('CAUGHT EXPECTED ERROR:', error.name, error.message);
  } finally {
    await sequelize.close();
  }
}

test();
