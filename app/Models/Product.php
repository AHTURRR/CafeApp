<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'description', 'price', 'image_url', 'image_public_id', 'stock', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'status' => ProductStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Option group yang dikaitkan, berurutan menurut sort_order pada tabel pivot. */
    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(OptionGroup::class, 'product_option_groups')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /** Produk yang tampil di katalog: status AVAILABLE/SOLD_OUT dan kategorinya aktif. */
    public function scopeVisibleInCatalog(Builder $query): Builder
    {
        return $query
            ->whereIn('products.status', array_map(
                static fn (ProductStatus $s) => $s->value,
                ProductStatus::visibleInCatalog(),
            ))
            ->whereHas('category', fn (Builder $c) => $c->where('is_active', true));
    }
}
