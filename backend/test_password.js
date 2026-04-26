const { User } = require('./src/models');
const bcrypt = require('bcryptjs');

async function test() {
  const user = await User.findOne({ where: { email: 'customer@gmail.com' } });
  if (!user) {
    console.log('User not found');
    return;
  }
  
  console.log('User password hash:', user.password);
  
  const matchesPassword = await bcrypt.compare('password', user.password);
  console.log('Matches "password":', matchesPassword);
  
  const matchesCustomer123 = await bcrypt.compare('customer123', user.password);
  console.log('Matches "customer123":', matchesCustomer123);
}

test().catch(console.error).finally(() => process.exit(0));
