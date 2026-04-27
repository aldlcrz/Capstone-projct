const { Report, User } = require('./src/models');

async function check() {
  try {
    const count = await Report.count();
    console.log(`Reports count: ${count}`);
    const reports = await Report.findAll({ limit: 1 });
    console.log('Sample report:', JSON.stringify(reports, null, 2));
  } catch (error) {
    console.error('Error checking reports:', error.message);
  }
  process.exit();
}

check();
