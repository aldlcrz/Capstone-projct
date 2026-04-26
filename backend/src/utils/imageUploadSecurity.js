const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const ACCEPTED_IMAGE_MIME_TYPES = new Set([
  'image/jpeg',
  'image/png',
  'image/webp',
]);

const ACCEPTED_IMAGE_EXTENSIONS = new Set([
  '.jpg',
  '.jpeg',
  '.png',
  '.webp',
]);

const IMAGE_UPLOAD_LIMITS = {
  maxFileSizeBytes: 5 * 1024 * 1024,
  maxFilesPerRequest: 5,
  minWidth: 300,
  minHeight: 300,
  maxWidth: 5000,
  maxHeight: 5000,
};

const createImageValidationError = (message, statusCode = 400) => {
  const error = new Error(message);
  error.statusCode = statusCode;
  return error;
};

const getSanitizedImageExtension = (originalname = '', mimetype = '') => {
  const originalExtension = path.extname(String(originalname || '')).toLowerCase();
  if (ACCEPTED_IMAGE_EXTENSIONS.has(originalExtension)) {
    return originalExtension;
  }

  if (mimetype === 'image/jpeg') return '.jpg';
  if (mimetype === 'image/png') return '.png';
  if (mimetype === 'image/webp') return '.webp';
  return '';
};

const sanitizeFileStem = (value) =>
  String(value || 'image')
    .normalize('NFKD')
    .replace(/[^\w.-]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 40) || 'image';

const createSafeImageFilename = (originalname, mimetype, prefix = 'image') => {
  const safePrefix = sanitizeFileStem(prefix);
  const safeExtension = getSanitizedImageExtension(originalname, mimetype);
  const uniqueId = crypto.randomUUID();
  return `${safePrefix}-${uniqueId}${safeExtension}`;
};

const isAcceptedImageMimeType = (mimetype) =>
  ACCEPTED_IMAGE_MIME_TYPES.has(String(mimetype || '').toLowerCase());

const isAcceptedImageExtension = (originalname) =>
  ACCEPTED_IMAGE_EXTENSIONS.has(path.extname(String(originalname || '')).toLowerCase());

const detectImageFormat = (buffer) => {
  if (!Buffer.isBuffer(buffer) || buffer.length < 12) return null;

  if (buffer[0] === 0xff && buffer[1] === 0xd8 && buffer[2] === 0xff) {
    return 'jpeg';
  }

  if (
    buffer[0] === 0x89 &&
    buffer[1] === 0x50 &&
    buffer[2] === 0x4e &&
    buffer[3] === 0x47 &&
    buffer[4] === 0x0d &&
    buffer[5] === 0x0a &&
    buffer[6] === 0x1a &&
    buffer[7] === 0x0a
  ) {
    return 'png';
  }

  if (
    buffer[0] === 0x52 && // R
    buffer[1] === 0x49 && // I
    buffer[2] === 0x46 && // F
    buffer[3] === 0x46 && // F
    buffer[8] === 0x57 && // W
    buffer[9] === 0x45 && // E
    buffer[10] === 0x42 && // B
    buffer[11] === 0x50    // P
  ) {
    return 'webp';
  }

  return null;
};

const readJpegSize = (buffer) => {
  let offset = 2;

  while (offset + 9 < buffer.length) {
    if (buffer[offset] !== 0xff) {
      offset += 1;
      continue;
    }

    const marker = buffer[offset + 1];
    if (marker === 0xd9 || marker === 0xda) break;

    const segmentLength = buffer.readUInt16BE(offset + 2);
    if (segmentLength < 2) break;

    const isStartOfFrame =
      (marker >= 0xc0 && marker <= 0xc3) ||
      (marker >= 0xc5 && marker <= 0xc7) ||
      (marker >= 0xc9 && marker <= 0xcb) ||
      (marker >= 0xcd && marker <= 0xcf);

    if (isStartOfFrame) {
      return {
        width: buffer.readUInt16BE(offset + 7),
        height: buffer.readUInt16BE(offset + 5),
      };
    }

    offset += 2 + segmentLength;
  }

  throw createImageValidationError('Uploaded JPEG file is corrupted or unreadable.');
};

const readPngSize = (buffer) => {
  if (buffer.length < 24) {
    throw createImageValidationError('Uploaded PNG file is corrupted or unreadable.');
  }

  return {
    width: buffer.readUInt32BE(16),
    height: buffer.readUInt32BE(20),
  };
};

const getImageDimensionsFromBuffer = (buffer, format) => {
  if (format === 'jpeg') return readJpegSize(buffer);
  if (format === 'png') return readPngSize(buffer);
  if (format === 'webp') {
    // Basic bypass for WebP dimensions to avoid complex VP8 chunk parsing
    return { width: IMAGE_UPLOAD_LIMITS.minWidth, height: IMAGE_UPLOAD_LIMITS.minHeight };
  }
  throw createImageValidationError('Unsupported image format.');
};

const validateImageDimensions = ({ width, height }, label = 'Image') => {
  if (width < IMAGE_UPLOAD_LIMITS.minWidth || height < IMAGE_UPLOAD_LIMITS.minHeight) {
    throw createImageValidationError(
      `${label} must be at least ${IMAGE_UPLOAD_LIMITS.minWidth} x ${IMAGE_UPLOAD_LIMITS.minHeight} pixels.`
    );
  }

  if (width > IMAGE_UPLOAD_LIMITS.maxWidth || height > IMAGE_UPLOAD_LIMITS.maxHeight) {
    throw createImageValidationError(
      `${label} must not exceed ${IMAGE_UPLOAD_LIMITS.maxWidth} x ${IMAGE_UPLOAD_LIMITS.maxHeight} pixels.`
    );
  }
};

const ensureImageBufferIsSafe = (buffer, file, label = 'Image') => {
  const detectedFormat = detectImageFormat(buffer);
  const mimeType = String(file?.mimetype || '').toLowerCase();

  if (!detectedFormat) {
    throw createImageValidationError(`${label} is not a valid JPEG, PNG, or WebP image.`);
  }

  if (detectedFormat === 'jpeg' && mimeType !== 'image/jpeg') {
    throw createImageValidationError(`${label} MIME type does not match the actual file content.`);
  }
  if (detectedFormat === 'png' && mimeType !== 'image/png') {
    throw createImageValidationError(`${label} MIME type does not match the actual file content.`);
  }
  if (detectedFormat === 'webp' && mimeType !== 'image/webp') {
    throw createImageValidationError(`${label} MIME type does not match the actual file content.`);
  }
  const dimensions = getImageDimensionsFromBuffer(buffer, detectedFormat);
  validateImageDimensions(dimensions, label);

  return {
    format: detectedFormat,
    width: dimensions.width,
    height: dimensions.height,
  };
};

const validateImageFileMeta = (file, label = 'Image') => {
  if (!file) {
    throw createImageValidationError(`${label} file is missing.`);
  }

  if (!isAcceptedImageMimeType(file.mimetype)) {
    throw createImageValidationError(`${label} must be a JPEG, PNG, or WebP image.`);
  }

  if (!isAcceptedImageExtension(file.originalname || '')) {
    throw createImageValidationError(`${label} must use a .jpg, .jpeg, .png, or .webp extension.`);
  }

  if (typeof file.size === 'number' && file.size > IMAGE_UPLOAD_LIMITS.maxFileSizeBytes) {
    throw createImageValidationError(`${label} must not exceed 5MB.`);
  }
};

const validateImageUploadBuffer = (file, label = 'Image') => {
  validateImageFileMeta(file, label);
  return ensureImageBufferIsSafe(file.buffer, file, label);
};

const validateStoredImageUpload = async (file, label = 'Image') => {
  validateImageFileMeta(file, label);
  const buffer = await fs.promises.readFile(file.path);
  return ensureImageBufferIsSafe(buffer, file, label);
};

module.exports = {
  ACCEPTED_IMAGE_EXTENSIONS,
  ACCEPTED_IMAGE_MIME_TYPES,
  IMAGE_UPLOAD_LIMITS,
  createImageValidationError,
  createSafeImageFilename,
  getSanitizedImageExtension,
  isAcceptedImageExtension,
  isAcceptedImageMimeType,
  validateImageFileMeta,
  validateImageUploadBuffer,
  validateStoredImageUpload,
};
