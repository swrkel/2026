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
        Schema::table('vat_invoices_2', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_invoices_2', 'place_of_supply')) {
                Schema::table('vat_invoices_2', function (Blueprint $table) {
                    $table->string('place_of_supply')
                        ->nullable()
                        ->after('supplied_on');
                });
            }
            $table->string('total_amount_words')->nullable()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vat_invoices_2', function (Blueprint $table) {
            $table->dropColumn(['place_of_supply', 'total_amount_words']);
        });
    }
};
