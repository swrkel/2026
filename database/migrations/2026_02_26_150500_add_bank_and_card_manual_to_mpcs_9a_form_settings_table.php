<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mpcs_9a_form_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('mpcs_9a_form_settings', 'pre_day_bank_manual')) {
                $table->text('pre_day_bank_manual')->nullable()->after('pre_day_cheques');
            }

            if (! Schema::hasColumn('mpcs_9a_form_settings', 'pre_day_card_manual')) {
                $table->text('pre_day_card_manual')->nullable()->after('pre_day_bank_manual');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mpcs_9a_form_settings', function (Blueprint $table) {
            if (Schema::hasColumn('mpcs_9a_form_settings', 'pre_day_bank_manual')) {
                $table->dropColumn('pre_day_bank_manual');
            }
            if (Schema::hasColumn('mpcs_9a_form_settings', 'pre_day_card_manual')) {
                $table->dropColumn('pre_day_card_manual');
            }
        });
    }
};

