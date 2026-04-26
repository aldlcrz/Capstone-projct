const express = require('express');
const router = express.Router();
const refundController = require('../controllers/refundController');
const { protect, restrictTo } = require('../middleware/authMiddleware');
const { videoUpload } = require('../middleware/uploadMiddleware');

// Note: Using a custom upload limit for video proof
const uploadProof = videoUpload.single('video');

router.use(protect);

router.post('/request', uploadProof, refundController.createRefundRequest);
router.get('/customer', refundController.getCustomerRefundRequests);

router.get('/seller', restrictTo('seller'), refundController.getSellerRefundRequests);
router.put('/:id/status', restrictTo('seller'), refundController.updateRefundStatus);

module.exports = router;
