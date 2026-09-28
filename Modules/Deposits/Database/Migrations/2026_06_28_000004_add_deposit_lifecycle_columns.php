<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepositLifecycleColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('deposit_accounts')) {
            Schema::table('deposit_accounts', function (Blueprint $table) {
                if (! Schema::hasColumn('deposit_accounts', 'certificate_no')) {
                    $table->string('certificate_no')->nullable()->index()->after('account_no');
                }
                if (! Schema::hasColumn('deposit_accounts', 'maturity_amount')) {
                    $table->decimal('maturity_amount', 20, 4)->default(0)->after('current_balance');
                }
                if (! Schema::hasColumn('deposit_accounts', 'last_interest_posted_on')) {
                    $table->date('last_interest_posted_on')->nullable()->after('interest_accrued');
                }
                if (! Schema::hasColumn('deposit_accounts', 'closed_on')) {
                    $table->date('closed_on')->nullable()->after('maturity_on');
                }
                if (! Schema::hasColumn('deposit_accounts', 'auto_renew')) {
                    $table->boolean('auto_renew')->default(false)->after('status');
                }
                if (! Schema::hasColumn('deposit_accounts', 'renewed_from_account_id')) {
                    $table->unsignedBigInteger('renewed_from_account_id')->nullable()->index()->after('deposit_product_id');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('deposit_accounts')) {
            Schema::table('deposit_accounts', function (Blueprint $table) {
                foreach (['certificate_no','maturity_amount','last_interest_posted_on','closed_on','auto_renew','renewed_from_account_id'] as $column) {
                    if (Schema::hasColumn('deposit_accounts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
