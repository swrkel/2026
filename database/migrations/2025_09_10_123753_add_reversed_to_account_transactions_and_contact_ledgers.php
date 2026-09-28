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
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->boolean('reversed')
                ->default(0)
                ->comment('Marks old rows reversed on edit');
        });

        Schema::table('contact_ledgers', function (Blueprint $table) {
            $table->boolean('reversed')
                ->default(0)
                ->comment('Marks old rows reversed on edit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('reversed');
        });

        Schema::table('contact_ledgers', function (Blueprint $table) {
            $table->dropColumn('reversed');
        });
    }
};
