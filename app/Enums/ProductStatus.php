<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Available = 'AVAILABLE';
    case Unavailable = 'UNAVAILABLE';
    case SoldOut = 'SOLD_OUT';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }

    /**
     * Status yang tampil di katalog Customer dan Kasir. UNAVAILABLE = sengaja disembunyikan Owner dari menu.
     *
     * @return list<self>
     */
    public static function visibleInCatalog(): array
    {
        return [self::Available, self::SoldOut];
    }
}
