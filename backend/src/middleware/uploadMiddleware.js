const multer = require('multer');
const fs = require('fs');
const { IMAGE_UPLOAD_LIMITS, imageFileFilter, videoFileFilter } = require('../utils/uploadValidation');
const { createSafeImageFilename } = require('../utils/imageUploadSecurity');
const path = require('path');

const uploadDir = path.join(__dirname, '../../public/uploads');
if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
}

const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        cb(null, uploadDir);
    },
    filename: function (req, file, cb) {
        cb(null, createSafeImageFilename(file.originalname, file.mimetype, file.fieldname || 'upload'));
    }
});

const createUpload = ({ fileFilter, fileSize }) => multer({
    storage,
    limits: { fileSize },
    fileFilter,
});

const imageUpload = createUpload({
    fileFilter: imageFileFilter,
    fileSize: IMAGE_UPLOAD_LIMITS.maxFileSizeBytes,
});

const videoUpload = createUpload({
    fileFilter: videoFileFilter,
    fileSize: 50 * 1024 * 1024,
});

module.exports = {
    imageUpload,
    videoUpload,
};
