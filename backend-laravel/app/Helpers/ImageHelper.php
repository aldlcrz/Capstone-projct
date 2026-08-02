<?php

namespace App\Helpers;

class ImageHelper
{
    const IMAGE_UPLOAD_RULES = [
        'acceptedMimeTypes' => ['image/jpeg', 'image/png'],
        'acceptedExtensions' => ['jpg', 'jpeg', 'png'],
        'maxFileSizeBytes' => 5 * 1024 * 1024,
        'maxFilesPerRequest' => 5,
        'minWidth' => 300,
        'minHeight' => 300,
        'maxWidth' => 5000,
        'maxHeight' => 5000,
    ];

    public static function validateImage($file, $label = "Image")
    {
        if (!$file) {
            return "$label is missing.";
        }

        if (!in_array($file->getMimeType(), self::IMAGE_UPLOAD_RULES['acceptedMimeTypes'])) {
            return "$label must be a JPEG or PNG image.";
        }

        if (!in_array($file->getClientOriginalExtension(), self::IMAGE_UPLOAD_RULES['acceptedExtensions'])) {
            return "$label must use a .jpg, .jpeg, or .png extension.";
        }

        if ($file->getSize() > self::IMAGE_UPLOAD_RULES['maxFileSizeBytes']) {
            return "$label must be 5MB or smaller.";
        }

        list($width, $height) = getimagesize($file->getRealPath());

        if ($width < self::IMAGE_UPLOAD_RULES['minWidth'] || $height < self::IMAGE_UPLOAD_RULES['minHeight']) {
            return "$label must be at least " . self::IMAGE_UPLOAD_RULES['minWidth'] . " x " . self::IMAGE_UPLOAD_RULES['minHeight'] . " pixels.";
        }

        if ($width > self::IMAGE_UPLOAD_RULES['maxWidth'] || $height > self::IMAGE_UPLOAD_RULES['maxHeight']) {
            return "$label must not exceed " . self::IMAGE_UPLOAD_RULES['maxWidth'] . " x " . self::IMAGE_UPLOAD_RULES['maxHeight'] . " pixels.";
        }

        return null;
    }
}
