<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IS1991 (#1): storage for the Prefix List on Expenses-New > Settings.
 *
 * Until now a prefix was a single row in expnew_settings under the key
 * `category_code_prefix`. That holds ONE value, so saving a second prefix
 * silently overwrote the first and there was nothing to list, nothing to say
 * who created it, and nothing to edit or delete.
 *
 * This table keeps every prefix that has been saved, with its starting number,
 * the date text entered on the form and who saved it. expnew_settings is left
 * exactly as it is: it still carries the prefix currently in use, which is what
 * CategoryCodeGenerator reads, so code generation is unchanged by this table.
 *
 * The unique key is (business_id, prefix) - the same prefix saved twice updates
 * the existing row rather than filling the list with duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expnew_expense_prefixes')) {
            return;
        }

        Schema::create('expnew_expense_prefixes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_expense_prefixes_business_idx');
            $table->string('prefix', 20);
            // Kept as a string so a starting number of 0001 preserves its width,
            // which is what decides the padding of a generated code.
            $table->string('starting_no', 12)->nullable();
            // Free text, exactly as typed on the form (dd/mm/yyyy HH:mm).
            $table->string('code_date', 32)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'prefix'], 'expnew_expense_prefixes_business_prefix_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expnew_expense_prefixes');
    }
};
