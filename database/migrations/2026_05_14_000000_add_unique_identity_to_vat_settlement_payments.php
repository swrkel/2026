<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEXES = [
        'vat_settlement_cash_payments' => [
            'column' => 'customer_payment_id',
            'index' => 'uk_vat_scp_settlement_customer_payment',
        ],
        'vat_settlement_card_payments' => [
            'column' => 'customer_payment_id',
            'index' => 'uk_vat_scdp_settlement_customer_payment',
        ],
        'vat_settlement_credit_sale_payments' => [
            'column' => 'transaction_id',
            'index' => 'uk_vat_scsp_settlement_transaction',
        ],
    ];

    public function up(): void
    {
        $this->preflightCheck();

        foreach (self::UNIQUE_INDEXES as $table => $config) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $config['column'])) {
                continue;
            }

            if ($this->indexExists($table, $config['index'])) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$table}` ADD UNIQUE KEY `{$config['index']}` " .
                "(`business_id`, `settlement_no`, `{$config['column']}`)"
            );
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUE_INDEXES as $table => $config) {
            if (! Schema::hasTable($table) || ! $this->indexExists($table, $config['index'])) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$config['index']}`");
        }
    }

    private function preflightCheck(): void
    {
        foreach (self::UNIQUE_INDEXES as $table => $config) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $config['column'])) {
                continue;
            }

            $column = $config['column'];
            $dupes = DB::select(
                "SELECT business_id, settlement_no, `{$column}`, COUNT(*) AS c " .
                "FROM `{$table}` WHERE `{$column}` IS NOT NULL " .
                "GROUP BY business_id, settlement_no, `{$column}` HAVING c > 1"
            );

            if (! empty($dupes)) {
                throw new \RuntimeException(
                    "Pre-flight failed on {$table}: " . count($dupes) . " duplicate " .
                    "(business_id, settlement_no, {$column}) groups exist. Resolve manually before re-running."
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
