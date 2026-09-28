<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Petro PD Stable Recovery M2 V3
 *
 * Guard service for safe finalization. This file is intentionally standalone
 * inside PetroPD and uses the active tenant connection only.
 */
class PetroPdFinalizationGuard
{
    public function assertSettlementCanFinalize($settlement, ?int $businessId = null): void
    {
        if (!$settlement) {
            throw new RuntimeException('Settlement not found for finalization.');
        }

        if ($businessId !== null && isset($settlement->business_id) && (int) $settlement->business_id !== (int) $businessId) {
            throw new RuntimeException('Settlement does not belong to the active business.');
        }

        $status = strtolower((string) ($settlement->status ?? $settlement->settlement_status ?? ''));
        $isFinalized = (bool) ($settlement->is_finalized ?? false)
            || in_array($status, ['finalized', 'finalised', 'closed', 'completed'], true);

        if ($isFinalized) {
            throw new RuntimeException('This settlement is already finalized.');
        }
    }

    public function lockSettlementRow(string $table, int $id)
    {
        if (!Schema::hasTable($table)) {
            throw new RuntimeException("Required table {$table} does not exist in tenant database.");
        }

        return DB::table($table)->where('id', $id)->lockForUpdate()->first();
    }

    public function assertBalanced(float $debit, float $credit, float $tolerance = 0.01): void
    {
        if (abs(round($debit, 2) - round($credit, 2)) > $tolerance) {
            throw new RuntimeException('Petro PD posting is not balanced. Finalization stopped before save.');
        }
    }
}
