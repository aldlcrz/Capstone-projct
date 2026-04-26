const http = require('http');

http.get('http://localhost:5000/api/v1/admin/public-settings', (res) => {
  let data = '';
  res.on('data', (chunk) => { data += chunk; });
  res.on('end', () => {
    console.log('Public Settings:', JSON.parse(data));
    process.exit(0);
  });
}).on('error', (err) => {
  console.error('Error:', err.message);
  process.exit(1);
});
