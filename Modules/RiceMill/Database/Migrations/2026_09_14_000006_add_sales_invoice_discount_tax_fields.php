<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rcm_dispatches')) {
            return;
        }

        if (! Schema::hasColumn('rcm_dispatches', 'discount_type')) {
            Schema::table('rcm_dispatches', function (Blueprint $t) {
                $t->string('discount_type', 20)->default('fixed')->after('subtotal');
            });
        }
        if (! Schema::hasColumn('rcm_dispatches', 'discount_value')) {
            Schema::table('rcm_dispatches', function (Blueprint $t) {
                $t->decimal('discount_value', 20, 4)->default(0)->after('discount_type');
            });
        }
        if (! Schema::hasColumn('rcm_dispatches', 'tax_percent')) {
            Schema::table('rcm_dispatches', function (Blueprint $t) {
                $t->decimal('tax_percent', 10, 4)->default(0)->after('discount_amount');
            });
        }

        // Preserve the meaning of historical sales. Their old discount field
        // was a fixed currency amount, while Tax Amount can be converted back
        // into its percentage when a positive taxable base exists.
        DB::statement("UPDATE `rcm_dispatches` SET `discount_type`='fixed', `discount_value`=`discount_amount` WHERE `discount_value`=0 AND `discount_amount`<>0");
        DB::statement("UPDATE `rcm_dispatches` SET `tax_percent`=ROUND((`tax_amount` / (`subtotal` - `discount_amount`)) * 100, 4) WHERE `tax_percent`=0 AND `tax_amount`<>0 AND (`subtotal` - `discount_amount`) > 0");
    }

    public function down(): void
    {
        if (! Schema::hasTable('rcm_dispatches')) {
            return;
        }

        $drop = [];
        foreach (['discount_type', 'discount_value', 'tax_percent'] as $column) {
            if (Schema::hasColumn('rcm_dispatches', $column)) {
                $drop[] = $column;
            }
        }
        if ($drop) {
            Schema::table('rcm_dispatches', function (Blueprint $t) use ($drop) {
                $t->dropColumn($drop);
            });
        }
    }
};
