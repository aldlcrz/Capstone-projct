<?php

namespace App\Services\Courier\Contracts;

interface CourierRateProviderInterface
{
    /**
     * Calculate live shipping fee using official courier Open API.
     * Returns null if courier credentials are not configured or request fails (safely falling back to database matrix).
     *
     * @param string $originPostalCode
     * @param string $destProvince
     * @param string $destCity
     * @param string|null $destPostalCode
     * @param float $actualWeightKg
     * @param float $lengthCm
     * @param float $widthCm
     * @param float $heightCm
     * @return array|null ['fee' => float, 'min_days' => int, 'max_days' => int]
     */
    public function calculateRate(
        string $originPostalCode,
        string $destProvince,
        string $destCity,
        ?string $destPostalCode,
        float $actualWeightKg,
        float $lengthCm,
        float $widthCm,
        float $heightCm
    ): ?array;
}
