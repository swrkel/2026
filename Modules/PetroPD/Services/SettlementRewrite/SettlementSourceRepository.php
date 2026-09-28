<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\PumperDayEntry;

class SettlementSourceRepository
{
    public function oldestPendingClosedShift(int $businessId): ?PetroShift
    {
        return PetroShift::query()
            ->where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('status', 2)
                    ->orWhereNotNull('closed_time');
            })
            ->whereExists(function ($query) use ($businessId) {
                $query->select(DB::raw(1))
                    ->from('pump_operator_assignments as poa')
                    ->whereColumn('poa.shift_id', 'petro_shifts.id')
                    ->where('poa.business_id', $businessId)
                    ->where('poa.status', 'close')
                    ->whereNotNull('poa.close_date_and_time')
                    ->where(function ($q) {
                        $q->whereNull('poa.settlement_id')
                            ->orWhere('poa.settlement_id', 0);
                    })
                    ->where(function ($q) {
                        $q->whereNull('poa.closed_in_settlement')
                            ->orWhere('poa.closed_in_settlement', 0);
                    });
            })
            ->orderBy('id', 'asc')
            ->first();
    }

    public function assignmentsForShift(int $businessId, int $shiftId): Collection
    {
        return PumpOperatorAssignment::query()
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->where('status', 'close')
            ->whereNotNull('close_date_and_time')
            ->where(function ($q) {
                $q->whereNull('settlement_id')->orWhere('settlement_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('closed_in_settlement')->orWhere('closed_in_settlement', 0);
            })
            ->orderBy('id')
            ->get();
    }

    public function meterSalesForShift(int $businessId, int $shiftId): Collection
    {
        $assignmentIds = $this->assignmentsForShift($businessId, $shiftId)->pluck('id')->filter()->values();

        $query = PumperDayEntry::query()
            ->where('business_id', $businessId)
            ->where(function ($q) {
                $q->whereNull('closed_in_settlement')->orWhere('closed_in_settlement', 0);
            })
            ->where(function ($q) {
                $q->whereNull('settlement_no')->orWhere('settlement_no', '');
            });

        $query->where(function ($q) use ($shiftId, $assignmentIds) {
            $q->where('shift_id', $shiftId);
            if ($assignmentIds->isNotEmpty()) {
                $q->orWhereIn('pumper_assignment_id', $assignmentIds->all());
            }
        });

        return $query->orderBy('id')
            ->get()
            ->unique(function ($row) {
                return implode('|', [
                    $row->pumper_assignment_id ?: 'no_assignment',
                    $row->pump_id,
                    number_format((float) $row->starting_meter, 6, '.', ''),
                    number_format((float) $row->closing_meter, 6, '.', ''),
                    number_format((float) $row->amount, 6, '.', ''),
                ]);
            })
            ->values();
    }

    public function paymentsForShift(int $businessId, int $shiftId): Collection
    {
        return PumpOperatorPayment::query()
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->orderBy('id')
            ->get();
    }

    public function otherSalesForShift(int $businessId, int $shiftId): Collection
    {
        return PumpOperatorOtherSale::query()
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->orderBy('id')
            ->get();
    }

    public function markSourcesAsSettled(int $businessId, int $shiftId, int $settlementId, string $settlementNo): void
    {
        PumpOperatorAssignment::query()
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->update([
                'settlement_id' => $settlementId,
                'closed_in_settlement' => 1,
            ]);

        PumperDayEntry::query()
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->update([
                'settlement_no' => $settlementNo,
                'closed_in_settlement' => 1,
            ]);

        PumpOperatorPayment::query()
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->whereNull('deleted_at')
            ->update([
                'settlement_no' => $settlementNo,
                'is_used' => 1,
            ]);
    }
}
