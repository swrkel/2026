<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['mn_regions', 'mn_setting_options'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'is_active')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->index();
                });
            }
        }

        $creatorTables = [
            'mn_members',
            'mn_plans',
            'mn_payments',
            'mn_linked_businesses',
            'mn_point_rules',
            'mn_point_transactions',
            'mn_share_holdings',
            'mn_dividend_batches',
            'mn_dividend_payments',
            'mn_identity_cards',
            'mn_customer_maps',
            'mn_central_members',
            'mn_member_business_maps',
            'mn_business_customer_histories',
            'mn_duplicate_candidates',
            'mn_outlet_transaction_queue',
            'mn_merge_requests',
            'mn_dividend_payouts',
            'mn_audit_logs',
            'mn_approval_requests',
            'mn_business_access_rules',
            'mn_error_logs',
            'mn_import_batches',
            'mn_regions',
            'mn_setting_options',
        ];

        foreach ($creatorTables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'created_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->index();
            });
        }

        // Backfill only where the historical source column unambiguously means the creator/requester.
        $this->backfill('mn_audit_logs', 'user_id');
        $this->backfill('mn_error_logs', 'user_id');
        $this->backfill('mn_approval_requests', 'requested_by');
    }

    private function backfill(string $tableName, string $sourceColumn): void
    {
        if (!Schema::hasTable($tableName)
            || !Schema::hasColumn($tableName, 'created_by')
            || !Schema::hasColumn($tableName, $sourceColumn)) {
            return;
        }

        DB::table($tableName)
            ->whereNull('created_by')
            ->whereNotNull($sourceColumn)
            ->update(['created_by' => DB::raw('`' . $sourceColumn . '`')]);
    }

    public function down(): void
    {
        // Intentionally non-destructive. Creator/status history must not be removed on rollback.
    }
};
