<?php

namespace App\Services\Catalog;

use App\Repositories\Contracts\SettingRepositoryInterface;

final class SettingService
{
    public function __construct(private readonly SettingRepositoryInterface $settings)
    {
    }

    /**
     * Nilai yang aman dibaca client untuk estimasi total di Checkout.
     * Angka resmi tetap dihitung server saat order dibuat.
     *
     * @return array{tax_percent: int|float, service_charge_percent: int|float, cafe_name: string}
     */
    public function publicSettings(): array
    {
        $all = $this->settings->all();

        return [
            'tax_percent' => ($all['tax_percent'] ?? 0) + 0,
            'service_charge_percent' => ($all['service_charge_percent'] ?? 0) + 0,
            'cafe_name' => (string) ($all['cafe_name'] ?? 'CafeApp'),
        ];
    }
}
