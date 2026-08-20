<?php

namespace App\Support;

trait NormalizesIndonesianNumbers
{
    protected function normalizeIndonesianNumber(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        if (str_contains($value, ',')) {
            return str_replace(',', '.', str_replace('.', '', $value));
        }

        $dotCount = substr_count($value, '.');
        if ($dotCount > 1 || ($dotCount === 1 && strlen((string) strrchr($value, '.')) === 4)) {
            return str_replace('.', '', $value);
        }

        return $value;
    }
}
