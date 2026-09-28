<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_members', 'renewal_period')) {
                $table->string('renewal_period', 20)->nullable()->after('membership_status_id');
            }

            if (!Schema::hasColumn('membership_members', 'renewal_cycles')) {
                $table->unsignedInteger('renewal_cycles')->nullable()->after('renewal_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (Schema::hasColumn('membership_members', 'renewal_cycles')) {
                $table->dropColumn('renewal_cycles');
            }

            if (Schema::hasColumn('membership_members', 'renewal_period')) {
                $table->dropColumn('renewal_period');
            }
        });
    }
};
