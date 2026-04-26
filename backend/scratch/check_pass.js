const bcrypt = require('bcryptjs');

const password = 'customer123';
const hashInDb = '$2b$10$UIspHmT9TtDNCfpkkHXDt.OZA.HTd4b.pXinF.N/mHuU.OjcROsWG';

async function check() {
  const match = await bcrypt.compare(password, hashInDb);
  console.log('Match result:', match);
}

check();
