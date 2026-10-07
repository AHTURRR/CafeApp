<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'is_available' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];

        // Hanya saat membuat; option tidak dapat dipindah ke group lain lewat update.
        if ($this->isMethod('POST')) {
            $rules['option_group_id'] = ['required', 'integer', Rule::exists('option_groups', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}
