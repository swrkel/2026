<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MA-002: remember which basis a product's profit percentage was measured on.
 *
 * variations.profit_percent stores a single number. Without this column that
 * number means two different real margins depending on a setting nobody
 * recorded - and any report reading it would be right only half the time.
 *
 * Existing rows default to 'exclusive', which is how every figure stored
 * before today was actually calculated, so nothing changes meaning.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (Schema::hasColumn('products', 'profit_basis')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('profit_basis', 20)
                ->default('exclusive')
                ->after('tax_type')
                ->comment('exclusive|inclusive - basis for variations.profit_percent');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        if (! Schema::hasColumn('products', 'profit_basis')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('profit_basis');
        });
    }
};
