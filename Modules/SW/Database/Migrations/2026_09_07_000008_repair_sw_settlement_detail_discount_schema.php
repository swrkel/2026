<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IS2208 - repair discount columns on older SW settlement detail tables.
 *
 * Some tenant databases created sw_settlement_lines before the three discount
 * fields were added to the original SW CREATE migration. Editing that old
 * migration does not change an already-created table, so Save Settlement fails
 * with SQLSTATE[42S22] "Unknown column 'discount_type'". Add only missing
 * columns and leave all existing data/keys untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->repairDiscountColumns('sw_settlement_lines');
        $this->repairDiscountColumns('sw_other_sales');
    }

    private function repairDiscountColumns(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $missing = [
            'discount_type' => ! Schema::hasColumn($tableName, 'discount_type'),
            'discount_value' => ! Schema::hasColumn($tableName, 'discount_value'),
            'amount_before_discount' => ! Schema::hasColumn($tableName, 'amount_before_discount'),
        ];

        if (! in_array(true, $missing, true)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($missing) {
            if ($missing['discount_type']) {
                $table->string('discount_type', 20)->nullable();
            }
            if ($missing['discount_value']) {
                $table->decimal('discount_value', 22, 4)->default(0);
            }
            if ($missing['amount_before_discount']) {
                $table->decimal('amount_before_discount', 22, 4)->default(0);
            }
        });
    }

    public function down(): void
    {
        // Compatibility-only migration. Never remove columns from live tenant
        // settlement history during a rollback.
    }
};
