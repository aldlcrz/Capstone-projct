const express = require('express');
const rateLimit = require('express-rate-limit');
const { ipKeyGenerator } = require('express-rate-limit');
const authMiddleware = require('../middleware/authMiddleware');
const { register, login, forgotPassword, resetPassword, getProfile, googleLogin, setPassword } = require('../controllers/authController');
const { imageUpload } = require('../middleware/uploadMiddleware');
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
  message: { error: 'Too many password reset requests, please try again after an hour' }
});

router.post(
  '/register',
  authLimiter,
  imageUpload.fields([
    { name: 'indigencyCertificate', maxCount: 1 },
    { name: 'validId', maxCount: 1 },
    { name: 'gcashQrCode', maxCount: 1 },
  ]),
  register
);
router.post('/login', loginLimiter, login);
router.post('/forgot-password', passwordResetLimiter, forgotPassword);
router.post('/reset-password', passwordResetLimiter, resetPassword);
router.post('/google', googleLogin);
router.post('/set-password', authMiddleware.protect, setPassword);

module.exports = router;
module.exports.resetLoginRateLimit = resetLoginRateLimit;
