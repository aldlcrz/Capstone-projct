const User = require('../src/models/User');
const sequelize = require('../src/config/database');

async function listUsers() {
    try {
        const users = await User.findAll({
            attributes: ['id', 'email', 'role', 'name']
        });
        console.log('Users found:');
        users.forEach(u => {
            console.log(`- ${u.name} (${u.email}): ${u.role}`);
        });
    } catch (error) {
        console.error('Failed to list users:', error);
    } finally {
        await sequelize.close();
    }
}

listUsers();
