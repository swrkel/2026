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
        Schema::table('vat_contacts', function (Blueprint $table) {
            $table->string('address')->nullable()->after('vat_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vat_contacts', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
