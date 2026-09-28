<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Petro PD Stable Recovery M2 V4
 *
 * Central validation guard for Settlement / Payment / Finalize actions.
 * Keep this service tenant-safe: it uses the active Laravel connection only.
 */
class PetroPdSettlementValidationService
{
    public function assertBusinessId($businessId): int
    {
        $businessId = (int) $businessId;
        if ($businessId <= 0) {
            throw new InvalidArgumentException('Petro PD business id is missing. Please re-login to the correct business and try again.');
        }

        return $businessId;
    }

    public function assertShiftCanBeSettled(int $businessId, int $shiftId): void
    {
        if ($shiftId <= 0) {
            throw new InvalidArgumentException('Please select a valid closed shift.');
        }

        if (!DB::getSchemaBuilder()->hasTable('petro_shifts')) {
            return;
        }

        $shift = DB::table('petro_shifts')
            ->where('id', $shiftId)
            ->when(DB::getSchemaBuilder()->hasColumn('petro_shifts', 'business_id'), function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->first();

        if (!$shift) {
            throw new InvalidArgumentException('The selected shift does not belong to the active tenant/business.');
        }

        $status = strtolower((string) ($shift->status ?? $shift->shift_status ?? ''));
        if ($status && !in_array($status, ['closed', 'close', 'completed'], true)) {
            throw new InvalidArgumentException('Only closed shifts can be settled in Petro PD.');
        }
    }

    public function assertSettlementNotFinalized(int $settlementId): void
    {
        foreach (['petro_pd_settlements', 'settlements'] as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $settlement = DB::table($table)->where('id', $settlementId)->first();
            if (!$settlement) {
                continue;
            }

            $status = strtolower((string) ($settlement->status ?? $settlement->settlement_status ?? ''));
            $isFinalized = (int) ($settlement->is_finalized ?? $settlement->finalized ?? 0) === 1;

            if ($isFinalized || in_array($status, ['finalized', 'finalised', 'posted'], true)) {
                throw new InvalidArgumentException('This Petro PD settlement is already finalized and cannot be changed.');
            }

            return;
        }
    }
}
