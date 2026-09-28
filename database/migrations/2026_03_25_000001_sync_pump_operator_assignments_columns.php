<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pump_operator_assignments')) {
            return;
        }

        Schema::table('pump_operator_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('pump_operator_assignments', 'close_date_and_time')) {
                $table->timestamp('close_date_and_time')->nullable()->after('date_and_time');
            }
            if (! Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
                $table->unsignedInteger('settlement_id')->nullable()->index()->after('status');
            }
            if (! Schema::hasColumn('pump_operator_assignments', 'assigned_by')) {
                $table->integer('assigned_by')->nullable()->after('updated_at');
            }
            if (! Schema::hasColumn('pump_operator_assignments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('is_confirmed');
            }
            if (! Schema::hasColumn('pump_operator_assignments', 'is_manually_closed')) {
                $table->integer('is_manually_closed')->default(0)->after('confirmed_at');
            }
            if (! Schema::hasColumn('pump_operator_assignments', 'pump_operator_other_sale_id')) {
                $table->integer('pump_operator_other_sale_id')->nullable()->index()->after('is_manually_closed');
            }
            if (! Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
                $table->integer('closed_in_settlement')->default(0)->after('pump_operator_other_sale_id');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('pump_operator_assignments')) {
            return;
        }

        Schema::table('pump_operator_assignments', function (Blueprint $table) {
            foreach ([
                'close_date_and_time',
                'settlement_id',
                'assigned_by',
                'confirmed_at',
                'is_manually_closed',
                'pump_operator_other_sale_id',
                'closed_in_settlement',
            ] as $column) {
                if (Schema::hasColumn('pump_operator_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
