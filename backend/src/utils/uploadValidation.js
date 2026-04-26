const {
  IMAGE_UPLOAD_LIMITS,
  createImageValidationError,
  isAcceptedImageExtension,
  isAcceptedImageMimeType,
} = require('./imageUploadSecurity');

const imageFileFilter = (req, file, cb) => {
  if (!isAcceptedImageMimeType(file?.mimetype)) {
    return cb(createImageValidationError('Only JPEG, PNG, and WebP image files are allowed.'));
  }

  if (!isAcceptedImageExtension(file?.originalname || '')) {
    return cb(createImageValidationError('Image files must use .jpg, .jpeg, .png, or .webp extensions.'));
  }

  return cb(null, true);
};

const videoFileFilter = (req, file, cb) => {
  const mimetype = String(file?.mimetype || '').toLowerCase();
  const isSupportedVideo =
    /^video\/(mp4|quicktime|x-msvideo|x-ms-wmv|x-matroska|webm)$/i.test(mimetype);

  if (isSupportedVideo) {
    return cb(null, true);
  }

  return cb(createImageValidationError('Only video files are allowed.'));
};

module.exports = {
  IMAGE_UPLOAD_LIMITS,
  imageFileFilter,
  videoFileFilter,
};
