<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOptionGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // 'present' (bukan 'required') agar daftar kosong dapat dipakai untuk melepas semua group.
            'option_groups' => ['present', 'array', 'max:20'],
            'option_groups.*.id' => [
                'required', 'integer', 'distinct',
                Rule::exists('option_groups', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'option_groups.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
