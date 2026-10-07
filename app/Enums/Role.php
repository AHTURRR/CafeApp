<?php

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Cashier = 'cashier';
    case Kitchen = 'kitchen';
    case Owner = 'owner';

    /** Akun staf dibuat oleh Owner, bukan lewat pendaftaran mandiri. */
    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }
}
