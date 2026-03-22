const express = require('express');
const router = express.Router();
const wishlistController = require('../controllers/wishlistController');
const authMiddleware = require('../middleware/authMiddleware');

router.use(authMiddleware(['customer', 'seller', 'admin']));

router.post('/toggle', wishlistController.toggleWishlist);
router.get('/', wishlistController.getMyWishlist);

module.exports = router;
