<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewPurchaseOrder;
use Modules\RestaurantNew\Entities\RestaurantNewGoodsReceipt;
use Modules\RestaurantNew\Entities\RestaurantNewProductionBatch;
use Modules\RestaurantNew\Entities\RestaurantNewCommissaryTransfer;

class RestaurantProcurementProductionService
{
    public function createPurchaseOrder(array $data): RestaurantNewPurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            $po = RestaurantNewPurchaseOrder::create($data);
            foreach ($lines as $line) {
                $po->lines()->create($line);
            }
            return $po;
        });
    }

    public function receiveGoods(array $data): RestaurantNewGoodsReceipt
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            $receipt = RestaurantNewGoodsReceipt::create($data);
            foreach ($lines as $line) {
                $receipt->lines()->create($line);
                // Stock movement hook stays inside RestaurantNew inventory service.
                app(RestaurantInventoryService::class)->recordInboundFromGoodsReceipt($receipt, $line);
            }
            return $receipt;
        });
    }

    public function createProductionBatch(array $data): RestaurantNewProductionBatch
    {
        return RestaurantNewProductionBatch::create($data);
    }

    public function createCommissaryTransfer(array $data): RestaurantNewCommissaryTransfer
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            $transfer = RestaurantNewCommissaryTransfer::create($data);
            foreach ($lines as $line) {
                $transfer->lines()->create($line);
            }
            return $transfer;
        });
    }

    public function summary(array $filters = []): array
    {
        return [
            'purchase_orders' => RestaurantNewPurchaseOrder::query()->count(),
            'goods_receipts' => RestaurantNewGoodsReceipt::query()->count(),
            'production_batches' => RestaurantNewProductionBatch::query()->count(),
            'commissary_transfers' => RestaurantNewCommissaryTransfer::query()->count(),
        ];
    }
}
