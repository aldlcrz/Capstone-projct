const express = require('express');
const router = express.Router();
const reportController = require('../controllers/reportController');
const authMiddleware = require('../middleware/authMiddleware');
const multer = require('multer');
const { IMAGE_UPLOAD_LIMITS, imageFileFilter } = require('../utils/uploadValidation');
const { validateImageUploadBuffer } = require('../utils/imageUploadSecurity');

// Configure multer for memory storage since we validate buffer
const upload = multer({ 
  storage: multer.memoryStorage(),
  limits: {
    fileSize: IMAGE_UPLOAD_LIMITS.maxFileSizeBytes,
    files: 3,
  },
  fileFilter: imageFileFilter,
});

const validateReportImages = (req, res, next) => {
  const files = Array.isArray(req.files) ? req.files : [];

  try {
    for (let index = 0; index < files.length; index += 1) {
      validateImageUploadBuffer(files[index], `Report evidence ${index + 1}`);
    }
    return next();
  } catch (error) {
    return next(error);
  }
};

// User & Seller routes
router.post('/request', authMiddleware.protect, upload.array('images', 3), validateReportImages, reportController.createReport);
router.get('/my-reports', authMiddleware.protect, reportController.getMyReports);

// Admin routes
router.get('/admin/all', authMiddleware.protect, authMiddleware.authorize('admin'), reportController.getAllReportsAdmin);
router.put('/admin/:id/resolve', authMiddleware.protect, authMiddleware.authorize('admin'), reportController.resolveReport);

module.exports = router;
