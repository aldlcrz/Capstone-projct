const express = require('express');
const rateLimit = require('express-rate-limit');
const { ipKeyGenerator } = require('express-rate-limit');
const authMiddleware = require('../middleware/authMiddleware');
const { register, login, forgotPassword, verifyOtp, resetPassword, getProfile, googleLogin, setPassword } = require('../controllers/authController');
const { imageUpload } = require('../middleware/uploadMiddleware');
const { validateStoredImageUpload } = require('../utils/imageUploadSecurity');
const fs = require('fs');
const router = express.Router();

// Email-keyed limiter for login — each account gets its own independent bucket.
// Spamming a frozen/blocked account will NOT consume the rate limit for any other account.
const { MemoryStore } = rateLimit;
const loginLimiterStore = new MemoryStore();

const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 10,
  standardHeaders: true,
  legacyHeaders: false,
  store: loginLimiterStore,
  keyGenerator: (req) => {
    const email = (req.body?.email || '').toLowerCase().trim();
    return email || ipKeyGenerator(req);
  },
  message: { error: 'Too many login attempts for this account, please try again after 15 minutes' }
});

/**
 * Resets the login rate-limit bucket for a specific email.
 * Call this whenever an admin unfreezes or re-activates an account so the
 * user is not blocked by a stale rate-limit window.
 */
const resetLoginRateLimit = (email) => {
  if (!email) return;
  const key = email.toLowerCase().trim();
  try {
    loginLimiterStore.resetKey(key);
  } catch (e) {
    console.warn('resetLoginRateLimit: could not reset key', key, e.message);
  }
};


// IP-based limiter for registration to prevent mass account creation
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 10,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: 'Too many requests from this IP, please try again after 15 minutes' }
});

const passwordResetLimiter = rateLimit({
  windowMs: 60 * 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => {
    return (req.body?.email || '').toLowerCase().trim() || ipKeyGenerator(req);
  },
  handler: (req, res) => {
    const resetTime = req.rateLimit.resetTime;
    res.status(429).json({
      message: 'Too many password reset requests',
      retryAfter: resetTime,
    });
  }
});

const validateRegistrationImages = async (req, res, next) => {
  const fileGroups = req.files ? Object.values(req.files).flat() : [];

  try {
    for (let index = 0; index < fileGroups.length; index += 1) {
      await validateStoredImageUpload(fileGroups[index], `Registration image ${index + 1}`);
    }

    return next();
  } catch (error) {
    await Promise.all(
      fileGroups.map((file) => fs.promises.unlink(file.path).catch(() => {}))
    );
    return next(error);
  }
};

router.post(
  '/register',
  authLimiter,
  imageUpload.fields([
    { name: 'indigencyCertificate', maxCount: 1 },
    { name: 'validId', maxCount: 1 },
    { name: 'gcashQrCode', maxCount: 1 },
  ]),
  validateRegistrationImages,
  register
);
router.post('/login', loginLimiter, login);
router.post('/forgot-password', passwordResetLimiter, forgotPassword);
router.post('/verify-otp', passwordResetLimiter, verifyOtp);
router.post('/reset-password', passwordResetLimiter, resetPassword);
router.post('/google', googleLogin);
router.post('/set-password', authMiddleware.protect, setPassword);

module.exports = router;
module.exports.resetLoginRateLimit = resetLoginRateLimit;
