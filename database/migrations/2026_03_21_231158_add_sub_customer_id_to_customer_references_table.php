<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Modified by Engr. Alex -- task 7882: Issue 1 - add sub_customer_id to customer_references for Vehicle No page dropdown
    public function up(): void
    {
        Schema::table('customer_references', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_references', 'sub_customer_id')) {
                $table->unsignedInteger('sub_customer_id')->nullable()->after('contact_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_references', function (Blueprint $table) {
            $table->dropColumn('sub_customer_id');
        });
    }
};
