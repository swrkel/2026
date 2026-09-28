<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pump_operator_meter_sales') && ! Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            Schema::table('pump_operator_meter_sales', function (Blueprint $table) {
                $table->string('source', 20)->nullable()->after('testing_qty');
            });

            // Backfill: tag payment-flow records via pump_operator_assignments.pump_operator_other_sale_id
            DB::statement("
                UPDATE pump_operator_meter_sales poms
                INNER JOIN pump_operator_meter_sale_details pomsd ON pomsd.sale_id = poms.id
                INNER JOIN pump_operator_assignments poa ON poa.pump_operator_other_sale_id = pomsd.id
                SET poms.source = 'payment'
            ");

            // Backfill: tag remaining records as closing
            DB::statement("
                UPDATE pump_operator_meter_sales
                SET source = 'closing'
                WHERE source IS NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('pump_operator_meter_sales') && Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            Schema::table('pump_operator_meter_sales', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
