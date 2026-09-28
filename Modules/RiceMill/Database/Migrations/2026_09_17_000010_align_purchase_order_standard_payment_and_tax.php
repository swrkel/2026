<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('rcm_paddy_purchases')) {
            Schema::table('rcm_paddy_purchases', function (Blueprint $t) {
                if (!Schema::hasColumn('rcm_paddy_purchases','purchase_tax_id')) {
                    $t->unsignedBigInteger('purchase_tax_id')->nullable()->index();
                }
                if (!Schema::hasColumn('rcm_paddy_purchases','purchase_tax_percent')) {
                    $t->decimal('purchase_tax_percent',10,4)->default(0);
                }
                if (!Schema::hasColumn('rcm_paddy_purchases','purchase_tax_amount')) {
                    $t->decimal('purchase_tax_amount',20,4)->default(0);
                }
                if (!Schema::hasColumn('rcm_paddy_purchases','core_transaction_id')) {
                    $t->unsignedBigInteger('core_transaction_id')->nullable()->index();
                }
                if (!Schema::hasColumn('rcm_paddy_purchases','payment_status')) {
                    $t->string('payment_status',30)->default('due')->index();
                }
            });
        }

        if (Schema::hasTable('rcm_purchase_payments')) {
            Schema::table('rcm_purchase_payments', function (Blueprint $t) {
                if (!Schema::hasColumn('rcm_purchase_payments','transaction_id')) {
                    $t->unsignedBigInteger('transaction_id')->nullable()->index();
                }
                if (!Schema::hasColumn('rcm_purchase_payments','transaction_payment_id')) {
                    $t->unsignedBigInteger('transaction_payment_id')->nullable()->index();
                }
                if (!Schema::hasColumn('rcm_purchase_payments','payment_status')) {
                    $t->string('payment_status',30)->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rcm_purchase_payments')) {
            Schema::table('rcm_purchase_payments', function (Blueprint $t) {
                foreach (['payment_status','transaction_payment_id','transaction_id'] as $column) {
                    if (Schema::hasColumn('rcm_purchase_payments',$column)) $t->dropColumn($column);
                }
            });
        }
        if (Schema::hasTable('rcm_paddy_purchases')) {
            Schema::table('rcm_paddy_purchases', function (Blueprint $t) {
                foreach (['payment_status','core_transaction_id','purchase_tax_amount','purchase_tax_percent','purchase_tax_id'] as $column) {
                    if (Schema::hasColumn('rcm_paddy_purchases',$column)) $t->dropColumn($column);
                }
            });
        }
    }
};
