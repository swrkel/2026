<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;

class TransporterScorecardService
{
    public function scorecardRows(array $filters = array()): array
    {
        $query = DB::table('stn_logistics_shipments as s')
            ->leftJoin('stn_delivery_confirmations as d', 'd.shipment_id', '=', 's.id')
            ->leftJoin('stn_freight_invoices as f', 'f.shipment_id', '=', 's.id')
            ->leftJoin('stn_discrepancy_claims as c', 'c.shipment_id', '=', 's.id')
            ->select(
                DB::raw('COALESCE(s.transporter_name, "Internal") as transporter_name'),
                DB::raw('COUNT(DISTINCT s.id) as transfer_count'),
                DB::raw('SUM(CASE WHEN d.delivered_at IS NOT NULL AND s.eta_at IS NOT NULL AND d.delivered_at <= s.eta_at THEN 1 ELSE 0 END) as on_time_count'),
                DB::raw('SUM(CASE WHEN d.delivered_at IS NOT NULL AND s.eta_at IS NOT NULL AND d.delivered_at > s.eta_at THEN 1 ELSE 0 END) as delayed_count'),
                DB::raw('COUNT(DISTINCT c.id) as damage_claim_count'),
                DB::raw('COALESCE(SUM(COALESCE(f.invoice_amount,0) - COALESCE(f.expected_amount,0)),0) as freight_variance')
            )
            ->groupBy(DB::raw('COALESCE(s.transporter_name, "Internal")'));

        $this->applyTenantFilters($query, $filters);

        if (!empty($filters['transporter_name'])) {
            $query->where('s.transporter_name', 'like', '%' . $filters['transporter_name'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('s.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('s.created_at', '<=', $filters['date_to']);
        }

        return $query->get()->map(function ($row) {
            $score = 100;
            $score -= ((int) $row->delayed_count) * 5;
            $score -= ((int) $row->damage_claim_count) * 10;
            $score -= abs((float) $row->freight_variance) > 0 ? 5 : 0;
            $score = max(0, min(100, $score));

            return [
                'transporter_name' => $row->transporter_name,
                'transfer_count' => (int) $row->transfer_count,
                'on_time_count' => (int) $row->on_time_count,
                'delayed_count' => (int) $row->delayed_count,
                'damage_claim_count' => (int) $row->damage_claim_count,
                'freight_variance' => round((float) $row->freight_variance, 4),
                'score' => $score,
            ];
        })->toArray();
    }

    public function summary(array $filters = array()): array
    {
        $rows = $this->scorecardRows($filters);
        $transfers = array_sum(array_column($rows, 'transfer_count'));
        $delayed = array_sum(array_column($rows, 'delayed_count'));
        $claims = array_sum(array_column($rows, 'damage_claim_count'));
        $variance = array_sum(array_column($rows, 'freight_variance'));

        return [
            'transporters' => count($rows),
            'transfers' => $transfers,
            'delayed' => $delayed,
            'claims' => $claims,
            'freight_variance' => round($variance, 4),
        ];
    }

    protected function applyTenantFilters($query, array $filters): void
    {
        foreach (['business_id', 'location_id', 'store_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where('s.' . $field, $filters[$field]);
            }
        }
    }
}
