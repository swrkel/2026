<?php

namespace Modules\DistributionNew\Services\Analytics;

use Carbon\Carbon;
use Modules\DistributionNew\Entities\DisnewAnalyticsSnapshot;
use Modules\DistributionNew\Entities\DisnewKpiMetric;

class DisnewAnalyticsSnapshotService
{
    public function refreshDailySnapshot(int $businessId, ?int $locationId = null, ?string $date = null): array
    {
        $snapshotDate = $date ?: Carbon::today()->toDateString();
        $snapshot = DisnewAnalyticsSnapshot::updateOrCreate(
            [
                'business_id' => $businessId,
                'business_location_id' => $locationId,
                'snapshot_date' => $snapshotDate,
                'snapshot_type' => 'executive',
                'reference_type' => 'business',
                'reference_id' => $businessId,
            ],
            [
                'orders_count' => 0,
                'invoices_count' => 0,
                'deliveries_count' => 0,
                'returns_count' => 0,
                'gross_sales' => 0,
                'net_sales' => 0,
                'collections' => 0,
                'outstanding' => 0,
                'payload_json' => ['source' => 'disnew_stage15', 'note' => 'Hook actual order/invoice totals in tenant service layer.'],
            ]
        );

        $this->upsertMetric($businessId, $locationId, 'today_orders', 'Today Orders', $snapshotDate, $snapshot->orders_count, 0);
        $this->upsertMetric($businessId, $locationId, 'today_sales', 'Today Sales', $snapshotDate, $snapshot->net_sales, 0);
        $this->upsertMetric($businessId, $locationId, 'today_collections', 'Today Collections', $snapshotDate, $snapshot->collections, 0);

        return ['success' => true, 'snapshot_id' => $snapshot->id];
    }

    protected function upsertMetric(int $businessId, ?int $locationId, string $code, string $name, string $date, $value, $target): void
    {
        DisnewKpiMetric::updateOrCreate(
            ['business_id' => $businessId, 'business_location_id' => $locationId, 'metric_code' => $code, 'metric_date' => $date],
            ['metric_name' => $name, 'metric_group' => 'distribution', 'metric_value' => $value, 'target_value' => $target, 'variance_value' => $value - $target, 'status' => 'neutral']
        );
    }
}
