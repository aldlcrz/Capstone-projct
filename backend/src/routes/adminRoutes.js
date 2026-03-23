const express = require('express');
const router = express.Router();
const adminController = require('../controllers/adminController');
const { protect, authorize } = require('../middleware/authMiddleware');

router.get('/stats', protect, authorize('admin'), adminController.getGlobalStats);
router.get('/pending-sellers', protect, authorize('admin'), adminController.getPendingSellers);
router.get('/customers', protect, authorize('admin'), adminController.getCustomers);
router.delete('/customers/:id', protect, authorize('admin'), adminController.deleteCustomer);
router.put('/customers/:id/toggle-status', protect, authorize('admin'), adminController.toggleCustomerStatus);
router.put('/verify-seller/:id', protect, authorize('admin'), adminController.verifySeller);

// System Settings
router.get('/settings', protect, authorize('admin'), adminController.getSettings);
router.put('/settings', protect, authorize('admin'), adminController.updateSettings);
router.delete('/purge-cache', protect, authorize('admin'), adminController.purgeCache);

module.exports = router;
