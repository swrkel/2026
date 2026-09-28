<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('day_ends', function (Blueprint $table) {
            $table->text('pos_transaction_ids')->nullable()->after('pumps');
        });
    }

    public function down()
    {
        Schema::table('day_ends', function (Blueprint $table) {
            $table->dropColumn('pos_transaction_ids');
        });
    }
};
