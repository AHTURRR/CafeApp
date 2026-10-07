<?php

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OptionGroup */
class OptionGroupResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_required' => $this->is_required,
            'min_selection' => $this->min_selection,
            'max_selection' => $this->max_selection,
            'options' => OptionResource::collection($this->options),
        ];
    }
}
