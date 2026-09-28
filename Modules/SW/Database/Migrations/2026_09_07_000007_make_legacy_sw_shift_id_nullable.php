<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The current SW Settlement supports several shifts through
 * sw_settlement_shifts. Some older tenant databases still have the previous
 * sw_settlements.sw_shift_id column as NOT NULL with a foreign key to sw_shifts.
 *
 * Keep the legacy column and FK for compatibility, but make the obsolete header
 * value nullable so it can never block a modern multi-shift settlement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sw_settlements')
            || ! Schema::hasColumn('sw_settlements', 'sw_shift_id')) {
            return;
        }

        try {
            $rows = DB::select("SHOW COLUMNS FROM `sw_settlements` LIKE 'sw_shift_id'");
            $column = $rows[0] ?? null;

            if (! $column) {
                return;
            }

            $nullable = strtoupper((string) ($column->Null ?? $column->null ?? 'NO')) === 'YES';
            $default = $column->Default ?? $column->default ?? null;

            if ($nullable && $default === null) {
                return;
            }

            $type = (string) ($column->Type ?? $column->type ?? '');
            if ($type === '' || ! preg_match('/^[A-Za-z0-9(), ]+$/', $type)) {
                return;
            }

            DB::statement(
                'ALTER TABLE `sw_settlements` MODIFY COLUMN `sw_shift_id` ' . $type . ' NULL DEFAULT NULL'
            );
        } catch (\Throwable $e) {
            // Runtime SettlementSaveService has the same guarded repair plus a
            // validated first-shift fallback where ALTER is unavailable.
        }
    }

    public function down(): void
    {
        // Compatibility-only migration. Never make the obsolete column
        // mandatory again on a live tenant database.
    }
};
