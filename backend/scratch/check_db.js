const { SystemSetting } = require('../src/models');
const sequelize = require('../src/config/db');

async function check() {
  try {
    const settings = await SystemSetting.findAll();
    console.log(JSON.stringify(settings, null, 2));
    process.exit(0);
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
}

check();
