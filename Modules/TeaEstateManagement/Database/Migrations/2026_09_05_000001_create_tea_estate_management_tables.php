<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $path=__DIR__.'/../SQL/01_CREATE_TEA_TABLES.sql';
        if (is_file($path)) {
            $sql=file_get_contents($path);
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
                $statement=trim($statement);
                if ($statement!=='' && !str_starts_with($statement,'--')) DB::unprepared($statement);
                elseif ($statement!=='' && str_contains($statement,'CREATE TABLE')) {
                    $statement=preg_replace('/^(?:--[^\n]*\n)+/m','',$statement);
                    if(trim($statement)!=='') DB::unprepared($statement);
                }
            }
        }
        if (Schema::hasTable('tea_purchase_payments') && !Schema::hasColumn('tea_purchase_payments','finance_account_id')) {
            Schema::table('tea_purchase_payments', fn($t) => $t->unsignedBigInteger('finance_account_id')->nullable()->after('amount')->index());
        }
        if (Schema::hasTable('tea_sale_receipts') && !Schema::hasColumn('tea_sale_receipts','finance_account_id')) {
            Schema::table('tea_sale_receipts', fn($t) => $t->unsignedBigInteger('finance_account_id')->nullable()->after('amount')->index());
        }

        if (Schema::hasTable('tea_field_activities') && !Schema::hasColumn('tea_field_activities','finance_account_id')) {
            Schema::table('tea_field_activities', fn($t) => $t->unsignedBigInteger('finance_account_id')->nullable()->after('cost_amount')->index());
        }
        if (Schema::hasTable('tea_processing_stage_entries') && !Schema::hasColumn('tea_processing_stage_entries','cost_amount')) {
            Schema::table('tea_processing_stage_entries', fn($t) => $t->decimal('cost_amount',20,4)->default(0)->after('moisture_percent'));
        }
        if (Schema::hasTable('tea_processing_stage_entries') && !Schema::hasColumn('tea_processing_stage_entries','finance_account_id')) {
            Schema::table('tea_processing_stage_entries', fn($t) => $t->unsignedBigInteger('finance_account_id')->nullable()->after('cost_amount')->index());
        }

    }
    public function down(): void
    {
        foreach (['tea_finance_events','tea_finance_account_mappings','tea_audit_logs','tea_sale_receipts','tea_sale_lines','tea_sales','tea_stock_movements','tea_inventory_lots','tea_batch_outputs','tea_grades','tea_processing_stage_entries','tea_batch_inputs','tea_processing_batches','tea_processing_stages','tea_factories','tea_purchase_payments','tea_leaf_purchases','tea_parties','tea_harvests','tea_field_activities','tea_fields','tea_divisions','tea_estates','tea_varieties','tea_number_sequences','tea_settings'] as $table) Schema::dropIfExists($table);
    }
};
