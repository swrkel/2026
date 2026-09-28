<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureAccountTransactionsReconcileStatusForFinanceBankReconciliation extends Migration
{
    public function up()
    {
        if (Schema::hasTable('account_transactions') && !Schema::hasColumn('account_transactions', 'reconcile_status')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->boolean('reconcile_status')->default(false)->index();
            });
        }
    }

    public function down()
    {
        // Intentionally not removed on rollback. This column may be shared with
        // the pre-existing Account Book reconciliation control.
    }
}
