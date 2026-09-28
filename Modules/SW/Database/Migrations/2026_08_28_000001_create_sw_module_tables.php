<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8041 / 8042 SW Module - schema.
 *
 * THE OPERATION, as described in 8042 and confirmed in discussion:
 *
 *   open  ->  Close Shift  ->  closed  ->  Settlement  ->  settled
 *
 * A SW Shift belongs to a LOCATION and has MANY operators. Operators are
 * assigned to the shift, not to particular pumps.
 *
 * Daily entries - Cash, Credit Sales, Cards - are recorded PER OPERATOR,
 * because the money is that operator's responsibility. Balance In Hand is
 * computed PER SHIFT, because three of its four inputs (customer payments,
 * expenses, cash deposits) are recorded on Finance and core screens which ask
 * for a shift but not an operator.
 *
 * WHICH SHIFTS APPEAR WHERE
 *   expense / customer payment / cash deposit dropdowns -> OPEN shifts
 *   SW Settlement dropdown                              -> CLOSED shifts
 * A shift must be closed before it can be settled, so nothing can be attributed
 * to it while it is being reconciled. That is what makes Balance In Hand a
 * figure at a moment rather than a moving total.
 *
 * SHARED vs OWNED. pumps, pump_operators, contacts, products and
 * business_locations are READ, not copied - they are the business's records and
 * exist whether or not this module does. Everything this module records itself,
 * it owns. That line is what lets SettlementSW be retired without touching SW.
 *
 * ACCOUNTS come from the Finance module, never from core.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         | The SW Shift. Location scoped, because everything in this system is.
         |
         | sw_shift_no is SW-<location>-<n>, n running per location and neither
         | part padded: SW-3-1, SW-14-1. The number is unique per location, not
         | per business, so the unique key includes location_id.
        */
        if (! Schema::hasTable('sw_shifts')) {
            Schema::create('sw_shifts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->index();

                $table->string('sw_shift_no', 60);
                $table->date('shift_date')->index();
                $table->string('shift_name', 100)->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();

                // 0 open, 1 closed, 2 settled, 3 void.
                $table->unsignedTinyInteger('status')->default(0)->index();

                $table->timestamp('closed_at')->nullable();
                $table->unsignedInteger('closed_by')->nullable();
                // Reopening is allowed but recorded: closing too early should
                // not trap anyone, but it should leave a trace.
                $table->timestamp('reopened_at')->nullable();
                $table->unsignedInteger('reopened_by')->nullable();

                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'location_id', 'sw_shift_no'], 'sw_shifts_unique_no');
                $table->index(['location_id', 'shift_date', 'status'], 'sw_shifts_loc_date_status');
            });
        }

        /*
         | Operators on a shift. Many per shift, and NOT tied to a pump - pumps
         | are handled at settlement.
        */
        if (! Schema::hasTable('sw_shift_operators')) {
            Schema::create('sw_shift_operators', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->timestamps();

                $table->unique(['sw_shift_id', 'pump_operator_id'], 'sw_shift_operator_unique');
                $table->foreign('sw_shift_id')->references('id')->on('sw_shifts')->onDelete('cascade');
            });
        }

        /*
         | The three daily tabs. Each row carries BOTH the shift and the
         | operator: the shift is what the money is reconciled against, the
         | operator is who is answerable for it.
        */
        if (! Schema::hasTable('sw_daily_cash')) {
            Schema::create('sw_daily_cash', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('sw_shift_id')->references('id')->on('sw_shifts')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('sw_daily_credit_sales')) {
            Schema::create('sw_daily_credit_sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->string('vehicle_no', 60)->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('reference', 191)->nullable();
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('sw_shift_id')->references('id')->on('sw_shifts')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('sw_daily_cards')) {
            Schema::create('sw_daily_cards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->string('card_type', 60)->nullable();
                // The Finance account this card settlement lands in.
                $table->unsignedInteger('account_id')->nullable()->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('reference', 191)->nullable();
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('sw_shift_id')->references('id')->on('sw_shifts')->onDelete('cascade');
            });
        }

        /*
         | Settlement, against a CLOSED shift.
         |
         | No pump_operator_id here: a shift has many operators, so a settlement
         | reconciles the shift rather than one person.
        */
        if (! Schema::hasTable('sw_settlements')) {
            Schema::create('sw_settlements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->index();
                $table->string('settlement_no', 60);
                $table->date('transaction_date')->index();
                $table->date('finish_date')->nullable();

                /*
                 | 8043: one operator per settlement, and several of THAT
                 | operator's shifts selected against it. The shifts live in
                 | sw_settlement_shifts - a settlement covering three shifts is
                 | normal, so a single shift_id column would not do.
                */
                $table->unsignedInteger('pump_operator_id')->nullable()->index();

                // Note and coin breakdown. Free text on the old table.
                $table->json('cash_denomination')->nullable();
                $table->text('note')->nullable();

                /*
                 | Section totals, held so the list page and reports need not
                 | re-sum every line of every settlement to show a figure.
                 | Recomputed from the lines whenever the settlement is saved -
                 | they are a cache of the lines, never the source.
                */
                $table->decimal('total_meter_sales', 22, 4)->default(0);
                $table->decimal('total_other_sales', 22, 4)->default(0);
                $table->decimal('total_other_income', 22, 4)->default(0);
                $table->decimal('total_credit_sales', 22, 4)->default(0);
                $table->decimal('total_sales', 22, 4)->default(0);
                $table->decimal('total_collected', 22, 4)->default(0);
                $table->decimal('variance', 22, 4)->default(0);

                // 0 draft, 1 open, 2 settled, 3 void.
                $table->unsignedTinyInteger('status')->default(0)->index();
                $table->boolean('is_edited')->default(0);

                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'location_id', 'settlement_no'], 'sw_settlements_unique_no');
            });
        }

        /*
         | 8043: the shifts a settlement covers.
         |
         | Several SW Shifts can be settled together, so this is a join rather
         | than a column. Every shift listed must be CLOSED and must belong to
         | the settlement's operator - both are enforced when saving.
         |
         | The unique key stops the same shift being settled twice, which would
         | double-count its collections.
        */
        if (! Schema::hasTable('sw_settlement_shifts')) {
            Schema::create('sw_settlement_shifts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('settlement_id')->index();
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->timestamps();

                $table->unique(['settlement_id', 'sw_shift_id'], 'sw_settlement_shift_unique');
                $table->unique('sw_shift_id', 'sw_shift_settled_once');

                $table->foreign('settlement_id')->references('id')->on('sw_settlements')->onDelete('cascade');
                $table->foreign('sw_shift_id')->references('id')->on('sw_shifts');
            });
        }

        /*
         | Meter readings, one row per pump. Separate from the settlement so a
         | site with twelve pumps needs no twelve columns, and per-pump
         | reporting is a query rather than a parse.
        */
        if (! Schema::hasTable('sw_settlement_lines')) {
            Schema::create('sw_settlement_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('settlement_id')->index();
                $table->unsignedInteger('pump_id')->index();
                $table->unsignedInteger('product_id')->nullable()->index();

                // decimal(13,4) matches pumps.starting_meter, so a reading
                // copied from the pump cannot lose precision in transit.
                $table->decimal('opening_meter', 13, 4)->default(0);
                // The table calls this "Reconfirm Meter"; it is the same
                // reading the entry form takes as Pump Closing Meter.
                $table->decimal('closing_meter', 13, 4)->default(0);
                $table->decimal('testing_qty', 22, 4)->default(0);
                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('rate', 22, 4)->default(0);

                // 8043: discount per line, fixed or percentage.
                $table->string('discount_type', 20)->nullable();
                $table->decimal('discount_value', 22, 4)->default(0);
                $table->decimal('amount_before_discount', 22, 4)->default(0);
                $table->decimal('amount', 22, 4)->default(0);

                $table->timestamps();

                $table->foreign('settlement_id')->references('id')->on('sw_settlements')->onDelete('cascade');
            });
        }

        /*
         | 8044: Other Sales - stock items sold during the shift.
         |
         | Separate from meter sales because these come out of a STORE and
         | reduce stock, where meter sales come off a pump. Same discount
         | treatment, so the columns match sw_settlement_lines.
        */
        if (! Schema::hasTable('sw_other_sales')) {
            Schema::create('sw_other_sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('settlement_id')->index();

                $table->unsignedInteger('store_id')->nullable()->index();
                $table->unsignedInteger('product_id')->index();
                $table->unsignedInteger('variation_id')->nullable()->index();

                /*
                 | The stock on hand WHEN THE LINE WAS ADDED. Recorded rather
                 | than looked up later: stock moves, and a settlement printed
                 | next month should show what the operator saw, not today's
                 | figure.
                */
                $table->decimal('balance_stock', 22, 4)->default(0);

                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('rate', 22, 4)->default(0);

                $table->string('discount_type', 20)->nullable();
                $table->decimal('discount_value', 22, 4)->default(0);
                $table->decimal('amount_before_discount', 22, 4)->default(0);
                $table->decimal('amount', 22, 4)->default(0);

                $table->timestamps();

                $table->foreign('settlement_id')->references('id')->on('sw_settlements')->onDelete('cascade');
            });
        }

        /*
         | 8045: Other Income - services rather than goods.
         |
         | The Service dropdown lists products NOT under stock management, so
         | nothing here touches stock. A permitted user may override the price,
         | which is why the amount is stored per line rather than read from the
         | product each time.
        */
        if (! Schema::hasTable('sw_other_income')) {
            Schema::create('sw_other_income', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('settlement_id')->index();

                $table->unsignedInteger('product_id')->index();   // the service
                $table->string('details', 191)->nullable();
                $table->decimal('quantity', 22, 4)->default(1);

                // The rate actually charged. Defaults to the product's price,
                // but a permitted user may edit it, so the line keeps its own.
                $table->decimal('rate', 22, 4)->default(0);
                $table->decimal('amount', 22, 4)->default(0);

                /*
                 | Whether the price was overridden, and by whom. A changed
                 | price is the sort of thing questioned later, and "the product
                 | costs this now" does not answer what was charged then.
                */
                $table->boolean('price_edited')->default(0);
                $table->unsignedInteger('price_edited_by')->nullable();

                $table->timestamps();

                $table->foreign('settlement_id')->references('id')->on('sw_settlements')->onDelete('cascade');
            });
        }

        /*
         | Credit sales on the settlement.
         |
         | These AUTO-LOAD from sw_daily_credit_sales for the shifts selected
         | and the settlement's operator - both must match. The operator may
         | then add more at settlement time.
         |
         | A copy rather than a join, deliberately. The daily row records what
         | was entered during the shift; this records what was settled. If a
         | figure is corrected at settlement the two differ, and that difference
         | is worth being able to see. daily_credit_sale_id links back to the
         | original, or is null for a line added here.
        */
        if (! Schema::hasTable('sw_settlement_credit_sales')) {
            Schema::create('sw_settlement_credit_sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('settlement_id')->index();

                // null when added at settlement rather than carried from a shift
                $table->unsignedBigInteger('daily_credit_sale_id')->nullable()->index();

                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->unsignedInteger('pump_operator_id')->nullable()->index();
                $table->string('vehicle_no', 60)->nullable();

                // IS2232: keep the same product/price/discount audit detail the
                // settlement Credit Sales form shows. Existing tenant tables are
                // self-healed by SettlementSaveService before the first save.
                $table->string('order_no', 191)->nullable();
                $table->date('order_date')->nullable();
                $table->unsignedInteger('product_id')->nullable()->index();
                $table->unsignedInteger('variation_id')->nullable()->index();
                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('unit_discount', 22, 4)->default(0);
                $table->decimal('amount_before_discount', 22, 4)->default(0);

                $table->decimal('amount', 22, 4)->default(0);
                $table->string('reference', 191)->nullable();
                $table->text('note')->nullable();

                $table->timestamps();

                $table->foreign('settlement_id')->references('id')->on('sw_settlements')->onDelete('cascade');
            });
        }

        /*
         | What was collected against the settlement, and where it posts.
         | account_id is a FINANCE account.
        */
        if (! Schema::hasTable('sw_collections')) {
            Schema::create('sw_collections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('settlement_id')->index();
                $table->string('payment_method', 60)->index();
                $table->unsignedInteger('account_id')->nullable()->index();
                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('reference', 191)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->foreign('settlement_id')->references('id')->on('sw_settlements')->onDelete('cascade');
            });
        }

        /*
         | Per-location number sequences, for both shifts and settlements.
         |
         | A counter row rather than MAX()+1: deriving the next number from the
         | highest existing one means two people saving at the same moment read
         | the same maximum and write the same number. This row is locked for
         | the moment it is read and incremented.
        */
        if (! Schema::hasTable('sw_number_sequences')) {
            Schema::create('sw_number_sequences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('location_id')->index();
                $table->string('document_type', 40)->default('shift');
                $table->string('prefix', 20)->default('SW');
                $table->unsignedBigInteger('next_number')->default(1);
                $table->timestamps();

                $table->unique(['business_id', 'location_id', 'document_type'], 'sw_seq_unique');
            });
        }

        /*
         | sw_shift_no on the three tables whose figures feed Balance In Hand.
         |
         | Each already has a `shift_number` column, empty on every database
         | checked. It is left untouched: it can be reviewed in a few weeks and
         | dropped if still unused. Adding a column costs nothing; renaming one
         | that some forgotten code still references costs an outage.
         |
         | Nullable because most rows will never have one. Indexed because
         | Daily Cash Status filters on it.
        */
        foreach (['transactions', 'transaction_payments', 'account_transactions'] as $t) {
            if (Schema::hasTable($t) && ! Schema::hasColumn($t, 'sw_shift_no')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->string('sw_shift_no', 60)->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['transactions', 'transaction_payments', 'account_transactions'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'sw_shift_no')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropColumn('sw_shift_no');
                });
            }
        }

        Schema::dropIfExists('sw_settlement_credit_sales');
        Schema::dropIfExists('sw_other_income');
        Schema::dropIfExists('sw_other_sales');
        Schema::dropIfExists('sw_collections');
        Schema::dropIfExists('sw_settlement_lines');
        Schema::dropIfExists('sw_settlements');
        Schema::dropIfExists('sw_daily_cards');
        Schema::dropIfExists('sw_daily_credit_sales');
        Schema::dropIfExists('sw_daily_cash');
        Schema::dropIfExists('sw_shift_operators');
        Schema::dropIfExists('sw_shifts');
        Schema::dropIfExists('sw_number_sequences');
    }
};
