const bcrypt = require('bcryptjs');
const crypto = require('crypto');
const jwt = require('jsonwebtoken');
const { Op } = require('sequelize');
const { OAuth2Client } = require('google-auth-library');
const client = new OAuth2Client(process.env.GOOGLE_CLIENT_ID);
const { User } = require('../models');
const socketUtility = require('../utils/socketUtility');
const { sendPasswordResetEmail } = require('../utils/emailService');
const {
  LIMITS,
  normalizeWhitespace,
  validatePersonName,
  validatePhilippineMobileNumber,
} = require('../utils/inputValidation');
const { notifyAdmins } = require('../utils/notificationHelper');

const RESET_PASSWORD_WINDOW_MINUTES = Number(process.env.PASSWORD_RESET_TTL_MINUTES || 60);
const GENERIC_PASSWORD_RESET_MESSAGE = 'If an account with that email exists, a password reset link has been sent.';

const trimTrailingSlash = (value) => String(value || '').replace(/\/+$/, '');
const hashResetToken = (token) => crypto.createHash('sha256').update(token).digest('hex');
const buildResetUrl = (token) => {
  const frontendUrl = trimTrailingSlash(process.env.FRONTEND_URL || 'http://127.0.0.1:3000');
  return `${frontendUrl}/reset-password/?token=${encodeURIComponent(token)}`;
};

const generateOTP = () => {
  return Math.floor(100000 + Math.random() * 900000).toString();
};

exports.register = async (req, res) => {
  try {
    const { name, email, password, role, mobileNumber, gcashNumber, isAdult } = req.body;

    const cleanedName = validatePersonName(name, 'Name');
    const cleanedEmail = normalizeWhitespace(email).toLowerCase();

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(cleanedEmail)) {
      return res.status(400).json({ message: 'Please provide a valid email address' });
    }

    if (cleanedEmail.length > LIMITS.email) {
      return res.status(400).json({ message: `Email cannot exceed ${LIMITS.email} characters` });
    }

    if (password.length < LIMITS.passwordMin || password.length > LIMITS.passwordMax) {
      return res.status(400).json({
        message: `Password must be between ${LIMITS.passwordMin} and ${LIMITS.passwordMax} characters long`
      });
    }

    // Role-specific Validations (Seller)
    let sellerMobile = null;
    let sellerGcash = null;
    if (role === 'seller') {
      if (!mobileNumber || !gcashNumber) {
        return res.status(400).json({ message: 'Sellers must provide both a mobile number and a GCash number' });
      }

      sellerMobile = validatePhilippineMobileNumber(mobileNumber, 'Mobile number');
      sellerGcash = validatePhilippineMobileNumber(gcashNumber, 'GCash number');
    }

    const existingUser = await User.findOne({ where: { email: cleanedEmail } });
    if (existingUser) {
      return res.status(400).json({ message: 'An account with this email already exists' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);

    let indigencyCertificate = null;
    let validId = null;
    let gcashQrCode = null;

    if (req.files) {
      if (req.files.indigencyCertificate) indigencyCertificate = req.files.indigencyCertificate[0].filename;
      if (req.files.validId) validId = req.files.validId[0].filename;
      if (req.files.gcashQrCode) gcashQrCode = req.files.gcashQrCode[0].filename;
    }

    const newUser = await User.create({
      name: cleanedName,
      email: cleanedEmail,
      password: hashedPassword,
      role: role || 'customer',
      indigencyCertificate,
      validId,
      gcashQrCode,
      mobileNumber: sellerMobile,
      gcashNumber: sellerGcash,
      isAdult: isAdult === 'true' || isAdult === true,
    });

    socketUtility.emitUserUpdated(newUser, { action: 'registered' });

    if (newUser.role === 'seller') {
      await notifyAdmins(
        'New Seller Registration',
        `A new seller named ${newUser.name} has registered and is waiting for review.`,
        'system',
        '/admin/users'
      );
    }

    res.status(201).json({
      message: 'User registered successfully',
      user: {
        id: newUser.id,
        name: newUser.name,
        email: newUser.email,
        role: newUser.role,
      },
    });
  } catch (error) {
    res.status(error.statusCode || 500).json({ message: error.message });
  }
};

exports.login = async (req, res) => {
  try {
    const { email, password, rememberMe } = req.body;

    const user = await User.findOne({ where: { email: email.toLowerCase() } });
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (user.status === 'blocked') {
      return res.status(403).json({
        message: 'Account Terminated',
        reason: user.violationReason || 'Account terminated by administrator.',
        status: 'blocked'
      });
    }

    if (user.status === 'frozen') {
      return res.status(403).json({ 
        message: 'Violation Detected', 
        reason: user.violationReason || 'Account suspended for policy violations.',
        status: 'frozen'
      });
    }

    if (user.status === 'rejected') {
      return res.status(403).json({ 
        message: 'Registration Rejected', 
        reason: user.rejectionReason || 'Application does not meet community standards.',
        status: 'rejected'
      });
    }

    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      return res.status(400).json({ message: 'Invalid credentials' });
    }

    const token = jwt.sign(
      { id: user.id, role: user.role },
      process.env.JWT_SECRET || 'lumbarong_secret_key_2026',
      { expiresIn: rememberMe ? '30d' : '24h' }
    );

    res.status(200).json({
      token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        isVerified: user.isVerified,
      },
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

exports.forgotPassword = async (req, res) => {
  const email = String(req.body?.email || '').trim();

  if (!email) {
    return res.status(400).json({ message: 'Email is required' });
  }

  const cleanedEmail = normalizeWhitespace(email).toLowerCase();

  let user = null;

  try {
    user = await User.findOne({ where: { email: cleanedEmail } });

    if (!user) {
      return res.status(200).json({ message: GENERIC_PASSWORD_RESET_MESSAGE });
    }

    const otp = generateOTP();
    user.resetPasswordToken = hashResetToken(otp);
    user.resetPasswordExpires = new Date(Date.now() + RESET_PASSWORD_WINDOW_MINUTES * 60 * 1000);
    await user.save();

    const delivery = await sendPasswordResetEmail({
      email: user.email,
      name: user.name,
      otp, // Sending OTP instead of URL
      expiresInMinutes: RESET_PASSWORD_WINDOW_MINUTES,
    });

    const response = { message: 'A 6-digit code has been sent to your email.' };
    if (process.env.NODE_ENV !== 'production' && delivery?.provider === 'console') {
      response.devOtp = otp;
      response.delivery = 'console';
    }

    return res.status(200).json(response);
  } catch (error) {
    if (user) {
      user.resetPasswordToken = null;
      user.resetPasswordExpires = null;
      await user.save();
    }

    return res.status(500).json({ message: 'Unable to send a password reset link right now' });
  }
};

exports.verifyOtp = async (req, res) => {
  try {
    const { email, otp } = req.body;

    if (!email || !otp) {
      return res.status(400).json({ message: 'Email and OTP are required' });
    }

    const user = await User.findOne({
      where: {
        email: email.toLowerCase(),
        resetPasswordToken: hashResetToken(otp),
        resetPasswordExpires: {
          [Op.gt]: new Date(),
        },
      },
    });

    if (!user) {
      return res.status(400).json({ message: 'Invalid or expired OTP' });
    }

    // Return a temporary "reset token" so the next step is secure
    // We can just use the same OTP as the token for the reset-password call
    return res.status(200).json({ 
      message: 'OTP verified successfully',
      resetToken: otp 
    });
  } catch (error) {
    return res.status(500).json({ message: 'Unable to verify OTP right now' });
  }
};

exports.resetPassword = async (req, res) => {
  try {
    const token = String(req.body?.token || '').trim();
    const password = String(req.body?.password || '');

    if (!token || !password) {
      return res.status(400).json({ message: 'Reset token and new password are required' });
    }

    if (password.length < 6 || password.length > 32) {
      return res.status(400).json({ message: 'Password must be between 6 and 32 characters long' });
    }

    const user = await User.findOne({
      where: {
        resetPasswordToken: hashResetToken(token),
        resetPasswordExpires: {
          [Op.gt]: new Date(),
        },
      },
    });

    if (!user) {
      return res.status(400).json({ message: 'Reset link is invalid or has expired' });
    }

    user.password = await bcrypt.hash(password, 10);
    user.resetPasswordToken = null;
    user.resetPasswordExpires = null;
    user.passwordChangedAt = new Date();
    await user.save();

    socketUtility.emitUserUpdated(user, { action: 'password_reset' });

    return res.status(200).json({ message: 'Password reset successful' });
  } catch (error) {
    return res.status(500).json({ message: 'Unable to reset password right now' });
  }
};

exports.getProfile = async (req, res) => {
  try {
    const user = await User.findByPk(req.user.id, {
      attributes: { exclude: ['password'] },
    });
    res.status(200).json(user);
  } catch (error) {
    res.status(500).json(user);
  }
};

exports.googleLogin = async (req, res) => {
  try {
    const { credential } = req.body;
    if (!credential) {
      return res.status(400).json({ message: 'Google credential is required' });
    }

    const ticket = await client.verifyIdToken({
      idToken: credential,
      audience: process.env.GOOGLE_CLIENT_ID,
    });

    const payload = ticket.getPayload();
    const { email, name, picture, sub: googleId } = payload;

    if (!email) {
      return res.status(400).json({ message: 'Email not provided by Google' });
    }

    // Check for existing user by googleId or email
    let user = await User.findOne({ 
      where: { 
        [Op.or]: [
          { googleId },
          { email: email.toLowerCase() }
        ]
      } 
    });

    let isNewUser = false;

    if (!user) {
      // Auto-register new Google user as customer
      isNewUser = true;
      user = await User.create({
        name: name || email.split('@')[0],
        email: email.toLowerCase(),
        password: await bcrypt.hash(crypto.randomBytes(32).toString('hex'), 10),
        role: 'customer',
        status: 'active',
        isVerified: true,
        googleId,
        profilePhoto: picture,
        hasPasswordSet: false
      });
      socketUtility.emitUserUpdated(user, { action: 'registered' });
    } else {
      // Check account status for existing users
      if (user.status === 'blocked') {
        return res.status(403).json({
          message: 'Account Terminated',
          reason: user.violationReason || 'Account terminated by administrator.',
          status: 'blocked'
        });
      }
      if (user.status === 'frozen') {
        return res.status(403).json({
          message: 'Violation Detected',
          reason: user.violationReason || 'Account suspended for policy violations.',
          status: 'frozen'
        });
      }

      // Link googleId if not already linked
      if (!user.googleId) {
        user.googleId = googleId;
        if (picture && !user.profilePhoto) user.profilePhoto = picture;
        await user.save();
      }
    }

    const token = jwt.sign(
      { id: user.id, role: user.role },
      process.env.JWT_SECRET || 'lumbarong_secret_key_2026',
      { expiresIn: '30d' } // Social login usually gets longer session
    );

    res.status(200).json({
      token,
      isNewUser,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
        isVerified: user.isVerified,
        profilePhoto: user.profilePhoto,
        hasPasswordSet: user.hasPasswordSet
      },
    });
  } catch (error) {
    console.error('Google Login Error:', error);
    res.status(500).json({ message: 'Google authentication failed' });
  }
};

exports.setPassword = async (req, res) => {
  try {
    const { password } = req.body;
    const userId = req.user.id;

    if (!password) {
      return res.status(400).json({ message: 'Password is required' });
    }

    if (password.length < 6 || password.length > 32) {
      return res.status(400).json({ message: 'Password must be between 6 and 32 characters long' });
    }

    const user = await User.findByPk(userId);
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    user.password = await bcrypt.hash(password, 10);
    user.hasPasswordSet = true;
    user.passwordChangedAt = new Date();
    await user.save();

    socketUtility.emitUserUpdated(user, { action: 'password_set' });

    res.status(200).json({ message: 'Password set successfully' });
  } catch (error) {
    console.error('Set Password Error:', error);
    res.status(500).json({ message: 'Failed to set password' });
  }
};
