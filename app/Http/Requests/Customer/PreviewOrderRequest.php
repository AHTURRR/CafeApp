<?php
namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PreviewOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_type' => ['required', 'string', 'in:DINE_IN,TAKE_AWAY'],
            'table_number' => ['required_if:order_type,DINE_IN', 'nullable', 'string', 'max:10'],
            'payment_method' => ['required', 'string', 'in:CASH'],
            'expected_total' => ['nullable', 'integer', 'min:0'],
            'promotion_code' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.option_ids' => ['nullable', 'array'],
            'items.*.option_ids.*' => ['integer'],
            'items.*.special_request' => ['nullable', 'string', 'max:250'],
        ];
    }
}