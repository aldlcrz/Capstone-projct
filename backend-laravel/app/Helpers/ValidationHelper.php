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

    private static function sanitizeByRegex($value, $regex, $maxLength)
    {
        $sanitized = preg_replace($regex, "", (string)$value);
        $sanitized = preg_replace('/\s{2,}/', ' ', $sanitized);
        return mb_substr($sanitized, 0, $maxLength);
    }
}
