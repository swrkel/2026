<?php

namespace Modules\PetroPDNew\Services\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use RuntimeException;

class PoneSettlementReferenceWriter
{
    public function assertAvailableForCreation(int $businessId, int $shiftId, bool $lock = false): void
    {
        if (! Schema::hasTable('pone_shift_settlement_references')) {
            throw new RuntimeException('Pumper Dashboard-New settlement-reference table is missing.');
        }

        if ($lock) {
            $this->lockSourceShift($businessId, $shiftId);
        }

        $query = DB::table('pone_shift_settlement_references')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $existing = $query->first();

        if ($existing) {
            throw new RuntimeException(
                'This Pumper Dashboard-New shift is already finalized under settlement '
                . $existing->settlement_no . '. A second Petro PD-New settlement cannot be created.'
            );
        }
    }

    public function write(PdnewSettlement $settlement, int $userId): void
    {
        if (! Schema::hasTable('pone_shift_settlement_references')) {
            throw new RuntimeException('Pumper Dashboard-New settlement-reference table is missing.');
        }

        $businessId = (int) $settlement->business_id;
        $shiftId = (int) $settlement->pone_shift_id;
        $this->lockSourceShift($businessId, $shiftId);

        $query = DB::table('pone_shift_settlement_references')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId);
        $existing = $query->lockForUpdate()->first();

        if ($existing && (string) $existing->settlement_no !== (string) $settlement->settlement_number) {
            throw new RuntimeException(
                'This Pumper Dashboard-New shift is already linked to settlement ' . $existing->settlement_no . '.'
            );
        }

        $values = [
            'settlement_no' => $settlement->settlement_number,
            'settlement_date' => optional($settlement->settlement_date)->format('Y-m-d')
                ?: (string) $settlement->settlement_date,
            'note' => 'Finalized by Petro PD-New. Source hash: ' . $settlement->source_hash,
            'created_by' => $userId,
            'updated_at' => now(),
        ];

        if ($existing) {
            $query->update($values);
        } else {
            DB::table('pone_shift_settlement_references')->insert(array_merge(
                [
                    'business_id' => $businessId,
                    'shift_id' => $shiftId,
                    'created_at' => now(),
                ],
                $values
            ));
        }
    }

    public function remove(PdnewSettlement $settlement): void
    {
        if (! Schema::hasTable('pone_shift_settlement_references')) return;

        $businessId = (int) $settlement->business_id;
        $shiftId = (int) $settlement->pone_shift_id;
        $this->lockSourceShift($businessId, $shiftId);

        DB::table('pone_shift_settlement_references')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->where('settlement_no', $settlement->settlement_number)
            ->delete();
    }

    private function lockSourceShift(int $businessId, int $shiftId): void
    {
        if (! Schema::hasTable('pone_shifts')) {
            throw new RuntimeException('Pumper Dashboard-New shift table is missing.');
        }

        $shift = DB::table('pone_shifts')
            ->select('id')
            ->where('business_id', $businessId)
            ->where('id', $shiftId)
            ->lockForUpdate()
            ->first();

        if (! $shift) {
            throw new RuntimeException(
                'The Pumper Dashboard-New shift does not belong to the active business.'
            );
        }
    }
}
