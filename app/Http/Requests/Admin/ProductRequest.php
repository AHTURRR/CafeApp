<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'option_group_ids' => ['nullable', 'array', 'max:20'],
            'option_group_ids.*' => [
                'integer', 'distinct',
                Rule::exists('option_groups', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
        ];
    }
}
