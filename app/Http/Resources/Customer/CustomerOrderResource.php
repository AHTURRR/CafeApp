<?php
namespace App\Http\Resources\Customer;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'daily_number' => $this->daily_number,
            'status' => $this->status->value ?? $this->status,
            'source' => $this->source->value ?? $this->source,
            'order_type' => $this->order_type->value ?? $this->order_type,
            'table_number' => $this->table_number,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'options_price' => $item->options_price,
                    'subtotal' => $item->subtotal,
                    'options' => $item->options->map(function ($opt) {
                        return [
                            'group' => $opt->option_group_name,
                            'name' => $opt->option_name,
                            'price' => $opt->price,
                            'quantity' => $opt->quantity,
                        ];
                    }),
                    'special_request' => $item->special_request,
                ];
            }),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_percent' => $this->tax_percent,
            'tax_amount' => $this->tax_amount,
            'service_charge_percent' => $this->service_charge_percent,
            'service_charge_amount' => $this->service_charge_amount,
            'total' => $this->total,
            'payment' => $this->payment ? [
                'method' => $this->payment->method->value ?? $this->payment->method,
                'status' => $this->payment->status->value ?? $this->payment->status,
            ] : null,
            'timeline' => $this->statusLogs->map(function ($log) {
                return [
                    'status' => $log->to_status->value ?? $log->to_status,
                    'at' => $log->created_at->toIso8601String(),
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}