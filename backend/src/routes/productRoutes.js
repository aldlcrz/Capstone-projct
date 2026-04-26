const express = require('express');
const {
  getAllProducts,
  getProductById,
  getSellerProducts,
  createProduct,
  updateProduct,
  deleteProduct,
  getSellerStats,
  trackProductFunnelEvent,
  addReview,
} = require('../controllers/productController');
const router = express.Router();
const { protect, authorize, maybeProtect } = require('../middleware/authMiddleware');
const multer = require('multer');
const { ensureUploadDirs, productsUploadDir } = require('../utils/uploadPaths');
const { IMAGE_UPLOAD_LIMITS, imageFileFilter } = require('../utils/uploadValidation');
const { createSafeImageFilename, validateStoredImageUpload } = require('../utils/imageUploadSecurity');
const fs = require('fs');

// Ensure static-backed upload directories exist
ensureUploadDirs();

// Multer storage configuration to keep file extensions
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    cb(null, productsUploadDir);
  },
  filename: function (req, file, cb) {
    cb(null, createSafeImageFilename(file.originalname, file.mimetype, file.fieldname || 'product'));
  },
});

const upload = multer({
  storage,
  limits: {
    fileSize: IMAGE_UPLOAD_LIMITS.maxFileSizeBytes,
    files: IMAGE_UPLOAD_LIMITS.maxFilesPerRequest,
  },
  fileFilter: imageFileFilter,
});

const validateUploadedProductImages = async (req, res, next) => {
  const files = Array.isArray(req.files) ? req.files : [];

  try {
    for (let index = 0; index < files.length; index += 1) {
      await validateStoredImageUpload(files[index], `Product image ${index + 1}`);
    }

    return next();
  } catch (error) {
    await Promise.all(
      files.map((file) => fs.promises.unlink(file.path).catch(() => {}))
    );
    return next(error);
  }
};

router.get('/', getAllProducts);
router.get('/seller', protect, authorize('seller', 'admin'), getSellerProducts);
router.get('/seller-stats', protect, authorize('seller', 'admin'), getSellerStats);
router.get('/stats', protect, authorize('seller', 'admin'), getSellerStats);
router.get('/:id', maybeProtect, getProductById);

router.post('/', protect, authorize('seller'), upload.array('images', IMAGE_UPLOAD_LIMITS.maxFilesPerRequest), validateUploadedProductImages, createProduct);
router.post('/:id/funnel-event', maybeProtect, trackProductFunnelEvent);
router.put('/:id', protect, authorize('seller', 'admin'), upload.array('images', IMAGE_UPLOAD_LIMITS.maxFilesPerRequest), validateUploadedProductImages, updateProduct);
router.delete('/:id', protect, authorize('seller', 'admin'), deleteProduct);

router.post('/:id/reviews', protect, authorize('customer'), addReview);

module.exports = router;
