const path = require('path');
const { User } = require(path.resolve(__dirname, '../backend/src/models'));

async function checkUsers() {
  try {
    const users = await User.findAll({
      attributes: ['name', 'email', 'role', 'status']
    });
    console.log('--- Registered Users ---');
    console.table(users.map(u => u.toJSON()));
  } catch (error) {
    console.error('Error checking users:', error.message);
  } finally {
    process.exit();
  }
}

checkUsers();
