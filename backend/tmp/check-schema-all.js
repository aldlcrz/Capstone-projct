const sequelize = require('../src/config/database');

async function checkSchema() {
    try {
        for (const table of ['OrderItems', 'Users', 'Orders', 'ProductRatings', 'ProductImages']) {
            console.log(`Checking ${table} table schema...`);
            const [results] = await sequelize.query(`DESCRIBE ${table}`);
            results.forEach(col => {
                if (col.Field.toLowerCase().includes('image')) {
                    console.log(`- ${table}.${col.Field} (${col.Type})`);
                }
            });
        }
    } catch (error) {
        console.error('Failed to describe table:', error.message);
    } finally {
        await sequelize.close();
    }
}

checkSchema();
