<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('settlement_credit_sale_payments', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            if (Schema::hasColumn('settlement_credit_sale_payments', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
        });
    }
};
