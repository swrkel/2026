<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'vat_settlement_credit_sale_payments';
    private const COLUMN = 'transaction_id';
    private const INDEX = 'uk_vat_scsp_settlement_transaction';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        if ($this->indexExists()) {
            return;
        }

        $dupes = DB::select(
            "SELECT business_id, settlement_no, `" . self::COLUMN . "`, COUNT(*) AS c " .
            "FROM `" . self::TABLE . "` WHERE `" . self::COLUMN . "` IS NOT NULL " .
            "GROUP BY business_id, settlement_no, `" . self::COLUMN . "` HAVING c > 1"
        );

        if (! empty($dupes)) {
            throw new \RuntimeException(
                "Pre-flight failed on " . self::TABLE . ": " . count($dupes) .
                " duplicate (business_id, settlement_no, " . self::COLUMN . ") groups exist."
            );
        }

        DB::statement(
            "ALTER TABLE `" . self::TABLE . "` ADD UNIQUE KEY `" . self::INDEX . "` " .
            "(`business_id`, `settlement_no`, `" . self::COLUMN . "`)"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! $this->indexExists()) {
            return;
        }

        DB::statement("ALTER TABLE `" . self::TABLE . "` DROP INDEX `" . self::INDEX . "`");
    }

    private function indexExists(): bool
    {
        $rows = DB::select("SHOW INDEX FROM `" . self::TABLE . "` WHERE Key_name = ?", [self::INDEX]);
        return count($rows) > 0;
    }
};
