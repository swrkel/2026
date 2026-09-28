<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preserve leading zeros in every VAT prefix starting number.
     */
    public function up(): void
    {
        foreach (['vat_prefixes', 'vat_invoice2_prefixes', 'vat_statement_prefixes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'starting_no')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `starting_no` VARCHAR(50) NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // Intentionally not converted back to an integer because doing so would destroy
        // configured leading zeros and could change existing invoice sequences.
    }
};
