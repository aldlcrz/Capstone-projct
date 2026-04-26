const { SystemSetting } = require('./backend/src/models');

async function checkSettings() {
  try {
    const settings = await SystemSetting.findAll();
    console.log('--- System Settings ---');
    settings.forEach(s => {
      console.log(`${s.key}: ${s.value} (Type: ${typeof s.value})`);
    });
    console.log('-----------------------');
    process.exit(0);
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
}

checkSettings();
