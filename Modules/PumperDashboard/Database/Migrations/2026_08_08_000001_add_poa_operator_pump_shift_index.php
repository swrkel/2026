<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MA-002: the Pumper Day Entries page hangs on a three-column join.
 *
 * The page's ajax query joins pump_operator_assignments on THREE columns at
 * once:
 *
 *     poa.pump_operator_id = poms.pump_operator_id
 *     poa.pump_id          = pomsd.pump_id
 *     poa.shift_id         = poms.shift_id
 *
 * Every existing index on that table is SINGLE-COLUMN - business_id, pump_id,
 * pump_operator_id, shift_id, settlement_id. MySQL can use only one of them
 * and then checks the other two row by row.
 *
 * With seven joins stacked on top of that, the work multiplies and the page
 * spins rather than erroring.
 *
 * A composite index across the three columns lets the whole join condition be
 * satisfied in one lookup.
 *
 * READ-ONLY EFFECT: an index changes no data and no behaviour. It can be
 * dropped again with no consequence, which is why this is safe to run on a
 * live system.
 */
return new class extends Migration
{
    /**
     * Two indexes, both for joins this page performs.
     *
     *   pump_operator_assignments  joined on three columns at once, with only
     *                              single-column indexes available
     *
     *   daily_cards                joined TWICE on (business_id,
     *                              pump_operator_id), and has no index
     *                              covering that pair. Its sibling table
     *                              settlement_credit_sale_payments already
     *                              has one, which is how I spotted the gap.
     */
    private const INDEXES = [
        'pump_operator_assignments' => [
            'idx_poa_operator_pump_shift' => ['pump_operator_id', 'pump_id', 'shift_id'],
        ],
        'daily_cards' => [
            'idx_daily_cards_business_operator' => ['business_id', 'pump_operator_id'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                $missingColumn = false;

                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $missingColumn = true;
                        break;
                    }
                }

                if ($missingColumn || $this->indexExists($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! $this->indexExists($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }

    /**
     * Checked directly rather than with doctrine/dbal, which this build may
     * not have installed.
     */
    private function indexExists(string $table, string $name): bool
    {
        $rows = \DB::select(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
            [$name]
        );

        return ! empty($rows);
    }
};
