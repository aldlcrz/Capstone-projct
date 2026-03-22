const axios = require('axios');

async function testLoginHTTP() {
    try {
        console.log('Attempting login via HTTP to http://localhost:5000/api/v1/auth/login...');
        const res = await axios.post('http://localhost:5000/api/v1/auth/login', {
            email: 'artisan@lumban.ph',
            password: 'wrongpassword' // I don't know the password
        });
        console.log('Response:', res.status, res.data);
    } catch (error) {
        if (error.response) {
            console.log('Failed with status:', error.response.status);
            console.log('Error data:', error.response.data);
        } else {
            console.error('Request failed:', error.message);
        }
    }
}

testLoginHTTP();
