<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\LogisticsRateCard;
use Modules\StockTransferNew\Entities\FreightRateVariance;

class LogisticsRateCardService
{
    public function activeCards(int $businessId, array $filters = [])
    {
        return LogisticsRateCard::query()
            ->where('business_id', $businessId)
            ->when(!empty($filters['location_id']), fn ($q) => $q->where('location_id', $filters['location_id']))
            ->when(!empty($filters['store_id']), fn ($q) => $q->where('store_id', $filters['store_id']))
            ->when(!empty($filters['transporter_name']), fn ($q) => $q->where('transporter_name', 'like', '%' . $filters['transporter_name'] . '%'))
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->get();
    }

    public function saveCard(int $businessId, array $data, int $userId): LogisticsRateCard
    {
        $payload = array_merge($data, [
            'business_id' => $businessId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return DB::transaction(fn () => LogisticsRateCard::create($payload));
    }

    public function estimateAmount(LogisticsRateCard $card, array $metrics): float
    {
        $amount = (float) $card->base_rate;
        $amount += ((float) ($metrics['distance_km'] ?? 0)) * (float) $card->rate_per_km;
        $amount += ((float) ($metrics['weight_kg'] ?? 0)) * (float) $card->rate_per_kg;
        $amount += ((float) ($metrics['volume_cbm'] ?? 0)) * (float) $card->rate_per_cbm;

        return max($amount, (float) $card->minimum_charge);
    }

    public function createVarianceReview(int $businessId, array $data, int $userId): FreightRateVariance
    {
        $variance = ((float) $data['actual_amount']) - ((float) $data['expected_amount']);
        $percent = ((float) $data['expected_amount']) > 0 ? ($variance / (float) $data['expected_amount']) * 100 : 0;

        return FreightRateVariance::create(array_merge($data, [
            'business_id' => $businessId,
            'variance_amount' => $variance,
            'variance_percent' => $percent,
            'status' => $data['status'] ?? 'open',
            'created_by' => $userId,
        ]));
    }

    public function varianceSummary(int $businessId, array $filters = [])
    {
        return FreightRateVariance::query()
            ->where('business_id', $businessId)
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('id')
            ->get();
    }
}
