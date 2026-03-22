const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { User } = require('../models');
const socketUtility = require('../utils/socketUtility');

exports.register = async (req, res) => {
  try {
    const { name, email, password, role, mobileNumber, gcashNumber, isAdult } = req.body;
    
    // Core Validations
    if (!name || !email || !password) {
      return res.status(400).json({ message: 'Name, email, and password are required' });
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return res.status(400).json({ message: 'Please provide a valid email address' });
    }

    if (password.length < 6) {
      return res.status(400).json({ message: 'Password must be at least 6 characters long' });
    }

    // Role-specific Validations (Seller)
    if (role === 'seller') {
      if (!mobileNumber || !gcashNumber) {
        return res.status(400).json({ message: 'Sellers must provide both a mobile number and a GCash number' });
      }
      
      const phoneRegex = /^09\d{9}$/;
      if (!phoneRegex.test(mobileNumber) || !phoneRegex.test(gcashNumber)) {
        return res.status(400).json({ message: 'Phone numbers must be in the format 09123456789' });
      }

      if (isAdult !== 'true' && isAdult !== true) {
        return res.status(400).json({ message: 'Sellers must confirm they are of legal age' });
      }
    }

    const existingUser = await User.findOne({ where: { email } });
    if (existingUser) {
      return res.status(400).json({ message: 'An account with this email already exists' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);

    let indigencyCertificate = null;
    let validId = null;
    let gcashQrCode = null;

    if (req.files) {
      if (req.files.indigencyCertificate) indigencyCertificate = req.files.indigencyCertificate[0].path;
      if (req.files.validId) validId = req.files.validId[0].path;
      if (req.files.gcashQrCode) gcashQrCode = req.files.gcashQrCode[0].path;
    }

    const newUser = await User.create({
      name,
      email,
      password: hashedPassword,
      role: role || 'customer',
      indigencyCertificate,
      validId,
      gcashQrCode,
      mobileNumber: mobileNumber || null,
      gcashNumber: gcashNumber || null,
      isAdult: isAdult === 'true' || isAdult === true,
    });

    socketUtility.emitUserUpdated(newUser, { action: 'registered' });

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
    res.status(500).json({ message: error.message });
  }
};

exports.login = async (req, res) => {
  try {
    const { email, password, rememberMe } = req.body;

    const user = await User.findOne({ where: { email } });
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      return res.status(400).json({ message: 'Invalid credentials' });
    }

    const token = jwt.sign(
      { id: user.id, role: user.role },
      process.env.JWT_SECRET,
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

exports.getProfile = async (req, res) => {
  try {
    const user = await User.findByPk(req.user.id, {
      attributes: { exclude: ['password'] },
    });
    res.status(200).json(user);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};
