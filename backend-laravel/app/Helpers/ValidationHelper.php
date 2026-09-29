<?php

namespace App\Helpers;

class ValidationHelper
{
    const LETTER_RANGE = "A-Za-zÀ-ÖØ-öø-ÿ";
    
    const INPUT_LIMITS = [
        'personName' => 50,
        'email' => 100,
        'passwordMin' => 6,
        'passwordMax' => 32,
        'houseNo' => 40,
        'street' => 100,
        'barangay' => 50,
        'city' => 50,
        'province' => 50,
        'postalCode' => 4,
        'mobileNumber' => 11,
        'paymentReferenceMin' => 8,
        'paymentReferenceMax' => 20,
        'variationLabel' => 30,
    ];

    public static function sanitizePersonName($value)
    {
        return self::sanitizeByRegex($value, "/[^" . self::LETTER_RANGE . "'. -]/u", self::INPUT_LIMITS['personName']);
    }

    public static function sanitizePhone($value)
    {
        return substr(preg_replace('/\D/', '', (string)$value), 0, self::INPUT_LIMITS['mobileNumber']);
    }

    public static function validatePhilippineMobileNumber($value, $fieldName = "Phone number", $required = true)
    {
        $digits = self::sanitizePhone($value);
        if (!$digits) {
            if ($required) {
                throw new \Exception("$fieldName is required.");
            }
            return "";
        }
        if (!preg_match('/^09\d{9}$/', $digits)) {
            throw new \Exception("$fieldName must be an 11-digit Philippine mobile number starting with 09.");
        }
        return $digits;
    }

    public static function validateCourierTrackingNumber(?string $courier, ?string $trackingNumber): array
    {
        $tracking = strtoupper(trim((string)$trackingNumber));
        if ($tracking === '') {
            return [
                'valid' => false,
                'error' => 'Please enter the official courier tracking number.'
            ];
        }

        // Check for invalid characters (only alphanumeric and dashes allowed)
        if (!preg_match('/^[0-9A-Z\-]+$/', $tracking)) {
            return [
                'valid' => false,
                'error' => 'Tracking number can only contain letters, numbers, and hyphens.'
            ];
        }

        // Anti-spam / repeated character check (e.g. eeeeeee... or 1111111...)
        $cleanNoDashes = str_replace('-', '', $tracking);
        if (strlen($cleanNoDashes) >= 4 && preg_match('/^(.)\1+$/', $cleanNoDashes)) {
            return [
                'valid' => false,
                'error' => 'Invalid tracking number. Repeated single-character sequences are not allowed.'
            ];
        }

        $c = strtolower(trim((string)$courier));

        if (str_contains($c, 'j&t') || str_contains($c, 'jnt')) {
            if (!preg_match('/^(JT)?[0-9]{10,14}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid J&T Express tracking number format. Must be 10–14 digits (e.g., 781234567890 or JT123456789012).'
                ];
            }
        } elseif (str_contains($c, 'spx') || str_contains($c, 'shopee')) {
            if (!preg_match('/^SPX(PH)?[0-9A-Z]{6,16}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid SPX Express tracking number format. Expected format like SPXPH0123456789.'
                ];
            }
        } elseif (str_contains($c, 'lbc')) {
            if (!preg_match('/^(LBC)?[0-9]{5,15}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid LBC Express tracking number format. Must be a 5 to 14 digit waybill number (e.g., 123456789012).'
                ];
            }
        } elseif (str_contains($c, 'flash')) {
            if (!preg_match('/^(PH|FPH|TH|FL|FLASH)?[0-9A-Z]{6,20}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid Flash Express tracking number format. Expected format like PH012345678901.'
                ];
            }
        } elseif (str_contains($c, 'ninja')) {
            if (!preg_match('/^(NVPH|NVP|NVA|SHP|NINJA)?[0-9A-Z]{6,18}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid Ninja Van tracking number format. Expected format like NVPH0123456789.'
                ];
            }
        } elseif (str_contains($c, '2go')) {
            if (!preg_match('/^(2GO)?[0-9]{7,14}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid 2GO Express tracking number format. Must be 7 to 12 digits (e.g., 12345678).'
                ];
            }
        } elseif (str_contains($c, 'jrs')) {
            if (!preg_match('/^(JRS)?[0-9]{6,14}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid JRS Express tracking number format. Must be 7 to 12 digits (e.g., 1234567).'
                ];
            }
        } elseif (str_contains($c, 'lalamove')) {
            if (!preg_match('/^(LLM)?[0-9A-Z]{6,18}$/i', $cleanNoDashes)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid Lalamove order number format. Expected format like LLM12345678.'
                ];
            }
        } elseif (str_contains($c, 'grab')) {
            if (!preg_match('/^(GRAB|DLV|A-)?[0-9A-Z\-]{6,22}$/i', $tracking)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid GrabExpress delivery ID format. Expected format like A-123456789.'
                ];
            }
        } else {
            if (strlen($tracking) < 6 || strlen($tracking) > 30) {
                return [
                    'valid' => false,
                    'error' => 'Invalid tracking number format. Must be between 6 and 30 characters.'
                ];
            }
        }

        return ['valid' => true, 'cleaned' => $tracking];
    }

    private static function sanitizeByRegex($value, $regex, $maxLength)
    {
        $sanitized = preg_replace($regex, "", (string)$value);
        $sanitized = preg_replace('/\s{2,}/', ' ', $sanitized);
        return mb_substr($sanitized, 0, $maxLength);
    }
}
