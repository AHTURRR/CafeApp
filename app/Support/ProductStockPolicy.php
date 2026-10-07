<?php

namespace App\Support;

use App\Enums\ProductStatus;

/** stock = null berarti stok tidak dilacak. */
final class ProductStockPolicy
{
    /** Produk dengan stok 0 tidak boleh berstatus AVAILABLE. */
    public static function resolveStatus(ProductStatus $requested, ?int $stock): ProductStatus
    {
        if ($stock === 0 && $requested === ProductStatus::Available) {
            return ProductStatus::SoldOut;
        }

        return $requested;
    }

    public static function canBeAvailable(?int $stock): bool
    {
        return $stock === null || $stock > 0;
    }
}
