const multer = require('multer');

const errorHandler = (err, req, res, next) => {
  console.error(err.stack);

  let statusCode = err.statusCode || 500;
  let message = err.message || 'Internal Server Error';

  if (err instanceof multer.MulterError) {
    statusCode = 400;

    if (err.code === 'LIMIT_FILE_SIZE') {
      message = 'Each image must be 5MB or smaller.';
    } else if (err.code === 'LIMIT_FILE_COUNT' || err.code === 'LIMIT_UNEXPECTED_FILE') {
      message = 'You can upload up to 5 JPEG, PNG, or WebP images per request.';
    }
  }
  
  res.status(statusCode).json({
    success: false,
    message,
    data: null
  });
};

module.exports = errorHandler;
