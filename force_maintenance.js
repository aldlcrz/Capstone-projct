const { SystemSetting } = require('./backend/src/models');

async function setMaintenance(status) {
  try {
    await SystemSetting.upsert({ key: 'maintenanceMode', value: status });
    console.log(`maintenanceMode set to ${status}`);
    process.exit(0);
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
}

setMaintenance(true);
