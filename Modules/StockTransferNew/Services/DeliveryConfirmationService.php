<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransferDeliveryConfirmation;
use Modules\StockTransferNew\Entities\StockTransferDeliveryDamage;

class DeliveryConfirmationService
{
    public function list(array $filters)
    {
        return StockTransferDeliveryConfirmation::query()
            ->when($filters['business_id'] ?? null, fn ($q, $v) => $q->where('business_id', $v))
            ->when($filters['location_id'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->when($filters['store_id'] ?? null, fn ($q, $v) => $q->where('store_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('condition_status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('delivered_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('delivered_at', '<=', $v))
            ->latest('delivered_at')
            ->paginate(25);
    }

    public function find(int $id): ?StockTransferDeliveryConfirmation
    {
        return StockTransferDeliveryConfirmation::with('damages')->find($id);
    }

    public function confirm(array $data, int $userId): StockTransferDeliveryConfirmation
    {
        return DB::transaction(function () use ($data, $userId) {
            $delivery = StockTransferDeliveryConfirmation::updateOrCreate(
                ['transfer_id' => $data['transfer_id']],
                array_merge($data, ['confirmed_by' => $userId, 'delivered_at' => $data['delivered_at'] ?? now()])
            );

            DB::table('stn_activity_logs')->insert([
                'transfer_id' => $data['transfer_id'],
                'activity_type' => 'delivery_confirmed',
                'description' => 'Delivery confirmation recorded',
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $delivery;
        });
    }

    public function recordDamage(int $deliveryId, array $data, int $userId): StockTransferDeliveryDamage
    {
        return StockTransferDeliveryDamage::create(array_merge($data, [
            'delivery_confirmation_id' => $deliveryId,
            'created_by' => $userId,
        ]));
    }
}
