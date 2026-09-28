<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_collections', function (Blueprint $table) {
            $table->string('type')->default('daily_collection'); // values: daily_collection, daily_collection_sw
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_collections', function (Blueprint $table) {
            //
        });
    }
};
