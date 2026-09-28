<?php

namespace Modules\PetroPD\Repositories\PdSettlementRewrite;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PdSettlementRepository
{
    public function findSettlement(int $businessId, int $settlementId): ?object
    {
        return DB::table('settlements')
            ->where('business_id', $businessId)
            ->where('id', $settlementId)
            ->first();
    }

    public function listSettlements(int $businessId, array $filters = []): Collection
    {
        $query = DB::table('settlements as s')
            ->leftJoin('business_locations as bl', 's.location_id', '=', 'bl.id')
            ->leftJoin('pump_operators as po', 's.pump_operator_id', '=', 'po.id')
            ->where('s.business_id', $businessId)
            ->where(function ($q) {
                $q->where('s.settlement_no', 'LIKE', 'PDST%')
                  ->orWhere('s.is_petro_pd', 1);
            })
            ->select([
                's.*',
                'bl.name as location_name',
                'po.name as pump_operator_name',
            ]);

        if (!empty($filters['location_id'])) {
            $query->where('s.location_id', $filters['location_id']);
        }

        if (!empty($filters['pump_operator_id'])) {
            $query->where('s.pump_operator_id', $filters['pump_operator_id']);
        }

        if (!empty($filters['settlement_id'])) {
            $query->where('s.id', $filters['settlement_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereDate('s.transaction_date', '>=', $filters['start_date'])
                  ->whereDate('s.transaction_date', '<=', $filters['end_date']);
        }

        return $query->orderByDesc('s.id')->get();
    }

    public function shiftIdsForSettlement(object $settlement): array
    {
        $shiftIds = [];

        if (!empty($settlement->work_shift)) {
            $decoded = is_array($settlement->work_shift)
                ? $settlement->work_shift
                : json_decode((string) $settlement->work_shift, true);

            if (is_array($decoded)) {
                $shiftIds = array_filter(array_map('intval', $decoded));
            }
        }

        if (empty($shiftIds)) {
            $shiftIds = DB::table('pump_operator_assignments')
                ->where('settlement_id', $settlement->id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return $shiftIds;
    }

    public function settlementPayments(object $settlement, string $table): Collection
    {
        return DB::table($table)
            ->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                  ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->where('business_id', $settlement->business_id)
            ->get();
    }

    public function settlementMeterRows(object $settlement, array $shiftIds = []): Collection
    {
        $query = DB::table('pump_operator_meter_sales as poms')
            ->leftJoin('products as p', 'poms.product_id', '=', 'p.id')
            ->leftJoin('pumps as pump', 'poms.pump_id', '=', 'pump.id')
            ->where('poms.business_id', $settlement->business_id)
            ->where(function ($q) use ($settlement) {
                $q->where('poms.settlement_no', $settlement->id)
                  ->orWhere('poms.settlement_no', $settlement->settlement_no)
                  ->orWhere('poms.settlement_id', $settlement->id);
            })
            ->select([
                'poms.*',
                'p.name as product_name',
                'pump.pump_name as pump_name',
            ]);

        if (!empty($shiftIds)) {
            $query->whereIn('poms.shift_id', $shiftIds);
        }

        return $query->get();
    }
}
