<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('meter_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $table->decimal('mechanical_last_meter', 15, 3)->nullable()->after('closing_meter');
            }

            if (! Schema::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $table->decimal('mechanical_digital_last_meter', 15, 3)->nullable()->after('mechanical_last_meter');
            }

            if (! Schema::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $table->decimal('mechanical_meter_difference', 15, 3)->nullable()->after('mechanical_digital_last_meter');
            }
        });
    }

    public function down()
    {
        Schema::table('meter_sales', function (Blueprint $table) {
            if (Schema::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $table->dropColumn('mechanical_meter_difference');
            }

            if (Schema::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $table->dropColumn('mechanical_digital_last_meter');
            }

            if (Schema::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $table->dropColumn('mechanical_last_meter');
            }
        });
    }
};
