<?php

namespace App\Http\Requests\Admin;

use App\DataTransferObjects\AdminProductFilter;
use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminProductsRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'regex:/^-?(name|price|stock|created_at|updated_at)$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filter(): AdminProductFilter
    {
        return AdminProductFilter::fromArray($this->validated());
    }
}
