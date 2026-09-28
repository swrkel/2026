<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pos_payments')) {
            Schema::table('pos_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_payments', 'payment_row_no')) {
                    $table->unsignedInteger('payment_row_no')->default(1)->after('id');
                }
                if (!Schema::hasColumn('pos_payments', 'payment_account_id')) {
                    $table->unsignedBigInteger('payment_account_id')->nullable()->index()->after('payment_method');
                }
                if (!Schema::hasColumn('pos_payments', 'reference_no')) {
                    $table->string('reference_no')->nullable()->after('payment_account_id');
                }
                if (!Schema::hasColumn('pos_payments', 'card_type')) {
                    $table->string('card_type')->nullable()->after('reference_no');
                }
                if (!Schema::hasColumn('pos_payments', 'note')) {
                    $table->text('note')->nullable()->after('card_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_payments')) {
            Schema::table('pos_payments', function (Blueprint $table) {
                foreach (['note','card_type','reference_no','payment_account_id','payment_row_no'] as $column) {
                    if (Schema::hasColumn('pos_payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
