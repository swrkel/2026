<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepositProductRulesColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('deposit_products')) {
            Schema::table('deposit_products', function (Blueprint $table) {
                if (! Schema::hasColumn('deposit_products', 'maximum_amount')) {
                    $table->decimal('maximum_amount', 20, 4)->nullable()->after('minimum_amount');
                }
                if (! Schema::hasColumn('deposit_products', 'interest_method')) {
                    $table->string('interest_method')->default('simple')->after('interest_frequency');
                }
                if (! Schema::hasColumn('deposit_products', 'renewal_policy')) {
                    $table->string('renewal_policy')->default('manual')->after('interest_method');
                }
                if (! Schema::hasColumn('deposit_products', 'premature_closure_allowed')) {
                    $table->boolean('premature_closure_allowed')->default(true)->after('renewal_policy');
                }
                if (! Schema::hasColumn('deposit_products', 'penalty_rate')) {
                    $table->decimal('penalty_rate', 20, 6)->default(0)->after('premature_closure_allowed');
                }
                if (! Schema::hasColumn('deposit_products', 'require_nominee')) {
                    $table->boolean('require_nominee')->default(false)->after('penalty_rate');
                }
                if (! Schema::hasColumn('deposit_products', 'require_beneficiary')) {
                    $table->boolean('require_beneficiary')->default(false)->after('require_nominee');
                }
                if (! Schema::hasColumn('deposit_products', 'account_prefix')) {
                    $table->string('account_prefix', 20)->nullable()->after('code');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('deposit_products')) {
            Schema::table('deposit_products', function (Blueprint $table) {
                foreach ([
                    'maximum_amount', 'interest_method', 'renewal_policy', 'premature_closure_allowed',
                    'penalty_rate', 'require_nominee', 'require_beneficiary', 'account_prefix'
                ] as $column) {
                    if (Schema::hasColumn('deposit_products', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
