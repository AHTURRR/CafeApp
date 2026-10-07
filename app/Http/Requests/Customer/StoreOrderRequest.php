<?php
namespace App\Http\Requests\Customer;

class StoreOrderRequest extends PreviewOrderRequest
{
    public function authorize()
    {
        return true;
    }
}