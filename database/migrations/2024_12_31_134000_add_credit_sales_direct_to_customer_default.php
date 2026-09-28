<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * CODEX FIX: Migration to add credit_sales_direct_to_customer default to existing pump_operators
 * 
 * This migration fixes the "Undefined array key 'credit_sales_direct_to_customer'" error
 * that occurs on client servers where pump_operators records were created before this
 * setting was added to the dashboard settings form.
 * 
 * The migration is idempotent and safe to run multiple times.
 */
return new class extends Migration
{
    /**
     * Run the migrations - Add default credit_sales_direct_to_customer to existing settings
     */
    public function up(): void
    {
        // Guard: skip if pump_operators table doesn't exist yet (e.g. on a fresh migrate:fresh)
        if (!Schema::hasTable('pump_operators')) {
            return;
        }

        // Define all required defaults that should exist in dashboard_settings
        $defaults = [
            'credit_sales_direct_to_customer' => 'no',
            'show_bulk_pumps' => 'no',
            'meter_sales_compulsory' => 'no',
            'enter_cash_denominations' => 'no',
            'enter_card_numbers' => 'no',
            'card_amount_to_enter' => 'bulk',
        ];

        // Process pump_operators in chunks to avoid memory issues on large datasets
        DB::table('pump_operators')
            ->whereNotNull('dashboard_settings')
            ->where('dashboard_settings', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($operators) use ($defaults) {
                foreach ($operators as $operator) {
                    try {
                        $settings = json_decode($operator->dashboard_settings, true);

                        // Skip if JSON decode fails or returns non-array
                        if (!is_array($settings)) {
                            continue;
                        }

                        $needsUpdate = false;

                        // Check each default key and add if missing
                        foreach ($defaults as $key => $defaultValue) {
                            if (!array_key_exists($key, $settings)) {
                                $settings[$key] = $defaultValue;
                                $needsUpdate = true;
                            }
                        }

                        // Only update if changes were made (idempotent)
                        if ($needsUpdate) {
                            DB::table('pump_operators')
                                ->where('id', $operator->id)
                                ->update(['dashboard_settings' => json_encode($settings)]);
                        }
                    } catch (\Exception $e) {
                        // Log error but continue processing other records
                        Log::warning("Migration: Failed to update pump_operator {$operator->id}: " . $e->getMessage());
                    }
                }
            });

        // Also handle records where dashboard_settings is null or empty
        // by not doing anything - the controller/view now handles null gracefully
    }

    /**
     * Reverse the migrations
     * 
     * No destructive rollback - the key being present does not break anything.
     * Optionally could remove keys if they equal the default value, but safer to leave as-is.
     */
    public function down(): void
    {
        // No rollback action needed - non-destructive migration
        // The added keys do not break any functionality
    }
};
