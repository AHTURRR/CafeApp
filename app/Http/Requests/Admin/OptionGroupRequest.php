<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi bentuk per field. Konsistensi min/max/required dicek di CustomizationService. */
class OptionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_required' => ['nullable', 'boolean'],
            'min_selection' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_selection' => ['nullable', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
