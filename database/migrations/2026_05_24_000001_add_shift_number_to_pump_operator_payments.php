<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pump_operator_payments') && ! Schema::hasColumn('pump_operator_payments', 'shift_number')) {
            Schema::table('pump_operator_payments', function (Blueprint $table) {
                $table->string('shift_number', 50)->nullable()->after('shift_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pump_operator_payments') && Schema::hasColumn('pump_operator_payments', 'shift_number')) {
            Schema::table('pump_operator_payments', function (Blueprint $table) {
                $table->dropColumn('shift_number');
            });
        }
    }
};
