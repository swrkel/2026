<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pdirectnew_operators')) {
            return;
        }

        Schema::table('pdirectnew_operators', function (Blueprint $table) {
            if (!Schema::hasColumn('pdirectnew_operators', 'source_operator_id')) {
                $table->unsignedBigInteger('source_operator_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'address')) {
                $table->text('address')->nullable()->after('name');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'landline')) {
                $table->string('landline', 50)->nullable()->after('mobile');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'dob')) {
                $table->date('dob')->nullable()->after('landline');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'email')) {
                $table->string('email', 190)->nullable()->after('nic');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'username')) {
                $table->string('username', 100)->nullable()->after('email');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'passcode_hash')) {
                $table->string('passcode_hash', 255)->nullable()->after('username');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'opening_balance')) {
                $table->decimal('opening_balance', 22, 4)->default(0)->after('passcode_hash');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'commission_type')) {
                $table->string('commission_type', 30)->default('none')->after('opening_balance');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'commission_value')) {
                $table->decimal('commission_value', 22, 4)->default(0)->after('commission_type');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'short_amount')) {
                $table->decimal('short_amount', 22, 4)->default(0)->after('commission_value');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'excess_amount')) {
                $table->decimal('excess_amount', 22, 4)->default(0)->after('short_amount');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('excess_amount');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('transaction_date');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'can_fullscreen')) {
                $table->boolean('can_fullscreen')->default(false)->after('is_default');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
                $table->boolean('hide_in_direct_settlement_if_pending_shifts')->default(false)->after('can_fullscreen');
            }
            if (!Schema::hasColumn('pdirectnew_operators', 'source_updated_at')) {
                $table->dateTime('source_updated_at')->nullable()->after('is_active');
            }
        });

        try {
            Schema::table('pdirectnew_operators', function (Blueprint $table) {
                $table->unique(['business_id', 'source_operator_id'], 'pdn_operator_source_uq');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('pdirectnew_operators', function (Blueprint $table) {
                $table->index(['business_id', 'location_id', 'is_active', 'name'], 'pdn_operator_list_idx');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: operator history and configuration must remain.
    }
};
