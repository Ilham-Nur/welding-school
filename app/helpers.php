<?php

use App\Support\NormalizesIndonesianNumbers;

if (! function_exists('format_quantity')) {
    /**
     * Format a quantity or decimal number in Indonesian format.
     * Strips unnecessary trailing decimal zeros (e.g. 10.000 -> "10", 1000.000 -> "1.000", 10.500 -> "10,5").
     */
    function format_quantity(float|int|string|null $value, int $maxDecimals = 3): string
    {
        return NormalizesIndonesianNumbers::formatQuantity($value, $maxDecimals);
    }
}
