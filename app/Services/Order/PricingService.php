<?php
namespace App\Services\Order;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PricingService
{
    private ProductRepositoryInterface $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function calculate(array $itemsData, int $taxPercent = 0, int $serviceChargePercent = 0, int $discountAmount = 0): array
    {
        $subtotal = 0;
        $items = [];
        $issues = [];
        
        foreach ($itemsData as $index => $itemReq) {
            $product = $this->productRepository->findById($itemReq['product_id']);
            
            if (!$product) {
                $issues[] = ['item_index' => $index, 'product_id' => $itemReq['product_id'], 'code' => 'PRODUCT_UNAVAILABLE', 'message' => 'Produk tidak ditemukan.'];
                continue;
            }
            
            if ($product->status !== \App\Enums\ProductStatus::Available) {
                $issues[] = ['item_index' => $index, 'product_id' => $product->id, 'code' => 'PRODUCT_SOLD_OUT', 'message' => 'Maaf, produk ini sedang tidak tersedia atau habis.'];
                continue;
            }

            $optionsPrice = 0;
            $optionsResult = [];
            
            $requestedOptionIds = $itemReq['option_ids'] ?? [];
            
            // Group options validation
            $groupedRequests = [];
            
            // Load valid options
            $productOptionGroups = $product->optionGroups;
            $validOptionIds = [];
            $optionMap = [];
            
            foreach ($productOptionGroups as $group) {
                foreach ($group->options as $opt) {
                    $validOptionIds[] = $opt->id;
                    $optionMap[$opt->id] = ['option' => $opt, 'group' => $group];
                }
            }
            
            foreach ($requestedOptionIds as $optId) {
                if (!in_array($optId, $validOptionIds)) {
                    $issues[] = ['item_index' => $index, 'product_id' => $product->id, 'code' => 'OPTION_NOT_FOR_PRODUCT', 'option_id' => $optId, 'message' => 'Opsi tidak valid untuk produk ini.'];
                    continue 2;
                }
                
                $optData = $optionMap[$optId];
                if (!$optData['option']->is_available) {
                    $issues[] = ['item_index' => $index, 'product_id' => $product->id, 'code' => 'OPTION_UNAVAILABLE', 'option_id' => $optId, 'message' => "Pilihan {$optData['option']->name} sudah tidak tersedia."];
                    continue 2;
                }
                
                $groupId = $optData['group']->id;
                $groupedRequests[$groupId] = ($groupedRequests[$groupId] ?? 0) + 1;
                
                $optionsPrice += $optData['option']->price;
                $optionsResult[] = [
                    'option_id' => $optData['option']->id,
                    'option_group_name' => $optData['group']->name,
                    'option_name' => $optData['option']->name,
                    'price' => $optData['option']->price,
                    'quantity' => 1
                ];
            }
            
            foreach ($productOptionGroups as $group) {
                $selectedCount = $groupedRequests[$group->id] ?? 0;
                
                if ($group->is_required && $selectedCount < $group->min_selection) {
                    $issues[] = ['item_index' => $index, 'product_id' => $product->id, 'code' => 'SELECTION_BELOW_MIN', 'option_group_id' => $group->id, 'message' => "Pilih minimal {$group->min_selection} untuk {$group->name}."];
                } elseif ($selectedCount > $group->max_selection) {
                    $issues[] = ['item_index' => $index, 'product_id' => $product->id, 'code' => 'SELECTION_ABOVE_MAX', 'option_group_id' => $group->id, 'message' => "Maksimal {$group->max_selection} pilihan untuk {$group->name}."];
                }
            }
            
            $quantity = $itemReq['quantity'];
            $itemSubtotal = ($product->price + $optionsPrice) * $quantity;
            $subtotal += $itemSubtotal;
            
            $items[] = [
                'index' => $index,
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'options_price' => $optionsPrice,
                'subtotal' => $itemSubtotal,
                'options' => $optionsResult,
                'special_request' => $itemReq['special_request'] ?? null,
            ];
        }
        
        $taxAmount = (int) round(($subtotal - $discountAmount) * $taxPercent / 100);
        $serviceChargeAmount = (int) round(($subtotal - $discountAmount) * $serviceChargePercent / 100);
        $total = $subtotal - $discountAmount + $taxAmount + $serviceChargeAmount;
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'service_charge_percent' => $serviceChargePercent,
            'service_charge_amount' => $serviceChargeAmount,
            'total' => $total
        ];
    }
}