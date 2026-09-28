<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('petro_pd_amount_adjustment_requests')) {
            return;
        }

        $this->addIndexIfMissing(
            'petro_pd_amount_adjustment_requests',
            'ppd_adj_req_business_requested_idx',
            'ALTER TABLE `petro_pd_amount_adjustment_requests` ADD INDEX `ppd_adj_req_business_requested_idx` (`business_id`, `requested_at`)'
        );

        $this->addIndexIfMissing(
            'petro_pd_amount_adjustment_requests',
            'ppd_adj_req_business_applied_idx',
            'ALTER TABLE `petro_pd_amount_adjustment_requests` ADD INDEX `ppd_adj_req_business_applied_idx` (`business_id`, `applied_at`)'
        );

        $this->addIndexIfMissing(
            'petro_pd_amount_adjustment_requests',
            'ppd_adj_req_business_operator_idx',
            'ALTER TABLE `petro_pd_amount_adjustment_requests` ADD INDEX `ppd_adj_req_business_operator_idx` (`business_id`, `pump_operator_id`)'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('petro_pd_amount_adjustment_requests')) {
            return;
        }

        $this->dropIndexIfExists('petro_pd_amount_adjustment_requests', 'ppd_adj_req_business_requested_idx');
        $this->dropIndexIfExists('petro_pd_amount_adjustment_requests', 'ppd_adj_req_business_applied_idx');
        $this->dropIndexIfExists('petro_pd_amount_adjustment_requests', 'ppd_adj_req_business_operator_idx');
    }

    private function addIndexIfMissing(string $table, string $index, string $sql): void
    {
        if (! $this->indexExists($table, $index)) {
            DB::statement($sql);
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
