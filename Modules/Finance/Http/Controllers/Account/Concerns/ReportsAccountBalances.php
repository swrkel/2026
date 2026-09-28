<?php

namespace Modules\Finance\Http\Controllers\Account\Concerns;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;
use Modules\Finance\Entities\AccountSetting;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\AccountType;
use App\Business;
use Modules\Finance\Entities\BusinessLocation;
use App\Category;
use Modules\Finance\Entities\Contact;
use App\ContactLedger;
use App\Journal;
use App\NotificationTemplate;
use App\Product;
use App\PurchaseLine;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use Modules\Finance\Entities\TransactionPayment;
use App\TransactionSellLine;
use Modules\Finance\Entities\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\StockAdjustmentLine;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;
use Modules\Essentials\Entities\EssentialsEmployee;
use Modules\Fleet\Entities\Driver;
use Modules\Fleet\Entities\Fleet;
use Modules\Fleet\Entities\Helper;
use Modules\Hms\Entities\HmsRoom;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\PriceChanges\Entities\PriceChangesDetail;
use Modules\PriceChanges\Entities\PriceChangesHeader;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertySellLine;
use Modules\Shipping\Entities\ShippingAgent;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartner;
use Modules\Superadmin\Entities\AccountNumber;
use Modules\Superadmin\Entities\ModulePermissionLocation;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatPayment;
use Modules\Finance\Services\Reports\FinanceIntegrationLedgerService;
use Modules\Finance\Services\Accounts\AccountBookDataService;
use Modules\Finance\Services\Accounts\AccountListQueryService;
use Modules\Finance\Services\Deposits\BankDepositAccountResolver;
use Modules\Finance\Services\Deposits\CardDepositAccountResolver;
use Modules\Finance\Services\Deposits\ChequeDepositListService;
use Modules\Finance\Services\Transactions\FinanceTransactionGuard;
use Yajra\DataTables\Facades\DataTables;

/**
 * Balances, cash flow and the profit and loss report.
 *
 * MA-002: split out of Finance's AccountController, which was 9,782 lines in
 * a single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. The 84 routes that point at
 *   AccountController still resolve, action() targets still resolve, and the
 *   $this-> calls between these 98 methods still work. Separate controller
 *   classes would mean rewriting all of those.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: getAccountBalance, getAccountBalanceMain, debugAccountBalance, cashFlow, getProfitLossReport, reconcile, findCashAccount, updateOBs, updateLoans, getAccsForWhichToCheckInsufficientBalances
 */
trait ReportsAccountBalances
{
    public function getAccountBalance($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        $businessId = (int) session()->get('user.business_id');
        $account = Account::where('business_id', $businessId)->findOrFail($id);
        $account->balance = Account::getAccountBalance($id);
        $account->check_insufficient_balance = app(FinanceTransactionGuard::class)
            ->requiresAvailableBalance($businessId, (int) $id);

        return $account;
    }

    public function getAccountBalanceMain($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) (request()->session()->get('user.business_id') ?: request()->session()->get('business.id'));
        abort_if($businessId <= 0, 403, 'Business session is not available.');

        $mainAccountExists = Account::where('business_id', $businessId)
            ->where('id', (int) $id)
            ->exists();
        abort_if(! $mainAccountExists, 404, 'Account not found.');

        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');
        $transactionType = request()->input('type');

        $balance = DB::table('accounts as child')
            ->leftJoin('account_types as ats', 'child.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->leftJoin('account_transactions as atb', function ($join) use ($startDate, $endDate, $transactionType): void {
                $join->on('atb.account_id', '=', 'child.id')
                    ->whereNull('atb.deleted_at');

                if (! empty($startDate)) {
                    $join->where('atb.operation_date', '>=', $startDate . ' 00:00:00');
                }
                if (! empty($endDate)) {
                    $join->where('atb.operation_date', '<=', $endDate . ' 23:59:59');
                }
                if (in_array($transactionType, ['debit', 'credit'], true)) {
                    $join->where('atb.type', $transactionType);
                }
            })
            ->leftJoin('transaction_payments as tpb', 'tpb.id', '=', 'atb.transaction_payment_id')
            ->where('child.business_id', $businessId)
            ->where('child.parent_account_id', (int) $id)
            ->whereNull('child.deleted_at')
            ->where(function ($paymentQuery): void {
                $paymentQuery->whereNull('atb.id')
                    ->orWhereNull('atb.transaction_payment_id')
                    ->orWhereNull('tpb.deleted_at');
            })
            ->selectRaw("COALESCE(SUM(
                CASE
                    WHEN LOWER(COALESCE(pat.name, ats.name, '')) LIKE '%liabilit%'
                      OR LOWER(COALESCE(pat.name, ats.name, '')) LIKE '%equity%'
                      OR LOWER(COALESCE(pat.name, ats.name, '')) LIKE '%income%'
                    THEN CASE WHEN atb.type = 'debit' THEN -1 * COALESCE(atb.amount, 0) ELSE COALESCE(atb.amount, 0) END
                    ELSE CASE WHEN atb.type = 'credit' THEN -1 * COALESCE(atb.amount, 0) ELSE COALESCE(atb.amount, 0) END
                END
            ), 0) AS balance")
            ->value('balance');

        return response()->json(['balance' => round((float) $balance, 2)]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */

private function debugAccountBalance($account_id, $operation_date)
{
    try {
        // Get all transactions up to operation date
        $transactions = DB::table('account_transactions')
            ->where('account_id', $account_id)
            ->whereDate('operation_date', '<=', date('Y-m-d', strtotime($operation_date)))
            ->orderBy('operation_date', 'asc')
            ->get();
        
        $running_balance = 0;
        $details = [];
        
        foreach ($transactions as $trans) {
            $old_balance = $running_balance;
            
            if ($trans->type == 'debit') {
                $running_balance += $trans->amount;
            } else { // credit
                $running_balance -= $trans->amount;
            }
            
            $details[] = [
                'id' => $trans->id,
                'date' => $trans->operation_date,
                'type' => $trans->type,
                'amount' => $trans->amount,
                'sub_type' => $trans->sub_type,
                'old_balance' => $old_balance,
                'new_balance' => $running_balance
            ];
        }
        
        return [
            'final_balance' => $running_balance,
            'transaction_count' => count($transactions),
            'details' => $details
        ];
        
    } catch (\Exception $e) {
        Log::error('Debug balance calculation error: ' . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

    /**
     * Calculates account current balance.
     *
     * @param  int  $id
     * @return \App\Http\Controllers\json
     */

    public function cashFlow()
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) request()->session()->get('user.business_id');
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        if ($this->userCan('superadmin') || $this->userCan('account.access')) {
            $account_access = 1;
        }

        if (request()->ajax()) {
            if (! $account_access) {
                return DataTables::of(collect([]))->make(true);
            }

            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');

            // Build the business-wide running balance once per operation timestamp.
            // The previous correlated subquery recalculated the complete ledger for every
            // displayed row, which became progressively slower as the ledger grew.
            $running_balance_query = DB::table('account_transactions as RB')
                ->join('accounts as RA', 'RA.id', '=', 'RB.account_id')
                ->where('RA.business_id', $business_id)
                ->whereNull('RB.deleted_at')
                ->select([
                    'RB.operation_date',
                    DB::raw("SUM(CASE WHEN RB.type = 'credit' THEN RB.amount ELSE -1 * RB.amount END) as net_amount"),
                ])
                ->groupBy('RB.operation_date')
                ->orderBy('RB.operation_date');

            if (! empty($end_date)) {
                $running_balance_query->where('RB.operation_date', '<=', $end_date . ' 23:59:59');
            }

            $running_balances = [];
            $running_balance = 0.0;
            foreach ($running_balance_query->get() as $balance_row) {
                $running_balance += (float) $balance_row->net_amount;
                $running_balances[(string) $balance_row->operation_date] = $running_balance;
            }

            $accounts = AccountTransaction::join('accounts as A', 'account_transactions.account_id', '=', 'A.id')
                ->leftJoin('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
                ->leftJoin('transaction_payments as TP', 'account_transactions.transaction_payment_id', '=', 'TP.id')
                ->where('A.business_id', $business_id)
                ->whereNull('account_transactions.deleted_at')
                ->with(['transaction', 'transaction.contact', 'transfer_transaction', 'transfer_transaction.account'])
                ->select([
                    'account_transactions.type',
                    'account_transactions.amount',
                    'account_transactions.operation_date',
                    'account_transactions.created_at',
                    'account_transactions.sub_type',
                    'account_transactions.transfer_transaction_id',
                    'account_transactions.transaction_id',
                    'account_transactions.id',
                    'A.name as account_name',
                    'TP.payment_ref_no as payment_ref_no',
                ])
                ->orderBy('account_transactions.operation_date', 'asc')
                ->orderBy('account_transactions.id', 'asc');

            if (! empty(request()->input('type'))) {
                $accounts->where('account_transactions.type', request()->input('type'));
            }
            if (! empty(request()->input('account_id'))) {
                $accounts->where('A.id', request()->input('account_id'));
            }
            if (! empty(request()->location_id)) {
                $accounts->where('transactions.location_id', request()->location_id);
            } else {
                $allowed_locations = ModulePermissionLocation::getModulePermissionLocations($business_id, 'accounting_module');
                if (! empty($allowed_locations) && ! empty($allowed_locations->locations)) {
                    $accounts->whereIn('transactions.location_id', array_keys($allowed_locations->locations));
                }
            }
            if (! empty($start_date) && ! empty($end_date)) {
                $accounts->whereBetween('account_transactions.operation_date', [
                    $start_date . ' 00:00:00',
                    $end_date . ' 23:59:59',
                ]);
            }

            return DataTables::of($accounts)
                ->addColumn('debit', function ($row) {
                    return $row->type === 'debit'
                        ? '<span class="display_currency" data-currency_symbol="true">' . $row->amount . '</span>'
                        : '';
                })
                ->addColumn('credit', function ($row) {
                    return $row->type === 'credit'
                        ? '<span class="display_currency" data-currency_symbol="true">' . $row->amount . '</span>'
                        : '';
                })
                ->addColumn('balance', function ($row) use ($running_balances) {
                    $key = $row->operation_date instanceof \DateTimeInterface
                        ? $row->operation_date->format('Y-m-d H:i:s')
                        : (string) $row->operation_date;
                    $balance = $running_balances[$key] ?? 0;

                    return '<span class="display_currency" data-currency_symbol="true">' . $balance . '</span>';
                })
                ->editColumn('operation_date', function ($row) {
                    $dt = $row->operation_date ?? $row->created_at ?? null;

                    return $dt ? $this->commonUtil->format_date($dt, false) : '';
                })
                ->addColumn('description', function ($row) {
                    $details = '';
                    if (! empty($row->sub_type)) {
                        $details = __('account.' . $row->sub_type);
                        if (in_array($row->sub_type, ['fund_transfer', 'deposit'], true) && ! empty($row->transfer_transaction)) {
                            if ($row->type === 'credit') {
                                $details .= ' ( ' . __('account.from') . ': ' . ($row->transfer_transaction->account->name ?? 'N/A') . ')';
                            } else {
                                $details .= ' ( ' . __('account.to') . ': ' . ($row->transfer_transaction->account->name ?? 'N/A') . ')';
                            }
                        }
                    } elseif (! empty($row->transaction->type)) {
                        if ($row->transaction->type === 'purchase') {
                            $details = '<b>' . __('purchase.supplier') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A')
                                . '<br><b>' . __('purchase.ref_no') . ':</b> ' . ($row->transaction->ref_no ?? '');
                        } elseif ($row->transaction->type === 'sell') {
                            $details = '<b>' . __('contact.customer') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A')
                                . '<br><b>' . __('sale.invoice_no') . ':</b> ' . ($row->transaction->invoice_no ?? '');
                        }
                    }
                    if (! empty($row->payment_ref_no)) {
                        if ($details !== '') {
                            $details .= '<br/>';
                        }
                        $details .= '<b>' . __('lang_v1.pay_reference_no') . ':</b> ' . $row->payment_ref_no;
                    }

                    return $details;
                })
                ->removeColumn('id')
                ->rawColumns(['credit', 'debit', 'balance', 'sub_type', 'description'])
                ->make(true);
        }

        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $accounts = Account::forDropdown($business_id, false);
        $accounts->prepend(__('messages.all'), '');
        $disabled_message_color = System::getProperty('not_enalbed_module_user_color');
        $disabled_message_font_size = System::getProperty('not_enalbed_module_user_font_size');
        $disabled_message = System::getProperty('not_enalbed_module_user_message');

        return view('finance::account.cash_flow')->with(compact(
            'accounts',
            'business_locations',
            'account_access',
            'disabled_message_color',
            'disabled_message_font_size',
            'disabled_message'
        ));
    }

    /**
     * Shows account notes.
     *
     * @param  int  $id
     * @return Response
     */

    public function getProfitLossReport()
    {
        $business_id = (int) (request()->session()->get('user.business_id') ?: request()->session()->get('business.id'));
        $business_locations = BusinessLocation::forDropdown($business_id);

        // Check if manufacturing module is enabled
        if (\Module::has('Manufacturing') && ($this->userCan('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manufacturing_module'))) {
            $show_manufacturing_data = true;
        } else {
            $show_manufacturing_data = false;
        }
        // modified by iftekhar
        return view('finance::account.profit_loss_report')->with(compact(
            'business_locations',
            'show_manufacturing_data'
        ));
    }

    public function reconcile($id, Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) (session()->get('user.business_id') ?: session()->get('business.id'));
        if ($business_id <= 0) {
            return response()->json([
                'success' => false,
                'reason' => 'The active business session is not available, so the reconciliation status could not be saved.',
                'msg' => 'The active business session is not available, so the reconciliation status could not be saved.',
                'steps' => [
                    'Refresh the page once and sign in again if prompted.',
                    'Open Finance → List Accounts → Account Book and retry the reconciliation.',
                    'If the message continues, ask the administrator to verify the tenant/business session.',
                ],
            ], 422);
        }

        // Plug-and-play protection for older tenant databases.
        if (! Schema::hasColumn('account_transactions', 'reconcile_status')) {
            try {
                Schema::table('account_transactions', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->boolean('reconcile_status')->default(false)->index();
                });
            } catch (\Throwable $schemaException) {
                // A concurrent request may have added the column after hasColumn().
                if (! Schema::hasColumn('account_transactions', 'reconcile_status')) {
                    Log::error('Finance Account Book reconciliation schema self-heal failed', [
                        'business_id' => $business_id,
                        'account_transaction_id' => (int) $id,
                        'message' => $schemaException->getMessage(),
                    ]);

                    $reason = 'This tenant database is missing the reconciliation-status field and Finance could not add it automatically.';
                    return response()->json([
                        'success' => false,
                        'reason' => $reason,
                        'msg' => $reason,
                        'steps' => [
                            'Refresh the page once and retry.',
                            'If it still fails, ask the administrator to confirm the tenant database user has ALTER TABLE permission.',
                            'After that permission is available, reopen the Account Book and click Reconcile again.',
                        ],
                    ], 422);
                }
            }
        }

        $entry = DB::table('account_transactions as account_tx')
            ->join('accounts', 'accounts.id', '=', 'account_tx.account_id')
            ->where('accounts.business_id', $business_id)
            ->where('account_tx.id', (int) $id)
            ->whereNull('account_tx.deleted_at')
            ->select([
                'account_tx.id',
                'account_tx.reconcile_status',
                'account_tx.account_id',
                'account_tx.operation_date',
            ])
            ->first();

        if (! $entry) {
            $reason = 'This Account Book entry no longer exists, was deleted, or does not belong to the active business.';
            return response()->json([
                'success' => false,
                'reason' => $reason,
                'msg' => $reason,
                'steps' => [
                    'Refresh the Account Book to reload the latest ledger rows.',
                    'Locate the transaction again and retry only if it is still listed.',
                    'If the row keeps reappearing, ask the administrator to check duplicate/soft-deleted account_transactions rows.',
                ],
            ], 404);
        }

        $currentStatus = (int) ((bool) $entry->reconcile_status);
        $requestedStatus = $request->input('target_status', null);
        if ($requestedStatus === null || $requestedStatus === '') {
            // Backward compatibility for an older cached page. New Finance
            // buttons send target_status explicitly so the request is idempotent.
            $desiredStatus = $currentStatus === 1 ? 0 : 1;
        } else {
            $desiredStatus = (int) ((bool) ((int) $requestedStatus));
        }

        // A transaction included in a finalized Bank Reconciliation cannot be
        // un-reconciled directly from the Account Book.
        if ($desiredStatus === 0
            && Schema::hasTable('finance_bank_reconciliations')
            && Schema::hasTable('finance_bank_reconciliation_lines')) {
            $finalizedReconciliation = DB::table('finance_bank_reconciliation_lines as brl')
                ->join('finance_bank_reconciliations as br', 'br.id', '=', 'brl.reconciliation_id')
                ->where('br.business_id', $business_id)
                ->where('br.status', 'reconciled')
                ->whereNull('br.deleted_at')
                ->where('brl.is_cleared', 1)
                ->where('brl.account_transaction_id', (int) $entry->id)
                ->select([
                    'br.id',
                    'br.reconciliation_no',
                    'br.statement_date',
                ])
                ->orderByDesc('br.id')
                ->first();

            if ($finalizedReconciliation) {
                $reconciliationLabel = trim((string) ($finalizedReconciliation->reconciliation_no ?? ''));
                if ($reconciliationLabel === '') {
                    $reconciliationLabel = '#' . (int) $finalizedReconciliation->id;
                }

                $reason = 'This entry is part of finalized Bank Reconciliation ' . $reconciliationLabel . ' and cannot be un-reconciled directly from the Account Book.';
                return response()->json([
                    'success' => false,
                    'reason' => $reason,
                    'msg' => $reason,
                    'steps' => [
                        'Open Finance → Bank Reconciliation.',
                        'Open reconciliation ' . $reconciliationLabel . '.',
                        'Use Reopen to return that reconciliation to Draft status (an authorized user may be required).',
                        'Return to this Account Book and click the green Reconciled button again.',
                        'After correcting the entry, review and finalize the Bank Reconciliation again.',
                    ],
                    'reconciliation_id' => (int) $finalizedReconciliation->id,
                    'status' => $currentStatus,
                ], 409);
            }
        }

        try {
            $savedStatus = DB::transaction(function () use ($entry, $desiredStatus) {
                $accountTransaction = AccountTransaction::where('id', (int) $entry->id)
                    ->lockForUpdate()
                    ->first();

                if (! $accountTransaction || ! empty($accountTransaction->deleted_at)) {
                    throw new \RuntimeException('The Account Book entry was removed while the reconciliation was being saved.');
                }

                // Set an explicit desired status instead of toggling. This makes
                // the request idempotent even if more than one click handler
                // fires for the same button.
                if ((int) ((bool) $accountTransaction->reconcile_status) !== $desiredStatus) {
                    $accountTransaction->reconcile_status = $desiredStatus;
                    $accountTransaction->save();
                }

                return (int) ((bool) AccountTransaction::where('id', (int) $entry->id)
                    ->value('reconcile_status'));
            }, 3);
        } catch (\Throwable $exception) {
            Log::error('Finance Account Book reconciliation save failed', [
                'business_id' => $business_id,
                'account_transaction_id' => (int) $entry->id,
                'desired_status' => $desiredStatus,
                'message' => $exception->getMessage(),
            ]);

            $reason = 'Finance could not save the reconciliation status for this entry.';
            return response()->json([
                'success' => false,
                'reason' => $reason,
                'msg' => $reason,
                'steps' => [
                    'Refresh the Account Book and retry the entry once.',
                    'If this is a previously finalized bank item, reopen the related Bank Reconciliation first.',
                    'If it still fails, ask the administrator to check the Laravel log for Account Transaction ID: ' . (int) $entry->id . '.',
                ],
                'status' => $currentStatus,
            ], 422);
        }

        if ($savedStatus !== $desiredStatus) {
            $reason = 'The reconciliation status changed while the entry was being updated, so Finance did not confirm the change.';
            return response()->json([
                'success' => false,
                'reason' => $reason,
                'msg' => $reason,
                'steps' => [
                    'Refresh the Account Book.',
                    'Wait for the table to finish loading, then click the reconciliation button once.',
                    'If it happens again, ask the administrator to check for duplicate JavaScript handlers or concurrent updates to Account Transaction ID: ' . (int) $entry->id . '.',
                ],
                'status' => $savedStatus,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'status' => $savedStatus,
            'msg' => $savedStatus === 1
                ? 'Transaction reconciled successfully.'
                : 'Transaction un-reconciled successfully.',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

private function findCashAccount($business_id)
{
    $cash_account = Account::where('business_id', $business_id)
        ->where('name', 'like', '%Cash%')
        ->first();
    
    if ($cash_account) {
        return $cash_account;
    }
}

/**
 * Debug function to manually calculate account balance
 */

    public function updateOBs()
    {
        $business_id               = session()->get('user.business_id');
        $opening_balances          = Transaction::where('type', 'opening_balance')->where('business_id', $business_id)->whereNotNull('contact_id')->get();
        $opening_balance_equity_id = $this->transactionUtil->account_exist_return_id('Opening Balance Equity Account');

        foreach ($opening_balances as $bal) {
            $contact = Contact::find($bal->contact_id);

            if (! empty($contact)) {
                if ($contact->type == 'customer') {
                    $type = 'credit';
                } else {
                    $type = 'debit';
                }

                $account_transaction_data = [
                    'amount'         => $bal->final_total,
                    'account_id'     => $opening_balance_equity_id,
                    'type'           => $type,
                    'sub_type'       => 'ledger_show',
                    'operation_date' => $bal->transaction_date,
                    'created_by'     => $bal->created_by,
                    'transaction_id' => $bal->id,
                ];

                $id = AccountTransaction::updateOrCreate(['account_id' => $opening_balance_equity_id, 'transaction_id' => $bal->id], $account_transaction_data);
            }
        }
    }

    public function updateLoans()
    {
        $business_id      = session()->get('user.business_id');
        $opening_balances = Transaction::where('sub_type', 'loan_payment')->where('business_id', $business_id)->get();
        $cash             = $this->transactionUtil->account_exist_return_id('Cash');

        foreach ($opening_balances as $bal) {
            $account_transaction_data = [
                'amount'         => $bal->final_total,
                'account_id'     => $cash,
                'type'           => 'debit',
                'operation_date' => $bal->transaction_date,
                'created_by'     => $bal->created_by,
                'transaction_id' => $bal->id,
            ];

            $id = AccountTransaction::updateOrCreate(['account_id' => $cash, 'transaction_id' => $bal->id, 'type' => 'debit'], $account_transaction_data);

            $account_transaction_data['type'] = 'credit';
            $id                               = AccountTransaction::updateOrCreate(['account_id' => $cash, 'transaction_id' => $bal->id, 'type' => 'credit'], $account_transaction_data);
        }
    }

    public function getAccsForWhichToCheckInsufficientBalances()
    {
        // $names = ['Cash', 'Petty Cash', 'Cash Locker'];
        return Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->whereIn('account_groups.name', ['Cash Account', 'Card', "Cheques in Hand (Customer's)"])
            ->where('accounts.business_id', session()->get('user.business_id'))
            ->pluck('accounts.id');
    }
    // @eng END 15/2
}
