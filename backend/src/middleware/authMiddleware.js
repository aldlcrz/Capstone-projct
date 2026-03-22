const jwt = require('jsonwebtoken');

module.exports = (roles = []) => {
    return (req, res, next) => {
        // Support both 'x-auth-token' header and 'Authorization: Bearer <token>'
        let token = req.header('x-auth-token');

        if (!token) {
            const authHeader = req.header('Authorization');
            if (authHeader && authHeader.startsWith('Bearer ')) {
                token = authHeader.slice(7);
            }
        }

        if (!token) {
            return res.status(401).json({ message: 'No token, authorization denied' });
        }

        try {
            const decoded = jwt.verify(token, process.env.JWT_SECRET);
            req.user = decoded;
            // Normalize ID access
            req.user.id = decoded.userId || decoded.id;

            // Check if role is authorized
            if (roles.length > 0 && !roles.includes(req.user.role)) {
                return res.status(403).json({ message: 'Access denied: Unauthorized role' });
            }

            next();
        } catch (error) {
            res.status(401).json({ message: 'Token is not valid' });
        }
    };
};
