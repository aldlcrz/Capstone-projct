const sequelize = require('../src/config/db');
require('../src/models');

async function syncSchema() {
  try {
    console.log('Syncing database schema with { alter: true }...');
    await sequelize.authenticate();
    await sequelize.sync({ alter: true });
    console.log('✅ Database schema synced successfully');
  } catch (err) {
    console.error('❌ Error syncing schema:', err);
  } finally {
    await sequelize.close();
  }
}

syncSchema();
