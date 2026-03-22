const User = require('../src/models/User');
const sequelize = require('../src/config/database');

async function testLoginQuery() {
    try {
        console.log('Searching for Maria Clara...');
        const user = await User.findOne({ where: { email: 'artisan@lumban.ph' } });
        if (user) {
            console.log('User found! ID:', user.id);
        } else {
            console.log('User not found.');
        }
    } catch (error) {
        console.error('Query failed:', error);
    } finally {
        await sequelize.close();
    }
}

testLoginQuery();
