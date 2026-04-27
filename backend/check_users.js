const { User } = require('./src/models');

async function check() {
  try {
    const users = await User.findAll({
      attributes: ['id', 'name', 'email', 'role', 'status']
    });
    console.log('Users:');
    users.forEach(u => {
      console.log(`${u.role.padEnd(10)} | ${u.status.padEnd(10)} | ${u.email.padEnd(30)} | ${u.name}`);
    });
  } catch (error) {
    console.error('Error checking users:', error.message);
  }
  process.exit();
}

check();
