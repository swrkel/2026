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
        Schema::table('form_f22_headers', function (Blueprint $table) {
            $table->string('approved_by', 250)->nullable()->after('manager_name');
            $table->tinyInteger('is_approved')->default(0)->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_f22_headers', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'is_approved']);
        });
    }
};
