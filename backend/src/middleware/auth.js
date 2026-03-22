const jwt = require('jsonwebtoken');
const { User } = require('../models');

exports.protect = async (req, res, next) => {
  try {
    const token = (req.header('Authorization')?.replace('Bearer ', '') || req.header('x-auth-token'));
    
    // In dev, let's allow bypassing or handle common errors gracefully
    if (!token) return res.status(401).json({ message: 'No token, authorization denied' });

    const decoded = jwt.verify(token, process.env.JWT_SECRET || 'lumbarong_secret_key_2026');
    console.log('Decoded token:', decoded);
    
    const user = await User.findByPk(decoded.id);
    if (!user) {
      console.log('User not found for ID:', decoded.id);
      return res.status(401).json({ message: 'User not found' });
    }
    
    req.user = user; // Attach the actual user object
    next();
  } catch (error) {
    console.log('Auth error:', error.message);
    res.status(401).json({ message: 'Token is not valid' });
  }
};

exports.authorize = (...roles) => {
  return (req, res, next) => {
    console.log(`Checking authorization for roles: ${roles}. User role: ${req.user?.role}`);
    if (!req.user || !roles.includes(req.user.role)) {
      console.log(`Authorization failed for user: ${req.user?.email} (Role: ${req.user?.role})`);
      return res.status(403).json({ message: 'Access denied: Insufficient permissions' });
    }
    next();
  };
};
