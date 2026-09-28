<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communication_hub_messages')) {
            return;
        }

        Schema::table('communication_hub_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('communication_hub_messages', 'estimated_cost')) {
                $table->decimal('estimated_cost', 16, 4)->default(0)->after('cost');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'actual_cost')) {
                $table->decimal('actual_cost', 16, 4)->default(0)->after('estimated_cost');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'currency')) {
                $table->string('currency', 10)->default('LKR')->after('actual_cost');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'wallet_charge_status')) {
                $table->string('wallet_charge_status', 30)->nullable()->index()->after('currency');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'wallet_transaction_reference')) {
                $table->string('wallet_transaction_reference')->nullable()->index()->after('wallet_charge_status');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'wallet_response')) {
                $table->json('wallet_response')->nullable()->after('wallet_transaction_reference');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->index()->after('source_reference');
            }
            if (! Schema::hasColumn('communication_hub_messages', 'business_location_id')) {
                $table->unsignedBigInteger('business_location_id')->nullable()->index()->after('business_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('communication_hub_messages')) {
            return;
        }

        Schema::table('communication_hub_messages', function (Blueprint $table) {
            foreach ([
                'wallet_response',
                'wallet_transaction_reference',
                'wallet_charge_status',
                'currency',
                'actual_cost',
                'estimated_cost',
                'business_location_id',
                'business_id',
            ] as $column) {
                if (Schema::hasColumn('communication_hub_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
