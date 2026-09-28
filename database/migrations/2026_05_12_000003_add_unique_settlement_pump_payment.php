<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 3 Lock 1 — DB-layer UNIQUE constraint on (business_id, settlement_no, pump_payment_id).
 *
 * Runs a preflight check first; aborts loudly if any client DB has duplicates
 * on the non-NULL subset (the constraint would otherwise fail mid-ALTER).
 * MySQL treats NULL as distinct, so historical NULL rows are exempt.
 */
return new class extends Migration
{
    private const TABLES = [
        'settlement_card_payments',
        'settlement_cash_payments',
        'settlement_cheque_payments',
        'settlement_credit_sale_payments',
    ];

    public function up(): void
    {
        $this->preflightCheck();

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $indexName = "uk_{$table}_settlement_pump";
            if ($this->indexExists($table, $indexName)) {
                continue;
            }
            DB::statement(
                "ALTER TABLE `{$table}` " .
                "ADD UNIQUE KEY `{$indexName}` (`business_id`, `settlement_no`, `pump_payment_id`)"
            );
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $indexName = "uk_{$table}_settlement_pump";
            if (! $this->indexExists($table, $indexName)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }

    private function preflightCheck(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'pump_payment_id')) {
                continue;
            }
            $dupes = DB::select(
                "SELECT business_id, settlement_no, pump_payment_id, COUNT(*) AS c " .
                "FROM `{$table}` WHERE pump_payment_id IS NOT NULL " .
                "GROUP BY business_id, settlement_no, pump_payment_id HAVING c > 1"
            );
            if (! empty($dupes)) {
                throw new \RuntimeException(
                    "Pre-flight failed on {$table}: " . count($dupes) . " duplicate " .
                    "(business_id, settlement_no, pump_payment_id) groups exist. " .
                    "Resolve manually before re-running this migration. " .
                    "See docs/refactor/day1-manual-dedups.md."
                );
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($rows) > 0;
    }
};
