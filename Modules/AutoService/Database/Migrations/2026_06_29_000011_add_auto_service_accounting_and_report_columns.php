<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('auto_service_invoices')) {
            Schema::table('auto_service_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_invoices', 'accounting_status')) {
                    $table->string('accounting_status')->default('not_posted')->after('status');
                }
                if (!Schema::hasColumn('auto_service_invoices', 'accounting_reference')) {
                    $table->string('accounting_reference')->nullable()->after('accounting_status');
                }
                if (!Schema::hasColumn('auto_service_invoices', 'accounting_posted_at')) {
                    $table->timestamp('accounting_posted_at')->nullable()->after('accounting_reference');
                }
            });
        }

        if (Schema::hasTable('auto_service_settings')) {
            foreach ([
                'enable_accounting_posting' => '0',
                'account_labour_income' => '',
                'account_parts_income' => '',
                'account_tax_payable' => '',
                'account_cash_bank' => '',
                'account_customer_receivable' => '',
                'account_parts_cost' => '',
                'account_inventory' => '',
            ] as $key => $value) {
                DB::table('auto_service_settings')->insertOrIgnore([
                    'business_id' => 0,
                    'key' => $key,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auto_service_invoices')) {
            Schema::table('auto_service_invoices', function (Blueprint $table) {
                foreach (['accounting_posted_at','accounting_reference','accounting_status'] as $column) {
                    if (Schema::hasColumn('auto_service_invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
