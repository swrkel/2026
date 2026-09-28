<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_members', 'registration_renewal_amount')) {
                $table->decimal('registration_renewal_amount', 15, 2)->nullable()->after('renewal_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (Schema::hasColumn('membership_members', 'registration_renewal_amount')) {
                $table->dropColumn('registration_renewal_amount');
            }
        });
    }
};
