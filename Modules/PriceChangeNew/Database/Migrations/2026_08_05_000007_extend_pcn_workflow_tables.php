<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pcn_price_changes')) {
            Schema::table('pcn_price_changes', function (Blueprint $table): void {
                if (! Schema::hasColumn('pcn_price_changes', 'application_scope')) {
                    $table->string('application_scope', 40)->default('business_base')->after('stock_price_mode')->index();
                }
                if (! Schema::hasColumn('pcn_price_changes', 'approval_notes')) {
                    $table->text('approval_notes')->nullable()->after('failure_message');
                }
                if (! Schema::hasColumn('pcn_price_changes', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('approval_notes');
                }
                if (! Schema::hasColumn('pcn_price_changes', 'cancelled_by')) {
                    $table->unsignedInteger('cancelled_by')->nullable()->after('applied_by')->index();
                }
                if (! Schema::hasColumn('pcn_price_changes', 'cancelled_at')) {
                    $table->dateTime('cancelled_at')->nullable()->after('applied_at');
                }
                if (! Schema::hasColumn('pcn_price_changes', 'last_attempt_at')) {
                    $table->dateTime('last_attempt_at')->nullable()->after('cancelled_at');
                }
                if (! Schema::hasColumn('pcn_price_changes', 'application_attempts')) {
                    $table->unsignedInteger('application_attempts')->default(0)->after('last_attempt_at');
                }
            });
        }

        if (Schema::hasTable('pcn_price_change_lines')) {
            Schema::table('pcn_price_change_lines', function (Blueprint $table): void {
                if (! Schema::hasColumn('pcn_price_change_lines', 'apply_status')) {
                    $table->string('apply_status', 30)->nullable()->after('new_profit_percent')->index();
                }
                if (! Schema::hasColumn('pcn_price_change_lines', 'apply_message')) {
                    $table->text('apply_message')->nullable()->after('apply_status');
                }
                foreach ([
                    'actual_before_purchase_price_ex_tax', 'actual_before_purchase_price_inc_tax',
                    'actual_before_sell_price_ex_tax', 'actual_before_sell_price_inc_tax',
                    'actual_after_purchase_price_ex_tax', 'actual_after_purchase_price_inc_tax',
                    'actual_after_sell_price_ex_tax', 'actual_after_sell_price_inc_tax',
                ] as $column) {
                    if (! Schema::hasColumn('pcn_price_change_lines', $column)) {
                        $table->decimal($column, 22, 8)->nullable();
                    }
                }
                if (! Schema::hasColumn('pcn_price_change_lines', 'applied_at')) {
                    $table->dateTime('applied_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive for tenant safety.
    }
};
