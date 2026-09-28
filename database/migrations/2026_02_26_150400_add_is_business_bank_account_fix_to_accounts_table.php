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
        if (! Schema::hasColumn('accounts', 'is_business_bank_account')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->boolean('is_business_bank_account')
                    ->default(false)
                    ->after('account_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('accounts', 'is_business_bank_account')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('is_business_bank_account');
            });
        }
    }
};

