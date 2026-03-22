const express = require('express');
const { createOrder, getMyOrders, getSellerOrders, updateOrderStatus, cancelOrder } = require('../controllers/orderController');
const router = express.Router();
const { protect, authorize } = require('../middleware/auth');
const multer = require('multer');
const upload = multer({ dest: 'uploads/payments/' });

router.post('/', protect, upload.single('paymentProof'), createOrder);
router.get('/my-orders', protect, getMyOrders);
router.get('/seller', protect, authorize('seller', 'admin'), getSellerOrders);
router.get('/seller-orders', protect, authorize('seller', 'admin'), getSellerOrders);
router.patch('/:id/cancel', protect, cancelOrder);
router.put('/:id/status', protect, authorize('seller', 'admin'), updateOrderStatus);

module.exports = router;
