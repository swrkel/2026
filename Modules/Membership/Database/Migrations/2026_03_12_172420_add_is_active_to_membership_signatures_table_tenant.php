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
        Schema::table('membership_signatures', function (Blueprint $table) {
            $table->boolean('is_active')->default(0)->after('signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_signatures', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
