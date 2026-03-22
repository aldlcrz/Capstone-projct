const express = require('express');
const router = express.Router();
const userController = require('../controllers/userController');
const { protect } = require('../middleware/auth');

router.get('/profile', protect, userController.getProfile);
router.put('/profile', protect, userController.updateProfile);

router.get('/addresses', protect, userController.getAddresses);
router.post('/addresses', protect, userController.createAddress);
router.put('/addresses/:id', protect, userController.updateAddress);
router.delete('/addresses/:id', protect, userController.deleteAddress);
router.patch('/addresses/:id/default', protect, userController.setDefaultAddress);
router.patch('/fcm-token', protect, userController.updateFcmToken);

module.exports = router;
