<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MA-002 (S-622): "Need to Send SMS" on a customer.
 *
 * The field was missing from both the Add and Edit forms because there is
 * nowhere to store it - contacts has no such column. Adding the dropdown
 * without this migration would have thrown "Unknown column" on every save.
 *
 * DEFAULTS TO 1 (yes), deliberately.
 *
 * Every existing customer currently receives notifications - there is no flag
 * to stop them - so defaulting to 0 would SILENTLY SWITCH OFF SMS FOR EVERY
 * CUSTOMER ON THE SYSTEM the moment this ran. Defaulting to 1 preserves
 * exactly what happens today, and switching a customer off becomes a
 * deliberate act.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contacts')) {
            return;
        }

        if (Schema::hasColumn('contacts', 'need_to_send_sms')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('need_to_send_sms')
                ->default(1)
                ->after('credit_notification');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('contacts') && Schema::hasColumn('contacts', 'need_to_send_sms')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('need_to_send_sms');
            });
        }
    }
};
