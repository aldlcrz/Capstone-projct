const sequelize = require('./src/config/db');
const { Order } = require('./src/models');
const { approveCancellation } = require('./src/controllers/orderController');

const test = async () => {
  try {
    const order = await Order.findOne({ where: { status: 'Cancellation Pending' } });
    if (!order) {
      console.log('No order found.');
      process.exit(0);
    }

    const req = {
      params: { id: order.id },
      user: { id: order.sellerId } // mock auth
    };
    
    const res = {
      status: function(s) {
        this.statusCode = s;
        return this;
      },
      json: function(data) {
        console.log('Response:', this.statusCode, data);
        process.exit(0);
      }
    };

    console.log('Testing with Order ID:', order.id);
    await approveCancellation(req, res);
  } catch (err) {
    console.error('Test script error:', err);
    process.exit(1);
  }
};

test();
