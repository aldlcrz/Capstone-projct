const axios = require('axios');
const jwt = require('jsonwebtoken');

const test = async () => {
    const token = jwt.sign({ id: 'some-uuid', role: 'customer' }, 'lumbarong_secret_key_2026');
    try {
        const res = await axios.get('http://127.0.0.1:5000/api/v1/notifications', {
            headers: { Authorization: `Bearer ${token}` }
        });
        console.log('Response Status:', res.status);
        console.log('Data Length:', res.data.length);
    } catch (err) {
        console.error('Error Status:', err.response?.status);
        console.error('Error Message:', err.response?.data?.message || err.message);
    }
};

test();
