const mysql = require('mysql2/promise');
require('dotenv').config({ path: '../.env' });

async function fixTable() {
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'lumbarong'
  });

  try {
    console.log('Renaming "read" to "isRead" in Notifications table...');
    await connection.query('ALTER TABLE Notifications CHANGE COLUMN `read` `isRead` TINYINT(1) DEFAULT 0');
    console.log('✅ Done');
  } catch (err) {
    console.error('Error fixing table:', err.message);
  } finally {
    await connection.end();
  }
}

fixTable();
