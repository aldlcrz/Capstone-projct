const jwt = require('jsonwebtoken');
const { User } = require('../models');

// 1. Combined middleware (used by wishlist, return, upload)
const authMiddleware = (roles = []) => {
    return async (req, res, next) => {
        let token = req.header('x-auth-token');
        if (!token) {
            const authHeader = req.header('Authorization');
            if (authHeader && authHeader.startsWith('Bearer ')) token = authHeader.slice(7);
        }
        if (!token) return res.status(401).json({ message: 'No token, authorization denied' });

        try {
            const decoded = jwt.verify(token, process.env.JWT_SECRET || 'lumbarong_secret_key_2026');
            const user = await User.findByPk(decoded.id || decoded.userId);
            if (!user) return res.status(401).json({ message: 'User not found' });

            req.user = user;

            if (roles.length > 0 && !roles.includes(req.user.role)) {
                return res.status(403).json({ message: 'Access denied: Unauthorized role' });
            }
            next();
        } catch (error) {
            res.status(401).json({ message: 'Token is not valid' });
        }
    };
};

// 2. Separate protect middleware (from original auth.js)
authMiddleware.protect = async (req, res, next) => {
    try {
        let token = req.header('x-auth-token');
        if (!token) {
            const authHeader = req.header('Authorization');
            if (authHeader && authHeader.startsWith('Bearer ')) token = authHeader.slice(7);
        }
        if (!token) return res.status(401).json({ message: 'No token, authorization denied' });

        const decoded = jwt.verify(token, process.env.JWT_SECRET || 'lumbarong_secret_key_2026');
        const user = await User.findByPk(decoded.id || decoded.userId);
        
        if (!user) return res.status(401).json({ message: 'User not found' });
        
        req.user = user;
        next();
    } catch (error) {
        res.status(401).json({ message: 'Token is not valid' });
    }
};

// 3. Separate authorize middleware (from original auth.js)
authMiddleware.authorize = (...roles) => {
    return (req, res, next) => {
        if (!req.user || !roles.includes(req.user.role)) {
            return res.status(403).json({ message: 'Access denied: Insufficient permissions' });
        }
        next();
    };
};

module.exports = authMiddleware;
