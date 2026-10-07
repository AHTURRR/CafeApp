<?php

namespace App\Support;

/** Pengaman pencarian ILIKE: karakter % dan _ dari pengguna diperlakukan sebagai teks biasa. */
final class Like
{
    public static function escape(string $value): string
    {
        // Backslash harus diganti lebih dulu agar escape yang kita tambahkan tidak ikut diganda.
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public static function contains(string $value): string
    {
        return '%'.self::escape($value).'%';
    }
}
