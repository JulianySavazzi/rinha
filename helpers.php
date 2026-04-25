<?php

declare(strict_types=1);

final class helpers
{
    /**
     * can not create instances of this class
     */
    private function __construct()
    {}

    /**
     * Ensures that a given number never goes outside a predefined range
     * (a minimum limit and a maximum limit).
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    public static function clamp(float $value, float $min = 0, float $max = 1): float
    {
        return max($min, min($value, $max));
    }
}