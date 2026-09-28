<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pump_operators')) {
            return;
        }

        Schema::table('pump_operators', function (Blueprint $table) {
            if (! Schema::hasColumn('pump_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
                $table->boolean('hide_in_direct_settlement_if_pending_shifts')
                    ->default(false)
                    ->after('can_fullscreen');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('pump_operators')) {
            return;
        }

        Schema::table('pump_operators', function (Blueprint $table) {
            if (Schema::hasColumn('pump_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
                $table->dropColumn('hide_in_direct_settlement_if_pending_shifts');
            }
        });
    }
};
