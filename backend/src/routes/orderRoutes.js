const express = require('express');
const { createOrder, getMyOrders, getSellerOrders, updateOrderStatus, cancelOrder, approveCancellation, rejectCancellation, exportSellerReport } = require('../controllers/orderController');
const router = express.Router();
const { protect, authorize } = require('../middleware/authMiddleware');
const multer = require('multer');
const { ensureUploadDirs, paymentsUploadDir } = require('../utils/uploadPaths');
const { IMAGE_UPLOAD_LIMITS, imageFileFilter } = require('../utils/uploadValidation');
const { createSafeImageFilename, validateStoredImageUpload } = require('../utils/imageUploadSecurity');
const fs = require('fs');

// Ensure static-backed upload directories exist
ensureUploadDirs();

// Multer storage configuration to keep file extensions
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, paymentsUploadDir);
  },
  filename: function (req, file, cb) {
    cb(null, createSafeImageFilename(file.originalname, file.mimetype, 'payment'));
  },
});

const upload = multer({
  storage,
  limits: { fileSize: IMAGE_UPLOAD_LIMITS.maxFileSizeBytes },
  fileFilter: imageFileFilter,
});

const validatePaymentProof = async (req, res, next) => {
  if (!req.file) return next();

  try {
    await validateStoredImageUpload(req.file, 'Payment proof');
    return next();
  } catch (error) {
    await fs.promises.unlink(req.file.path).catch(() => {});
    return next(error);
  }
};

router.post('/', protect, upload.single('paymentProof'), validatePaymentProof, createOrder);
router.get('/my-orders', protect, getMyOrders);
router.get('/seller', protect, authorize('seller', 'admin'), getSellerOrders);
router.get('/seller-orders', protect, authorize('seller', 'admin'), getSellerOrders);
router.patch('/:id/cancel', protect, cancelOrder);
router.patch('/:id/status', protect, updateOrderStatus); 
router.put('/:id/status', protect, updateOrderStatus);
router.patch('/:id/approve-cancellation', protect, authorize('seller', 'admin'), approveCancellation);
router.patch('/:id/reject-cancellation', protect, authorize('seller', 'admin'), rejectCancellation);
router.get('/export-report', protect, authorize('seller', 'admin'), exportSellerReport);

module.exports = router;
