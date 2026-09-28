<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewWarehouseStock;
use Modules\DistributionNew\Models\DisnewWarehouseTransfer;
use Modules\DistributionNew\Models\DisnewWarehouseTransferLine;

class DisnewWarehouseStockService
{
    public function transfer(array $header, array $lines): DisnewWarehouseTransfer
    {
        return DB::transaction(function () use ($header, $lines) {
            $transfer = DisnewWarehouseTransfer::create($header + ['status' => $header['status'] ?? 'draft']);
            foreach ($lines as $line) {
                DisnewWarehouseTransferLine::create($line + [
                    'business_id' => $transfer->business_id,
                    'transfer_id' => $transfer->id,
                    'line_total' => ((float)($line['qty'] ?? 0)) * ((float)($line['unit_cost'] ?? 0)),
                ]);
            }
            return $transfer;
        });
    }

    public function balance(int $businessId, int $warehouseId, int $productId, ?int $variationId = null): float
    {
        $row = DisnewWarehouseStock::where('business_id', $businessId)->where('warehouse_id', $warehouseId)->where('product_id', $productId)->when($variationId, fn($q) => $q->where('variation_id', $variationId))->first();
        return $row ? (float)$row->qty_available : 0.0;
    }
}
