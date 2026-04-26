const express = require('express');
const router = express.Router();
const multer = require('multer');
const cloudinary = require('../utils/cloudinaryConfig');
const auth = require('../middleware/authMiddleware');
const { IMAGE_UPLOAD_LIMITS, imageFileFilter } = require('../utils/uploadValidation');
const {
    createSafeImageFilename,
    validateImageUploadBuffer,
} = require('../utils/imageUploadSecurity');
const { ensureUploadDirs, miscUploadDir } = require('../utils/uploadPaths');

const fs = require('fs');
const path = require('path');

const storage = multer.memoryStorage();
const upload = multer({
    storage,
    limits: {
        fileSize: IMAGE_UPLOAD_LIMITS.maxFileSizeBytes,
        files: IMAGE_UPLOAD_LIMITS.maxFilesPerRequest,
    },
    fileFilter: imageFileFilter,
});

ensureUploadDirs();

const collectUploadedFiles = (req) => {
    if (Array.isArray(req.files)) return req.files;
    if (req.files && typeof req.files === 'object') {
        return Object.values(req.files).flat();
    }
    if (req.file) return [req.file];
    return [];
};

router.post(
    '/',
    auth(['seller', 'admin', 'customer']),
    upload.fields([
        { name: 'image', maxCount: IMAGE_UPLOAD_LIMITS.maxFilesPerRequest },
        { name: 'images', maxCount: IMAGE_UPLOAD_LIMITS.maxFilesPerRequest },
    ]),
    async (req, res) => {
    try {
        const files = collectUploadedFiles(req);
        if (files.length === 0) {
            return res.status(400).json({ message: 'No image files were uploaded.' });
        }

        if (files.length > IMAGE_UPLOAD_LIMITS.maxFilesPerRequest) {
            return res.status(400).json({ message: `You can upload up to ${IMAGE_UPLOAD_LIMITS.maxFilesPerRequest} images at a time.` });
        }

        const validatedFiles = files.map((file, index) => {
            const metadata = validateImageUploadBuffer(file, `Image ${index + 1}`);
            return { file, metadata };
        });

        try {
            const uploads = [];
            for (const { file } of validatedFiles) {
                const fileStr = file.buffer.toString('base64');
                const fileUri = `data:${file.mimetype};base64,${fileStr}`;
                const uploadResponse = await cloudinary.uploader.upload(fileUri, {
                    folder: 'lumbarong/images',
                });

                uploads.push({
                    url: uploadResponse.secure_url,
                    name: file.originalname,
                });
            }

            if (uploads.length === 1) {
                return res.json({
                    message: 'Image uploaded successfully.',
                    url: uploads[0].url,
                    file: uploads[0],
                });
            }

            return res.json({
                message: `${uploads.length} images uploaded successfully.`,
                files: uploads,
                urls: uploads.map((entry) => entry.url),
            });
        } catch (cloudinaryError) {
            console.warn('Cloudinary upload failed or not configured, falling back to local storage:', cloudinaryError.message);

            const uploads = [];
            for (const { file } of validatedFiles) {
                const fileName = createSafeImageFilename(file.originalname, file.mimetype, 'upload');
                const filePath = path.join(miscUploadDir, fileName);
                fs.writeFileSync(filePath, file.buffer);
                const publicUrl = `${req.protocol}://${req.get('host')}/uploads/misc/${fileName}`;

                uploads.push({
                    url: publicUrl,
                    name: file.originalname,
                });
            }

            if (uploads.length === 1) {
                return res.json({
                    message: 'Image uploaded successfully.',
                    url: uploads[0].url,
                    file: uploads[0],
                });
            }

            return res.json({
                message: `${uploads.length} images uploaded successfully.`,
                files: uploads,
                urls: uploads.map((entry) => entry.url),
            });
        }
    } catch (error) {
        console.error('Upload error:', error);
        res.status(error.statusCode || 500).json({ message: error.message || 'Upload failed', error: error.message });
    }
});

module.exports = router;
