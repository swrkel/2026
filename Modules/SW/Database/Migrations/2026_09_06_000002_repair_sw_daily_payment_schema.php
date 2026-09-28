<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * IS2198 - align the tenant schema with the current SW Daily Cash/Card/Cheque code.
 *
 * The original SW migration predates Collection Form numbers, the current Card
 * fields and Daily Cheques.  Existing tenants may therefore have only part of
 * the schema even though the current controllers/views expect all of it.  This
 * migration is deliberately idempotent and non-destructive.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->repairDailyCash();
        $this->repairDailyCards();
        $this->repairDailyCheques();
        $this->backfillDailyCards();
        $this->refreshRunningTotals('sw_daily_cards');
        $this->refreshRunningTotals('sw_daily_cheques');
    }

    protected function repairDailyCash(): void
    {
        if (! Schema::hasTable('sw_daily_cash')) {
            return;
        }

        $missing = [
            'business_id' => ! Schema::hasColumn('sw_daily_cash', 'business_id'),
            'collection_form_no' => ! Schema::hasColumn('sw_daily_cash', 'collection_form_no'),
            'current_amount' => ! Schema::hasColumn('sw_daily_cash', 'current_amount'),
            'balance_collection' => ! Schema::hasColumn('sw_daily_cash', 'balance_collection'),
            'denom_qty' => ! Schema::hasColumn('sw_daily_cash', 'denom_qty'),
            'collection_date' => ! Schema::hasColumn('sw_daily_cash', 'collection_date'),
            'updated_by' => ! Schema::hasColumn('sw_daily_cash', 'updated_by'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('sw_daily_cash', function (Blueprint $table) use ($missing) {
                if ($missing['business_id']) $table->unsignedInteger('business_id')->nullable();
                if ($missing['collection_form_no']) $table->string('collection_form_no', 60)->nullable();
                if ($missing['current_amount']) $table->decimal('current_amount', 22, 4)->default(0);
                if ($missing['balance_collection']) $table->decimal('balance_collection', 22, 4)->default(0);
                if ($missing['denom_qty']) $table->text('denom_qty')->nullable();
                if ($missing['collection_date']) $table->date('collection_date')->nullable();
                if ($missing['updated_by']) $table->unsignedInteger('updated_by')->nullable();
            });
        }

        // Bring old rows forward without changing their monetary value.
        if (Schema::hasColumn('sw_daily_cash', 'current_amount') && Schema::hasColumn('sw_daily_cash', 'amount')) {
            DB::table('sw_daily_cash')->where('current_amount', 0)->where('amount', '!=', 0)
                ->update(['current_amount' => DB::raw('amount')]);
        }

        if (Schema::hasColumn('sw_daily_cash', 'business_id') && Schema::hasTable('sw_shifts')) {
            $rows = DB::table('sw_daily_cash as dc')
                ->join('sw_shifts as s', 's.id', '=', 'dc.sw_shift_id')
                ->whereNull('dc.business_id')
                ->get(['dc.id', 's.business_id']);

            foreach ($rows as $row) {
                DB::table('sw_daily_cash')->where('id', $row->id)
                    ->update(['business_id' => $row->business_id]);
            }
        }
    }

    protected function repairDailyCards(): void
    {
        if (! Schema::hasTable('sw_daily_cards')) {
            Schema::create('sw_daily_cards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->string('collection_form_no', 60)->nullable();
                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->unsignedInteger('card_type_account_id')->nullable()->index();
                $table->string('card_number', 100)->nullable();
                $table->string('slip_no', 100)->nullable();
                $table->date('card_date')->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->decimal('total_collection', 22, 4)->default(0);
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
            return;
        }

        $missing = [
            'sw_shift_id' => ! Schema::hasColumn('sw_daily_cards', 'sw_shift_id'),
            'pump_operator_id' => ! Schema::hasColumn('sw_daily_cards', 'pump_operator_id'),
            'collection_form_no' => ! Schema::hasColumn('sw_daily_cards', 'collection_form_no'),
            'contact_id' => ! Schema::hasColumn('sw_daily_cards', 'contact_id'),
            'card_type_account_id' => ! Schema::hasColumn('sw_daily_cards', 'card_type_account_id'),
            'card_number' => ! Schema::hasColumn('sw_daily_cards', 'card_number'),
            'slip_no' => ! Schema::hasColumn('sw_daily_cards', 'slip_no'),
            'card_date' => ! Schema::hasColumn('sw_daily_cards', 'card_date'),
            'amount' => ! Schema::hasColumn('sw_daily_cards', 'amount'),
            'total_collection' => ! Schema::hasColumn('sw_daily_cards', 'total_collection'),
            'note' => ! Schema::hasColumn('sw_daily_cards', 'note'),
            'created_by' => ! Schema::hasColumn('sw_daily_cards', 'created_by'),
            'updated_by' => ! Schema::hasColumn('sw_daily_cards', 'updated_by'),
            'created_at' => ! Schema::hasColumn('sw_daily_cards', 'created_at'),
            'updated_at' => ! Schema::hasColumn('sw_daily_cards', 'updated_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('sw_daily_cards', function (Blueprint $table) use ($missing) {
                if ($missing['sw_shift_id']) $table->unsignedBigInteger('sw_shift_id')->nullable();
                if ($missing['pump_operator_id']) $table->unsignedInteger('pump_operator_id')->nullable();
                if ($missing['collection_form_no']) $table->string('collection_form_no', 60)->nullable();
                if ($missing['contact_id']) $table->unsignedInteger('contact_id')->nullable();
                if ($missing['card_type_account_id']) $table->unsignedInteger('card_type_account_id')->nullable();
                if ($missing['card_number']) $table->string('card_number', 100)->nullable();
                if ($missing['slip_no']) $table->string('slip_no', 100)->nullable();
                if ($missing['card_date']) $table->date('card_date')->nullable();
                if ($missing['amount']) $table->decimal('amount', 22, 4)->default(0);
                if ($missing['total_collection']) $table->decimal('total_collection', 22, 4)->default(0);
                if ($missing['note']) $table->text('note')->nullable();
                if ($missing['created_by']) $table->unsignedInteger('created_by')->nullable();
                if ($missing['updated_by']) $table->unsignedInteger('updated_by')->nullable();
                if ($missing['created_at']) $table->timestamp('created_at')->nullable();
                if ($missing['updated_at']) $table->timestamp('updated_at')->nullable();
            });
        }
    }

    protected function repairDailyCheques(): void
    {
        if (! Schema::hasTable('sw_daily_cheques')) {
            Schema::create('sw_daily_cheques', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('sw_shift_id')->index();
                $table->unsignedInteger('pump_operator_id')->index();
                $table->string('collection_form_no', 60)->nullable();
                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->string('cheque_no', 60);
                $table->string('bank', 191)->nullable();
                $table->date('cheque_date')->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->decimal('total_collection', 22, 4)->default(0);
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
            return;
        }

        $missing = [
            'sw_shift_id' => ! Schema::hasColumn('sw_daily_cheques', 'sw_shift_id'),
            'pump_operator_id' => ! Schema::hasColumn('sw_daily_cheques', 'pump_operator_id'),
            'collection_form_no' => ! Schema::hasColumn('sw_daily_cheques', 'collection_form_no'),
            'contact_id' => ! Schema::hasColumn('sw_daily_cheques', 'contact_id'),
            'cheque_no' => ! Schema::hasColumn('sw_daily_cheques', 'cheque_no'),
            'bank' => ! Schema::hasColumn('sw_daily_cheques', 'bank'),
            'cheque_date' => ! Schema::hasColumn('sw_daily_cheques', 'cheque_date'),
            'amount' => ! Schema::hasColumn('sw_daily_cheques', 'amount'),
            'total_collection' => ! Schema::hasColumn('sw_daily_cheques', 'total_collection'),
            'note' => ! Schema::hasColumn('sw_daily_cheques', 'note'),
            'created_by' => ! Schema::hasColumn('sw_daily_cheques', 'created_by'),
            'updated_by' => ! Schema::hasColumn('sw_daily_cheques', 'updated_by'),
            'created_at' => ! Schema::hasColumn('sw_daily_cheques', 'created_at'),
            'updated_at' => ! Schema::hasColumn('sw_daily_cheques', 'updated_at'),
        ];

        if (in_array(true, $missing, true)) {
            Schema::table('sw_daily_cheques', function (Blueprint $table) use ($missing) {
                if ($missing['sw_shift_id']) $table->unsignedBigInteger('sw_shift_id')->nullable();
                if ($missing['pump_operator_id']) $table->unsignedInteger('pump_operator_id')->nullable();
                if ($missing['collection_form_no']) $table->string('collection_form_no', 60)->nullable();
                if ($missing['contact_id']) $table->unsignedInteger('contact_id')->nullable();
                if ($missing['cheque_no']) $table->string('cheque_no', 60)->nullable();
                if ($missing['bank']) $table->string('bank', 191)->nullable();
                if ($missing['cheque_date']) $table->date('cheque_date')->nullable();
                if ($missing['amount']) $table->decimal('amount', 22, 4)->default(0);
                if ($missing['total_collection']) $table->decimal('total_collection', 22, 4)->default(0);
                if ($missing['note']) $table->text('note')->nullable();
                if ($missing['created_by']) $table->unsignedInteger('created_by')->nullable();
                if ($missing['updated_by']) $table->unsignedInteger('updated_by')->nullable();
                if ($missing['created_at']) $table->timestamp('created_at')->nullable();
                if ($missing['updated_at']) $table->timestamp('updated_at')->nullable();
            });
        }
    }

    protected function backfillDailyCards(): void
    {
        if (! Schema::hasTable('sw_daily_cards')) {
            return;
        }

        if (Schema::hasColumn('sw_daily_cards', 'card_type_account_id')
            && Schema::hasColumn('sw_daily_cards', 'account_id')) {
            DB::table('sw_daily_cards')
                ->whereNull('card_type_account_id')
                ->whereNotNull('account_id')
                ->update(['card_type_account_id' => DB::raw('account_id')]);
        }

        if (Schema::hasColumn('sw_daily_cards', 'slip_no')
            && Schema::hasColumn('sw_daily_cards', 'reference')) {
            DB::table('sw_daily_cards')
                ->whereNull('slip_no')
                ->whereNotNull('reference')
                ->update(['slip_no' => DB::raw('reference')]);
        }
    }

    protected function refreshRunningTotals(string $table): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'total_collection')
            || ! Schema::hasColumn($table, 'amount')) {
            return;
        }

        $groups = DB::table($table)
            ->select('sw_shift_id', 'pump_operator_id')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table($table)
                ->where('sw_shift_id', $group->sw_shift_id)
                ->where('pump_operator_id', $group->pump_operator_id)
                ->orderBy('id')
                ->get(['id', 'amount']);

            $running = 0.0;
            foreach ($rows as $row) {
                $running += (float) $row->amount;
                DB::table($table)->where('id', $row->id)
                    ->update(['total_collection' => round($running, 4)]);
            }
        }
    }

    /**
     * Repair migrations intentionally do not remove production payment data on
     * rollback.  A later structural migration can retire obsolete columns after
     * every tenant has been verified on the new schema.
     */
    public function down(): void
    {
        // Non-destructive by design.
    }
};
