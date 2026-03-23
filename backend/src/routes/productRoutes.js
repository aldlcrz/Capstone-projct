const express = require('express');
const {
  getAllProducts,
  getProductById,
  getSellerProducts,
  createProduct,
  updateProduct,
  deleteProduct,
  getSellerStats,
} = require('../controllers/productController');
const router = express.Router();
const { protect, authorize } = require('../middleware/authMiddleware');
const multer = require('multer');
const upload = multer({ dest: 'uploads/products/' }); // Placeholder for local storage

router.get('/', getAllProducts);
router.get('/seller', protect, authorize('seller', 'admin'), getSellerProducts);
router.get('/seller-stats', protect, authorize('seller', 'admin'), getSellerStats);
router.get('/stats', protect, authorize('seller', 'admin'), getSellerStats);
router.get('/:id', getProductById);

router.post('/', protect, authorize('seller'), upload.array('images', 10), createProduct);
router.put('/:id', protect, authorize('seller', 'admin'), upload.array('images', 10), updateProduct);
router.delete('/:id', protect, authorize('seller', 'admin'), deleteProduct);

module.exports = router;
