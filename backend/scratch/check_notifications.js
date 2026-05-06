const mysql = require('mysql2/promise');
require('dotenv').config({ path: '../.env' });

async function checkTable() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'lumbarong'
  });

  try {
    const [rows] = await connection.query('DESCRIBE Notifications');
    console.log(JSON.stringify(rows, null, 2));
  } catch (err) {
    console.error(err);
  } finally {
    await connection.end();
  }
}

checkTable();
