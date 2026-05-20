<?php

namespace App\Services;

class PhoneNormalizer
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }

        $normalized = preg_replace('/\s+/', '', trim($phone));

        return $normalized !== '' ? $normalized : null;
    }
}
