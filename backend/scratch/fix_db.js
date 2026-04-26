const { SystemSetting } = require('../src/models');
const sequelize = require('../src/config/db');

async function fix() {
  try {
    const settings = await SystemSetting.findAll();
    for (const s of settings) {
      let val = s.value;
      let changed = false;
      
      // Keep parsing until it's no longer a string that looks like a JSON-encoded string
      while (typeof val === 'string' && val.startsWith('"') && val.endsWith('"')) {
        try {
          const parsed = JSON.parse(val);
          // Only accept the parse if it results in a simpler type or a valid object/array
          val = parsed;
          changed = true;
        } catch (e) {
          break;
        }
      }
      
      if (changed) {
        console.log(`Fixing key ${s.key}: ${JSON.stringify(s.value)} -> ${JSON.stringify(val)}`);
        s.value = val;
        await s.save();
      }
    }
    console.log('Database cleanup complete.');
    process.exit(0);
  } catch (err) {
    console.error(err);
    process.exit(1);
  }
}

fix();
