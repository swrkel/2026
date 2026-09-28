<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Facades\DB;

class PdSettlementSourceMarker
{
    public function markAsSettled(int $businessId, int $shiftId, int $settlementId, string $settlementNo): void
    {
        DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->update([
                'settlement_id' => $settlementId,
                'closed_in_settlement' => 1,
                'updated_at' => now(),
            ]);

        DB::table('pumper_day_entries')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->update([
                'settlement_no' => $settlementNo,
                'closed_in_settlement' => 1,
                'settlement_added_by' => auth()->id(),
                'updated_at' => now(),
            ]);

        DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->whereNull('deleted_at')
            ->update([
                'settlement_no' => $settlementNo,
                'is_used' => 1,
                'updated_at' => now(),
            ]);
    }
}
