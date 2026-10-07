<?php

namespace App\Support;

final class PerPage
{
    public const DEFAULT = 20;

    public const MAX = 100;

    /** Nilai tidak valid memakai bawaan, nilai terlalu besar dipotong ke batas maksimum. */
    public static function resolve(mixed $value, int $default = self::DEFAULT, int $max = self::MAX): int
    {
        if (! is_numeric($value) || (int) $value < 1) {
            return $default;
        }

        return min((int) $value, $max);
    }
}
