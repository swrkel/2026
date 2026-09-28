<?php
namespace Modules\POS\Services;

class AdvancedSaleService
{
    public function searchProducts(array $filters): array
    {
        return ['items' => [], 'filters' => $filters];
    }

    public function holdSale(array $payload): array
    {
        return ['hold_reference' => 'HOLD-' . now()->format('YmdHis'), 'payload' => $payload];
    }

    public function mergeSales(array $saleIds): array
    {
        return ['merged_sale_ids' => $saleIds, 'status' => 'merged'];
    }
}
