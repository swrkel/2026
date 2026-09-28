<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Petro PD settlement rewrite source repository.
 *
 * This repository is the single read layer for the new PD settlement flow.
 * It intentionally reads only the confirmed pumper-dashboard source tables:
 * - petro_shifts
 * - pump_operator_assignments
 * - pumper_day_entries
 * - pump_operator_payments
 * - pump_operator_other_sales
 */
class PdSettlementSourceRepository
{
    public function oldestPendingClosedShift(int $businessId): ?object
    {
        return DB::table('petro_shifts as ps')
            ->join('pump_operator_assignments as poa', 'poa.shift_id', '=', 'ps.id')
            ->where('ps.business_id', $businessId)
            ->where('poa.business_id', $businessId)
            ->where('poa.status', 'close')
            ->where(function ($query) {
                $query->where('poa.closed_in_settlement', 0)
                    ->orWhereNull('poa.closed_in_settlement');
            })
            ->whereNull('poa.settlement_id')
            ->whereNotNull('poa.close_date_and_time')
            ->where(function ($query) {
                $query->where('ps.status', 2)
                    ->orWhereNotNull('ps.closed_time')
                    ->orWhere('poa.is_manually_closed', 1);
            })
            ->select([
                'ps.id as shift_id',
                'poa.shift_number',
                'poa.pump_operator_id',
                'ps.shift_date',
                'ps.closed_time',
                'ps.work_shift_id',
            ])
            ->groupBy('ps.id', 'poa.shift_number', 'poa.pump_operator_id', 'ps.shift_date', 'ps.closed_time', 'ps.work_shift_id')
            ->orderBy('ps.id', 'asc')
            ->first();
    }

    public function assignments(int $businessId, int $shiftId): Collection
    {
        return DB::table('pump_operator_assignments as poa')
            ->where('poa.business_id', $businessId)
            ->where('poa.shift_id', $shiftId)
            ->where('poa.status', 'close')
            ->where(function ($query) {
                $query->where('poa.closed_in_settlement', 0)
                    ->orWhereNull('poa.closed_in_settlement');
            })
            ->whereNull('poa.settlement_id')
            ->orderBy('poa.id')
            ->get();
    }

    public function meterSales(int $businessId, int $shiftId, ?int $pumpOperatorId = null): Collection
    {
        $assignmentIds = DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        $query = DB::table('pumper_day_entries as pde')
            ->where('pde.business_id', $businessId)
            ->where(function ($query) {
                $query->where('pde.closed_in_settlement', 0)
                    ->orWhereNull('pde.closed_in_settlement');
            })
            ->where(function ($query) {
                $query->whereNull('pde.settlement_no')
                    ->orWhere('pde.settlement_no', '');
            });

        if ($pumpOperatorId) {
            $query->where('pde.pump_operator_id', $pumpOperatorId);
        }

        $query->where(function ($query) use ($shiftId, $assignmentIds) {
            $query->where('pde.shift_id', $shiftId);
            if (! empty($assignmentIds)) {
                $query->orWhereIn('pde.pumper_assignment_id', $assignmentIds);
            }
        });

        return $query
            ->select('pde.*')
            ->orderBy('pde.pump_id')
            ->orderBy('pde.id')
            ->get()
            ->unique(function ($row) {
                return implode('|', [
                    $row->pump_id ?? '',
                    $row->pump_no ?? '',
                    $row->starting_meter ?? '',
                    $row->closing_meter ?? '',
                    $row->sold_ltr ?? '',
                    $row->amount ?? '',
                ]);
            })
            ->values();
    }

    public function payments(int $businessId, int $shiftId, ?int $pumpOperatorId = null): Collection
    {
        $query = DB::table('pump_operator_payments as pop')
            ->where('pop.business_id', $businessId)
            ->where('pop.shift_id', $shiftId)
            ->whereNull('pop.deleted_at')
            ->where(function ($query) {
                $query->where('pop.is_used', 0)
                    ->orWhereNull('pop.is_used');
            });

        if ($pumpOperatorId) {
            $query->where('pop.pump_operator_id', $pumpOperatorId);
        }

        return $query->orderBy('pop.id')->get();
    }

    public function otherSales(int $businessId, int $shiftId): Collection
    {
        return DB::table('pump_operator_other_sales as poos')
            ->where('poos.business_id', $businessId)
            ->where('poos.shift_id', $shiftId)
            ->orderBy('poos.id')
            ->get();
    }

    public function sourceSnapshot(int $businessId, int $shiftId, ?int $pumpOperatorId = null): array
    {
        return [
            'shift' => DB::table('petro_shifts')->where('business_id', $businessId)->where('id', $shiftId)->first(),
            'assignments' => $this->assignments($businessId, $shiftId),
            'meter_sales' => $this->meterSales($businessId, $shiftId, $pumpOperatorId),
            'payments' => $this->payments($businessId, $shiftId, $pumpOperatorId),
            'other_sales' => $this->otherSales($businessId, $shiftId),
        ];
    }
}
