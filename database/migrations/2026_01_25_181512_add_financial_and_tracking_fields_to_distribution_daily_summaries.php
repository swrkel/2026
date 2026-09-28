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
            $table->decimal('cash_deposited', 22, 4)->default(0)->after('grand_total');
            $table->decimal('cheque_deposited', 22, 4)->default(0)->after('cash_deposited');
            $table->decimal('credit_bills_bf', 22, 4)->default(0)->after('cheque_deposited');
            $table->decimal('cheques_in_hand', 22, 4)->default(0)->after('credit_bills_bf');
            $table->decimal('credit_bills_in_hand', 22, 4)->default(0)->after('cheques_in_hand');
            $table->decimal('cash_in_hand', 22, 4)->default(0)->after('credit_bills_in_hand');
            $table->unsignedInteger('calls_visited')->default(0)->after('cash_in_hand');
            $table->unsignedInteger('productive_calls')->default(0)->after('calls_visited');
            $table->unsignedInteger('calls_visited_bf')->default(0)->after('productive_calls');
            $table->unsignedInteger('productive_calls_bf')->default(0)->after('calls_visited_bf');
            $table->string('loading_sheet_no')->nullable()->after('productive_calls_bf');
            $table->json('loading_sheets')->nullable()->after('loading_sheet_no');
            $table->json('stock_status')->nullable()->after('loading_sheets');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_daily_summaries', function (Blueprint $table) {
            //
        });
    }
};
