<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_members', 'renewal_date')) {
                $table->date('renewal_date')->nullable()->after('renewal_cycles');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (Schema::hasColumn('membership_members', 'renewal_date')) {
                $table->dropColumn('renewal_date');
            }
        });
    }
};
