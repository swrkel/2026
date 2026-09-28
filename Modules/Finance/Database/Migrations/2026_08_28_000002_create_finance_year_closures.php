<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Close Financial Year.
 *
 * Once a year is closed, no transaction may be dated on or before the closing
 * date. That constrains PURCHASES, SALES, EXPENSES, JOURNALS, SETTLEMENTS -
 * everything - which is why this lives in Finance rather than in whichever
 * module happened to need it first. A rule that only one module consults is
 * worse than no rule: it produces a system that refuses an entry in one screen
 * and accepts the same entry in another.
 *
 * A TABLE, NOT A COLUMN
 *
 * A single `closed_upto` column on the business would record the current state
 * and nothing else. Closing a financial year is a deliberate act with real
 * consequences, and the questions asked afterwards are "who closed it, when,
 * and was it ever reopened". A row per closure answers those; a column does
 * not.
 *
 * Reopening is allowed, for a superadmin, and recorded rather than erased -
 * the closure row stays, marked reopened. Deleting it would lose the fact that
 * a year was ever closed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_year_closures')) {
            Schema::create('finance_year_closures', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();

                /*
                 | Everything on or before this date is closed. Held as a date
                 | rather than a year so a business closing part-way through -
                 | a change of accounting period, an acquisition - is expressible.
                */
                $table->date('closed_upto')->index();

                $table->string('label', 100)->nullable();   // "FY 2025/26"
                $table->text('note')->nullable();

                $table->unsignedInteger('closed_by');
                $table->timestamp('closed_at');

                // Reopening is recorded, never deleted.
                $table->unsignedInteger('reopened_by')->nullable();
                $table->timestamp('reopened_at')->nullable();
                $table->text('reopen_reason')->nullable();

                $table->timestamps();

                $table->index(['business_id', 'reopened_at'], 'fyc_active_lookup');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_year_closures');
    }
};
