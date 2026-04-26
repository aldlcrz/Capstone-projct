const axios = require('axios');

async function test() {
  try {
    const response = await axios.post('http://127.0.0.1:3000/api/auth/login', {
      email: 'customer@gmail.com',
      password: 'password'
    });
    console.log('Login success:', response.data);
  } catch (error) {
    console.log('Login failed:', error.response?.data || error.message);
  }
}

test();
