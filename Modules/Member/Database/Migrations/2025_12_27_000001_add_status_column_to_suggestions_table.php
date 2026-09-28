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
        if (!Schema::hasTable('suggestions')) {
            return;
        }
        if (Schema::hasColumn('suggestions', 'status')) {
            return;
        }
        Schema::table('suggestions', function (Blueprint $table) {
            // Add 'status' column before 'state_of_urgency'
            $table->string('status')->default('pending')->after('state_of_urgency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suggestions', function (Blueprint $table) {
            // Drop the 'status' column if rollback is executed
            $table->dropColumn('status');
        });
    }
};
