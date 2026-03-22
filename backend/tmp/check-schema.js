const sequelize = require('../src/config/database');

async function checkSchema() {
    try {
        console.log('Checking Products table schema...');
        const [results] = await sequelize.query('DESCRIBE Products');
        console.log('Columns in Products table:');
        results.forEach(col => {
            console.log(`- ${col.Field} (${col.Type})`);
        });
    } catch (error) {
        console.error('Failed to describe table:', error.message);
    } finally {
        await sequelize.close();
    }
}

checkSchema();
