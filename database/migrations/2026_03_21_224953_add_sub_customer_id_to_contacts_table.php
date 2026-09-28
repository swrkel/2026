<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Modified by Engr. Alex -- task 7882: Issue 1 - add sub_customer_id to contacts for Vehicle No page dropdown
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'sub_customer_id')) {
                $table->unsignedInteger('sub_customer_id')->nullable()->after('sub_customer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('sub_customer_id');
        });
    }
};
