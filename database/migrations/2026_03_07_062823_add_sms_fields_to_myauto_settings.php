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
        if (!Schema::hasTable('myauto_settings')) {
            return;
        }

        Schema::table('myauto_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('myauto_settings', 'sms_mobile_numbers')) {
                $table->text('sms_mobile_numbers')->nullable()->after('passcode');
            }
            if (!Schema::hasColumn('myauto_settings', 'is_sms_enabled')) {
                $table->boolean('is_sms_enabled')->default(0)->after('sms_mobile_numbers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('myauto_settings')) {
            return;
        }

        Schema::table('myauto_settings', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('myauto_settings', 'sms_mobile_numbers')) {
                $dropColumns[] = 'sms_mobile_numbers';
            }
            if (Schema::hasColumn('myauto_settings', 'is_sms_enabled')) {
                $dropColumns[] = 'is_sms_enabled';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
