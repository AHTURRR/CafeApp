<?php

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail produk + struktur customization. Tampilan Product Detail di aplikasi dibangun
 * dari option_groups ini, tanpa logika yang bergantung pada nama produk.
 *
 * @mixin \App\Models\Product
 */
class ProductDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => $this->image_url,
            'status' => $this->status->value,
            'category' => ['id' => $this->category->id, 'name' => $this->category->name],
            // Diisi saat fitur review (P1) aktif.
            'rating' => ['average' => null, 'count' => 0],
            'option_groups' => OptionGroupResource::collection($this->optionGroups),
        ];
    }
}
