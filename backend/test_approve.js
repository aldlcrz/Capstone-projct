const axios = require('axios');
const test = async () => {
  try {
    const loginRes = await axios.post('http://localhost:5000/api/v1/auth/login', {
      email: 'seller@example.com',
      password: 'password123'
    });
    const token = loginRes.data.token;
    
    const ordersRes = await axios.get('http://localhost:5000/api/v1/orders/seller', {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    const pendingOrder = ordersRes.data.find(o => o.status === 'Cancellation Pending');
    if (!pendingOrder) {
      console.log('No cancellation pending order found. Using first order to see if it gives 400 error or 500 error on something else.');
      const orderToCancel = ordersRes.data[0];
      if (!orderToCancel) return;
      try {
        await axios.patch(`http://localhost:5000/api/v1/orders/${orderToCancel.id}/approve-cancellation`, {}, {
          headers: { Authorization: `Bearer ${token}` }
        });
      } catch (e) {
         console.log('Error 1:', e.response ? e.response.data : e.message);
      }
      return;
    }
    
    console.log('Approving order:', pendingOrder.id);
    const approveRes = await axios.patch(`http://localhost:5000/api/v1/orders/${pendingOrder.id}/approve-cancellation`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
    console.log('Success:', approveRes.data);
  } catch (err) {
    console.log('Error:', err.response ? err.response.data : err.message);
  }
};
test();
