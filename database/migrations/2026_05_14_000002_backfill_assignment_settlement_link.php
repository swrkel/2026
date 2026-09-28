<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settlements') || ! Schema::hasTable('pump_operator_assignments')) {
            return;
        }

        DB::table('settlements')
            ->where('status', 0)
            ->whereNotNull('work_shift')
            ->orderBy('id')
            ->select(['id', 'business_id', 'pump_operator_id', 'work_shift'])
            ->chunkById(200, function ($settlements) {
                foreach ($settlements as $settlement) {
                    $shiftNumbers = $this->parseWorkShift($settlement->work_shift);
                    if (empty($shiftNumbers)) {
                        continue;
                    }

                    DB::table('pump_operator_assignments')
                        ->where('business_id', $settlement->business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereNull('settlement_id')
                        ->whereNotNull('shift_id')
                        ->whereIn('shift_number', $shiftNumbers)
                        ->update([
                            'settlement_id' => $settlement->id,
                            'closed_in_settlement' => 1,
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        // Data backfill only. Intentionally not reversible.
    }

    private function parseWorkShift($workShift): array
    {
        if ($workShift === null || $workShift === '') {
            return [];
        }

        $decoded = json_decode($workShift, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $values = is_array($decoded) ? $decoded : [$decoded];
        } else {
            $values = explode(',', trim((string) $workShift, '[]'));
        }

        return collect($values)
            ->map(fn($value) => trim((string) $value, " \t\n\r\0\x0B\"'"))
            ->filter(fn($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }
};
