<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('rcm_dispatches') && ! Schema::hasColumn('rcm_dispatches', 'unit_discount_amount')) {
            Schema::table('rcm_dispatches', function (Blueprint $t) {
                $t->decimal('unit_discount_amount', 20, 4)->default(0)->after('subtotal');
            });
        }

        if (Schema::hasTable('rcm_dispatch_lines')) {
            if (! Schema::hasColumn('rcm_dispatch_lines', 'unit_discount_type')) {
                Schema::table('rcm_dispatch_lines', function (Blueprint $t) {
                    $t->string('unit_discount_type', 20)->default('fixed')->after('unit_price');
                });
            }
            if (! Schema::hasColumn('rcm_dispatch_lines', 'unit_discount_value')) {
                Schema::table('rcm_dispatch_lines', function (Blueprint $t) {
                    $t->decimal('unit_discount_value', 20, 4)->default(0)->after('unit_discount_type');
                });
            }
            if (! Schema::hasColumn('rcm_dispatch_lines', 'unit_discount_amount')) {
                Schema::table('rcm_dispatch_lines', function (Blueprint $t) {
                    $t->decimal('unit_discount_amount', 20, 4)->default(0)->after('unit_discount_value');
                });
            }
            if (! Schema::hasColumn('rcm_dispatch_lines', 'net_unit_price')) {
                Schema::table('rcm_dispatch_lines', function (Blueprint $t) {
                    $t->decimal('net_unit_price', 20, 4)->default(0)->after('unit_discount_amount');
                });
            }

            // Historical rows had no per-unit discount; their net unit price is
            // therefore the original unit price.
            DB::statement("UPDATE `rcm_dispatch_lines` SET `unit_discount_type`='fixed', `unit_discount_value`=0, `unit_discount_amount`=0, `net_unit_price`=`unit_price` WHERE `net_unit_price`=0");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rcm_dispatch_lines')) {
            $drop=[];
            foreach (['unit_discount_type','unit_discount_value','unit_discount_amount','net_unit_price'] as $column) {
                if (Schema::hasColumn('rcm_dispatch_lines',$column)) {$drop[]=$column;}
            }
            if ($drop) {
                Schema::table('rcm_dispatch_lines', function (Blueprint $t) use ($drop) {$t->dropColumn($drop);});
            }
        }

        if (Schema::hasTable('rcm_dispatches') && Schema::hasColumn('rcm_dispatches', 'unit_discount_amount')) {
            Schema::table('rcm_dispatches', function (Blueprint $t) {$t->dropColumn('unit_discount_amount');});
        }
    }
};
