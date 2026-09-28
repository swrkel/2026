<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expnew_expenses') || Schema::hasColumn('expnew_expenses', 'accounting_module')) {
            return;
        }

        Schema::table('expnew_expenses', function (Blueprint $table): void {
            $table->string('accounting_module', 50)
                ->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expnew_expenses') || ! Schema::hasColumn('expnew_expenses', 'accounting_module')) {
            return;
        }

        Schema::table('expnew_expenses', function (Blueprint $table): void {
            $table->dropColumn('accounting_module');
        });
    }
};
