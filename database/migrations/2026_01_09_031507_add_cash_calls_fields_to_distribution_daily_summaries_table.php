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
        Schema::table('distribution_daily_summaries', function (Blueprint $table) {
            $table->decimal('cash_deposited', 18, 4)->default(0)->after('grand_total');
            $table->decimal('cheque_deposited', 18, 4)->default(0)->after('cash_deposited');
            $table->decimal('credit_bills_bf', 18, 4)->default(0)->after('cheque_deposited');
            $table->decimal('cheques_in_hand', 18, 4)->default(0)->after('credit_bills_bf');
            $table->decimal('credit_bills_in_hand', 18, 4)->default(0)->after('cheques_in_hand');
            $table->decimal('cash_in_hand', 18, 4)->default(0)->after('credit_bills_in_hand');
            $table->integer('calls_visited')->default(0)->after('cash_in_hand');
            $table->integer('productive_calls')->default(0)->after('calls_visited');
            $table->integer('calls_visited_bf')->default(0)->after('productive_calls');
            $table->integer('productive_calls_bf')->default(0)->after('calls_visited_bf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_daily_summaries', function (Blueprint $table) {
            $table->dropColumn([
                'cash_deposited',
                'cheque_deposited',
                'credit_bills_bf',
                'cheques_in_hand',
                'credit_bills_in_hand',
                'cash_in_hand',
                'calls_visited',
                'productive_calls',
                'calls_visited_bf',
                'productive_calls_bf',
            ]);
        });
    }
};
