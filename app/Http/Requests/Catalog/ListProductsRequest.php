<?php

namespace App\Http\Requests\Catalog;

use App\DataTransferObjects\CatalogFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            // UNAVAILABLE tidak pernah tampil di katalog.
            'status' => ['nullable', Rule::in(['AVAILABLE', 'SOLD_OUT'])],
            'popular' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],   // lebih dari 100 dipotong ke 100
        ];
    }

    public function filter(): CatalogFilter
    {
        return CatalogFilter::fromArray($this->validated());
    }
}
