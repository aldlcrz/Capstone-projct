const express = require('express');
const router = express.Router();
const adminController = require('../controllers/adminController');
const { protect, authorize } = require('../middleware/auth');

router.get('/stats', protect, authorize('admin'), adminController.getGlobalStats);
router.get('/pending-sellers', protect, authorize('admin'), adminController.getPendingSellers);
router.get('/customers', protect, authorize('admin'), adminController.getCustomers);
router.put('/verify-seller/:id', protect, authorize('admin'), adminController.verifySeller);

// System Settings
router.get('/settings', protect, authorize('admin'), adminController.getSettings);
router.put('/settings', protect, authorize('admin'), adminController.updateSettings);

module.exports = router;
