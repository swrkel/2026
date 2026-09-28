<?php

namespace Modules\PetroPD\Http\Controllers\Settlement\Concerns;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\PetroPD\Entities\CustomerPayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DayEnd;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\OtherIncome;
use Modules\PetroPD\Entities\OtherSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PetroWhatsAppTemplate;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorCommission;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashDeposit;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementCustomerLoan;
use Modules\PetroPD\Entities\SettlementDrawingPayment;
use Modules\PetroPD\Entities\SettlementEditHistory;
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\TankSellLine;
use Modules\PetroPD\Entities\TanksTransactionDetail;
use Modules\PetroPD\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\PetroPD\Services\PetroPdSmsNotificationService;

/**
 * Cash reconciliation between the settlement and the pumper dashboard, and shortage/excess accounts.
 *
 * MA-002: split out of PetroPDSettlementController, which was 15,639 lines in
 * a single file - the largest controller in the application after core's
 * ReportController.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   PetroPDSettlementController, action() targets still resolve, and $this->
 *   calls between these 111 methods still work. Splitting into separate
 *   controller classes would mean rewriting routes and every action()
 *   reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: ensurePdSettlementCashMatchesPumperDashboard, resolvePdShortageReceivableAccountId, resolvePdExcessTransaction, resolvePdExcessPayableAccountId, ensurePdExcessAccountsPayableEntry, removePdShortageCashAccountFallback, removePdAutomaticCashCorrectionAccountRows, ensurePdSettlementCashMatchesSubmittedFinalizeTotal
 */
trait ReconcilesPdCash
{
    private function ensurePdSettlementCashMatchesPumperDashboard($settlement, int $business_id, array $work_shifts = []): void
    {
        try {
            if (empty($settlement) || empty($settlement->id)) {
                return;
            }

            $work_shifts = array_filter(array_map('intval', (array) $work_shifts));

            if (empty($work_shifts) && ! empty($settlement->work_shift)) {
                $decoded = is_array($settlement->work_shift)
                    ? $settlement->work_shift
                    : json_decode($settlement->work_shift, true);

                $work_shifts = is_array($decoded)
                    ? $decoded
                    : explode(',', (string) $settlement->work_shift);

                $work_shifts = array_filter(array_map('intval', (array) $work_shifts));
            }

            if (empty($work_shifts)) {
                Log::warning('PETROPD-CASHSAVE-ROOTFIX-004 skipped: missing work shifts', [
                    'settlement_id'    => $settlement->id,
                    'settlement_no'    => $settlement->settlement_no,
                    'pump_operator_id' => $settlement->pump_operator_id,
                ]);
                return;
            }

            // ROOT FIX 004:
            // Pumper Dashboard closed-shift summary calculates Cash for the full closed shift.
            // It does NOT restrict the management/settlement view to only the selected settlement
            // pump_operator_id. The previous fixes still used pump_operator_id here, so the
            // reconciliation expected amount was the already-reduced operator-only cash amount.
            // This must use the same source/scope as the dashboard: business + shift + payment_type.
            $expected_cash = (float) \Modules\PetroPD\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('payment_type', 'cash')
                ->whereIn('shift_id', $work_shifts)
                ->sum('payment_amount');

            $saved_cash_query = SettlementCashPayment::where('business_id', $business_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                });

            /*
             * Rebuild only rows created by PetroPD's own correction routines.
             * This also cleans an already-posted stale correction before the
             * submitted-total reconciliation runs. Genuine user, Daily Collection
             * and pumper-linked cash rows are excluded by source columns and note.
             */
            $automatic_corrections = (clone $saved_cash_query)
                ->where(function ($query) {
                    $query->where('note', 'like', 'PETROPD-CASHSAVE-ROOTFIX-004%')
                        ->orWhere('note', 'like', 'PETROPD-CASHSAVE-ROOTFIX-005%');
                })
                ->when(Schema::hasColumn('settlement_cash_payments', 'daily_collection_id'), function ($query) {
                    $query->whereNull('daily_collection_id');
                })
                ->when(Schema::hasColumn('settlement_cash_payments', 'customer_payment_id'), function ($query) {
                    $query->whereNull('customer_payment_id');
                })
                ->when(Schema::hasColumn('settlement_cash_payments', 'pump_payment_id'), function ($query) {
                    $query->whereNull('pump_payment_id');
                })
                ->orderBy('id')
                ->get();

            foreach ($automatic_corrections as $automatic_correction) {
                $this->removePdAutomaticCashCorrectionAccountRows(
                    $automatic_correction,
                    $business_id,
                    $settlement
                );
                $automatic_correction->delete();
            }

            $saved_cash = (float) (clone $saved_cash_query)->sum('amount');

            $missing_cash = round($expected_cash - $saved_cash, 4);

            Log::info('PETROPD-CASHSAVE-ROOTFIX-004 cash reconciliation check', [
                'settlement_id'    => $settlement->id,
                'settlement_no'    => $settlement->settlement_no,
                'pump_operator_id' => $settlement->pump_operator_id,
                'work_shifts'      => $work_shifts,
                'expected_cash_full_shift' => $expected_cash,
                'saved_cash'       => $saved_cash,
                'missing_cash'     => $missing_cash,
            ]);

            if ($missing_cash <= 0.0049) {
                return;
            }

            $walkin_customer = Contact::where('name', 'Walk-In Customer')
                ->where('business_id', $business_id)
                ->first();

            $data = [
                'business_id'    => $business_id,
                'settlement_no'  => $settlement->id,
                'amount'         => $missing_cash,
                'customer_id'    => $walkin_customer ? $walkin_customer->id : null,
                'note'           => 'PETROPD-CASHSAVE-ROOTFIX-004 auto cash correction: matched full closed-shift Pumper Dashboard cash total',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            if (Schema::hasColumn('settlement_cash_payments', 'pump_operator_id')) {
                $data['pump_operator_id'] = $settlement->pump_operator_id;
            }
            if (Schema::hasColumn('settlement_cash_payments', 'daily_collection_id')) {
                $data['daily_collection_id'] = null;
            }
            if (Schema::hasColumn('settlement_cash_payments', 'customer_payment_id')) {
                $data['customer_payment_id'] = null;
            }
            if (Schema::hasColumn('settlement_cash_payments', 'pump_payment_id')) {
                $data['pump_payment_id'] = null;
            }

            DB::table('settlement_cash_payments')->insert($data);

            Log::warning('PETROPD-CASHSAVE-ROOTFIX-004 inserted missing full-shift settlement cash correction', [
                'settlement_id'        => $settlement->id,
                'expected_cash'        => $expected_cash,
                'previous_saved_cash'  => $saved_cash,
                'inserted_amount'      => $missing_cash,
            ]);
        } catch (\Throwable $e) {
            Log::error('PETROPD-CASHSAVE-ROOTFIX-004 cash reconciliation failed', [
                'settlement_id' => $settlement->id ?? null,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Resolve PetroPD shortage receivables strictly inside the current business.
     * Returning null is intentional: a missing receivable account must never be
     * allowed to fall through AccountTransaction's default Cash-account fallback.
     */

    private function resolvePdShortageReceivableAccountId(int $business_id): ?int
    {
        $account_id = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->whereIn(DB::raw('LOWER(TRIM(name))'), [
                'accounts receivable',
                'account receivable',
                'accounts receivables',
                'receivables',
            ])
            ->value('id');

        if (! empty($account_id)) {
            return (int) $account_id;
        }

        $utility_account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

        if (empty($utility_account_id)) {
            return null;
        }

        $valid_account_id = Account::where('id', $utility_account_id)
            ->where('business_id', $business_id)
            ->where('is_closed', 0)
            ->whereRaw('LOWER(TRIM(name)) NOT LIKE ?', ['%cash%'])
            ->value('id');

        return ! empty($valid_account_id) ? (int) $valid_account_id : null;
    }

    /**
     * Create or repair the one transaction owned by a PetroPD excess detail.
     *
     * The legacy createTransaction() helper deduplicates only by the last user's
     * integer amount. Two unrelated postings with the same amount can therefore
     * share a transaction. Excess/AP posting needs a stable source identity, so
     * use settlement + excess-detail id instead.
     */

    private function resolvePdExcessTransaction(
        Settlement $settlement,
        SettlementExcessPayment $excess_payment,
        string $settlement_no,
        int $business_id
    ): Transaction {
        $source_ref = 'PD-EXCESS-' . $excess_payment->id;
        $transaction = null;

        if (! empty($excess_payment->transaction_id)) {
            $candidate = Transaction::where('id', $excess_payment->transaction_id)
                ->where('business_id', $business_id)
                ->where('type', 'settlement')
                ->where('sub_type', 'excess')
                ->first();

            if (! empty($candidate)
                && ((int) $candidate->petro_settlement_id === (int) $settlement->id
                    || (string) $candidate->invoice_no === $settlement_no)) {
                $transaction = $candidate;
            }
        }

        if (empty($transaction)) {
            $transaction = Transaction::where('business_id', $business_id)
                ->where('petro_settlement_id', $settlement->id)
                ->where('type', 'settlement')
                ->where('sub_type', 'excess')
                ->where('ref_no', $source_ref)
                ->first();
        }

        $location_id = ! empty($settlement->location_id)
            ? (int) $settlement->location_id
            : (int) BusinessLocation::where('business_id', $business_id)->value('id');
        $created_by = (int) (request()->session()->get('user.id') ?: auth()->id());
        $amount = -abs((float) $excess_payment->amount);
        $transaction_data = [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'settlement',
            'sub_type' => 'excess',
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $settlement->pump_operator_id,
            'transaction_date' => \Carbon\Carbon::parse($settlement->transaction_date)->format('Y-m-d'),
            'total_before_tax' => $amount,
            'final_total' => $amount,
            'discount_amount' => 0,
            'created_by' => $created_by,
            'is_settlement' => 1,
            'invoice_no' => $settlement_no,
            'ref_no' => $source_ref,
            'petro_settlement_id' => $settlement->id,
        ];

        if (empty($transaction)) {
            $transaction = Transaction::create($transaction_data);
        } else {
            $transaction->fill($transaction_data);
            $transaction->save();
        }

        return $transaction;
    }

    /**
     * Resolve Accounts Payable only within the current business. A missing AP
     * account intentionally returns null so the common Cash fallback can never
     * receive a PetroPD excess.
     */

    private function resolvePdExcessPayableAccountId(int $business_id): ?int
    {
        $open_account = function ($query) {
            $query->where('is_closed', 0)->orWhereNull('is_closed');
        };

        $account_id = Account::where('business_id', $business_id)
            ->where($open_account)
            ->whereIn(DB::raw('LOWER(TRIM(name))'), [
                'accounts payable',
                'account payable',
                'accounts payables',
                'payables',
            ])
            ->value('id');

        if (! empty($account_id)) {
            return (int) $account_id;
        }

        $utility_account_id = $this->transactionUtil->account_exist_return_id('Accounts Payable');
        if (empty($utility_account_id)) {
            $utility_account_id = $this->transactionUtil->account_exist_return_id('Account Payable');
        }

        if (empty($utility_account_id)) {
            return null;
        }

        $valid_account_id = Account::where('id', $utility_account_id)
            ->where('business_id', $business_id)
            ->where($open_account)
            ->whereRaw('LOWER(TRIM(name)) NOT LIKE ?', ['%cash%'])
            ->whereRaw('LOWER(TRIM(name)) NOT LIKE ?', ['%receivable%'])
            ->value('id');

        return ! empty($valid_account_id) ? (int) $valid_account_id : null;
    }

    /**
     * Ensure the finalized PetroPD excess is visible exactly once as a credit
     * in Finance > Accounts Payable Account Book. This is an operator liability,
     * so no Walk-In Customer contact-ledger pair is created.
     */

    private function ensurePdExcessAccountsPayableEntry(
        Transaction $transaction,
        SettlementExcessPayment $excess_payment,
        int $account_id,
        int $business_id,
        string $settlement_no,
        float $amount
    ): void {
        $token = '[PETROPD-EXCESS:' . $excess_payment->id . '|SETL:' . $settlement_no . ']';
        $note = trim($token . ' ' . (string) $excess_payment->note);
        $created_by = (int) ($transaction->created_by ?: request()->session()->get('user.id') ?: auth()->id());

        $rows = AccountTransaction::withTrashed()
            ->where('business_id', $business_id)
            ->where('transaction_id', $transaction->id)
            ->where('type', 'credit')
            ->orderBy('id')
            ->get();

        $canonical = $rows->first();
        $data = [
            'business_id' => $business_id,
            'account_id' => $account_id,
            'contact_id' => null,
            'amount' => abs($amount),
            'type' => 'credit',
            'sub_type' => 'petropd_excess_payable',
            'operation_date' => \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s'),
            'created_by' => $created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => $note,
        ];

        if (empty($canonical)) {
            $canonical = AccountTransaction::create($data);
        } else {
            if (method_exists($canonical, 'trashed') && $canonical->trashed()) {
                $canonical->restore();
            }
            $canonical->fill($data);
            $canonical->save();
        }

        foreach ($rows->where('id', '<>', $canonical->id) as $duplicate) {
            $duplicate->delete();
        }

        Log::info('PetroPD excess posted to Accounts Payable Account Book', [
            'business_id' => $business_id,
            'settlement_no' => $settlement_no,
            'excess_payment_id' => $excess_payment->id,
            'transaction_id' => $transaction->id,
            'account_transaction_id' => $canonical->id,
            'account_id' => $account_id,
            'amount' => abs($amount),
        ]);
    }

    /**
     * Repair an earlier shortage posting that reached Cash through the common
     * missing-account fallback. The transaction itself remains the authoritative
     * shortage record; only its erroneous Cash Account Book row is removed.
     */

    private function removePdShortageCashAccountFallback(
        Transaction $transaction,
        int $business_id,
        int $receivable_account_id,
        string $settlement_no,
        int $shortage_payment_id
    ): void {
        $cash_account_id = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->whereRaw('LOWER(TRIM(name)) = ?', ['cash'])
            ->value('id');

        if (empty($cash_account_id) || (int) $cash_account_id === $receivable_account_id) {
            return;
        }

        $wrong_cash_rows = AccountTransaction::where('business_id', $business_id)
            ->where('transaction_id', $transaction->id)
            ->where('account_id', $cash_account_id)
            ->get();

        if ($wrong_cash_rows->isEmpty()) {
            return;
        }

        foreach ($wrong_cash_rows as $wrong_cash_row) {
            $wrong_cash_row->delete();
        }

        Log::warning('PetroPD removed shortage posting from Cash Account Book', [
            'business_id'          => $business_id,
            'settlement_no'        => $settlement_no,
            'shortage_payment_id'  => $shortage_payment_id,
            'transaction_id'       => $transaction->id,
            'receivable_account_id'=> $receivable_account_id,
            'cash_account_id'      => $cash_account_id,
            'rows_removed'         => $wrong_cash_rows->count(),
        ]);
    }

    /**
     * Remove accounting rows created from a PetroPD automatic cash-correction
     * source row. Matching uses the cash-payment idempotency token, so genuine
     * user/Daily Collection/pumper cash rows cannot be affected.
     */

    private function removePdAutomaticCashCorrectionAccountRows(
        SettlementCashPayment $cash_payment,
        int $business_id,
        Settlement $settlement
    ): void {
        $token = '|CP:' . $cash_payment->id . ']';

        $account_rows = AccountTransaction::where('business_id', $business_id)
            ->where('note', 'like', '%' . $token . '%')
            ->get();

        $transaction_ids = $account_rows->pluck('transaction_id')->filter()->unique()->values();
        $transaction_payment_ids = $account_rows->pluck('transaction_payment_id')->filter()->unique()->values();

        foreach ($account_rows as $account_row) {
            $account_row->delete();
        }

        ContactLedger::where('business_id', $business_id)
            ->where('note', 'like', '%' . $token . '%')
            ->delete();

        if ($account_rows->isNotEmpty()) {
            Log::warning('PetroPD removed stale automatic cash-correction accounting rows', [
                'business_id'             => $business_id,
                'settlement_id'           => $settlement->id,
                'settlement_no'           => $settlement->settlement_no,
                'cash_payment_id'         => $cash_payment->id,
                'cash_payment_amount'     => $cash_payment->amount,
                'account_rows_removed'    => $account_rows->count(),
                'transaction_ids'         => $transaction_ids->all(),
                'transaction_payment_ids' => $transaction_payment_ids->all(),
            ]);
        }
    }


    /**
     * PETROPD-CASHSAVE-ROOTFIX-005
     *
     * Use the exact Total Amount submitted from the Payment to Finalize form
     * as the final authority. The previous fixes tried to recalculate expected
     * cash from backend tables, but the issue is that those backend filters can
     * be different from the amount already shown to the user before save.
     *
     * Formula used:
     * expected cash = submitted Total Amount - saved non-cash incoming payments
     *
     * This matches the user's expectation: if Payment to Finalize shows the
     * correct total when saving, the saved settlement, print preview and
     * Accounting Cash Account must use the same total and cannot lose cash.
     */

    private function ensurePdSettlementCashMatchesSubmittedFinalizeTotal($settlement, int $business_id, Request $request): void
    {
        try {
            if (empty($settlement) || empty($settlement->id)) {
                return;
            }

            $raw_total = $request->input('total_amount', null);
            if ($raw_total === null || $raw_total === '') {
                return;
            }

            $submitted_total = (float) str_replace(',', '', (string) $raw_total);
            if ($submitted_total <= 0) {
                return;
            }

            // PETROPD-PAYDUE-SOURCE-TRUTH-20260705:
            // Save the exact Payment to Finalize Total Amount on the settlement.
            // Add Payment, refresh, preview, finalize and account books can then
            // read the same persisted value instead of recalculating from live rows.
            if (Schema::hasColumn('settlements', 'total_amount')) {
                $settlement->total_amount = $submitted_total;
                $settlement->save();
            }

            $settlementNoValues = [$settlement->id, $settlement->settlement_no];

            $card_total = (float) SettlementCardPayment::where('business_id', $business_id)
                ->whereIn('settlement_no', $settlementNoValues)
                ->sum('amount');

            $cheque_total = (float) SettlementChequePayment::where('business_id', $business_id)
                ->whereIn('settlement_no', $settlementNoValues)
                ->sum('amount');

            $credit_sale_total = (float) SettlementCreditSalePayment::where('business_id', $business_id)
                ->whereIn('settlement_no', $settlementNoValues)
                ->sum('amount');

            // Only deduct payment methods that are part of the settlement payment total.
            // Cash deposits/expenses/drawings are cash movements after receiving cash;
            // they must not reduce the saved cash received amount.
            // Numeric settlement id is unique in the tenant database and is the
            // canonical linkage used by PetroPD. Do not require business_id here:
            // older/synchronised shortage rows can have a missing business_id,
            // which previously made shortage_total zero and converted the same
            // amount into an automatic Cash Payment.
            $settlement_shortage_total = (float) SettlementShortagePayment::where(function ($query) use ($settlement) {
                $query->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', (string) $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })->sum('amount');

            $work_shifts = is_array($settlement->work_shift)
                ? $settlement->work_shift
                : json_decode((string) $settlement->work_shift, true);
            if (! is_array($work_shifts)) {
                $work_shifts = explode(',', (string) $settlement->work_shift);
            }
            $work_shifts = array_values(array_unique(array_filter(array_map('intval', $work_shifts))));

            $pump_shortage_total = 0.0;
            if (! empty($work_shifts)) {
                $pump_shortage_total = (float) PumpOperatorPayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $work_shifts)
                    ->whereRaw('LOWER(TRIM(payment_type)) = ?', ['shortage'])
                    ->sum('payment_amount');
            }

            // Use the larger source total to tolerate old rows that exist in only
            // one side of the settlement/pumper synchronisation. Never add both,
            // because they represent the same shortage.
            $shortage_total = max($settlement_shortage_total, $pump_shortage_total);

            // S282-004: shortage is not cash collected. If Payment to Finalize total
            // contains shortage, deduct it before calculating any missing cash correction.
            // Otherwise the shortage amount is wrongly inserted as SettlementCashPayment
            // and appears in Finance > Cash Account Book.
            $non_cash_total = $card_total + $cheque_total + $credit_sale_total + $shortage_total;
            $expected_cash = round($submitted_total - $non_cash_total, 4);

            if ($expected_cash < 0) {
                Log::warning('PETROPD-CASHSAVE-ROOTFIX-005 skipped: expected cash became negative', [
                    'settlement_id'    => $settlement->id,
                    'settlement_no'    => $settlement->settlement_no,
                    'submitted_total'  => $submitted_total,
                    'non_cash_total'   => $non_cash_total,
                ]);
                return;
            }

            $saved_cash_query = SettlementCashPayment::where('business_id', $business_id)
                ->whereIn('settlement_no', $settlementNoValues);

            /*
             * Rebuild only PetroPD's own automatic cash corrections. ROOTFIX-004
             * runs immediately before this method and old finalizations may also
             * have left a ROOTFIX-005 row. Removing these rows first makes the
             * submitted-total calculation deterministic and prevents a stale
             * shortage-sized correction from reaching the Cash Account Book.
             * User-entered, Daily Collection and linked pumper cash are untouched.
             */
            $automatic_corrections = (clone $saved_cash_query)
                ->where(function ($query) {
                    $query->where('note', 'like', 'PETROPD-CASHSAVE-ROOTFIX-004%')
                        ->orWhere('note', 'like', 'PETROPD-CASHSAVE-ROOTFIX-005%');
                })
                ->when(Schema::hasColumn('settlement_cash_payments', 'daily_collection_id'), function ($query) {
                    $query->whereNull('daily_collection_id');
                })
                ->when(Schema::hasColumn('settlement_cash_payments', 'customer_payment_id'), function ($query) {
                    $query->whereNull('customer_payment_id');
                })
                ->when(Schema::hasColumn('settlement_cash_payments', 'pump_payment_id'), function ($query) {
                    $query->whereNull('pump_payment_id');
                })
                ->orderBy('id')
                ->get();

            foreach ($automatic_corrections as $automatic_correction) {
                $this->removePdAutomaticCashCorrectionAccountRows(
                    $automatic_correction,
                    $business_id,
                    $settlement
                );
                $automatic_correction->delete();
            }

            $saved_cash = (float) (clone $saved_cash_query)->sum('amount');

            $missing_cash = round($expected_cash - $saved_cash, 4);

            Log::warning('PETROPD-CASHSAVE-ROOTFIX-005 submitted-total cash reconciliation check', [
                'settlement_id'      => $settlement->id,
                'settlement_no'      => $settlement->settlement_no,
                'submitted_total'    => $submitted_total,
                'card_total'         => $card_total,
                'cheque_total'       => $cheque_total,
                'credit_sale_total'  => $credit_sale_total,
                'shortage_total'     => $shortage_total,
                'expected_cash'      => $expected_cash,
                'saved_cash'         => $saved_cash,
                'missing_cash'       => $missing_cash,
            ]);

            if ($missing_cash <= 0.0049) {
                return;
            }

            $walkin_customer = Contact::where('name', 'Walk-In Customer')
                ->where('business_id', $business_id)
                ->first();

            $data = [
                'business_id'    => $business_id,
                'settlement_no'  => $settlement->id,
                'amount'         => $missing_cash,
                'customer_id'    => $walkin_customer ? $walkin_customer->id : null,
                'note'           => 'PETROPD-CASHSAVE-ROOTFIX-005 auto cash correction: matched submitted Payment to Finalize Total Amount',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            if (Schema::hasColumn('settlement_cash_payments', 'pump_operator_id')) {
                $data['pump_operator_id'] = $settlement->pump_operator_id;
            }
            if (Schema::hasColumn('settlement_cash_payments', 'daily_collection_id')) {
                $data['daily_collection_id'] = null;
            }
            if (Schema::hasColumn('settlement_cash_payments', 'customer_payment_id')) {
                $data['customer_payment_id'] = null;
            }
            if (Schema::hasColumn('settlement_cash_payments', 'pump_payment_id')) {
                $data['pump_payment_id'] = null;
            }

            DB::table('settlement_cash_payments')->insert($data);

            Log::warning('PETROPD-CASHSAVE-ROOTFIX-005 inserted submitted-total cash correction', [
                'settlement_id'       => $settlement->id,
                'expected_cash'       => $expected_cash,
                'previous_saved_cash' => $saved_cash,
                'inserted_amount'     => $missing_cash,
            ]);
        } catch (\Throwable $e) {
            Log::error('PETROPD-CASHSAVE-ROOTFIX-005 cash reconciliation failed', [
                'settlement_id' => $settlement->id ?? null,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }
}
