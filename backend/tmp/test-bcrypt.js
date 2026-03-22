const bcrypt = require('bcryptjs');

async function testBcrypt() {
    try {
        console.log('Testing bcrypt...');
        const password = 'testpassword';
        const salt = await bcrypt.genSalt(10);
        console.log('Salt generated:', salt);
        const hash = await bcrypt.hash(password, salt);
        console.log('Hash generated:', hash);
        const match = await bcrypt.compare(password, hash);
        console.log('Match result:', match);
    } catch (error) {
        console.error('Bcrypt test failed:', error);
    }
}

testBcrypt();
