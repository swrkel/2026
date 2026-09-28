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
use Yajra\DataTables\Facades\DataTables;

/**
 * The account book screen. show() is the large one - see the note in this file.
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
 * A NOTE ON show()
 *   show() is 3,801 lines on its own - the account book screen, which builds
 *   its rows from several sources and formats each one. Splitting it means
 *   understanding how those sources are merged and de-duplicated, which is a
 *   piece of work in itself rather than a file move.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: show, getMainAccountBook, getIndividualAccountTransactions, accountBookRedirect, accountBookContactOptions, accountBookData, chequeOpeningFilterOptions, getDescription, getPaymentDescriptionForAccountRow, extractSettlementNoFromNote, getSettlementNoFromDailyCards, getSettlementNoForCreditPayment, getNotes
 */
trait ShowsAccountBook
{
    public function show($id, Request $request)
    {
        $isIframe = $request->query('is_iframe');

        $is_iframe = ! empty($isIframe) ? $isIframe : 0;

        // return $acount_balance_pre = Account::getAccountBalance($id, '2022-12-01', '2022-12-31', true, true, false);
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        // Session::flush();
        $business_id              = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');

        // IS8036: every Account Book opens on the current calendar month.
        // Do not reuse a previously saved/global date range here; the user can
        // still select any other range after the page opens.
        $account_book_start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $account_book_end_date = Carbon::now()->endOfMonth()->format('Y-m-d');

        $account_access           = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $card_account_id          = $this->transactionUtil->account_exist_return_id('Cards (Credit Debit) Account');
        $cheque_return_account_id = $this->transactionUtil->account_exist_return_id('Cheque Return Income');
        $card_group_id            = AccountGroup::getGroupByName('Card', true);
        $bank_group_id            = AccountGroup::getGroupByName('Bank Account', true);
        $cheque_in_hand_group_id  = AccountGroup::getGroupByName("Cheques in Hand (Customer's)", true);
        $this_account = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->leftjoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->where('accounts.business_id', $business_id)
            ->select('accounts.*', 'account_groups.name as group_name')
            ->with(['account_type', 'account_type.parent_account'])
            ->where('accounts.id', $id)->firstOrFail();
        if ($this->userCan('superadmin') || $this->userCan('account.access')) {
            $account_access = 1;
        }
        if (! $account_access) {
            $account_type_name = optional($this_account->account_type)->name;
            if ($this_account->group_name == 'COGS Account Group' || $account_type_name == 'Income' || $account_type_name == 'Fixed Assets' || $account_type_name == 'Equity' || strpos($account_type_name, 'Liabilities') !== false) {
                $account_access = 0;
            } else {
                $account_access = 1;
            }
        }

        if (request()->ajax()) {
            // dd($request->all());
            // Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            try {

                $is_iframe  = request()->input('is_iframe');
                $start_date = request()->input('start_date');
                $end_date   = request()->input('end_date');
                Session::forget('account_balance'); // forget value if previously store in it
                $acount_balance_pre = Account::getAccountBalance($id, $start_date, $end_date, true, true, false);

                Session::put('account_balance', $acount_balance_pre);

                $integrationLedger = app(FinanceIntegrationLedgerService::class);
                $dailyCollectionLookup = $integrationLedger->dailyCollectionSettlementLookup((int) $business_id);

                $accounts = AccountTransaction::join(
                    'accounts as A',
                    'account_transactions.account_id',
                    '=',
                    'A.id'
                )
                    ->leftjoin('transaction_payments AS TP', function ($join) {
                        $join->on('TP.id', '=', 'account_transactions.transaction_payment_id');
                    })
                    // S763: Supplier Pay Due root rows have no parent purchase
                    // transaction, so payment_for contact type is required to
                    // use paid_on as their Transaction Date.
                    ->leftJoin('contacts AS supplier_payment_contact', 'supplier_payment_contact.id', '=', 'TP.payment_for')
                    ->leftJoin('users AS u', 'account_transactions.created_by', '=', 'u.id')
                    ->leftjoin(
                        'account_types as ats',
                        'A.account_type_id',
                        '=',
                        'ats.id'
                    )
                    ->leftJoin('account_groups as ag', 'A.asset_type', '=', 'ag.id')
                    ->leftJoin('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                    ->leftJoinSub($dailyCollectionLookup, 'finance_daily_collections', function ($join) use ($business_id) {
                        $join->on('finance_daily_collections.collection_form_no', '=', DB::raw('CAST(REPLACE(transactions.ref_no, "Daily Collection #", "") AS UNSIGNED)'))
                            ->where('finance_daily_collections.business_id', '=', $business_id)
                            ->where('transactions.type', '=', 'daily_collection');
                    })
                    ->leftJoin('settlements', function ($join) use ($business_id) {
                        $join->on('settlements.id', '=', 'finance_daily_collections.settlement_id')
                            ->where('settlements.business_id', '=', $business_id);
                    })
                    ->addSelect('settlements.settlement_no', 'finance_daily_collections.collection_form_no')
                    ->where('A.business_id', $business_id)
                    ->where(function ($query) use ($id, $card_account_id) {
                        $query->where('A.id', $id);

                        if (! empty($card_account_id) && intval($id) === intval($card_account_id)) {
                            $query->orWhere('A.parent_account_id', $card_account_id);
                        }
                    });


                $accounts = $accounts->with(['transaction', 'transaction.contact', 'transfer_transaction', 'transfer_transaction.account'])->where(function ($query) {
                    $query->whereNull('account_transactions.transaction_payment_id')
                        ->orWhereNull('TP.deleted_at');
                })
                ->whereNull('account_transactions.deleted_at');

                $is_card_account = ! empty($this_account) && (
                    (! empty($card_group_id) && intval($this_account->asset_type) === intval($card_group_id))
                    || (! empty($card_account_id) && intval($id) === intval($card_account_id))
                );

                // CRITICAL FIX: For Card Accounts that are SUB-ACCOUNTS (like Visa Master, MasterCard, etc.),
                // we should show ALL transactions for that specific card account.
                // The filter for settlement/card_payment should ONLY apply to the MAIN card account (Cards (Credit Debit) Account)
                // NOT to the individual card sub-accounts.
                if ($is_card_account && intval($id) === intval($card_account_id)) {
                    // Show settlement card payments plus manual deposits made from card sub-accounts.
                    $accounts->where(function ($query) {
                        $query->where(function ($settlementQuery) {
                            $settlementQuery->where('transactions.type', 'settlement')
                                ->where('transactions.sub_type', 'card_payment');
                        })
                        // IS2217: SW posts the normal settlement/card_payment
                        // shape and also marks its ledger row explicitly. Keep
                        // this source marker as a compatibility path so an SW
                        // card receipt cannot be hidden by legacy subtype data.
                        ->orWhere('account_transactions.txnType', 'sw_settlement_payment')
                        ->orWhere('account_transactions.sub_type', 'deposit');
                    });
                }
                // For sub-card accounts (Visa Master, MasterCard, etc.), show all transactions without filter

                $accounts = $accounts->where(function ($query) {
                    $query->whereNull('account_transactions.transaction_payment_id')
                        ->orWhere(function ($query2) {
                            $query2->whereNotNull('account_transactions.transaction_payment_id');
                        });
                });

                if (! empty($start_date) && ! empty($end_date)) {
                    if (request()->date_based_on == 'transaction_date') {
                        $accounts = $accounts->where(function ($query) use ($start_date, $end_date) {
                            $query->whereBetween(\DB::raw("COALESCE(CASE WHEN supplier_payment_contact.type IN ('supplier','both') THEN TP.paid_on END, transactions.transaction_date, account_transactions.operation_date)"), [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                        });
                    } else {
                        $accounts = $accounts->where(function ($query) use ($start_date, $end_date) {
                            $query->where(function ($chequeDateQuery) use ($start_date, $end_date) {
                                $chequeDateQuery->where(function ($transactionPaymentDateQuery) use ($start_date, $end_date) {
                                    $transactionPaymentDateQuery->whereNotNull('TP.cheque_date')
                                        ->whereBetween('TP.cheque_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                                })->orWhere(function ($accountTransactionDateQuery) use ($start_date, $end_date) {
                                    $accountTransactionDateQuery->whereNull('TP.cheque_date')
                                        ->whereBetween('account_transactions.cheque_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                                });
                            })->orWhere(function ($depositQuery) use ($start_date, $end_date) {
                                $depositQuery->where('account_transactions.sub_type', 'deposit')
                                    ->whereBetween(\DB::raw('COALESCE(transactions.transaction_date, account_transactions.operation_date)'), [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                            });
                        });
                    }
                }

                if (! empty(request()->input('type'))) {
                    $accounts->where('type', request()->input('type'));
                }
                if (! empty(request()->input('card_type'))) {
                    $accounts->where('TP.card_type', request()->input('card_type'));
                }

                if (! empty(request()->input('card_number'))) {
                    $accounts->where('TP.card_number', request()->input('card_number'));
                }

                if (! empty(request()->input('cheque_number'))) {
                    $cheque_number = request()->input('cheque_number');
                    $accounts->where(function ($query) use ($cheque_number) {
                        $query->where('TP.cheque_number', $cheque_number)
                            ->orWhere('account_transactions.cheque_number', $cheque_number);
                    });
                }

                if (! empty(request()->input('customer_cheque_no'))) {
                    $customer_cheque_no = request()->input('customer_cheque_no');
                    $accounts->where(function ($query) use ($customer_cheque_no) {
                        $query->where('TP.cheque_number', $customer_cheque_no)
                            ->orWhere('account_transactions.cheque_number', $customer_cheque_no);
                    });
                }

                $slip_filter = request()->input('slip_no');
                if (! empty($slip_filter)) {
                    $accounts->where('account_transactions.slip_no', $slip_filter);
                }

                $customer = request()->input('customer');

                if (! empty($customer)) {
                    $accounts->whereHas('transaction', function ($query) use ($customer) {
                        $query->where('contact_id', $customer);
                    });
                }

                $supplier = request()->input('supplier');

                if (! empty($supplier)) {
                    $accounts->whereHas('transaction', function ($query) use ($supplier) {
                        $query->where('contact_id', $supplier);
                    });
                }
                $accounts = $accounts->with(['transaction', 'transaction.contact', 'transfer_transaction', 'transfer_transaction.account'])
                    ->select([
                        'account_transactions.type as type',
                        'slip_no',
                        'account_transactions.account_id',
                        'account_transactions.fixed_asset_id',
                        'account_transactions.amount',
                        'account_transactions.interest',
                        'account_transactions.reconcile_status',
                        'account_transactions.sub_type as at_sub_type',
                        'account_transactions.sub_type as sub_type',
                        'operation_date', 'account_transactions.note',
                        'journal_deleted',
                        'account_transactions.deleted_by',
                        'journal_entry',
                        'account_transactions.sell_line_id',
                        'account_transactions.income_type',
                        'account_transactions.attachment',
                        'account_transactions.cheque_number as dep_trans_cheque_number',
                        'account_transactions.transaction_payment_id as tp_id',
                        'TP.cheque_number', 'TP.bank_name', 'TP.cheque_date',
                        'TP.card_type',
                        'TP.card_number', // CRITICAL FIX: Add card_number to show in Card Account Book
                        'TP.method',
                        'TP.paid_on',
                        'TP.payment_ref_no',
                        'TP.payment_for',
                        // S763: needed to distinguish a Supplier Pay Due root
                        // payment (no linked transaction) from Advance/Direct Purchase payments.
                        'TP.transaction_id as payment_transaction_id',
                        'TP.account_id as bank_account_id',
                        'updated_type',
                        'updated_by',
                        'account_transactions.updated_at',
                        'A.name as account_name',
                        'transactions.sub_type as transaction_sub_type',
                        'transfer_transaction_id',
                        'ats.name as account_type_name',
                        'account_transactions.transaction_id',
                        'account_transactions.id',
                        'account_transactions.pair_at_id',
                        'account_transactions.auto_transfer',
                        'account_transactions.txnType',
                        'account_transactions.employee_advance_id',
                        'account_transactions.created_at',
                        'ag.name as group_name',
                        'finance_daily_collections.settlement_id',
                        'finance_daily_collections.collection_form_no',
                        'settlements.settlement_no',
                        'account_transactions.new_deleted_at',
                        'transactions.transaction_date',
                        DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                    ])->withTrashed();
                $business_details   = Business::find($business_id);
                $currency_precision = ! empty($business_details) && ! empty($business_details->currency_precision) ? $business_details->currency_precision : config('constants.currency_precision', 2);
                if (! $account_access) {
                    $accounts = collect([]);
                }

                // Order by operation_date (transaction date) for all account books - oldest first (ASC)
                $accounts = $accounts->orderBy('account_transactions.operation_date', 'asc')
                    ->orderBy('account_transactions.id', 'asc')
                    ->get();

                $accounts = $accounts->filter(function ($row) {
                    $amount = is_array($row) ? ($row['amount'] ?? 0) : ($row->amount ?? 0);
                    $sub_type = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                    if ($sub_type === 'expense_reverse') {
                        return true;
                    }
                    return abs((float) $amount) > 0.001;
                });

                // S280-003: Shortage settlement rows must not appear in the Cash Account Book.
                if (!empty($this_account) && stripos((string) ($this_account->name ?? ''), 'cash') !== false) {
                    $accounts = $accounts->filter(function ($row) {
                        $transaction = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        $type = is_object($transaction) ? ($transaction->type ?? null) : (is_array($transaction) ? ($transaction['type'] ?? null) : null);
                        $subType = is_object($transaction) ? ($transaction->sub_type ?? null) : (is_array($transaction) ? ($transaction['sub_type'] ?? null) : null);
                        $atSubType = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);

                        return !($type === 'settlement' && ($subType === 'shortage' || $atSubType === 'shortage'));
                    })->values();
                }

                // IS1497: PD Settlement must never show duplicate account-book rows.
                // This is a display-side safety net for old data while the posting source is guarded.
                $accounts = $this->is1497RemovePdSettlementDuplicateRows($accounts);

                // Fix for Settlement Cash Deposits: Merge them into the accounts collection if this is the Cash Account
                if ($id == $this->transactionUtil->account_exist_return_id('Cash')) {
                    // Check date filters from request which are available in scope
                    $req_start_date = request()->input('start_date');
                    $req_end_date   = request()->input('end_date');

                    $cash_deposits_query = SettlementCashDeposit::where('business_id', $business_id);

                    if (! empty($req_start_date) && ! empty($req_end_date)) {
                        $cash_deposits_query->whereBetween('created_at', [$req_start_date . ' 00:00:00', $req_end_date . ' 23:59:59']);
                    }

                    // account_transactions is authoritative. Legacy source rows are shown only
                    // when no real cash-deposit ledger posting exists for the settlement.
                    $coveredCashDepositKeys = $integrationLedger->coveredSettlementKeys(
                        (int) $business_id,
                        ['cash_deposit']
                    );
                    if ($coveredCashDepositKeys !== []) {
                        $cash_deposits_query->whereNotIn('settlement_no', $coveredCashDepositKeys);
                    }

                    $cash_deposits = $cash_deposits_query->get()
                        ->transform(function ($deposit) {
                            // Use AccountTransaction model instead of stdClass to satisfy Eloquent Collection requirements
                            $row = new \Modules\Finance\Entities\AccountTransaction();

                            $row->id                         = 'SCD-' . $deposit->id;
                            $row->is_settlement_cash_deposit = true;
                            $row->settlement_no              = $deposit->settlement_no;
                            $row->amount                     = $deposit->amount;

                            // Safe date parsing
                            // We set attributes directly on the model
                            $row->operation_date = Carbon::parse($deposit->time_deposited ?? $deposit->created_at)->format('Y-m-d H:i:s');
                            $row->created_at     = Carbon::parse($deposit->created_at)->format('Y-m-d H:i:s');

                            $row->type            = 'credit';
                            $row->account_type_name = 'Assets';
                            $row->account_name    = 'Cash';
                            $row->sub_type        = 'deposit';
                            $row->note            = 'Cash Deposit for Settlement ' . $deposit->settlement_no;
                            $row->created_by      = null;
                            $row->added_by        = 'System';
                            $row->journal_deleted = 0;
                            $row->payment_ref_no  = null; // CRITICAL FIX: Add payment_ref_no to prevent DataTables error

                            // Mock transaction object using Transaction model
                            $transaction                  = new \App\Transaction();
                            $transaction->type            = 'settlement_cash_deposit';
                            $transaction->ref_no          = $deposit->settlement_no;
                            $transaction->invoice_no      = null;
                            $transaction->id              = 0;
                            $transaction->contact_id      = null;
                            $transaction->sub_type        = null;
                            $transaction->discount_type   = null;
                            $transaction->discount_amount = 0;
                            $transaction->is_settlement   = 0;
                            $transaction->new_deleted_at  = null;

                            // Set the relationship
                            $row->setRelation('transaction', $transaction);

                            return $row;
                        });

                    // Merge and re-sort
                    // Use values() to reset keys, critical for DataTables
                    $accounts = $accounts->merge($cash_deposits);

                    // Fix for Settlement Loan Payments: Merge them into the accounts collection if this is the Cash Account
                    $loan_payments_query = \Modules\Petro\Entities\SettlementLoanPayment::query()
                        ->where('business_id', $business_id);

                    if (! empty($req_start_date) && ! empty($req_end_date)) {
                        $loan_payments_query->whereBetween('created_at', [$req_start_date . ' 00:00:00', $req_end_date . ' 23:59:59']);
                    }

                    $coveredLoanPaymentKeys = $integrationLedger->coveredSettlementKeys(
                        (int) $business_id,
                        ['loan_payment']
                    );
                    if ($coveredLoanPaymentKeys !== []) {
                        $loan_payments_query->whereNotIn('settlement_no', $coveredLoanPaymentKeys);
                    }

                    $loan_payments = $loan_payments_query->get();
                    $loanAccountNames = $integrationLedger->accountNameMap(
                        (int) $business_id,
                        $loan_payments->pluck('loan_account')
                    );
                    $loan_payments = $loan_payments->transform(function ($payment) use ($loanAccountNames) {
                           // Use AccountTransaction model
                           $row = new \Modules\Finance\Entities\AccountTransaction();

                           $row->id = 'SLP-' . $payment->id;
                           $row->is_settlement_loan_payment = true;
                           $row->settlement_no = $payment->settlement_no; // Assuming this column exists based on model relationship
                           $row->amount = $payment->amount; // Assuming amount column exists
                           $row->linked_account_name = $loanAccountNames[(int) $payment->loan_account] ?? 'Unknown Account';

                           $row->operation_date = Carbon::parse($payment->created_at)->format('Y-m-d H:i:s');
                           $row->created_at = Carbon::parse($payment->created_at)->format('Y-m-d H:i:s');

                           $row->type = 'debit'; // User requested Debit column
                            $row->account_type_name = 'Assets';
                           $row->account_name = 'Cash';
                           $row->sub_type = 'loan_payment';
                           $row->note = 'Loan Payment from Settlement ' . ($payment->settlement_no ?? '');
                           $row->created_by = null;
                           $row->added_by = 'System';
                           $row->journal_deleted = 0;
                           $row->payment_ref_no = null; // CRITICAL FIX: Add payment_ref_no to prevent DataTables error

                           // Mock transaction object
                           $transaction = new \App\Transaction();
                           $transaction->type = 'settlement_loan_payment';
                           $transaction->ref_no = $payment->settlement_no ?? '';
                           $transaction->id = 0;
                           $transaction->sub_type = 'settlement_loan_payment';
                           $transaction->is_settlement = 0;

                           $row->setRelation('transaction', $transaction);

                           return $row;
                       });

                   $accounts = $accounts->merge($loan_payments)->sortBy('operation_date')->values();

                    // Legacy customer-loan rows are scoped through their settlement because
                    // older source rows do not consistently carry a business_id column.
                    $customer_loans_query = \Modules\Petro\Entities\SettlementCustomerLoan::query()
                        ->join('settlements as finance_customer_loan_settlements', function ($join) {
                            $join->on(
                                'finance_customer_loan_settlements.id',
                                '=',
                                DB::raw('CAST(settlement_customer_loans.settlement_no AS UNSIGNED)')
                            )->orOn(
                                'finance_customer_loan_settlements.settlement_no',
                                '=',
                                'settlement_customer_loans.settlement_no'
                            );
                        })
                        ->where('finance_customer_loan_settlements.business_id', $business_id)
                        ->select('settlement_customer_loans.*');

                    if (! empty($req_start_date) && ! empty($req_end_date)) {
                        $customer_loans_query->whereBetween('settlement_customer_loans.created_at', [$req_start_date . ' 00:00:00', $req_end_date . ' 23:59:59']);
                    }

                    $coveredCustomerLoanKeys = $integrationLedger->coveredSettlementKeys(
                        (int) $business_id,
                        ['customer_loan']
                    );
                    if ($coveredCustomerLoanKeys !== []) {
                        $customer_loans_query->whereNotIn('settlement_customer_loans.settlement_no', $coveredCustomerLoanKeys);
                    }

                    $customer_loans = $customer_loans_query->get();
                    $customerNames = $integrationLedger->contactNameMap(
                        (int) $business_id,
                        $customer_loans->pluck('customer_id')
                    );
                    $customer_loans = $customer_loans->transform(function ($loan) use ($customerNames) {
                           $row = new \Modules\Finance\Entities\AccountTransaction();

                           $row->id = 'SCL-' . $loan->id;
                           $row->is_settlement_customer_loan = true;
                           $row->settlement_no = $loan->settlement_no;
                           $row->amount = $loan->amount;

                           $row->linked_account_name = $customerNames[(int) $loan->customer_id] ?? 'Unknown Customer';

                           $row->operation_date = Carbon::parse($loan->created_at)->format('Y-m-d H:i:s');
                           $row->created_at = Carbon::parse($loan->created_at)->format('Y-m-d H:i:s');

                           $row->type = 'debit'; // User requested Debit column
                            $row->account_type_name = 'Assets';
                           $row->account_name = 'Cash';
                           $row->sub_type = 'customer_loan';
                           $row->note = 'Loan to Customer from Settlement ' . ($loan->settlement_no ?? '');
                           $row->created_by = null;
                           $row->added_by = 'System';
                           $row->journal_deleted = 0;
                           $row->payment_ref_no = null; // CRITICAL FIX: Add payment_ref_no to prevent DataTables error

                           // Mock transaction object
                           $transaction = new \App\Transaction();
                           $transaction->type = 'settlement_customer_loan';
                           $transaction->ref_no = $loan->settlement_no ?? '';
                           $transaction->id = 0;
                           $transaction->sub_type = 'settlement_customer_loan';
                           $transaction->is_settlement = 0;

                           $row->setRelation('transaction', $transaction);

                           return $row;
                       });

                       $accounts = $accounts->merge($customer_loans)->sortBy('operation_date')->values();
                }


                // Merge duplicate Accounts Receivable debit rows from POS that share same invoice_no and operation_date
                // Applies only to Accounts Receivable account book view
                // EXCLUDE credit sales from settlements - they should show separately
                $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                if (! empty($accounts_receivable_id) && intval($id) === intval($accounts_receivable_id)) {
                    $grouped = [];
                    foreach ($accounts as $accRow) {
                        $invoice_no   = optional($accRow->transaction)->invoice_no;
                        $op_date      = ! empty($accRow->operation_date) ? date('Y-m-d', strtotime($accRow->operation_date)) : '';
                        $txn_sub_type = optional($accRow->transaction)->sub_type;
                        $at_sub_type  = $accRow->at_sub_type ?? $accRow->subtype;

                        // Only merge debit rows tied to a sell/fpos_sale transaction with a valid invoice_no
                        // EXCLUDE credit sales (sub_type = 'credit_sale' or is_credit_sale = 1) and settlement transactions
                        // EXCLUDE transactions with sub_type 'credit_sale' in account_transactions
                        $txn_type       = optional($accRow->transaction)->type;
                        $is_credit_sale = ($txn_sub_type === 'credit_sale') ||
                            (optional($accRow->transaction)->is_credit_sale == 1) ||
                            ($at_sub_type === 'credit_sale') ||
                            (optional($accRow->transaction)->is_settlement == 1 && $txn_sub_type === 'credit_sale');
                        $can_merge = ($accRow->type === 'debit') && ! empty($invoice_no) &&
                        in_array($txn_type, ['sell', 'fpos_sale']) &&
                        ! $is_credit_sale; // Don't merge credit sales

                        if ($can_merge) {
                            $key = $invoice_no . '|' . $op_date;
                            if (! isset($grouped[$key])) {
                                $grouped[$key] = clone $accRow;
                                // Initialize with 0 and accumulate
                                $grouped[$key]->amount = 0;
                            }
                            $grouped[$key]->amount += (float) $accRow->amount;
                            // Keep earliest created_at for consistency
                            if (! empty($accRow->created_at)) {
                                if (empty($grouped[$key]->created_at) || strtotime($accRow->created_at) < strtotime($grouped[$key]->created_at)) {
                                    $grouped[$key]->created_at = $accRow->created_at;
                                }
                            }
                        } else {
                            // Keep as-is (including credit sales which should not be merged)
                            $grouped[] = $accRow;
                        }
                    }
                    // Replace $accounts with merged collection while preserving chronological order
                    $accounts = collect($grouped)->values()->sortBy(function ($row) {
                        $d1 = ! empty($row->operation_date) ? strtotime($row->operation_date) : 0;
                        $d2 = ! empty($row->created_at) ? strtotime($row->created_at) : 0;

                        // single comparable string key for stable sort
                        return sprintf('%010d-%010d', $d1, $d2);
                    })->values();
                }

                // Merge duplicate Finished Goods Account credit rows (stock account book) that share
                // the same invoice_no, operation_date, and sell_line_id from POS multi-payment invoices.
                // Applies only when viewing the Finished Goods Account book.
                $finished_goods_account_id = $this->transactionUtil->account_exist_return_id('Finished Goods Account');
                if (! empty($finished_goods_account_id) && intval($id) === intval($finished_goods_account_id)) {
                    $groupedFga = [];
                    foreach ($accounts as $accRow) {
                        $invoice_no = optional($accRow->transaction)->invoice_no;
                        $op_date    = ! empty($accRow->operation_date) ? date('Y-m-d', strtotime($accRow->operation_date)) : '';
                        $txn_type   = optional($accRow->transaction)->type;
                        $sell_line_id = $accRow->sell_line_id ?? null;
                        $transaction_id = optional($accRow->transaction)->id ?? null;

                        // IS1830 old-data compatibility: earlier purchase postings may
                        // have been stored as credits. Treat them as Finished Goods
                        // debits in the Account Book while new postings are normalized
                        // at save time by FinanceServiceProvider.
                        if ($txn_type === 'purchase' && $accRow->type !== 'debit') {
                            $accRow = clone $accRow;
                            $accRow->type = 'debit';
                        }

                        if (in_array($txn_type, ['sell', 'fpos_sale'])) {
                            $can_merge = ($accRow->type === 'credit') && ! empty($invoice_no);

                        } elseif ($txn_type === 'purchase') {
                            $can_merge = ($accRow->type === 'debit') && ! empty($invoice_no);

                        } elseif ($txn_type === 'purchase_return') {
                            // FIX: Purchase return should be CREDIT, not debit
                            $can_merge = ($accRow->type === 'credit') && ! empty($invoice_no);

                        } else {
                            $can_merge = false;
                        }

                        if ($can_merge) {
                            // Group by invoice_no, operation_date, and sell_line_id (or transaction_id if sell_line_id is null)
                            // This ensures entries from the same sell line are merged together
                            // Note: Entries may have different transaction_payment_id values (from old data) but should still be merged
                            $key = $invoice_no . '|' . $op_date . '|' . ($sell_line_id ?? $transaction_id ?? '');
                            if (! isset($groupedFga[$key])) {
                                $groupedFga[$key]         = clone $accRow;
                                $groupedFga[$key]->amount = 0;
                                // Clear transaction_payment_id from merged entry since Finished Goods Account entries are transaction-level
                                $groupedFga[$key]->transaction_payment_id = null;
                                $groupedFga[$key]->tp_id = null;
                            }
                            $groupedFga[$key]->amount += (float) $accRow->amount;
                            if (! empty($accRow->created_at)) {
                                if (empty($groupedFga[$key]->created_at) || strtotime($accRow->created_at) < strtotime($groupedFga[$key]->created_at)) {
                                    $groupedFga[$key]->created_at = $accRow->created_at;
                                }
                            }
                        } else {
                            $groupedFga[] = $accRow;
                        }
                    }

                    $accounts = collect($groupedFga)->values()->sortBy(function ($row) {
                        $d1 = ! empty($row->operation_date) ? strtotime($row->operation_date) : 0;
                        $d2 = ! empty($row->created_at) ? strtotime($row->created_at) : 0;

                        return sprintf('%010d-%010d', $d1, $d2);
                    })->values();
                }

                $is_sales_income_account = ! empty($this_account->name) && stripos($this_account->name, 'Sales Income') !== false;
                if ($is_sales_income_account) {
                    $groupedSalesIncome = [];
                    foreach ($accounts as $accRow) {
                        $invoice_no     = optional($accRow->transaction)->invoice_no;
                        $op_date        = ! empty($accRow->operation_date) ? date('Y-m-d', strtotime($accRow->operation_date)) : '';
                        $transaction_id = optional($accRow->transaction)->id ?? null;
                        $txn_type       = optional($accRow->transaction)->type;

                        $can_merge = in_array($txn_type, ['sell', 'fpos_sale']) && ($accRow->type === 'credit') && ! empty($invoice_no);

                        if ($can_merge) {
                            $key = $invoice_no . '|' . $op_date . '|' . ($transaction_id ?? '');
                            if (! isset($groupedSalesIncome[$key])) {
                                // Modified by Engr. Alex -- task 7889: use AT amount (not final_total) so each
                                // income account shows only its own portion, not the full transaction total
                                $groupedSalesIncome[$key]         = clone $accRow;
                                $groupedSalesIncome[$key]->amount = abs(floatval($accRow->amount));
                                $groupedSalesIncome[$key]->transaction_payment_id = null;
                                $groupedSalesIncome[$key]->tp_id = null;
                            } else {
                                // Accumulate amounts for multiple sell lines of the same invoice
                                // that posted to this same income account
                                $groupedSalesIncome[$key]->amount += abs(floatval($accRow->amount));
                            }
                        } else {
                            $groupedSalesIncome[] = $accRow;
                        }
                    }

                    $accounts = collect($groupedSalesIncome)->values()->sortBy(function ($row) {
                        $d1 = ! empty($row->operation_date) ? strtotime($row->operation_date) : 0;
                        $d2 = ! empty($row->created_at) ? strtotime($row->created_at) : 0;

                        return sprintf('%010d-%010d', $d1, $d2);
                    })->values();
                }

                // IS2258 #1: final fallback-reader order must match the first
                // Account Book Date & Time column. Sort every transaction type by
                // its system entry timestamp, oldest first. Synthetic legacy rows
                // created above also carry created_at; operation_date remains the
                // safe fallback for historical rows without it.
                $accounts = $accounts->sortBy(function ($row) {
                    $createdAt = is_array($row) ? ($row['created_at'] ?? null) : ($row->created_at ?? null);
                    $operationDate = is_array($row) ? ($row['operation_date'] ?? null) : ($row->operation_date ?? null);
                    $id = is_array($row) ? ($row['id'] ?? '') : ($row->id ?? '');

                    $timestamp = $createdAt ?: $operationDate ?: '9999-12-31 23:59:59';

                    try {
                        $sortTime = Carbon::parse($timestamp)->format('Y-m-d H:i:s.u');
                    } catch (\Throwable $e) {
                        $sortTime = (string) $timestamp;
                    }

                    return $sortTime . '|' . str_pad((string) $id, 20, '0', STR_PAD_LEFT);
                })->values();

                // The posted account_transactions collection is the single authoritative
                // Account Book source. Older code expanded one settlement ledger row into
                // Daily Collection source rows during rendering; that changed displayed
                // totals and could duplicate entries already posted by Settlement SW.
                $modifiedAccounts = $accounts->values();

                // Final old-data safety pass after the legacy fallback rows are merged.
                // Duplicate detection uses settlement/payment identity, never row ids.
                $modifiedAccounts = $this->is1497RemovePdSettlementDuplicateRows($modifiedAccounts);

                // Bulk integration context for Account Book descriptions. All maps are
                // prepared once per draw; description callbacks must not query the DB.
                $paymentForContactNames = $integrationLedger->contactNameMap(
                    (int) $business_id,
                    $modifiedAccounts->pluck('payment_for')
                );

                // S763: build the supplier/customer classification once for the
                // whole Account Book draw. Supplier Pay Due root payments have no
                // parent transaction, so payment_for is the reliable identity.
                $paymentForContactTypes = [];
                $paymentForIds = $modifiedAccounts->pluck('payment_for')
                    ->filter()
                    ->map(static fn ($value) => (int) $value)
                    ->unique()
                    ->values();
                if ($paymentForIds->isNotEmpty()) {
                    $paymentForContactTypes = DB::table('contacts')
                        ->where('business_id', $business_id)
                        ->whereIn('id', $paymentForIds->all())
                        ->pluck('type', 'id')
                        ->map(static fn ($value) => strtolower(trim((string) $value)))
                        ->all();
                }

                $settlementInvoiceNumbers = $modifiedAccounts
                    ->map(function ($row) {
                        $transaction = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        if (! is_object($transaction) || (int) ($transaction->is_settlement ?? 0) !== 1) {
                            return null;
                        }

                        return trim((string) ($transaction->invoice_no ?? ''));
                    })
                    ->filter()
                    ->unique()
                    ->values();

                $settlementsByNumber = $settlementInvoiceNumbers->isEmpty()
                    ? collect()
                    : Settlement::where('business_id', $business_id)
                        ->whereIn('settlement_no', $settlementInvoiceNumbers->all())
                        ->get(['id', 'settlement_no'])
                        ->keyBy('settlement_no');

                $settlementIds = $settlementsByNumber->pluck('id')->map(fn ($value) => (int) $value)->values();
                $settlementTransactionIds = $modifiedAccounts
                    ->map(function ($row) {
                        $transaction = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        return is_object($transaction) && (int) ($transaction->is_settlement ?? 0) === 1
                            ? (int) ($transaction->id ?? 0)
                            : 0;
                    })
                    ->filter()
                    ->unique()
                    ->values();

                $cashAccountIdForMaps = $this->transactionUtil->account_exist_return_id('Cash');
                $loanTargetAccountNames = $settlementTransactionIds->isEmpty()
                    ? []
                    : AccountTransaction::join('accounts as finance_loan_target_accounts', 'finance_loan_target_accounts.id', '=', 'account_transactions.account_id')
                        ->where('account_transactions.business_id', $business_id)
                        ->whereIn('account_transactions.transaction_id', $settlementTransactionIds->all())
                        ->where('account_transactions.type', 'debit')
                        ->when(! empty($cashAccountIdForMaps), function ($query) use ($cashAccountIdForMaps) {
                            $query->where('account_transactions.account_id', '!=', $cashAccountIdForMaps);
                        })
                        ->whereNull('account_transactions.deleted_at')
                        ->orderBy('account_transactions.id')
                        ->get(['account_transactions.transaction_id', 'finance_loan_target_accounts.name'])
                        ->unique('transaction_id')
                        ->pluck('name', 'transaction_id')
                        ->mapWithKeys(fn ($name, $transactionId) => [(int) $transactionId => (string) $name])
                        ->all();

                $cashDepositReferenceIds = $modifiedAccounts
                    ->map(function ($row) {
                        $transaction = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        if (! is_object($transaction) || ($transaction->sub_type ?? null) !== 'cash_deposit') {
                            return 0;
                        }

                        return (int) ($transaction->ref_no ?? 0);
                    })
                    ->filter()
                    ->unique()
                    ->values();

                $cashDepositBankNames = $cashDepositReferenceIds->isEmpty()
                    ? []
                    : DB::table('settlement_cash_deposits as finance_scd')
                        ->join('settlements as finance_scd_settlement', function ($join) {
                            $join->on('finance_scd_settlement.id', '=', DB::raw('CAST(finance_scd.settlement_no AS UNSIGNED)'))
                                ->orOn('finance_scd_settlement.settlement_no', '=', 'finance_scd.settlement_no');
                        })
                        ->leftJoin('accounts as finance_scd_bank', 'finance_scd_bank.id', '=', 'finance_scd.bank_id')
                        ->where('finance_scd_settlement.business_id', $business_id)
                        ->whereIn('finance_scd.id', $cashDepositReferenceIds->all())
                        ->pluck('finance_scd_bank.name', 'finance_scd.id')
                        ->mapWithKeys(fn ($name, $sourceId) => [(int) $sourceId => (string) ($name ?? '')])
                        ->all();

                $customerPaymentNames = $settlementIds->isEmpty()
                    ? []
                    : DB::table('customer_payments as finance_cp')
                        ->leftJoin('contacts as finance_cp_contact', 'finance_cp_contact.id', '=', 'finance_cp.customer_id')
                        ->whereIn('finance_cp.settlement_no', $settlementIds->all())
                        ->where('finance_cp_contact.business_id', $business_id)
                        ->orderBy('finance_cp.id')
                        ->get(['finance_cp.settlement_no', 'finance_cp_contact.name'])
                        ->unique('settlement_no')
                        ->pluck('name', 'settlement_no')
                        ->mapWithKeys(fn ($name, $settlementId) => [(int) $settlementId => (string) $name])
                        ->all();

                $drawingAccountNames = $settlementIds->isEmpty()
                    ? []
                    : DB::table('settlement_drawing_payments as finance_sdp')
                        ->leftJoin('accounts as finance_sdp_account', 'finance_sdp_account.id', '=', 'finance_sdp.loan_account')
                        ->whereIn('finance_sdp.settlement_no', $settlementIds->all())
                        ->get(['finance_sdp.settlement_no', 'finance_sdp.amount', 'finance_sdp_account.name'])
                        ->mapWithKeys(function ($row) {
                            $key = (int) $row->settlement_no . '|' . number_format((float) $row->amount, 6, '.', '');
                            return [$key => (string) ($row->name ?? 'N/A')];
                        })
                        ->all();

                // Per-request cache for the account book column callbacks.
                $accountBookArId = null;

                return DataTables::of($modifiedAccounts)
                    ->addColumn('slip_no', function ($row) {
                        $slip_no = is_array($row) ? ($row['slip_no'] ?? null) : ($row->slip_no ?? null);

                        if (! empty($slip_no)) {
                            return e($slip_no);
                        }

                        $payment_ref_no = is_array($row) ? ($row['payment_ref_no'] ?? null) : ($row->payment_ref_no ?? null);
                        if (! empty($payment_ref_no)) {
                            return e($payment_ref_no);
                        }

                        return '-';
                    })
                    ->addColumn('opening_balance', function ($row) use ($currency_precision) {
                        $openingBalance = number_format(Session::get('account_balance'), $currency_precision, '.', '');

                        return '<span class="display_currency" data-currency_symbol=true data-orig-value="' . $openingBalance . '" >' . $openingBalance . '</span>';
                    })
                    ->addColumn('debit', function ($row) use ($currency_precision, $paymentForContactTypes) {
                        // return $row->type;
                        $total_daily_collection = 0;

                        // Finished Goods purchases must appear in the debit column.
                        // Check both by account ID and account name for old installations.

                        if (is_array($row)) {
                            $account_id   = $row['account_id'] ?? null;
                            $account_name = $row['account_name'] ?? null;
                            $transaction  = $row['transaction'] ?? null;
                            $type         = $row['type'] ?? null;
                        } else {
                            $account_id   = $row->account_id ?? null;
                            $account_name = $row->account_name ?? null;
                            $transaction  = $row->transaction ?? null;
                            $type         = $row->type ?? null;
                        }

                        $total_daily_collection = 0;

                        // S763 urgent: Supplier Pay Due accounting is always
                        // DR Accounts Payable / CR selected payment account.
                        // Keep the Account Book presentation correct even for a
                        // historical SLP row whose legacy type flag was stored
                        // incorrectly. The database writer is fixed in Suppliers;
                        // this is a display-level safety net only.
                        $payment_for = is_array($row) ? ($row['payment_for'] ?? null) : ($row->payment_for ?? null);
                        $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                        $payment_transaction_id = is_array($row) ? ($row['payment_transaction_id'] ?? null) : ($row->payment_transaction_id ?? null);
                        $payment_ref_no = is_array($row) ? ($row['payment_ref_no'] ?? null) : ($row->payment_ref_no ?? null);
                        $bank_account_id = is_array($row) ? ($row['bank_account_id'] ?? null) : ($row->bank_account_id ?? null);
                        $paymentForType = ! empty($payment_for) ? ($paymentForContactTypes[(int) $payment_for] ?? '') : '';
                        $isSupplierPayDueSlp = ! empty($tp_id)
                            && in_array($paymentForType, ['supplier', 'both'], true)
                            && empty($payment_transaction_id)
                            && strpos(strtoupper(trim((string) $payment_ref_no)), 'SLP') === 0;
                        if ($isSupplierPayDueSlp) {
                            $isAccountsPayable = in_array(strtolower(trim((string) $account_name)), ['accounts payable', 'account payable'], true);
                            if ($isAccountsPayable) {
                                $val = number_format((float) ($row->amount ?? 0), $currency_precision, '.', '');
                                return '<span class="display_currency debit_col" data-currency_symbol=false data-orig-value="' . $val . '" >' . $val . '</span>';
                            }
                            if (! empty($bank_account_id) && (int) $account_id === (int) $bank_account_id) {
                                return '';
                            }
                        }

                        // Finished Goods purchases appear in debit; purchase returns remain credit.
                        $finished_goods_account_id = $this->transactionUtil->account_exist_return_id('Finished Goods Account');

                        if (
                            (($account_id == $finished_goods_account_id) ||
                                ($account_name == 'Finished Goods Account') ||
                                (strpos($account_name, 'Finished Goods') !== false)) &&
                            ! is_null($transaction) &&
                            in_array($transaction->type, ['purchase', 'purchase_return_transfer'])
                        ) {
                            $val = number_format($row->amount, $currency_precision, '.', '');

                            return '<span class="display_currency debit_col" data-currency_symbol=false data-orig-value="' . $val . '" >' . $val . '</span>';
                        }

                        // Check if this is a reverse entry - don't display it but include in balance
                        $sub_type = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                        if ($sub_type === 'expense_reverse') {
                            return ''; // Hide reverse entries from display
                        }

                        if (! empty($row->amount) && $row->type == 'debit') {
                            // Add null checks to prevent errors
                            if (is_null($row->transaction)) {
                                $val = number_format($row->amount, $currency_precision, '.', '');

                                return '<span class="display_currency debit_col" data-currency_symbol=false data-orig-value="' . $val . '" >' . $val . '</span>';
                            }
                            $amount_with_discount = $row->amount;

                            static $sales_income_account_ids = null;
                            if ($sales_income_account_ids === null) {
                                $sales_income_account_ids = Account::where('business_id', request()->session()->get('user.business_id'))
                                    ->where('name', 'like', '%Sales Income%')
                                    ->pluck('id')
                                    ->toArray();
                            }

                            if ($row->account_type_name != 'Expenses' && ! is_null($row->transaction) && $row->account_id != $this->transactionUtil->account_exist_return_id('Finished Goods Account')) {
                                $is_sales_income = (!empty($row->account_name) && stripos($row->account_name, 'Sales Income') !== false)
                                    || (!empty($account_id) && in_array($account_id, $sales_income_account_ids));

                                if (!$is_sales_income) {
                                    $ar_account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                                    $is_accounts_receivable = ($account_id == $ar_account_id);
                                    $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                                    $has_payment_id = !empty($tp_id);

                                    if ($is_accounts_receivable || $has_payment_id) {
                                        $amount_with_discount = $row->amount;
                                    } else {
                                        if (! is_null($row->transaction->discount_type) && $row->transaction->discount_type == 'percentage') {
                                            if ($row->transaction->type == 'hms_booking') {
                                                $amount_with_discount = $row->amount;
                                            } else {
                                                $amount_with_discount = $row->amount - $row->amount * $row->transaction->discount_amount / 100;
                                            }
                                        } elseif (! is_null($row->transaction->discount_type) && $row->transaction->discount_type == 'fixed') {
                                            $sum_quantitly = DB::table('transaction_sell_lines')
                                                ->where('transaction_id', $row->transaction->id)
                                                ->sum('quantity');
                                            if (! is_null($sum_quantitly) && $sum_quantitly != 0) {
                                                $amount_with_discount = $row->amount - $row->transaction->discount_amount / $sum_quantitly;
                                            }
                                        } else {
                                            $amount_with_discount = $row->amount;
                                        }
                                    }
                                } else {
                                    $amount_with_discount = $row->amount;
                                }
                            }
                            if ($row->account_type_name != 'Expenses' && ! is_null($row->transaction) && $row->transaction->type == 'sell' && $row->transaction->sub_type == 'settlement' && $row->account_id != $this->transactionUtil->account_exist_return_id('Finished Goods Account')) {
                                $is_sales_income_account = (!empty($row->account_name) && stripos($row->account_name, 'Sales Income') !== false)
                                    || (!empty($account_id) && in_array($account_id, $sales_income_account_ids));

                                if (!$is_sales_income_account) {
                                    $sell_info = DB::table('transaction_sell_lines')->where('id', $row->sell_line_id)->first();

                                    if ($sell_info) {
                                        if ($sell_info->line_discount_type == 'fixed') {
                                            $amount_with_discount = $row->amount - $sell_info->line_discount_amount;
                                        }
                                        if ($sell_info->line_discount_type == 'percentage') {
                                            $amount_with_discount = $row->amount * (1 - $sell_info->line_discount_amount / 100);
                                        }
                                    }
                                }
                            }

                            $val = number_format($amount_with_discount, $currency_precision, '.', '');

                            return '<span class="display_currency debit_col" data-currency_symbol=false data-orig-value="' . $val . '" >' . $val . '</span>';
                            // }
                        } else {
                            return '';
                        }
                    })
                    /*
                     |----------------------------------------------------------
                     | $accountBookArId is passed BY REFERENCE.
                     |----------------------------------------------------------
                     |
                     | This closure runs once per ROW. account_exist_return_id()
                     | queries the accounts table every call, so resolving
                     | "Accounts Receivable" inside it meant one extra query per
                     | row - a few hundred on a busy account book.
                     |
                     | Passing by reference lets the value resolved on the first
                     | row be reused by every later one. Without the &, each row
                     | would get its own copy and query again.
                     */
                    ->addColumn('credit', function ($row) use ($currency_precision, $id, &$accountBookArId, $paymentForContactTypes) {

                        // ✅ Handle both array and object cases safely
                        $account_id        = is_array($row) ? ($row['account_id'] ?? null) : ($row->account_id ?? null);
                        $account_name      = is_array($row) ? ($row['account_name'] ?? null) : ($row->account_name ?? null);
                        $account_type_name = is_array($row) ? ($row['account_type_name'] ?? null) : ($row->account_type_name ?? null);
                        $amount            = is_array($row) ? ($row['amount'] ?? null) : ($row->amount ?? null);
                        $transaction       = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        $sell_line_id      = is_array($row) ? ($row['sell_line_id'] ?? null) : ($row->sell_line_id ?? null);

                        $finished_goods_account_id = $this->transactionUtil->account_exist_return_id('Finished Goods Account');

                        // S763 urgent counterpart of the debit safety net above:
                        // Supplier Pay Due must show on the Credit side of the
                        // payment account selected in the Supplier payment form.
                        $payment_for = is_array($row) ? ($row['payment_for'] ?? null) : ($row->payment_for ?? null);
                        $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                        $payment_transaction_id = is_array($row) ? ($row['payment_transaction_id'] ?? null) : ($row->payment_transaction_id ?? null);
                        $payment_ref_no = is_array($row) ? ($row['payment_ref_no'] ?? null) : ($row->payment_ref_no ?? null);
                        $bank_account_id = is_array($row) ? ($row['bank_account_id'] ?? null) : ($row->bank_account_id ?? null);
                        $paymentForType = ! empty($payment_for) ? ($paymentForContactTypes[(int) $payment_for] ?? '') : '';
                        $isSupplierPayDueSlp = ! empty($tp_id)
                            && in_array($paymentForType, ['supplier', 'both'], true)
                            && empty($payment_transaction_id)
                            && strpos(strtoupper(trim((string) $payment_ref_no)), 'SLP') === 0;
                        if ($isSupplierPayDueSlp) {
                            $isAccountsPayable = in_array(strtolower(trim((string) $account_name)), ['accounts payable', 'account payable'], true);
                            if ($isAccountsPayable) {
                                return '';
                            }
                            if (! empty($bank_account_id) && (int) $account_id === (int) $bank_account_id) {
                                $val = number_format((float) $amount, $currency_precision, '.', '');
                                return '<span class="display_currency credit_col" data-currency_symbol=false data-orig-value="' . $val . '">' . $val . '</span>';
                            }
                        }

                        // Check if this is a reverse entry - don't display it but include in balance
                        $sub_type = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                        if ($sub_type === 'expense_reverse') {
                            return ''; // Hide reverse entries from display
                        }

                        $transaction_type = is_object($transaction)
                            ? ($transaction->type ?? null)
                            : (is_array($transaction) ? ($transaction['type'] ?? null) : null);
                        $is_finished_goods_account =
                            ((int) $account_id === (int) $finished_goods_account_id) ||
                            ($account_name === 'Finished Goods Account') ||
                            ($account_name === 'Finished Goods Accounting') ||
                            (! empty($account_name) && stripos($account_name, 'Finished Goods') !== false);

                        // IS1830: old purchase rows that were persisted as credits are
                        // intentionally rendered only on the debit side.
                        if ($is_finished_goods_account && $transaction_type === 'purchase') {
                            return '';
                        }

                        // ✅ Regular credit case
                        if (! empty($amount) && (is_array($row) ? ($row['type'] ?? null) : ($row->type ?? null)) == 'credit') {

                            if (is_null($transaction)) {
                                $val = number_format($amount, $currency_precision, '.', '');

                                return '<span class="display_currency credit_col" data-currency_symbol=false data-orig-value="' . $val . '">' . $val . '</span>';
                            }

                            $amount_with_discount = $amount;

                            static $sales_income_account_ids_credit = null;
                            if ($sales_income_account_ids_credit === null) {
                                $sales_income_account_ids_credit = Account::where('business_id', request()->session()->get('user.business_id'))
                                    ->where('name', 'like', '%Sales Income%')
                                    ->pluck('id')
                                    ->toArray();
                            }

                            $transaction_id = is_object($transaction) ? ($transaction->id ?? null) : ($transaction['id'] ?? null);
                            $transaction_type = is_object($transaction) ? ($transaction->type ?? null) : ($transaction['type'] ?? null);
                            $transaction_sub_type = is_object($transaction) ? ($transaction->sub_type ?? null) : ($transaction['sub_type'] ?? null);
                            $transaction_is_settlement = (int) (is_object($transaction) ? ($transaction->is_settlement ?? 0) : ($transaction['is_settlement'] ?? 0));

                            if (
                                $id == $this->transactionUtil->account_exist_return_id('Accounts Receivable')
                                && ! is_null($transaction)
                                && in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true)
                                && ($transaction->is_settlement != 1)
                            ) {
                                static $shownAggregatedTxnIds = [];
                                $txnId                        = $transaction->id;
                                if (! isset($shownAggregatedTxnIds[$txnId])) {
                                    $shownAggregatedTxnIds[$txnId] = true;
                                    /*
                                     | Resolved ONCE per request.
                                     |
                                     | account_exist_return_id() runs a query every
                                     | time, and this sits inside a DataTables column
                                     | callback - so it fired once per ROW. The id
                                     | cannot change while the page is being built.
                                     */
                                    if ($accountBookArId === null) {
                                        $accountBookArId = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                                    }
                                    $arId                          = $accountBookArId;
                                    $sum                           = DB::table('account_transactions')
                                        ->where('transaction_id', $txnId)
                                        ->where('account_id', $arId)
                                        ->where('type', 'credit')
                                        ->sum('amount');
                                    $amount_with_discount = $sum;
                                } else {
                                    return '';
                                }
                            }

                            if ($account_type_name != 'Expenses' && ! is_null($transaction) && $account_id != $finished_goods_account_id) {
                                $is_sales_income = (!empty($account_name) && stripos($account_name, 'Sales Income') !== false)
                                    || (!empty($account_id) && in_array($account_id, $sales_income_account_ids_credit));

                                if (!$is_sales_income) {
                                    $ar_account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                                    $is_accounts_receivable = ($account_id == $ar_account_id);
                                    $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                                    $has_payment_id = !empty($tp_id);

                                    if ($is_accounts_receivable || $has_payment_id) {
                                        $amount_with_discount = $amount;
                                    } else {
                                        if (! is_null($transaction->discount_type) && $transaction->discount_type == 'percentage') {
                                            if ($transaction->type == 'hms_booking') {
                                                $amount_with_discount = $amount;
                                            } else {
                                                $amount_with_discount = $amount - $amount * $transaction->discount_amount / 100;
                                            }
                                        } elseif (! is_null($transaction->discount_type) && $transaction->discount_type == 'fixed') {
                                            $sum_quantity = DB::table('transaction_sell_lines')
                                                ->where('transaction_id', $transaction->id)
                                                ->sum('quantity');
                                            if (! is_null($sum_quantity) && $sum_quantity != 0) {
                                                $amount_with_discount = $amount - $transaction->discount_amount / $sum_quantity;
                                            }
                                        } else {
                                            $amount_with_discount = $amount;
                                        }
                                    }
                                } else {
                                    if (in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'], true) && ($transaction_sub_type === 'settlement' || $transaction_is_settlement === 1)) {
                                        $amount_with_discount = $amount;
                                    } elseif (in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'], true) && ! is_null($transaction_id)) {
                                        $transaction_data = DB::table('transactions')
                                            ->where('id', $transaction_id)
                                            ->select('discount_type', 'discount_amount', 'final_total')
                                            ->first();

                                        if ($transaction_data) {
                                            $final_total = abs(floatval($transaction_data->final_total ?? 0));
                                            $stored_amount = abs(floatval($amount));

                                            if (abs($stored_amount - $final_total) > 0.01) {
                                                $discount_type_val = $transaction_data->discount_type ?? null;
                                                $discount_amount_val = $transaction_data->discount_amount ?? null;

                                                if (!is_null($discount_type_val) && !is_null($discount_amount_val) && floatval($discount_amount_val) > 0) {
                                                    $total_before_discount = 0;
                                                    $sell_lines = DB::table('transaction_sell_lines')
                                                        ->where('transaction_id', $transaction_id)
                                                        ->get();
                                                    foreach ($sell_lines as $line) {
                                                        $unit_price_inc_tax = floatval($line->unit_price_inc_tax ?? 0);
                                                        $quantity = floatval($line->quantity ?? 0);
                                                        $total_before_discount += $unit_price_inc_tax * $quantity;
                                                    }

                                                    if ($total_before_discount > 0) {
                                                        if ($discount_type_val === 'percentage') {
                                                            $discount = $total_before_discount * (floatval($discount_amount_val) / 100);
                                                            $amount_after_discount = $total_before_discount - $discount;
                                                            $amount_with_discount = ($amount / $total_before_discount) * $amount_after_discount;
                                                        } elseif ($discount_type_val === 'fixed') {
                                                            $discount = floatval($discount_amount_val);
                                                            $amount_after_discount = $total_before_discount - $discount;
                                                            $amount_with_discount = ($amount / $total_before_discount) * $amount_after_discount;
                                                        } else {
                                                            $amount_with_discount = $final_total;
                                                        }
                                                    } else {
                                                        $amount_with_discount = $final_total;
                                                    }
                                                } else {
                                                    $amount_with_discount = $final_total;
                                                }
                                            } else {
                                                $amount_with_discount = $amount;
                                            }
                                        } else {
                                            $amount_with_discount = $amount;
                                        }
                                    } else {
                                        $amount_with_discount = $amount;
                                    }
                                }
                            }

                            //Settlement adjustment
                            if (
                                $account_type_name != 'Expenses' &&
                                ! is_null($transaction) &&
                                in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true) &&
                                $transaction->sub_type == 'settlement' &&
                                $account_id != $finished_goods_account_id
                            ) {
                                $is_sales_income_account = (!empty($account_name) && stripos($account_name, 'Sales Income') !== false)
                                    || (!empty($account_id) && in_array($account_id, $sales_income_account_ids_credit));

                                if (!$is_sales_income_account) {
                                    $sell_info = DB::table('transaction_sell_lines')->where('id', $sell_line_id)->first();
                                    if ($sell_info) {
                                        if ($sell_info->line_discount_type == 'fixed') {
                                            $amount_with_discount = $amount - $sell_info->line_discount_amount;
                                        } elseif ($sell_info->line_discount_type == 'percentage') {
                                            $amount_with_discount = $amount * (1 - $sell_info->line_discount_amount / 100);
                                        }
                                    }
                                }
                            }

                            $val = number_format($amount_with_discount, $currency_precision, '.', '');

                            return '<span class="display_currency credit_col" data-currency_symbol=false data-orig-value="' . $val . '">' . $val . '</span>';
                        }

                        return '';
                    })

                    ->editColumn('cheque_date', function ($row) {
                        if (! empty($row->cheque_date)) {
                            return $this->commonUtil->format_date($row->cheque_date);
                        }
                    })
                    ->editColumn('cheque_number', function ($row) {
                        // Safe access for transaction
                        $transaction = is_array($row)
                            ? ($row['transaction'] ?? null)
                            : ($row->transaction ?? null);
                            

                        if (! empty($transaction) && $transaction->new_deleted_at && $transaction->new_deleted_parent_id) {
                            $chequeTransactionPayments = TransactionPayment::where('transaction_id', $transaction->new_deleted_parent_id)
                                ->whereNotNull('cheque_number')
                                ->pluck('cheque_number')
                                ->filter()
                                ->toArray();

                            if (! empty($chequeTransactionPayments)) {
                                return 'Cheque No: ' . implode(', ', $chequeTransactionPayments);
                            }
                        }

                        $transaction_payment = $transaction
                            ? TransactionPayment::where('transaction_id', is_array($transaction) ? $transaction['id'] : $transaction->id)->first()
                            : null;

                        // Safe access for other $row properties
                        $sub_type         = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                        $tp_id            = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                        $cheque_number_tp = is_array($row) ? ($row['cheque_number'] ?? '') : ($row->cheque_number ?? '');
                        $cheque_number_at = is_array($row) ? ($row['dep_trans_cheque_number'] ?? '') : ($row->dep_trans_cheque_number ?? '');
                        $group_name       = strtolower(is_array($row) ? ($row['group_name'] ?? '') : ($row->group_name ?? ''));
                        $card_number      = is_array($row) ? ($row['card_number'] ?? '') : ($row->card_number ?? '');

                        $chequeNumber = '';
                        if (! empty($transaction_payment) && ! empty($transaction_payment->cheque_number)) {
                            $chequeNumber = $transaction_payment->cheque_number;
                        } elseif ($sub_type === 'deposit' && ! empty($tp_id)) {
                            $tp           = TransactionPayment::find($tp_id);
                            $chequeNumber = $tp?->cheque_number ?? '';
                        } else {
                            // For account transaction rows (purchase return), cheque is in account_transactions.cheque_number.
                            // $chequeNumber = ! empty($cheque_number_at) ? $cheque_number_at : $cheque_number_tp;
                            if (!empty($transaction_payment) && !empty($transaction_payment->cheque_number)) {
                                $chequeNumber = $transaction_payment->cheque_number;

                            } elseif ($sub_type === 'deposit' && !empty($tp_id)) {
                                $tp = TransactionPayment::find($tp_id);
                                $chequeNumber = $tp?->cheque_number ?? '';

                            } elseif (!empty($cheque_number_at)) {
                                // THIS FIXES ACCOUNT TRANSACTIONS (purchase return, opening balance, etc.)
                                $chequeNumber = $cheque_number_at;

                            } elseif (!empty($cheque_number_tp)) {
                                $chequeNumber = $cheque_number_tp;
                            }
                        }

                        $cardNumber = '';
                        // Get card number from multiple sources
                        $payment_method = is_array($row) ? ($row['method'] ?? '') : ($row->method ?? '');

                        // For card account OR card payment method, show card number
                        if ($group_name === 'card' || $payment_method === 'card') {
                            if (! empty($card_number)) {
                                $cardNumber = $card_number;
                            } elseif (! empty($transaction_payment) && ! empty($transaction_payment->card_number)) {
                                $cardNumber = $transaction_payment->card_number;
                            }
                        }

                        $parts = [];
                        if (! empty($chequeNumber)) {
                            $parts[] = 'Cheq No. ' . $chequeNumber;
                        }
                        if (! empty($cardNumber)) {
                            $parts[] = 'Card No. ' . $cardNumber;
                        }

                        return implode(' or ', $parts);
                    })
                    ->editColumn('balance', function ($row) use ($business_details, $currency_precision, &$i) {
                        $balance = Session::get('account_balance');

                        $deleted_at     = is_array($row) ? ($row['deleted_at'] ?? null) : ($row->deleted_at ?? null);
                        $new_deleted_at = is_array($row) ? ($row['new_deleted_at'] ?? null) : ($row->new_deleted_at ?? null);
                        $sub_type       = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);

                        // Balance calculation logic:
                        // 1. Include normal entries (not deleted)
                        // 2. Include deleted expense entries (new_deleted_at) - they were already counted, show with strikethrough
                        // 3. Include reverse entries (sub_type = 'expense_reverse') - they offset deleted entries but are hidden
                        // 4. Exclude only soft-deleted entries (deleted_at) that are not reverse entries
                        $is_reverse_entry = ($sub_type === 'expense_reverse');
                        // Include all entries except soft-deleted ones (unless they're reverse entries or have new_deleted_at)
                        // This ensures deleted expense entries (both debit and credit) are included in balance calculation
                        $should_include_in_balance = empty($deleted_at) || !empty($new_deleted_at) || $is_reverse_entry;

                        if ($should_include_in_balance) {

                            $account_name      = is_array($row) ? ($row['account_name'] ?? null) : ($row->account_name ?? null);
                            $account_type_name = is_array($row) ? ($row['account_type_name'] ?? null) : ($row->account_type_name ?? null);
                            $type              = is_array($row) ? ($row['type'] ?? null) : ($row->type ?? null);
                            $amount            = is_array($row) ? ($row['amount'] ?? 0) : ($row->amount ?? 0);
                            $transaction       = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                            $account_id        = is_array($row) ? ($row['account_id'] ?? null) : ($row->account_id ?? null);
                            $sell_line_id      = is_array($row) ? ($row['sell_line_id'] ?? null) : ($row->sell_line_id ?? null);

                            // Assets or Expenses
                            if (! is_null($account_type_name) && (strpos($account_type_name, 'Assets') !== false || strpos($account_type_name, 'Expenses') !== false)) {
                                if ($type === 'credit') {
                                    $balance -= (float) $amount;
                                }
                                if ($type === 'debit') {
                                    $balance += (float) $amount;
                                }
                            }

                            // Income, Equity, Liabilities
                            elseif (! is_null($account_type_name) && (strpos($account_type_name, 'Income') !== false || strpos($account_type_name, 'Equity') !== false || strpos($account_type_name, 'Liabilities') !== false)) {
                                if ($type === 'credit') {
                                    $amount_with_discount = $amount;
                                    $transaction_type     = is_object($transaction) ? $transaction->type ?? null : ($transaction['type'] ?? null);
                                    $transaction_sub_type = is_object($transaction) ? $transaction->sub_type ?? null : ($transaction['sub_type'] ?? null);
                                    $discount_type        = is_object($transaction) ? $transaction->discount_type ?? null : ($transaction['discount_type'] ?? null);
                                    $discount_amount      = is_object($transaction) ? $transaction->discount_amount ?? null : ($transaction['discount_amount'] ?? null);
                                    $transaction_id       = is_object($transaction) ? $transaction->id ?? null : ($transaction['id'] ?? null);

                                    static $sales_income_account_ids_balance = null;
                                    if ($sales_income_account_ids_balance === null) {
                                        $sales_income_account_ids_balance = Account::where('business_id', request()->session()->get('user.business_id'))
                                            ->where('name', 'like', '%Sales Income%')
                                            ->pluck('id')
                                            ->toArray();
                                    }

                                    if (! is_null($transaction) && $account_id != $this->transactionUtil->account_exist_return_id('Finished Goods Account')) {
                                        $is_sales_income = (!empty($account_name) && stripos($account_name, 'Sales Income') !== false)
                                            || (!empty($account_id) && in_array($account_id, $sales_income_account_ids_balance));

                                        if (!$is_sales_income) {
                                            if ($discount_type === 'percentage') {
                                                $amount_with_discount = $amount - $amount * $discount_amount / 100;
                                            } elseif ($discount_type === 'fixed') {
                                                $sum_quantity = DB::table('transaction_sell_lines')->where('transaction_id', $transaction_id)->sum('quantity');
                                                if (! is_null($sum_quantity) && $sum_quantity != 0) {
                                                    $amount_with_discount = $amount - $discount_amount / $sum_quantity;
                                                }
                                            }
                                        } else {
                                            if (in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'], true) && ($transaction_sub_type === 'settlement' || (int) (is_object($transaction) ? ($transaction->is_settlement ?? 0) : ($transaction['is_settlement'] ?? 0)) === 1)) {
                                                $amount_with_discount = $amount;
                                            } elseif (in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'], true) && ! is_null($transaction_id)) {
                                                $transaction_data = DB::table('transactions')
                                                    ->where('id', $transaction_id)
                                                    ->select('discount_type', 'discount_amount', 'final_total')
                                                    ->first();

                                                if ($transaction_data) {
                                                    $final_total = abs(floatval($transaction_data->final_total ?? 0));
                                                    $stored_amount = abs(floatval($amount));

                                                    if (abs($stored_amount - $final_total) > 0.01) {
                                                        $discount_type_val = $transaction_data->discount_type ?? null;
                                                        $discount_amount_val = $transaction_data->discount_amount ?? null;

                                                        if (!is_null($discount_type_val) && !is_null($discount_amount_val) && floatval($discount_amount_val) > 0) {
                                                            $total_before_discount = 0;
                                                            $sell_lines = DB::table('transaction_sell_lines')
                                                                ->where('transaction_id', $transaction_id)
                                                                ->get();
                                                            foreach ($sell_lines as $line) {
                                                                $unit_price_inc_tax = floatval($line->unit_price_inc_tax ?? 0);
                                                                $quantity = floatval($line->quantity ?? 0);
                                                                $total_before_discount += $unit_price_inc_tax * $quantity;
                                                            }

                                                            if ($total_before_discount > 0) {
                                                                if ($discount_type_val === 'percentage') {
                                                                    $discount = $total_before_discount * (floatval($discount_amount_val) / 100);
                                                                    $amount_after_discount = $total_before_discount - $discount;
                                                                    $amount_with_discount = ($amount / $total_before_discount) * $amount_after_discount;
                                                                } elseif ($discount_type_val === 'fixed') {
                                                                    $discount = floatval($discount_amount_val);
                                                                    $amount_after_discount = $total_before_discount - $discount;
                                                                    $amount_with_discount = ($amount / $total_before_discount) * $amount_after_discount;
                                                                } else {
                                                                    $amount_with_discount = $final_total;
                                                                }
                                                            } else {
                                                                $amount_with_discount = $final_total;
                                                            }
                                                        } else {
                                                            $amount_with_discount = $final_total;
                                                        }
                                                    } else {
                                                        $amount_with_discount = $amount;
                                                    }
                                                } else {
                                                    $amount_with_discount = $amount;
                                                }
                                            } else {
                                                $amount_with_discount = $amount;
                                            }
                                        }
                                    }

                                    if (in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale'], true) && $transaction_sub_type === 'settlement' && $account_id != $this->transactionUtil->account_exist_return_id('Finished Goods Account')) {
                                        $is_sales_income_account = (!empty($account_name) && stripos($account_name, 'Sales Income') !== false)
                                            || (!empty($account_id) && in_array($account_id, $sales_income_account_ids_balance));

                                        if (!$is_sales_income_account) {
                                            $sell_info = DB::table('transaction_sell_lines')->where('id', $sell_line_id)->first();
                                            if ($sell_info) {
                                                if ($sell_info->line_discount_type === 'fixed') {
                                                    $amount_with_discount = $amount - $sell_info->line_discount_amount;
                                                }
                                                if ($sell_info->line_discount_type === 'percentage') {
                                                    $amount_with_discount = $amount * (1 - $sell_info->line_discount_amount / 100);
                                                }
                                            }
                                        }
                                    }

                                    $balance += (float) $amount_with_discount;
                                }

                                if ($type === 'debit') {
                                    $balance -= (float) $amount;
                                }
                            }
                        }

                        Session::put('account_balance', $balance);

                        return '<span class="display_currency" data-currency_symbol="true">' . $this->productUtil->num_f($balance, false, $business_details, true) . '</span>';
                    })
                    ->editColumn('operation_date', function ($row) {
                        // S280-004: Date column = system entered date.
                        // The separate Transaction Date column is handled by realize_date below.
                        $created_at = is_array($row) ? ($row['created_at'] ?? null) : ($row->created_at ?? null);
                        $operation_date = is_array($row) ? ($row['operation_date'] ?? null) : ($row->operation_date ?? null);
                        $date_to_use = $created_at ?? $operation_date;
                        return $date_to_use ? $this->commonUtil->format_date($date_to_use, false) : '';
                    })
                    ->addColumn('operation_date_raw', function ($row) {
                        $created_at = is_array($row) ? ($row['created_at'] ?? null) : ($row->created_at ?? null);
                        $operation_date = is_array($row) ? ($row['operation_date'] ?? null) : ($row->operation_date ?? null);
                        return $created_at ?? $operation_date ?? '';
                    })
                    ->addColumn('operation_time', function ($row) {
                        // Display the entry time beneath the system-entered Date.
                        // Prefer created_at because the Date column itself uses it;
                        // fall back to operation_date for historical rows.
                        $created_at = is_array($row) ? ($row['created_at'] ?? null) : ($row->created_at ?? null);
                        $operation_date = is_array($row) ? ($row['operation_date'] ?? null) : ($row->operation_date ?? null);
                        $date_to_use = $created_at ?? $operation_date;

                        if (empty($date_to_use)) {
                            return '';
                        }

                        try {
                            $timeFormat = (int) session('business.time_format', 24) === 24 ? 'H:i' : 'h:i A';

                            return Carbon::parse($date_to_use)->format($timeFormat);
                        } catch (\Throwable $e) {
                            return '';
                        }
                    })
                    ->addColumn('description', function ($row) use ($business_id, $id, $currency_precision, $paymentForContactNames, $paymentForContactTypes, $settlementsByNumber, $loanTargetAccountNames, $cashDepositBankNames, $customerPaymentNames, $drawingAccountNames) {
                        // Custom Description for Settlement Cash Deposits
                        if (is_array($row) ? isset($row['is_settlement_cash_deposit']) : isset($row->is_settlement_cash_deposit)) {
                            $settlementNumber = is_array($row) ? ($row['settlement_no'] ?? '') : ($row->settlement_no ?? '');
                            return 'Cash Deposit' . "<br>Settlement SW No: " . $settlementNumber;
                        }

                        // Check for Settlement Loan Payment
                        if (is_array($row) ? isset($row['is_settlement_loan_payment']) : isset($row->is_settlement_loan_payment)) {
                            $settlementNumber  = is_array($row) ? ($row['settlement_no'] ?? '') : ($row->settlement_no ?? '');
                            $linkedAccountName = is_array($row) ? ($row['linked_account_name'] ?? '') : ($row->linked_account_name ?? '');

                            $desc = "Settlement No: " . $settlementNumber;
                            $desc .= "<br> Loan Payment";
                            $desc .= "<br> To: " . $linkedAccountName;

                            return $desc;
                        }

                        // Check for Settlement Customer Loan (Loan to Customer)
                        if (is_array($row) ? isset($row['is_settlement_customer_loan']) : isset($row->is_settlement_customer_loan)) {
                            $settlementNumber = is_array($row) ? ($row['settlement_no'] ?? '') : ($row->settlement_no ?? '');
                            // Note: user asked for "Cash payment:" static text, so we include it.

                            $desc = "Settlement No: " . $settlementNumber;
                            $desc .= "<br> Loan to customer";

                            return $desc;
                        }

                        // Check for Distribution Invoice transactions
if (!empty($row->transaction) && isset($row->transaction->type) && $row->transaction->type == 'dis_invoice') {
    $transaction = $row->transaction;
    
    // Get invoice number
    $invoice_no = $transaction->invoice_no ?? 
                  (isset($transaction->ref_no) ? $transaction->ref_no : 'N/A');
    
    // Get customer name
    $customer_name = '';
    
    // Try to get customer from transaction contact
    if (!empty($transaction->contact)) {
        $customer_name = $transaction->contact->name;
    } 
    // Fallback to note if available
    elseif (!empty($row->note) && preg_match('/Customer Name: (.+)/', $row->note, $matches)) {
        $customer_name = $matches[1];
    }
    // Fallback to stored value in row
    elseif (!empty($row->customer_name)) {
        $customer_name = $row->customer_name;
    }
    
    // Build the description
    $details = '<b>Dis. Invoice No:</b> ' . e($invoice_no) . '<br>';
    $details .= '<b>Customer Name:</b> ' . e($customer_name);
    
    return $details;
}

                        $details = '';

                        $id_val                  = is_array($row) ? ($row['id'] ?? null) : ($row->id ?? null);
                        $fixed_asset_id          = is_array($row) ? ($row['fixed_asset_id'] ?? null) : ($row->fixed_asset_id ?? null);
                        $transaction             = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        $transaction_id          = is_array($row) ? ($row['transaction_id'] ?? null) : ($row->transaction_id ?? null);
                        $txnType                 = is_array($row) ? ($row['txnType'] ?? null) : ($row->txnType ?? null);
                        $employee_advance_id     = is_array($row) ? ($row['employee_advance_id'] ?? null) : ($row->employee_advance_id ?? null);
                        $type                    = is_array($row) ? ($row['type'] ?? null) : ($row->type ?? null);
                        $account_name            = is_array($row) ? ($row['account_name'] ?? null) : ($row->account_name ?? null);
                        $account_id              = is_array($row) ? ($row['account_id'] ?? null) : ($row->account_id ?? null);
                        $sub_type                = is_array($row) ? ($row['sub_type'] ?? null) : ($row->sub_type ?? null);
                        $journal_deleted         = is_array($row) ? ($row['journal_deleted'] ?? null) : ($row->journal_deleted ?? null);
                        $transfer_transaction    = is_array($row) ? ($row['transfer_transaction'] ?? null) : ($row->transfer_transaction ?? null);
                        $payment_for             = is_array($row) ? ($row['payment_for'] ?? null) : ($row->payment_for ?? null);
                        $transaction_payment_id  = is_array($row) ? ($row['transaction_payment_id'] ?? null) : ($row->transaction_payment_id ?? null);
                        $payment_ref_no          = is_array($row) ? ($row['payment_ref_no'] ?? null) : ($row->payment_ref_no ?? null);
                        $cheque_number           = is_array($row) ? ($row['cheque_number'] ?? null) : ($row->cheque_number ?? null);
                        $dep_trans_cheque_number = is_array($row) ? ($row['dep_trans_cheque_number'] ?? null) : ($row->dep_trans_cheque_number ?? null);
                        $post_dated_cheque       = is_array($row) ? ($row['post_dated_cheque'] ?? null) : ($row->post_dated_cheque ?? null);
                        $cheque_date             = is_array($row) ? ($row['cheque_date'] ?? null) : ($row->cheque_date ?? null);
                        $tp_id                    = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                        $payment_transaction_id   = is_array($row) ? ($row['payment_transaction_id'] ?? null) : ($row->payment_transaction_id ?? null);

                        // S763: Supplier Pay Due is a root transaction_payment. It
                        // deliberately has no linked purchase transaction, so the
                        // old generic Account Book description could not determine
                        // that this was a Pay Due payment. payment_for identifies the
                        // supplier and TP.transaction_id distinguishes it from an
                        // Advance/Direct Purchase payment.
                        $paymentForType = ! empty($payment_for)
                            ? ($paymentForContactTypes[(int) $payment_for] ?? '')
                            : '';
                        $isSupplierPayDue = ! empty($tp_id)
                            && in_array($paymentForType, ['supplier', 'both'], true)
                            && empty($payment_transaction_id)
                            && empty($transaction_id);

                        if ($isSupplierPayDue) {
                            $supplierName = ! empty($payment_for)
                                ? ($paymentForContactNames[(int) $payment_for] ?? '')
                                : '';
                            $payDueDetails = ['<strong>Pay Due Amount</strong>'];
                            if ($supplierName !== '') {
                                $payDueDetails[] = '<b>Supplier:</b> ' . e($supplierName);
                            }
                            if (! empty($payment_ref_no)) {
                                $payDueDetails[] = '<b>Payment Ref No:</b> ' . e($payment_ref_no);
                            }

                            return implode('<br>', $payDueDetails);
                        }

                        // ---------- Customer payment: ensure customer shows in description ----------
                        // Some flows store only cheque/card info in note; Account Books should always show Customer name.
                        if (! empty($transaction_payment_id)) {
                            $customer_name = '';

                            // Prefer already-loaded transaction contact
                            if (is_object($transaction) && ! empty($transaction->contact)) {
                                $customer_name = $transaction->contact->name ?? '';
                            }

                            // Fallback to payment_for contact id
                            if (empty($customer_name) && ! empty($payment_for)) {
                                $customer_name = $paymentForContactNames[(int) $payment_for] ?? '';
                            }

                            if (! empty($customer_name)) {
                                $details .= '<b>Customer:</b> ' . e($customer_name) . '<br>';
                            }

                            if (! empty($payment_ref_no)) {
                                $details .= '<b>Payment Ref No:</b> ' . e($payment_ref_no) . '<br>';
                            }
                        }

                        static $cashAccountId = null;
                        if (is_null($cashAccountId)) {
                            $cashAccountId = $this->transactionUtil->account_exist_return_id('Cash');
                        }

                        if (
                            $transaction &&
                            isset($transaction->sub_type) &&
                            $transaction->sub_type === 'settlement_pd' &&
                            ! empty($cashAccountId) &&
                            intval($id) === intval($cashAccountId)
                        ) {
                            $settlementNumber = $transaction->ref_no ?? $transaction->invoice_no ?? null;

                            if (! empty($settlementNumber) && strpos($settlementNumber, '#') !== false) {
                                $settlementNumber = trim(substr($settlementNumber, strpos($settlementNumber, '#') + 1));
                            }

                            if (empty($settlementNumber) && isset($transaction->id)) {
                                $settlementRecord = Settlement::where('id', $transaction->id)->select('settlement_no')->first();
                                if (! empty($settlementRecord)) {
                                    $settlementNumber = $settlementRecord->settlement_no;
                                }
                            }

                            $settlementNumber = $settlementNumber ?? 'N/A';

                            return 'Settlement No: ' . $settlementNumber;
                        }

                        if (! empty($fixed_asset_id)) {
                            $fixed_asset = self::ma002Find(\App\FixedAsset::class, $fixed_asset_id);
                            $details .= ! empty($fixed_asset) ? '<b>Asset: </b>' . $fixed_asset->asset_name . '<br><b>Location: </b>' . $fixed_asset->asset_location : '';
                        }

                        if (is_object($transaction)) {
                            $transaction_sell_line = TransactionSellLine::leftJoin('products', 'transaction_sell_lines.product_id', 'products.id')
                                ->where('transaction_id', $transaction->id)
                                ->first();
                        }

                        $at_sub_type_check = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                        if ($at_sub_type_check === 'opening_balance') {
                            $details = '<b>Opening Balance</b><br>'
                                . __('account.account') . ': ' . ($row->account_name ?? 'N/A');
                        }

                        // return $row->employee_advance_id;
                        if ($txnType == 'advance' && is_null($transaction_id) && ! is_null($employee_advance_id)) {

                            $chkAdvance = AccountTransaction::join('essentials_employee_advances', 'account_transactions.employee_advance_id', 'essentials_employee_advances.id')
                                ->join('essentials_employee_payment_settings', 'essentials_employee_advances.payment_type_id', 'essentials_employee_payment_settings.id')
                                ->where('account_transactions.id', $row->id)
                                ->select([
                                    'essentials_employee_advances.datetime_entered',
                                    'essentials_employee_advances.payment_type_id',
                                    'essentials_employee_advances.salary_period_start',
                                    'essentials_employee_advances.salary_period_end',
                                    'essentials_employee_advances.amount_paid',
                                    'essentials_employee_advances.payment_status',
                                    'essentials_employee_advances.account_id',
                                    'essentials_employee_advances.check_no',
                                    'essentials_employee_payment_settings.name as EPS_name',
                                    'essentials_employee_payment_settings.liability_account_id as EPS_LA_id',
                                    'essentials_employee_payment_settings.id as EPS_id',
                                ])->first();
                            if ($chkAdvance) {
                                // dd('dddd');
                                $details .= '<span style="color: #ff0000;">Payment Type:</span>' . $chkAdvance->EPS_name . '<br>';
                                $salary_start = $chkAdvance->salary_period_start ?? '';
                                $salary_end   = $chkAdvance->salary_period_end ?? '';
                                if (! empty($salary_start) || ! empty($salary_end)) {
                                    $details .= '<span style="color: #ff0000;">Salary Period:</span>' . $salary_start . ' - ' . $salary_end . '<br>';
                                }

                                if ($row->type == 'debit') {
                                    $liableAccount = self::ma002Find(Account::class, $chkAdvance->account_id);
                                    if ($liableAccount) {
                                        $details .= '<span style="color: #ff0000;">Payment Methods: </span>' . $liableAccount->name . '<br>';
                                    } else {
                                        $details .= '<span style="color: #ff0000;">Payment Method: </span>' . $row->account_name . '<br>';
                                    }
                                } else {
                                    $details .= '<span style="color: #ff0000;">Expense Account: </span>' . $row->account_name . '<br>';
                                }
                            }
                        } else {

                            // Log::info('row transaction type: '.$row->transaction->type ?? 'null');
                            // Log::info('row transaction sub type: '.$row->transaction->sub_type ?? 'null');
                            // Log::info('slip_noo: '.($row->slip_no ?? 'null'));
                            // Log::info('note: '.($row->note ?? 'null'));

                            $transaction_type = is_object($transaction) ? $transaction->type : ($transaction['type'] ?? null);
                            if (! empty($transaction) && $transaction_type == 'settlement_card_payment') {

                                // Extract settlement number from note for settlement_card_payment
                                $settlement_no = $this->extractSettlementNoFromNote($row->note ?? '') ?? 'N/A';

                                $details .= '</br>Card Sale';
                                $details .= '<br><b>Settlement No:</b> ' . $settlement_no;

                            } elseif (! empty($transaction) && $transaction_type == 'daily_card_payment') {

                                // Extract slip number from note
                                $slip_no = null;
                                $note = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');
                                if (! empty($note) && preg_match('/Slip:\s*([0-9]+)/', $note, $matches)) {
                                    $slip_no = $matches[1];
                                }

                                // Get settlement number from daily_cards using extracted slip_no
                                $settlement_no = $this->extractSettlementNoFromNote($note) ?? 'N/A';
                                if (! empty($slip_no)) {
                                    $daily_card = DB::table('daily_cards')
                                        ->where('slip_no', $slip_no)
                                        ->select('settlement_no')
                                        ->first();

                                    if (! empty($daily_card)) {
                                        $settlement_no = $daily_card->settlement_no;
                                    }
                                }

                                $details .= '</br>Card Payment';
                                $details .= '<br><b>Settlement No:</b> ' . $settlement_no;

                            } elseif (! empty($transaction) && $transaction_type == 'daily_credit_payment') {

                                // Multiple fallbacks for credit payments
                                $note = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');
                                $settlement_no = $this->extractSettlementNoFromNote($note) ?? 'N/A';

                                // Second try: extract slip_no and get from daily_cards
                                if ($settlement_no == 'N/A' && ! empty($note)) {
                                    if (preg_match('/Slip:\s*([0-9]+)/', $note, $matches)) {
                                        $slip_no    = $matches[1];
                                        $daily_card = DB::table('daily_cards')
                                            ->where('slip_no', $slip_no)
                                            ->select('settlement_no')
                                            ->first();

                                        if (! empty($daily_card)) {
                                            $settlement_no = $daily_card->settlement_no;
                                        }
                                    }
                                }

                                $details .= '</br>Cheque Payment';
                                $details .= '<br><b>Settlement No:</b> ' . $settlement_no;

                                if (! empty($transaction_payment)) {
                                    $details .= '<br><b>Bank:</b> ' . ($transaction_payment->bank_name ?? 'N/A');
                                    $details .= '<br><b>Cheque No:</b> ' . ($transaction_payment->cheque_number ?? 'N/A');
                                    $details .= '<br><b>Cheque Date:</b> ' . ($transaction_payment->cheque_date ?? 'N/A');
                                }
                            } elseif (! empty($transaction) &&
                                ($transaction_type == 'settlement_cash_payment' || $transaction_type == 'daily_collection')) {

                                $settlement_no = 'N/A';

                                $ref_no = is_array($row) ? ($row['ref_no'] ?? null) : ($row->ref_no ?? null);
                                $note = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');
                                if ($transaction_type == 'settlement_cash_payment' && ! empty($ref_no)) {
                                    $from_note = $this->extractSettlementNoFromNote($note);
                                    if (! empty($from_note)) {
                                        $settlement_no = $from_note;
                                    }
                                } elseif ($transaction_type == 'daily_collection') {
                                    // Use the joined settlement_no directly for daily_collection
                                    $settlement_no = is_array($row) ? ($row['settlement_no'] ?? 'N/A') : ($row->settlement_no ?? 'N/A');

                                    // Get collection form number from joined data or extract from ref_no/note
                                    $collection_form_no = is_array($row) ? ($row['collection_form_no'] ?? 'N/A') : ($row->collection_form_no ?? 'N/A');
                                    $transaction_ref_no = is_object($transaction) ? ($transaction->ref_no ?? null) : ($transaction['ref_no'] ?? null);
                                    if (($collection_form_no == 'N/A' || empty($collection_form_no)) && ! empty($transaction_ref_no)) {
                                        // Extract from "Daily Collection #123"
                                        if (preg_match('/Daily Collection #(\d+)/', $transaction_ref_no, $matches)) {
                                            $collection_form_no = $matches[1];
                                        }
                                    }
                                    if (($collection_form_no == 'N/A' || empty($collection_form_no)) && ! empty($note) && preg_match('/Daily Collection #(\d+)/', $note, $matches)) {
                                        $collection_form_no = $matches[1];
                                    }

                                    $details .= '</br>Daily Collection Cash';
                                    $details .= '<br><b>Collection Form No:</b> ' . $collection_form_no;
                                    if ($settlement_no != 'N/A' && ! empty($settlement_no)) {
                                        $details .= '<br><b>Settlement No:</b> ' . $settlement_no;
                                    }
                                } else {
                                    $details .= '</br>Cash Payment';
                                    $details .= '<br><b>Settlement No:</b> ' . $settlement_no;
                                }
                            } elseif ((is_array($row) ? ($row['subtype'] ?? ($row['at_sub_type'] ?? '')) : ($row->subtype ?? ($row->at_sub_type ?? ''))) === 'deposit') {

                                // Determine deposit type based on source account
                                $deposit_type = 'Cash Deposit'; // Default

                                // Check if source account is a card account
                                $source_account = null;
                                $source_cheque_number = null;

                                // Try to get source account from transfer_transaction relationship
                                if (! empty($row->transfer_transaction) && ! empty($row->transfer_transaction->account)) {
                                    $source_account = $row->transfer_transaction->account;
                                    // Get cheque_number from the transfer transaction (used as card number for card deposits)
                                    $source_cheque_number = $row->transfer_transaction->cheque_number ?? null;
                                }
                                // Fallback: Query directly if relationship not loaded
                                elseif (! empty($row->transfer_transaction_id)) {
                                    $transfer_trans = self::ma002Find(AccountTransaction::class, $row->transfer_transaction_id);
                                    if (! empty($transfer_trans) && ! empty($transfer_trans->account_id)) {
                                        $source_account = self::ma002Find(Account::class, $transfer_trans->account_id);
                                        $source_cheque_number = $transfer_trans->cheque_number ?? null;
                                    }
                                }

                                if (! empty($source_account)) {
                                    // Check if source account is a card account by checking account group
                                    $is_card_account = false;

                                    if (! empty($source_account->asset_type)) {
                                        // MA-002 PERF: cached, see ma002AccountGroupByName().
                                        $card_group = self::ma002AccountGroupByName($business_id, 'Card');
                                        if (! empty($card_group) && $source_account->asset_type == $card_group->id) {
                                            $is_card_account = true;
                                        }
                                    }

                                    if ($is_card_account) {
                                        // Show card number if available, otherwise show card account name
                                        if (! empty($source_cheque_number)) {
                                            $deposit_type = 'Deposit from Card ' . $source_cheque_number;
                                        } else {
                                            $deposit_type = 'Deposit from Card ' . $source_account->name;
                                        }
                                    } elseif (! empty($row->cheque_number) || ! empty($source_cheque_number)) {
                                        // Regular cheque deposit
                                        $deposit_type = 'Cheque Deposit';
                                    }
                                } elseif (! empty($row->cheque_number)) {
                                    $deposit_type = 'Cheque Deposit';
                                }

                                $details .= '<br>' . $deposit_type;

                                // -----------------------------
                                // Extract Settlement No from note
                                // Check both direct note and transfer_transaction note
                                // -----------------------------
                                $settlement_no = 'N/A';

                                $note_to_check = $row->note ?? ($row->transfer_transaction->note ?? '');


                                if (! empty($note_to_check)) {
                                    // Pattern 1: "Settlement No: SET-SW1" or "settlement #SET-SW1"
                                    if (preg_match('/settlement\s*(?:no|#|number)?[\s:]*([A-Za-z0-9\-]+)/i', $note_to_check, $matches)) {
                                        $settlement_no = $matches[1];
                                    }
                                    // Pattern 2: Direct match "SET-SW1" or "ST1"
                                    elseif (preg_match('/(SET-SW[A-Za-z0-9\-]+|ST[A-Za-z0-9\-]+)/i', $note_to_check, $matches)) {
                                        $settlement_no = $matches[0];
                                    }
                                }

                                $details .= '<br><b>Settlement No:</b> ' . $settlement_no;

                                // -----------------------------
                                // Cheque details (if exists)
                                // -----------------------------
                                if (! empty($transaction_payment)) {
                                    $details .= '<br><b>Bank:</b> ' . ($transaction_payment->bank_name ?? 'N/A');
                                    $details .= '<br><b>Cheque No:</b> ' . $transaction_payment->cheque_number;
                                    $details .= '<br><b>Cheque Date:</b> ' . $transaction_payment->cheque_date;
                                } elseif (! empty($row->cheque_number)) {
                                    $details .= '<br><b>Cheque No:</b> ' . $row->cheque_number;
                                }

                            } elseif (! empty($transaction) && $transaction_type == 'points_redeemed') {
                                $transaction_id_for_query = is_object($transaction) ? $transaction->id : ($transaction['id'] ?? null);
                                $transaction = Transaction::leftJoin('membership_points', 'transactions.rp_point_id', '=', 'membership_points.id')
                                    ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                                    ->where('transactions.id', $transaction_id_for_query)
                                    ->select(
                                        'membership_points.form_number',
                                        'membership_points.business_name',
                                        'membership_points.bill_number',
                                        'contacts.name as customer_name'
                                    )
                                    ->first();
                                $details = '<b>Customer Name: </b>' . $transaction->customer_name . '<br>';
                                $details .= '<b>Ref. Bill No: </b>' . $transaction->bill_number . '<br>';
                                $details .= '<b>Add Points Form No: </b>' . $transaction->form_number . '<br>';
                                $details .= '<b>Business Location: </b>' . $transaction->business_name;
                            } elseif (! empty($transaction) && $transaction_type == 'sell' && (is_object($transaction) ? $transaction->sub_type : ($transaction['sub_type'] ?? null)) == 'credit_sale' && (is_object($transaction) ? ($transaction->is_settlement ?? 0) : ($transaction['is_settlement'] ?? 0)) == 1) {
                                $transaction_invoice_no = is_object($transaction) ? $transaction->invoice_no : ($transaction['invoice_no'] ?? 'N/A');
                                $transaction_contact_name = is_object($transaction) ? (optional($transaction->contact)->name ?? 'N/A') : (($transaction['contact']['name'] ?? 'N/A') ?? 'N/A');
                                $details = '<b>Settlement No: </b>' . $transaction_invoice_no;
                                $details .= '<br><b>Credit Sale Customer: </b>' . $transaction_contact_name;
                                $transaction_interest = is_object($transaction) ? ($transaction->interest ?? 0) : ($transaction['interest'] ?? 0);
                                if ($transaction_interest > 0) {
                                    $details .= '<br><b>Interest: </b>' . number_format($transaction_interest, $currency_precision, '.', '');
                                }
                            } elseif (! empty($transaction) && $transaction_type == 'settlement' && (is_object($transaction) ? $transaction->sub_type : ($transaction['sub_type'] ?? null)) == 'card_payment') {
                                $transaction_invoice_no = is_object($transaction) ? $transaction->invoice_no : ($transaction['invoice_no'] ?? 'N/A');
                                $transaction_contact_name = is_object($transaction) ? (optional($transaction->contact)->name ?? 'N/A') : (($transaction['contact']['name'] ?? 'N/A') ?? 'N/A');
                                $details = '<b>Settlement No: </b>' . $transaction_invoice_no;
                                $details .= '<br><b>Card Sale Customer: </b>' . $transaction_contact_name;
                            }
                            if (! empty($transaction) && $transaction->type == 'property_purchase') {

                                $transaction_id_for_property = is_object($transaction) ? $transaction->id : ($transaction['id'] ?? null);
                                $property = Property::leftjoin('contacts', 'properties.supplier_id', 'contacts.id')
                                    ->where('properties.transaction_id', $transaction_id_for_property)
                                    ->select(['contacts.name as supplier_name', 'properties.name as property_name'])
                                    ->first();

                                $transaction_invoice_no = is_object($transaction) ? $transaction->invoice_no : ($transaction['invoice_no'] ?? 'N/A');
                                $details .= '<b>PO Number:</b>' . $transaction_invoice_no . '<br>';
                                if ($property) {
                                    $details .= '<b>Supplier Name:</b>' . ($property->supplier_name ?? 'N/A') . '<br>';
                                    $details .= '<b>Property Name:</b>' . ($property->property_name ?? 'N/A') . '<br>';
                                } else {
                                    $details .= '<b>Supplier Name:</b> N/A<br>';
                                    $details .= '<b>Property Name:</b> N/A<br>';
                                }

                                $accountTransection = is_object($transaction) ? ($transaction->accountTransection ?? null) : ($transaction['accountTransection'] ?? null);
                                if ($accountTransection) {
                                    $payment_method = is_object($accountTransection) ? $accountTransection->payment_method : ($accountTransection['payment_method'] ?? null);
                                    $details .= '<br>' . 'Payment Method:' . str_replace('_', ' ', $payment_method);
                                    if ($payment_method == 'cheque') {
                                        $cheque_numbers = is_object($accountTransection) ? $accountTransection->cheque_numbers : ($accountTransection['cheque_numbers'] ?? null);
                                        $details .= '<br> Cheque No:' . str_replace('_', ' ', $cheque_numbers);
                                    }
                                }
                            } elseif (! empty($transaction_type) && $transaction_type == 'hms_booking') {
                                $transaction_id_for_hms = is_object($transaction) ? $transaction->id : ($transaction['id'] ?? null);
                                $line = DB::table('hms_booking_lines')
                                    ->where('transaction_id', $transaction_id_for_hms)
                                    ->first();
                                $room = self::ma002Find(HmsRoom::class, $line->hms_room_id ?? null);
                                $transaction_ref_no = is_object($transaction) ? $transaction->ref_no : ($transaction['ref_no'] ?? 'N/A');
                                $transaction_contact_name = is_object($transaction) ? (optional($transaction->contact)->name ?? 'N/A') : (($transaction['contact']['name'] ?? 'N/A') ?? 'N/A');
                                $details .= '<b>HMS Bill No:</b>' . $transaction_ref_no . '<br>';
                                $details .= '<b>Room Nos: </b>' . ($room->room_number ?? '') . '<br>';
                                $details .= '<b>' . __('contact.customer') . ':</b> ' . $transaction_contact_name . '<br>';
                            } elseif (empty(is_object($transaction) ? $transaction->pump_operator_id : ($transaction['pump_operator_id'] ?? null)) || (is_object($transaction) ? $transaction->sub_type : ($transaction['sub_type'] ?? null)) == 'credit_sale' || (is_object($transaction) ? $transaction->sub_type : ($transaction['sub_type'] ?? null)) == 'expense' || (is_object($transaction) ? $transaction->sub_type : ($transaction['sub_type'] ?? null)) == 'settlement') {

                                if ($journal_deleted == 0 &&
                                    (empty($transaction->pump_operator_id) ||
                                        ($transaction->sub_type == 'credit_sale') ||
                                        $transaction->sub_type == 'expense' ||
                                        $transaction->sub_type == 'settlement')) {
                                    $transaction_is_settlement = is_object($transaction) ? ($transaction->is_settlement ?? 0) : ($transaction['is_settlement'] ?? 0);
                                    if (in_array($sub_type, ['fund_transfer', 'cheque_realize', 'deposit', 'payable', 'stock', 'opening_balance']) && ((! empty($transaction) && $transaction_is_settlement != 1) || empty($transaction))) {
                                        if (in_array($sub_type, ['cheque_realize']) && !empty($row->note)) {
                                            $details = nl2br(e($row->note));
                                            $rendered_cheque_number = !empty($dep_trans_cheque_number) ? $dep_trans_cheque_number : $cheque_number;
                                            if (!empty($rendered_cheque_number)) {
                                                $details .= '<br><b>' . __('cheque.cheque_number') . ':</b> ' . e($rendered_cheque_number);
                                            }
                                        }
                                        if (in_array($sub_type, ['cheque_realize']) && ! empty($transfer_transaction)) {

                                            // update font color by virtual it professional referance docs number 7338
                                            $sourceAccountName = 'Post Dated Cheques Account';
                                            if (
                                                $account_id == $this->moduleUtil->account_exist_return_id('Issued Post Dated Cheques') ||
                                                (!empty($transfer_transaction->account_id) && $transfer_transaction->account_id == $this->moduleUtil->account_exist_return_id('Issued Post Dated Cheques'))
                                            ) {
                                                $sourceAccountName = 'Issued Post Dated Cheques Account';
                                            }

                                            if ($type == 'credit') {
                                                $details = empty($details) ? "Cheque to Realize from <span style='color: red; font-weight:bold'>{$sourceAccountName}</span>" : $details;
                                            } else {
                                                $details = empty($details) ? "Cheque to Realize from <span style='color: red;font-weight:bold'>{$sourceAccountName}</span>" : $details;
                                            }
                                        }
                                        if (in_array($sub_type, ['deposit']) && ! empty($transfer_transaction)) {

                                            $cash_account_id = Account::getAccountByAccountName('Cash')->id;

                                            if ($account_id == $cash_account_id && ! empty($cheque_number)) {

                                                $details .= __('cheque.cheque_number') . ': ' . $cheque_number . '<br><b>' . __('account.encashed') . '</b>';
                                            } else {
                                                $details = __('account.' . $sub_type);
                                                if ($type == 'credit') {

                                                    $details .= ' ( ' . __('account.to') . ': ' . ($transfer_transaction->account->name ?? 'N/A') . ')';
                                                } else {
                                                    $details .= ' ( ' . __('account.from') . ': ' . ($transfer_transaction->account->name ?? 'N/A') . ')';
                                                }
                                                if (! empty($payment_for)) {
                                                    $contact = self::ma002Find(Contact::class, $payment_for);
                                                    if (! empty($contact)) {
                                                        $details .= '<br><b>' . __('fleet::lang.customer') . ':</b>' . ($contact->name ?? 'N/A');
                                                    }
                                                }
                                                if ($post_dated_cheque == 1) {
                                                    $details .= '<br><b>PD Cheque Dates </b>' . $cheque_date;
                                                }
                                                $cheque_number = ! empty($dep_trans_cheque_number) ? $dep_trans_cheque_number : $cheque_number;
                                                $details .= '<br>' . __('cheque.cheque_number') . ': ' . $cheque_number;
                                            }
                                        }
                                        if (in_array($sub_type, ['fund_transfer']) && ! empty($transfer_transaction)) {
                                            if (! empty($auto_transfer)) {
                                                if (! empty($payment_for)) {
                                                    $contact = self::ma002Find(Contact::class, $payment_for);
                                                    if (! empty($contact)) {
                                                        $details .= '<b>' . __('fleet::lang.customer') . ':</b>' . ($contact->name ?? 'N/A');
                                                    }
                                                }
                                                if ($type == 'credit') {
                                                    $details .= '<br><span style="color: red;">Auto Transferred to Account ' . ($transfer_transaction->account->name ?? 'N/A') . '</span>';
                                                } else {
                                                    $details .= '<br><span style="color: red;"><b>Post Dated Cheque of</b> ' . $this->commonUtil->format_date($row->operation_date) . '</span>';
                                                }
                                            } else {
                                                $details = __('account.' . $row->sub_type);
                                                if ($row->type == 'credit') {

                                                    $details .= ' ( ' . __('account.to') . ': ' . ($row->transfer_transaction->account->name ?? 'N/A') . ')';
                                                } else {
                                                    $details .= ' ( ' . __('account.from') . ': ' . ($row->transfer_transaction->account->name ?? 'N/A') . ')';
                                                }
                                                if (! empty($row->payment_for)) {
                                                    $contact = self::ma002Find(Contact::class, $row->payment_for);
                                                    if (! empty($contact)) {
                                                        $details .= '<br><b>' . __('fleet::lang.customer') . ':</b>' . ($contact->name ?? 'N/A');
                                                    }
                                                }
                                                $details .= '<br>' . __('cheque.cheque_number') . ': ' . $row->dep_trans_cheque_number;
                                            }
                                        }
                                        if (in_array($sub_type, ['payable', 'stock'], true) || (! empty($transaction) && ($transaction->type ?? null) == 'purchase_return')) {
                                            // Old account rows can remain after their parent purchase transaction
                                            // has been deleted. Never dereference a missing transaction, because one
                                            // orphaned Accounts Payable row would otherwise fail the whole DataTable.
                                            if (empty($transaction)) {
                                                $fallback_note = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');
                                                $details .= ! empty($fallback_note)
                                                    ? '<b>Note:</b> ' . e($fallback_note) . '<br>'
                                                    : '<b>' . __('lang_v1.description') . ':</b> ' . e(ucwords(str_replace('_', ' ', (string) $sub_type)));
                                            } else {
                                                $supplier_name = optional($transaction->contact)->name ?? 'N/A';
                                                $purchase_order_no = $transaction->invoice_no ?? '';
                                                $purchase_ref_no = $transaction->ref_no ?? '';
                                                $purchase_transaction_id = $transaction->id ?? null;

                                                // Check if this is Accounts Payable or Finished Goods Account book
                                                if ($account_name == 'Accounts Payable') {
                                                    $details .= '<b>Supplier Name:</b> ' . e($supplier_name) . '<br>' .
                                                        '<b>Purchase Order No:</b> ' . e($purchase_order_no) . '<br>' .
                                                        '<b>Purchase Ref No:</b> ' . e($purchase_ref_no) . '<br>';

                                                    if (! empty($row->note)) {
                                                        $details .= '<b>Note:</b> ' . e($row->note) . '<br>';
                                                    }
                                                } elseif ($account_name == 'Finished Goods Account') {
                                                    $details .= '<b>Supplier Name:</b> ' . e($supplier_name) . '<br>' .
                                                        '<b>Purchase Order No:</b> ' . e($purchase_order_no) . '<br>' .
                                                        '<b>Purchase Ref No:</b> ' . e($purchase_ref_no) . '<br>';

                                                    $purchase_line = null;
                                                    if (! empty($purchase_transaction_id)) {
                                                        $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')
                                                            ->where('purchase_lines.transaction_id', $purchase_transaction_id)
                                                            ->select('products.name', 'purchase_lines.*')
                                                            ->first();
                                                    }
                                                    if ($purchase_line) {
                                                        $details .= '<b>Product:</b> ' . e($purchase_line->name ?? 'N/A') . '<br>';
                                                    }

                                                    if (! empty($row->note)) {
                                                        $details .= '<b>Note:</b> ' . e($row->note) . '<br>';
                                                    }
                                                } else {
                                                    $details .= '<b>' . __('purchase.supplier') . ':</b> ' . e($supplier_name) . '<br><b>' .
                                                        __('purchase.ref_no') . ':</b> ' . e($purchase_ref_no);
                                                }
                                            }
                                        }
                                        if (in_array($row->type, ['opening_balance'])) {
                                            $details .= '<b class="text-danger">' . __('account.opening_balance') . '</b><br>';
                                            if ($row->account_name == 'Opening Balance Equity Account' && ! empty($row->pair_at_id)) {
                                                $pair_at = self::ma002Find(AccountTransaction::class, $row->pair_at_id);
                                                if (! empty($pair_at->account_id)) {
                                                    $account = self::ma002Find(Account::class, $pair_at->account_id);
                                                    $details .= '<b>' . __('account.loan_account') . '</b>:' . ($account->name ?? 'N/A');
                                                }
                                            }

                                            if (! empty($row->payment_for)) {
                                                $contact = self::ma002Find(Contact::class, $row->payment_for);
                                                if (! empty($contact)) {
                                                    $details .= '<br><b>' . __('fleet::lang.customer') . ':</b>' . ($contact->name ?? 'N/A');
                                                }
                                            }
                                        }
                                    } else {
                                        if (! empty($row->transaction->type)) {
                                            $transaction_payment = $row->transaction
                                                ? TransactionPayment::where('transaction_id', $row->transaction->id)->first()
                                                : null;
                                            if ($row->transaction->type == 'purchase') {
                                                $at_sub_type_val = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                                                if ($at_sub_type_val == 'purchase_edit') {
                                                    $details = '<span style="color: red;">Payee ' . (($row->transaction->contact->name ?? 'N/A') ?? 'N/A') . '</span>';
                                                } else {
                                                    $effective_cheque_number = '';
                                                    if (! empty($transaction_payment->cheque_number)) {
                                                        $effective_cheque_number = trim($transaction_payment->cheque_number, ',');
                                                    } elseif (! empty($row->dep_trans_cheque_number)) {
                                                        $effective_cheque_number = trim($row->dep_trans_cheque_number, ',');
                                                    }

                                                    $is_prepayment_purchase = (! empty($transaction_payment) && $transaction_payment->method == 'pre_payments')
                                                        || (! empty($effective_cheque_number) && $row->note == 'Prepayment');

                                                    if ($row->account_name == 'Taxes Receivable') {
                                                        $label = 'VAT';
                                                    } else {
                                                        $label = __('lang_v1.purchase');
                                                    }

                                                    // Check if this is Accounts Payable or Finished Goods Account book
                                                    if ($row->account_name == 'Accounts Payable') {
                                                        $details = '<b>' . $label . '</b><br>' .
                                                        '<b>Supplier Name:</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br>' .
                                                        '<b>Purchase Order No:</b> ' . ($row->transaction->invoice_no ?? '') . '<br>';

                                                        if (! empty($row->transaction->ref_no)) {
                                                            $details .= '<b>Bill Number:</b> ' . $row->transaction->ref_no . '<br>';
                                                        }

                                                        // Get product information
                                                        $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')
                                                            ->where('transaction_id', $row->transaction->id)
                                                            ->first();

                                                        // Get note information
                                                        if (! empty($row->note) && $row->note !== 'Prepayment') {
                                                            $details .= '<b>Note:</b> ' . $row->note . '<br>';
                                                        }

                                                        // Add payment method information
                                                        if (! empty($effective_cheque_number)) {
                                                            $details .= '<b>' . __('cheque.cheque_number') . ':</b> ' . $effective_cheque_number . '<br>';
                                                        } elseif ($row->method == 'bank_transfer') {
                                                            $details .= __('lang_v1.bank_transfer') . '<br>' . __('cheque.cheque_number') . ': ' . $row->cheque_number . '<br>' .
                                                            __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                        } else {
                                                            $details .= ucfirst($row->method);
                                                        }

                                                        if ($is_prepayment_purchase) {
                                                            $details .= '<b><span style="color: red;">' . __('Prepayment') . '</span></b><br>';
                                                        }
                                                    } elseif ($row->account_name == 'Finished Goods Account') {
                                                        $details = '<b>' . $label . '</b><br>' .
                                                        '<b>Supplier Name:</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br>' .
                                                        '<b>Purchase Order No:</b> ' . ($row->transaction->invoice_no ?? '') . '<br>';

                                                        if (! empty($row->transaction->ref_no)) {
                                                            $details .= '<b>Bill Number:</b> ' . $row->transaction->ref_no . '<br>';
                                                        }

                                                        // Get product information
                                                        $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')
                                                            ->where('purchase_lines.transaction_id', $row->transaction->id)
                                                            ->select('products.name', 'purchase_lines.*')
                                                            ->first();
                                                        if ($purchase_line) {
                                                            $details .= '<b>Product:</b> ' . ($purchase_line->name ?? 'N/A') . '<br>';
                                                        }

                                                        // Get note information
                                                        if (! empty($row->note) && $row->note !== 'Prepayment') {
                                                            $details .= '<b>Note:</b> ' . $row->note . '<br>';
                                                        }

                                                        // Add payment method information
                                                        if (! empty($effective_cheque_number)) {
                                                            $details .= '<b>' . __('cheque.cheque_number') . ':</b> ' . $effective_cheque_number . '<br>';
                                                        } elseif ($row->method == 'bank_transfer') {
                                                            $details .= __('lang_v1.bank_transfer') . '<br>' . __('cheque.cheque_number') . ': ' . $row->cheque_number . '<br>' .
                                                            __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                        } else {
                                                            $details .= ucfirst($row->method);
                                                        }

                                                        if ($is_prepayment_purchase) {
                                                            $details .= '<b><span style="color: red;">' . __('Prepayment') . '</span></b><br>';
                                                        }
                                                    } else {
                                                        // Original logic for other accounts
                                                        $details = '<b>' . $label . '</b><br> ' . '<b>' .
                                                        __('purchase.supplier') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>' .
                                                        __('purchase.purchase_order') . ':</b> ' . ($row->transaction->invoice_no ?? '') . '<br>';

                                                        if (! empty($row->transaction->ref_no)) {
                                                            $details .= '<b>Bill Number:</b> ' . $row->transaction->ref_no . '<br>';
                                                        }

                                                        if (! empty($effective_cheque_number)) {
                                                            $details .= '<b>' . __('cheque.cheque_number') . ':</b> ' . $effective_cheque_number . '<br>';
                                                        } elseif ($row->method == 'bank_transfer') {
                                                            // $details .=    __('lang_v1.bank_transfer') . '</b> <br>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                            __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                        } elseif ($row->method == 'bank_transfer') {
                                                            $details .= __('lang_v1.bank_transfer') . '</b> <br>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                            __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                        } else {

                                                            $details .= ucfirst($row->method);
                                                        }
                                                        if ($is_prepayment_purchase) {
                                                            $details .= '<b><span style="color: red;">' . __('Prepayment') . '</span></b> <br>';
                                                        } else {
                                                            $details .= ucfirst($row->method);
                                                        }
                                                        $account_group = self::ma002AccountGroupById($business_id, $row->asset_type); // MA-002 PERF: cached
                                                        if (! empty($account_group) && $account_group->name == 'Inventory' && $row->account_name != 'Accounts Payable' && $row->account_name != 'Finished Goods Account') {
                                                            $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')->where('transaction_id', $row->transaction->id)->first();
                                                            $details .= 'Product: ' . ($purchase_line->name ?? 'N/A');
                                                        }
                                                    }
                                                }
                                            } elseif ($row->transaction->type == 'cheque') {
                                                $at_sub_type_val = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                                                if ($at_sub_type_val == 'purchase_edit') {
                                                    // $details = '<span style="color: red;">Payee ' . ($row->transaction->contact->name ?? 'N/A') . '</span>';
                                                } else {
                                                    if ($row->account_name == 'Taxes Receivable') {
                                                        $label = 'VAT';
                                                    } else {
                                                        $label = __('lang_v1.purchase');
                                                    }
                                                    $details = '<b>' . '' . '</b> ' . '<b>' .
                                                    __('Payee Name') . ':</b> ' . (($row->transaction->contact->name ?? 'N/A') ?? 'N/A') . '<br><b>' .
                                                    __('Bank Name') . ':</b> ' . $transaction_payment->bank_name . '<br><b>';
                                                    if ($transaction_payment && in_array($transaction_payment->method, ['cheque', 'pre_payments'])) {
                                                        $details .= '<b>' . __('cheque.cheque_number') . ':</b> ' . $transaction_payment->cheque_number . '<br>';
                                                    } elseif ($row->method == 'bank_transfer') {
                                                        __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                    } elseif ($row->method == 'bank_transfer') {
                                                        $details .= __('lang_v1.bank_transfer') . '</b> <br>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                        __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                    }

                                                    if ($transaction_payment && $transaction_payment->method == 'pre_payments') {
                                                        $details .= '<b><span style="color: red;">' . __('Prepayment') . '</span></b> <br>';
                                                    } else {
                                                        $details .= ucfirst($row->method);
                                                    }
                                                }
                                            } elseif ($row->transaction->type == 'purchase_return') {
                                                // Check if this is Accounts Payable or Finished Goods Account book
                                                if ($row->account_name == 'Accounts Payable') {
                                                    $details = '<b>' . __('lang_v1.purchase_return') . '</b><br>' .
                                                    '<b>Supplier Name:</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br>' .
                                                    '<b>Purchase Order No:</b> ' . $row->transaction->invoice_no . '<br>' .
                                                    '<b>Purchase Ref No:</b> ' . $row->transaction->ref_no . '<br>';

                                                    // Resolve selected Purchase Return Account consistently.
                                                    // For debit row, it should show current account. For credit row
                                                    // (Finished Goods), show the matching debit account.
                                                    $debit_entry = \Modules\Finance\Entities\AccountTransaction::where('transaction_id', $row->transaction->id)
                                                        ->where('type', 'debit')
                                                        ->with('account')
                                                        ->first();
                                                    $purchase_return_account_name = $debit_entry->account->name ?? '';
                                                    if (empty($purchase_return_account_name) && $row->type == 'debit') {
                                                        $purchase_return_account_name = $row->account_name;
                                                    }
                                                    if (! empty($purchase_return_account_name)) {
                                                        $details .= '<b>Purchase Return Account:</b> ' . $purchase_return_account_name . '<br>';
                                                    }

                                                    // Get product information
                                                    $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')
                                                        ->where('transaction_id', $row->transaction->id)
                                                        ->first();
                                                    if ($purchase_line) {
                                                        $details .= '<b>Product:</b> ' . ($purchase_line->name ?? 'N/A') . '<br>';
                                                    }

                                                    // Get note information
                                                    if (! empty($row->note)) {
                                                        $details .= '<b>Note:</b> ' . $row->note . '<br>';
                                                    }

                                                    // Add payment method information
                                                    if ($row->method == 'cheque') {
                                                        $details .= '<b>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                        '<b>' . __('cheque.cheque_date') . ':</b> ' . $this->transactionUtil->format_date($row->cheque_date);
                                                    } elseif (! empty($row->method)) {
                                                        $details .= ucfirst($row->method);
                                                    }
                                                } elseif ($row->account_name == 'Finished Goods Account') {
                                                    $details = '<b>' . __('lang_v1.purchase_return') . '</b><br>' .
                                                    '<b>Supplier Name:</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br>' .
                                                    '<b>Purchase Order No:</b> ' . $row->transaction->invoice_no . '<br>' .
                                                    '<b>Purchase Ref No:</b> ' . $row->transaction->ref_no . '<br>';

                                                    // Show the selected Purchase Return Account on the
                                                    // Finished Goods credit side as well.
                                                    $debit_entry = \Modules\Finance\Entities\AccountTransaction::where('transaction_id', $row->transaction->id)
                                                        ->where('type', 'debit')
                                                        ->with('account')
                                                        ->first();
                                                    $purchase_return_account_name = $debit_entry->account->name ?? '';
                                                    if (empty($purchase_return_account_name) && $row->type == 'debit') {
                                                        $purchase_return_account_name = $row->account_name;
                                                    }
                                                    if (! empty($purchase_return_account_name)) {
                                                        $details .= '<b>Purchase Return Account:</b> ' . $purchase_return_account_name . '<br>';
                                                    }

                                                    // Get product information
                                                    $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')
                                                        ->where('purchase_lines.transaction_id', $row->transaction->id)
                                                        ->select('products.name', 'purchase_lines.*')
                                                        ->first();
                                                    if ($purchase_line) {
                                                        $details .= '<b>Product:</b> ' . ($purchase_line->name ?? 'N/A') . '<br>';
                                                    }

                                                    // Get note information
                                                    if (! empty($row->note)) {
                                                        $details .= '<b>Note:</b> ' . $row->note . '<br>';
                                                    }

                                                    // Add payment method information
                                                    if ($row->method == 'cheque') {
                                                        $details .= '<b>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                        '<b>' . __('cheque.cheque_date') . ':</b> ' . $this->transactionUtil->format_date($row->cheque_date);
                                                    } elseif (! empty($row->method)) {
                                                        $details .= ucfirst($row->method);
                                                    }
                                                } else {
                                                    // Original logic for other accounts
                                                    $details = '<b>' . __('lang_v1.purchase_return') . '</b><br> ' . '<b>' .
                                                    __('purchase.supplier') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>' .
                                                    __('purchase.ref_no') . ':</b> ' . $row->transaction->ref_no . '<br><b>';
                                                    if ($row->method == 'cheque') {
                                                        //  $details .=    __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br><b>' .
                                                        __('cheque.cheque_date') . ':</b> ' . $this->transactionUtil->format_date($row->cheque_date);
                                                    } elseif (! empty($row->method)) {
                                                        $details .= ucfirst($row->method);
                                                    }
                                                }
                                            } elseif ($row->transaction->type == 'sell' && $row->transaction->is_settlement != 1) {
                                                if ($row->transaction->is_direct_sale) {
                                                    if ($row->account_name == 'Taxes Payable') {
                                                        $details = '<b>Tax</b><br> ';
                                                    } else {
                                                        $details = '<b>' . __('lang_v1.invoice_sale') . '</b><br> ';
                                                    }

                                                    $details .= '<b>' . __('contact.customer') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>';
                                                } else {
                                                    // if ($row->account_name == 'Taxes Payable') {
                                                    //     $details = '<b>Tax</b><br> ';
                                                    // }
                                                    // else {
                                                    //     // POS sale header
                                                    //     $details = '<b>'.__('lang_v1.pos_sale').'</b><br> ';
                                                    // }
                                                    // Customer label
                                                    $details .= '<b>' . __('contact.customer') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>';
                                                }
                                                // POS Invoice Number label
                                                if ($row->transaction->is_settlement != 1) {
                                                    $details .= __('sale.invoice_no') . ':</b> ' . $row->transaction->invoice_no;
                                                }
                                                $account_group = self::ma002AccountGroupById($business_id, $row->asset_type); // MA-002 PERF: cached
                                                if (! empty($account_group) && $account_group->name == 'Inventory') {
                                                    $transaction_sell_line = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')->where('transaction_id', $row->transaction->id)->first();
                                                    $details .= '<br>Product: ' . ($transaction_sell_line->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'opening_stock' && $row->imported == 1) {
                                                $details       = 'Opening Stock <br> <b>Date:</b> ' . $this->commonUtil->format_date($row->transaction->transaction_date);
                                                $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')->where('transaction_id', $row->transaction->id)->first();
                                                if (! empty($purchase_line)) {
                                                    $details .= '<br>Product: ' . ($purchase_line->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'expense') {
                                                if ($row->account_name == 'Taxes Receivable') {
                                                    $expense_cat = self::ma002Find(\App\ExpenseCategory::class, $row->transaction->expense_category_id);
                                                    $details .= '<b>VAT Tax <br></b> ' . (! empty($expense_cat) ? $expense_cat->name : '') . '<br>';
                                                } else {
                                                    $details .= 'Expense <br> <b>Ref:</b> ' . $row->transaction->ref_no . '<br>';
                                                }

                                                if ($row->method == 'cheque') {
                                                    // $details .=    __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br><b>' .
                                                    __('cheque.cheque_date') . ':</b> ' . $this->transactionUtil->format_date($row->cheque_date);
                                                } elseif ($row->method == 'bank_transfer') {
                                                    $details .= __('lang_v1.bank_transfer') . '</b> <br>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                    __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                } else {
                                                    $details .= ucfirst($row->method);
                                                }
                                            } elseif ($row->transaction->type == 'opening_balance') {
                                                $contact = Contact::where('id', $row->transaction->contact_id)->first();
                                                if (! is_null($contact) && $contact->type == 'supplier') {
                                                    $details = '<b>' . __('purchase.supplier') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>' . 'Supplier Opening Balance <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->ref_no . '<br>';
                                                }
                                                if (! is_null($contact) && $contact->type == 'customer') {
                                                    $details = '<b>' . __('contact.customer') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>' . 'Customer Opening Balance <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->ref_no . '<br>';
                                                }
                                                if ($row->method == 'cheque') {
                                                    __('cheque.cheque_date') . ':</b> ' . $this->transactionUtil->format_date($row->cheque_date);
                                                } elseif ($row->method == 'bank_transfer') {
                                                    $bank_account = null;
                                                    if (! empty($row->bank_account_id)) {
                                                        $bank_account = Account::where('id', $row->bank_account_id)->first();
                                                    }
                                                    $details .= __('lang_v1.bank_transfer') . ':</b> <br>' . __('cheque.cheque_number') . ':</b> ' . $row->cheque_number . '<br>' .
                                                    __('cheque.cheque_date') . ': ' . $this->transactionUtil->format_date($row->cheque_date);
                                                    if (empty($bank_account)) {
                                                        $details .= '<br><b>Bank:</b>' . ($bank_account->name ?? 'N/A');
                                                    }
                                                } else {
                                                    $details .= ucfirst($row->method);
                                                }
                                            } elseif ($row->transaction->type == 'opening_stock') {
                                                $details       = 'Stock adjustment - Opening Stock <br> <b>Date:</b> ' . $this->commonUtil->format_date($row->transaction->transaction_date);
                                                $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')->where('transaction_id', $row->transaction->id)->first();
                                                if (! empty($purchase_line)) {
                                                    $details .= '<br>Product: ' . ($purchase_line->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'stock_taking') {
                                                $details = '<b>Stock Taking:</b>' . ' ' . $row->transaction->ref_no;
                                            } elseif ($row->transaction->type == 'shipping_agent_commission') {
                                                $label    = '';
                                                $label    = __('shipping::lang.shipping_agent_commission');
                                                $shipment = ShippingAgentCommission::leftjoin('shipments', 'shipping_agent_commission.shipment_id', 'shipments.id')
                                                    ->leftjoin('shipping_agents', 'shipping_agents.id', 'shipments.agent_id')
                                                    ->where('shipping_agent_commission.id', $row->transaction->ref_no)
                                                    ->select([
                                                        'shipments.tracking_no',
                                                        'shipping_agents.name as agent_name',
                                                    ])->first();
                                                if (! empty($shipment)) {
                                                    $label .= '<br><b>' . __('shipping::lang.shipping_agent') . ':</b>' . $shipment->agent_name . '<br><b>' . __('shipping::lang.tracking_no') . ':</b>' . $shipment->tracking_no;
                                                }
                                                $details = $label;
                                            } elseif ($row->transaction->type == 'agent_payment') {
                                                $details = __('shipping::lang.agent_payment') . '<br><b>' . __('purchase.ref_no') . '</b>: ' . $row->transaction->ref_no;
                                                $agent   = self::ma002Find(ShippingAgent::class, $row->transaction->parent_transaction_id);

                                                if (! empty($agent)) {
                                                    $details .= '<br><b>' . __('shipping::lang.shipping_agent') . '</b>: ' . ($agent->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'fpos_sale') {
                                                $details = __('tpos.fpos') . '<br><b>' . __('tpos.fpos_no') . '</b>: ';
                                                $agent   = self::ma002Find(\App\TposSale::class, $row->transaction->parent_transaction_id);

                                                if (! empty($agent)) {
                                                    $details .= $agent->fpos_no;
                                                }
                                            } elseif ($row->transaction->type == 'shipping_agent_ob') {
                                                $details = __('shipping::lang.opening_balance') . '<br>';
                                                $agent   = self::ma002Find(ShippingAgent::class, $row->transaction->parent_transaction_id);

                                                if (! empty($agent)) {
                                                    $details .= '<br><b>' . __('shipping::lang.shipping_agent') . '</b>: ' . ($agent->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'shipping_partner_ob') {
                                                $details = __('shipping::lang.opening_balance') . '<br>';
                                                $agent   = self::ma002Find(ShippingPartner::class, $row->transaction->parent_transaction_id);

                                                if (! empty($agent)) {
                                                    $details .= '<b>' . __('shipping::lang.shipping_partner') . '</b>: ' . ($agent->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'partner_payment') {
                                                $details = __('shipping::lang.partner_payment') . '<br><b>' . __('purchase.ref_no') . '</b>: ' . $row->transaction->ref_no;
                                                $agent   = self::ma002Find(ShippingPartner::class, $row->transaction->parent_transaction_id);

                                                if (! empty($agent)) {
                                                    $details .= '<b>' . __('shipping::lang.shipping_partner') . '</b>: ' . ($agent->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'vat_opening_balance') {
                                                $details = __('vat::lang.vat_opening_balance');
                                            } elseif ($row->transaction->type == 'essentials_employee_ob') {
                                                $details  = __('essentials::lang.ob_of_employee');
                                                $employee = EssentialsEmployee::where('transaction_id', $row->transaction->id)->first();
                                                if (! empty($employee)) {
                                                    $details .= "<b> ($employee->name ?? 'N/A')</b>";
                                                }
                                            } elseif ($row->transaction->type == 'stock_adjustment') {
                                                $label = '';

                                                if ($row->transaction->sub_type == 'dip_resetting') {
                                                    $reset_label = 'Dip Resetting';
                                                    if ($row->transaction->stock_adjustment_type == 'increase') {
                                                        $reset_label .= ' (Increase)';
                                                    } elseif ($row->transaction->stock_adjustment_type == 'decrease') {
                                                        $reset_label .= ' (Decrease)';
                                                    }

                                                    $details = $reset_label . ' <br> <b>Date:</b> ' . $this->commonUtil->format_date($row->transaction->transaction_date) . '<br><b class="text-danger">Dip Reset No: </b>' . $row->transaction->invoice_no;

                                                    $reset_lines = StockAdjustmentLine::leftJoin('products', 'stock_adjustment_lines.product_id', 'products.id')
                                                        ->leftJoin('fuel_tanks', 'stock_adjustment_lines.tank_id', 'fuel_tanks.id')
                                                        ->where('stock_adjustment_lines.transaction_id', $row->transaction->id)
                                                        ->select(
                                                            'products.name as product_name',
                                                            'fuel_tanks.fuel_tank_number',
                                                            'stock_adjustment_lines.quantity'
                                                        )
                                                        ->get();

                                                    if ($reset_lines->isNotEmpty()) {
                                                        $line_descriptions = [];
                                                        foreach ($reset_lines as $line) {
                                                            $parts = [];
                                                            if (!empty($line->fuel_tank_number)) {
                                                                $parts[] = 'Tank: ' . $line->fuel_tank_number;
                                                            }
                                                            if (!empty($line->product_name)) {
                                                                $parts[] = 'Product: ' . $line->product_name;
                                                            }
                                                            $parts[] = 'Qty: ' . number_format((float) $line->quantity, 3, '.', ',');
                                                            $line_descriptions[] = implode(', ', $parts);
                                                        }

                                                        $details .= '<br><b>Reset Details:</b> ' . implode(' | ', $line_descriptions);
                                                    }
                                                } else {
                                                    // logger(json_encode($row->transaction));
                                                    if ($row->transaction->stock_adjustment_type == 'increase') {
                                                        $label = __('stock_adjustment.stock_adjustment_no') . $row->transaction->ref_no . __('stock_adjustment.increased');
                                                    } elseif ($row->transaction->stock_adjustment_type == 'decrease') {
                                                        $label = __('stock_adjustment.stock_adjustment_no') . $row->transaction->ref_no . __('stock_adjustment.decreased');
                                                    } else {
                                                        $label = __('stock_adjustment.stock_adjustment_no') . $row->transaction->ref_no . ' (Increase & Decrease)';
                                                    }
                                                    $details = $label . '<br> <b>Date:</b> ' . $this->commonUtil->format_date($row->transaction->transaction_date) . '<br><b>Stock Adjustment No: </b>' . $row->transaction->ref_no;
                                                    
                                                    $lines = StockAdjustmentLine::leftjoin('products', 'stock_adjustment_lines.product_id', 'products.id')
                                                        ->where('transaction_id', $row->transaction->id)
                                                        ->pluck('products.name')
                                                        ->toArray();
                                                    if (!empty($lines)) {
                                                        $details .= '<br><b>Products:</b> ' . implode(', ', $lines);
                                                    }
                                                }
                                            } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'expense') {
                                                $details = 'Expense <br> <b>' . 'Settlement No: ' . '</b>' . $row->transaction->invoice_no;
                                                $ref     = '';
                                                if ($row->transaction->is_settlement) {
                                                    $settlement_expense = SettlementExpensePayment::where('transaction_id', $row->transaction->id)->first();
                                                    if (! empty($settlement_expense)) {
                                                        $ref .= '<br><b>Reference No: </b>' . $settlement_expense->reference_no . '<br><b>Reason: </b>' . $settlement_expense->reason;
                                                    }
                                                }
                                                $details .= $ref;
                                            } elseif (! empty($row->transaction) && (
                                                $row->transaction->type == 'settlement_cash_payment' ||
                                                ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'cash_payment')
                                            )) {
                                                // Handle settlement cash payment transactions
                                                // Extract settlement number from ref_no (format: "Cash payment for settlement #ST1")
                                                $settlement_no = 'N/A';
                                                if (! empty($row->transaction->ref_no) && preg_match('/settlement #(.+)/', $row->transaction->ref_no, $matches)) {
                                                    $settlement_no = $matches[1];
                                                } elseif (! empty($row->transaction->invoice_no)) {
                                                    $settlement_no = $row->transaction->invoice_no;
                                                }
                                                $details = '<b>' . 'Settlement No: ' . '</b>' . $settlement_no;
                                            } elseif ($row->transaction->is_settlement == 1) {

                                                $transaction_payment = null;
                                                $this_tp             = null;
                                                $details             = '<b>' . 'Settlement No: ' . '</b>' . $row->transaction->invoice_no;
                                                // Payment data is already joined as TP in the main Account Book query.
                                                $transaction_payment = ! empty($row->tp_id)
                                                    ? (object) [
                                                        'method' => $row->method ?? null,
                                                        'amount' => (float) ($row->amount ?? 0),
                                                        'bank_name' => $row->bank_name ?? null,
                                                        'cheque_number' => $row->cheque_number ?? null,
                                                        'cheque_date' => $row->cheque_date ?? null,
                                                        'card_type' => $row->card_type ?? null,
                                                        'card_number' => $row->card_number ?? null,
                                                        'payment_ref_no' => $row->payment_ref_no ?? null,
                                                    ]
                                                    : null;

                                                // Check for settlement cash payment by at_sub_type (transaction type already handled above)
                                                $at_sub_type = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                                                if ($at_sub_type == 'settlement_cash_payment') {
                                                    $details .= '';
                                                } elseif ($row->transaction->type == 'sell' && $row->transaction->sub_type == 'credit_sale' && $row->type == 'debit') {
                                                    $details .= '<br>Credit Sale <br><b> Customer: </b> ' . ($row->transaction->contact->name ?? 'N/A');
                                                    // $details .=  $transaction_payment->method;
                                                    if (! empty($transaction_payment) && $transaction_payment->method == 'cheque') {
                                                        $details .= '</br> <b>Bank:</b> ' . $transaction_payment->bank_name . '<b> Cheque No: </b>' . $transaction_payment->cheque_number . '  <b>Cheque Date: </b>' . $transaction_payment->cheque_date;
                                                    }

                                                    if ($row->interest > 0) {
                                                        $details .= '<br><b> Interest: </b> ' . number_format($row->interest, $currency_precision, '.', '');
                                                    }
                                                } elseif ($row->transaction->type == 'sell' && $row->transaction->sub_type == 'credit_sale' && $row->type == 'credit') {
                                                    $details .= '<br> Credit Payment <br><b> Customer: </b> ' . ($row->transaction->contact->name ?? 'N/A');
                                                    if ($row->interest > 0) {
                                                        $details .= '<br><b> Interest: </b> ' . number_format($row->interest, $currency_precision, '.', '');
                                                    }
                                                } elseif (! empty($transaction_payment) && $row->transaction->is_credit_sale == 0 && $transaction_payment->method == 'cash') {
                                                    $this_tp = ! empty($row->transaction->contact) ? $row->transaction->contact : null;
                                                    $details .= '';
                                                    // $details .= !empty($row->daily_collection) ? "<b>Daily Collection: </b>".$row->daily_collection : '';
                                                    if (! empty($this_tp)) {
                                                        $details .= '<br><b>Customer:</b> ' . ($this_tp->name ?? 'N/A');
                                                    }
                                                } elseif ($row->sub_type == 'deposit') {
                                                    $settlementRecord = $settlementsByNumber->get((string) $row->transaction->invoice_no);
                                                    $details .= '</br>Customer Payment';
                                                    $customerName = ! empty($settlementRecord)
                                                        ? ($customerPaymentNames[(int) $settlementRecord->id] ?? '')
                                                        : '';
                                                    if ($customerName !== '') {
                                                        $details .= '<br><b>Customer:</b> ' . e($customerName);
                                                    }
                                                } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'loan_payment') {
                                                    // Check if note already contains formatted description
                                                    if (! empty($row->note) && (stripos($row->note, 'Loan Payment') !== false || stripos($row->note, 'Settlement No:') !== false)) {
                                                        // Note already contains formatted description, use it directly
                                                        // Replace <br> tags if present, or convert \n to <br>
                                                        $details = str_replace(['<br>', '<br/>', '<br />'], '<br>', $row->note);
                                                        $details = nl2br($details);
                                                    } else {
                                                        // Build description from transaction data
                                                        $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b><br>';
                                                        $details .= 'Loan Payment';

                                                        $cash = $this->transactionUtil->account_exist_return_id('Cash');
                                                        if ($cash == $row->account_id) {
                                                            $loanTargetName = $loanTargetAccountNames[(int) $row->transaction_id] ?? '';
                                                            if ($loanTargetName !== '') {
                                                                $details .= '<br>To: <b>' . e($loanTargetName) . '</b>';
                                                            }
                                                        }
                                                    }
                                                } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'customer_loan') {
                                                    // Check if note already contains formatted description
                                                    if (! empty($row->note) && (stripos($row->note, 'Loan to customer') !== false || stripos($row->note, 'Settlement No:') !== false)) {
                                                        // Note already contains formatted description, use it directly
                                                        // Replace <br> tags if present, or convert \n to <br>
                                                        $details = str_replace(['<br>', '<br/>', '<br />'], '<br>', $row->note);
                                                        $details = nl2br($details);
                                                    } else {
                                                        // Build description from transaction data
                                                        $details = "<b class='text-danger'>" . __('petro::lang.customer_loans') . '</b>';
                                                        $details .= '<br>' . '<b>Settlement No: </b>' . $row->transaction->invoice_no . ' <br>';

                                                        // Try to get customer name from contact relationship first
                                                        $customer_name = null;
                                                        if (! empty($row->transaction->contact)) {
                                                            $customer_name = $row->transaction->contact->name ?? null;
                                                        }

                                                        // If contact relationship is not available, try to extract from note
                                                        if (empty($customer_name) && ! empty($row->note)) {
                                                            // Pattern: "Customer: Customer Name" or "Customer: Customer Name \n"
                                                            if (preg_match('/Customer:\s*([^\n\r<]+)/i', $row->note, $matches)) {
                                                                $customer_name = trim($matches[1]);
                                                            }
                                                        }

                                                        $details .= '<b>Loan to customer</b><br>';
                                                        $details .= '<b>Customer: </b>' . ($customer_name ?? 'N/A');
                                                    }
                                                } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'drawing_payment') {
                                                    $details = '<span class="text-danger">Owners Drawing</span><br><b>Settlement No: </b>' . $row->transaction->invoice_no . ' <br>';

                                                    $settlementRecord = $settlementsByNumber->get((string) $row->transaction->invoice_no);
                                                    $drawingKey = (! empty($settlementRecord) ? (int) $settlementRecord->id : 0)
                                                        . '|' . number_format((float) $row->amount, 6, '.', '');
                                                    $drawingAccountName = $drawingAccountNames[$drawingKey] ?? 'N/A';
                                                    $details .= '<b>Owners Drawings Account: </b>' . e($drawingAccountName);
                                                } elseif (! empty($transaction_payment) && $row->transaction->is_credit_sale == 0 && $transaction_payment->method == 'cash_deposit') {
                                                    $details .= '</br><span class="text-danger">Direct Deposit</span>';
                                                } elseif ($row->transaction->is_credit_sale == 0 && $row->transaction->sub_type == 'cash_deposit') {
                                                    $bank = $cashDepositBankNames[(int) ($row->transaction->ref_no ?? 0)] ?? '';
                                                    $details .= '</br><span class="text-danger">Direct Deposit to bank: <b>' . e($bank) . '</b></span>';
                                                } elseif (! empty($transaction_payment) && $row->transaction->is_credit_sale == 0 && $transaction_payment->method == 'card' && $row->type == 'debit') {
                                                    $this_tp = ! empty($row->transaction->contact) ? $row->transaction->contact : null;
                                                    $details .= '</br>Card Sale ';
                                                    if (! empty($this_tp)) {
                                                        $details .= '<br><b>Customer:</b> ' . ($this_tp->name ?? 'N/A');
                                                    }
                                                } elseif (! empty($transaction_payment) && $row->transaction->is_credit_sale == 0 && $transaction_payment->method == 'card' && $row->type == 'credit') {
                                                    $this_tp = ! empty($row->transaction->contact) ? $row->transaction->contact : null;
                                                    $details .= '</br>Card Payment ';
                                                    if (! empty($this_tp)) {
                                                        $details .= '<br><b>Customer:</b> ' . ($this_tp->name ?? 'N/A');
                                                    }
                                                } elseif (! empty($transaction_payment) && $row->transaction->is_credit_sale == 0 && $transaction_payment->method == 'cheque' && $row->type == 'debit') {
                                                    $details .= '</br>Cheque Payment <br> <b>Bank:</b> ' . $transaction_payment->bank_name . '<b> Cheque No: </b>' . $transaction_payment->cheque_number . '  <b>Cheque Date: </b>' . $transaction_payment->cheque_date;
                                                    $this_tp = ! empty($row->transaction->contact) ? $row->transaction->contact : null;
                                                    if (! empty($this_tp)) {
                                                        $details .= '<br><b>Customer:</b> ' . ($this_tp->name ?? 'N/A');
                                                    }
                                                }

                                                if ($row->account_name == 'Taxes Payable') {
                                                    $details .= '<b>' . 'Settlement Amount: ' . '</b>' . $this->productUtil->num_f($row->transaction->final_total);
                                                }
                                            } elseif ($row->transaction->type == 'advance_payment') {
                                                if ($row->transaction->contact->type == 'customer') {
                                                    $details = '<b>' . 'Advance Payment done by ' . '</b>' . ($row->transaction->contact->name ?? 'N/A');
                                                }
                                                if ($row->transaction->contact->type == 'supplier') {
                                                    $details = '<b>' . 'Advance Payment done to ' . '</b>' . ($row->transaction->contact->name ?? 'N/A');
                                                    if ($row->method == "Bank") {
                                                        $details     = '<b class="text-danger">' . __('account.post_dated_cheques_full') . '</b><br>';
                                                        $expense_cat = self::ma002Find(\App\ExpenseCategory::class, $row->transaction->expense_category_id);
                                                        $details .= '<b>' . __('lang_v1.bank_name') . ': </b> ' . $row->acc_bank_name . ' / <b> ' . (! empty($expense_cat) ? $expense_cat->name : '') . '</b><br>';
                                                        $details .= ($row->transaction->contact->name ?? 'N/A') . '/ <span>Payee ' . (($row->transaction->contact->name ?? 'N/A') ?? 'N/A') . '</span><br>';
                                                        $details .= __('purchase.ref_no') . ': ' . $row->transaction->ref_no . ' / ';
                                                    } else {
                                                        $details = '<b>' . 'Advance Payment done to ' . '</b>' . ($row->transaction->contact->name ?? 'N/A');
                                                    }
                                                }
                                                $details .= '<br><b>Payment Method :  </b>' . $row->method;
                                            } elseif ($row->transaction->type == 'security_deposit') {
                                                if ($row->transaction->contact->type == 'customer') {
                                                    $details = '<b>' . 'Security Deposit     Customer ' . '</b>' . ($row->transaction->contact->name ?? 'N/A') . '<br><b> Payment Ref No.</b> ' . $row->transaction->ref_no;
                                                }
                                                if ($row->transaction->contact->type == 'supplier') {
                                                    $details = '<b>' . 'Security Deposit     Supplier ' . '</b>' . ($row->transaction->contact->name ?? 'N/A') . '<br><b> Payment Ref No.</b> ' . $row->transaction->ref_no;
                                                }
                                            } elseif ($row->transaction->type == 'refund_security_deposit') {
                                                if ($row->transaction->contact->type == 'customer') {
                                                    $details = '<b>' . 'Refund Security Deposit     Customer ' . '</b>' . ($row->transaction->contact->name ?? 'N/A') . '<br><b> Payment Ref No.</b> ' . $row->transaction->ref_no;
                                                }
                                                if ($row->transaction->contact->type == 'supplier') {
                                                    $details = '<b>' . 'Refund Security Deposit     Supplier ' . '</b>' . ($row->transaction->contact->name ?? 'N/A') . '<br><b> Payment Ref No.</b> ' . $row->transaction->ref_no;
                                                }
                                            } elseif ($row->transaction->type == 'security_deposit_refund') {
                                                if ($row->transaction->contact->type == 'customer') {
                                                    $details = '<b class="text-danger">' . 'Customer Security Deposit Refund' . '</b><br>' . ($row->transaction->contact->name ?? 'N/A') . '<br><b> Payment Ref No.</b> ' . $row->transaction->ref_no;
                                                }
                                                if ($row->transaction->contact->type == 'supplier') {
                                                    $details = '<b class="text-danger">' . 'Supplier Security Deposit Refund' . '</b><br>' . ($row->transaction->contact->name ?? 'N/A') . '<br><b> Payment Ref No.</b> ' . $row->transaction->ref_no;
                                                }
                                            } elseif ($row->transaction->type == 'direct_customer_loan') {
                                                $details = '<b class="text-danger">' . __('lang_v1.direct_loan_to_customer') . '</b><br>';
                                                if (! empty($row->transaction->contact)) {
                                                    $details .= ($row->transaction->contact->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'vat_price_adjustment') {
                                                $details = '<b class="">' . __('account.price_adjusted') . '</b><br>' . $row->transaction->invoice_no;
                                            } elseif ($row->transaction->type == 'ledger_discount') {
                                                $details = '<b style="color: red;">Sales / Early Payment Discount</b><br>';
                                                if (! empty($row->transaction->contact)) {
                                                    $details .= '<b>Customer:</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br>';
                                                }

                                                if (! empty($row->transaction->transaction_note)) {
                                                    $details .= '<b>Invoice / Bill No:</b> ';
                                                    $notes = json_decode($row->transaction->transaction_note);
                                                    if (is_array($notes)) {
                                                        foreach ($notes as $note) {
                                                            $details .= $note . ',';
                                                        }
                                                    } else {
                                                        $details .= $row->transaction->transaction_note;
                                                    }
                                                }
                                            } elseif ($row->transaction->type == 'postdated_transfer') {
                                                $details .= '<b>' . __('account.transferred_from_postdated_cheque') . ':</b>';
                                            } elseif ($row->transaction->type == 'postdated_deposit') {

                                                if ($this->moduleUtil->account_exist_return_id('Issued Post Dated Cheques') == $row->account_id) {
                                                    $details .= '<b class="text-danger">' . __('account.issued_post_dated_cheques') . ':</b><br>';
                                                } else {
                                                    $details .= '<b class="text-danger">' . __('account.post_dated_cheques_full') . ':</b><br>';
                                                }

                                                if (! empty(($row->transaction->contact->name ?? 'N/A'))) {
                                                    $details .= ($row->transaction->contact->name ?? 'N/A');
                                                }

                                                $related_account = self::ma002Find(Account::class, $row->related_account_id);
                                                if ($related_account) {
                                                    $details .= $related_account->name . '<br>';
                                                }
                                            } elseif ($row->transaction->type == 'cheque_opening_balance') {
                                                $details .= '<b class="text-danger">' . __('account.opening_balance') . '</b><br>';
                                                if ($row->account_name == 'Opening Balance Equity Account' && ! empty($row->pair_at_id)) {
                                                    $pair_at = self::ma002Find(AccountTransaction::class, $row->pair_at_id);
                                                    if (! empty($pair_at->account_id)) {
                                                        $account = self::ma002Find(Account::class, $pair_at->account_id);
                                                        $details .= '<b>' . __('account.loan_account') . '</b>:' . ($account->name ?? 'N/A');
                                                    }
                                                }

                                                if (! empty(($row->transaction->contact->name ?? 'N/A'))) {
                                                    $details .= '<br><b>' . __('fleet::lang.customer') . ':</b>' . ($row->transaction->contact->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'refund') {
                                                $details = __('lang_v1.refund') . ':' . $row->transaction->ref_no . ' <br> <b> ' . __('lang_v1.invoice_no') . ':' . '</b>' . $row->transaction->invoice_no;
                                            } elseif ($row->transaction->type == 'cheque_return' && $row->at_sub_type == 'cheque_return_charges') {
                                                $details = __('lang_v1.cheque_return_charges') . ':' . $row->transaction->ref_no;
                                                $details .= '<br><b>' . __('lang_v1.bank_name') . ': </b> ' . $row->acc_bank_name . ' <b> ' . __('lang_v1.cheque_no') . ': </b> ' . $row->dep_trans_cheque_number . ' <b> ' . __('lang_v1.cheque_date') . ': </b> ' . $row->cheque_date . ' <br><b> ' . __('lang_v1.cheque_return_date') . ': </b> ' . Carbon::parse($row->operation_date)->format('Y-m-d');
                                            } elseif ($row->transaction->type == 'cheque_return' && $row->at_sub_type != 'cheque_return_charges') {
                                                $details = __('lang_v1.cheque_return_ref_no') . ':' . $row->cheque_ref_no;
                                                $details .= '<br><b>' . __('fleet::lang.customer') . ':</b> ' . ($row->transaction->contact->name ?? 'N/A') . '<br><b>' . __('lang_v1.bank_name') . ': </b> ' . $row->acc_bank_name . ' <br><b> ' . __('lang_v1.cheque_no') . ': </b> ' . $row->dep_trans_cheque_number . ' <br><b> ' . __('lang_v1.cheque_date') . ': </b> ' . $row->cheque_date . ' <br><b> ' . __('lang_v1.cheque_return_date') . ': </b> ' . Carbon::parse($row->operation_date)->format('Y-m-d');
                                            } elseif ($row->transaction->type == 'property_sell') {
                                                $details = __('lang_v1.sell');
                                                $details .= '<br>' . __('lang_v1.invoice_no') . ': <b>' . $row->transaction->invoice_no . '</b>';
                                                $transaction_sell_line = PropertySellLine::where('transaction_id', $row->transaction->id)->first();
                                                $property              = Property::leftjoin('property_blocks', 'properties.id', 'property_blocks.property_id')
                                                    ->leftjoin('units', 'properties.unit_id', 'units.id')
                                                    ->where('properties.id', $transaction_sell_line->property_id)
                                                    ->where('property_blocks.id', $transaction_sell_line->block_id)
                                                    ->first();
                                                if (! empty($property)) {
                                                    $details .= '<br><b>Project Name: </b>' . $property->name;
                                                    $details .= '<br><b>Block Number: </b>' . $property->block_number;
                                                }
                                                if (! empty($row->income_type)) {
                                                    $details .= '<br><b>' . ucfirst($row->income_type) . '</b>';
                                                }
                                            } elseif ($row->transaction->type == 'route_operation') {
                                                $fleet   = self::ma002Find(Fleet::class, $row->transaction->fleet_id);
                                                $details = '<b>' . __('fleet::lang.route_operation_no') . ':</b>' . $row->transaction->invoice_no . '<br>';
                                                if (! empty($fleet)) {
                                                    $details .= '<b>' . __('fleet::lang.vehicle_no') . ':</b>' . $fleet->vehicle_number;
                                                }
                                                if (! empty($row->transaction->contact)) {
                                                    $details .= '<br><b>' . __('fleet::lang.customer') . ':</b>' . ($row->transaction->contact->name ?? 'N/A');
                                                }
                                            } elseif ($row->transaction->type == 'ro_advance') {
                                                if ($row->transaction->sub_type == 'driver') {
                                                    $staff = self::ma002Find(Driver::class, $row->transaction->contact_id)->driver_name;
                                                } else {
                                                    $staff = self::ma002Find(Helper::class, $row->transaction->contact_id)->helper_name;
                                                }
                                                $details = '<b>' . __('fleet::lang.advance') . '<br><b>Staff: </b>' . $staff;
                                            } elseif ($row->transaction->type == 'price_change_increase' || $row->transaction->type = 'price_change_decrease') {

                                                $transaction_id = is_array($row) ? ($row['transaction_id'] ?? null) : ($row->transaction_id ?? null);

                                                if (! empty($transaction_id)) {
                                                    $trans = Transaction::where('id', $transaction_id)->first();

                                                    if (! empty($trans)) {
                                                        $details .= '<b>' . __('contact.customer') . ':</b> ' . ($trans->contact->name ?? 'N/A') . '<br>';
                                                    }
                                                }
                                                $fdetail = self::ma002Find(PriceChangesDetail::class, $row->transaction->ref_no);
                                                $details .= '<br> ' . $row->note;
                                                if (! empty($fdetail)) {
                                                    $f17     = self::ma002Find(PriceChangesHeader::class, $fdetail->header_id);
                                                    $product = self::ma002Find(Product::class, $fdetail->product_id);

                                                    $form_no = ! empty($f17) ? $f17->form_no : '';
                                                    $product = ! empty($product) ? $product->name : '';

                                                    $details = '<b>' . __('pricechanges::lang.form_no') . ":</b> $form_no" . "<br><b>Product: </b> $product <br> <span class='text-danger'>Price Changed</span>";
                                                }
                                            } elseif ($row->transaction->type == 'ro_salary') {
                                                if ($row->transaction->sub_type == 'driver') {
                                                    $staff = self::ma002Find(Driver::class, $row->transaction->contact_id)->driver_name;
                                                } else {
                                                    $staff = self::ma002Find(Helper::class, $row->transaction->contact_id)->helper_name;
                                                }
                                                $details = '<b>' . __('fleet::lang.salary') . '<br><b>Staff: </b>' . $staff;
                                            } elseif ($row->transaction->type == 'fleet_opening_balance') {
                                                $fleet   = self::ma002Find(Fleet::class, $row->transaction->fleet_id);
                                                $details = '<b>' . __('fleet::lang.ob_of_to') . ':</b><br><b>Invoice No: </b>' . $row->transaction->invoice_no . '<br>';
                                                $contact = self::ma002Find(Contact::class, $row->transaction->contact_id);
                                                $details .= '<b>' . __('contact.customer') . ':</b> ' . ($contact->name ?? 'N/A');
                                                if (! empty($fleet)) {

                                                    $details .= '<br><b>' . __('fleet::lang.vehicle_no') . ':</b>' . $fleet->vehicle_number;
                                                }
                                            } elseif ($row->transaction->type == 'sell_return') {
                                                $trans   = self::ma002Find(Transaction::class, $row->transaction->return_parent_id);
                                                $details = '<b class="text-danger">' . __('lang_v1.sell_return') . ':</b><br><b>Parent Invoice No: </b>' . $trans->invoice_no . '<br><b>Parent Invoice Amount: </b>' . $trans->final_total . '<br>';

                                                $contact = self::ma002Find(Contact::class, $trans->contact_id);
                                                $details .= '<b>' . __('contact.customer') . ':</b> ' . ($contact->name ?? 'N/A');
                                            }

                                            if (! empty($row->deleted_by)) {

                                                $user = self::ma002Find(User::class, $row->deleted_by);

                                                $details .= '<br><b class="text-danger">' . __('lang_v1.deleted') . '<b> ';

                                                if (! empty($user)) {
                                                    $details .= __('lang_v1.by') . ' ' . $user->username . ' ';
                                                }

                                                if (! empty($row->deleted_at)) {
                                                    $details .= 'at ' . $this->transactionUtil->format_date($row->deleted_at, true);
                                                }
                                            }
                                        } else {
                                            // Handle both object and array row cases safely
                                            $transaction_id = is_array($row) ? ($row['transaction_id'] ?? null) : ($row->transaction_id ?? null);

                                            if (! empty($transaction_id)) {
                                                $trans = Transaction::where('id', $transaction_id)->first();

                                                if (! empty($trans) && $trans->type == 'hms_booking') {
                                                    $details .= '<b>HMS Bill No.:</b> ' . ($trans->ref_no ?? '') . '<br>';
                                                    $details .= '<b>' . __('contact.customer') . ':</b> ' . ($trans->contact->name ?? 'N/A') . '<br>';
                                                }
                                            }
                                        }

                                        if (! empty($row->journal_entry)) {
                                            $journal_id       = Journal::where('id', $row->journal_entry)->first()->journal_id;
                                            $details          = 'Journal Entry No. ' . $journal_id;
                                            $journal_accounts = Journal::where('business_id', $business_id)->where('journal_id', $journal_id)->get();
                                            if ($journal_accounts->count() === 2) {
                                                $other_journal = Journal::where('business_id', $business_id)->where('journal_id', $journal_id)->where('account_id', '!=', $id)->first();
                                                $other_account = Account::where('id', optional($other_journal)->account_id)->first();
                                                if (! empty($other_account)) {
                                                    $details .= '<br>' . $other_account->name;
                                                }
                                            }
                                        }

                                        // Handle both array and object cases safely
                                        $at_sub_type             = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                                        $transfer_transaction_id = is_array($row) ? ($row['transfer_transaction_id'] ?? null) : ($row->transfer_transaction_id ?? null);

                                        if ($at_sub_type == 'vat_payment') {
                                            $details     = '<b>' . __('vat::lang.vat_payment') . '</b><br>';
                                            $vat_payment = self::ma002Find(VatPayment::class, $transfer_transaction_id);

                                            if (! empty($vat_payment)) {
                                                $details .= '<b>' . __('vat::lang.form_no') . ':</b> ' . ($vat_payment->form_no ?? '') . '<br>';

                                                if (! empty($vat_payment->cheque_date)) {
                                                    $details .= '<b>' . __('vat::lang.cheque_date') . ':</b> ' . $vat_payment->cheque_date . '<br>';
                                                }

                                                if (! empty($vat_payment->cheque_number)) {
                                                    $details .= '<b>' . __('vat::lang.cheque_number') . ':</b> ' . $vat_payment->cheque_number . '<br>';
                                                }

                                                if (! empty($vat_payment->recipient_name)) {
                                                    $details .= '<b>' . __('vat::lang.recipient_name') . ':</b> ' . $vat_payment->recipient_name . '<br>';
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $journal_entry_id = is_array($row) ? ($row['journal_entry'] ?? null) : ($row->journal_entry ?? null);

                                    if (! empty($journal_entry_id)) {
                                        $journal = Journal::where('id', $journal_entry_id)->first();

                                        if (! empty($journal)) {
                                            $journal_id = $journal->journal_d ?? $journal->id; // fallback if journal_d is missing
                                            $details    = 'Journal Entry No. ' . $journal_id . ' Deleted ';
                                        }
                                    }
                                }
                                /**
                             * @ModifiedBy Afes
                             *
                             * @Task 127002
                             */
                                $transaction = null;

                                // Check if $row is an object or array and get 'transaction'
                                if (is_object($row) && isset($row->transaction)) {
                                    $transaction = $row->transaction;
                                } elseif (is_array($row) && isset($row['transaction'])) {
                                    $transaction = $row['transaction'];
                                }

                                if (! empty($transaction)) {
                                    $transaction_id = is_object($transaction) ? $transaction->id : $transaction['id'];

                                    $purchase_line = PurchaseLine::leftJoin('products', 'purchase_lines.product_id', 'products.id')
                                        ->where('transaction_id', $transaction_id)
                                        ->first();

                                    if (! empty($purchase_line)) {
                                        $details .= '<br><b>Product:</b> ' . $purchase_line['name'];
                                    }
                                }
                            } else {
                                // Safe null checking for pump operator
                                if (! empty($row->transaction->pump_operator_id) && ! empty($transaction)) {
                                    $pump_operator = self::ma002Find(PumpOperator::class, $row->transaction->pump_operator_id);

                                    if (! empty($pump_operator)) {
                                        if ($row->transaction->type == 'opening_balance') {
                                            $details = '<b>' . __('petro::lang.pump_operator') . ': ' . $pump_operator->name . '</b> <br><b>' . 'Opening Balance <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->ref_no;
                                        } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'shortage') {
                                            $details   = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br>Pump Operator: ' . $pump_operator->name . ' <br><b>Shortage</b><br><b>Payment Ref No : </b>' . $row->payment_ref_no;
                                            $ref_added = true;
                                        } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'excess') {
                                            $details   = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br>Pump Operator: ' . $pump_operator->name . ' <br><b>Excess</b><br><b>Payment Ref No: </b>' . $row->payment_ref_no;
                                            $ref_added = true;
                                        } elseif ($row->transaction->type == 'sell') {
                                            $scontact = ContactLedger::where('transaction_id', $row->transaction->id)->first();

                                            if (! empty($scontact)) {
                                                $contact = self::ma002Find(Contact::class, $scontact->contact_id); // Changed to find() instead of findOrFail()
                                                if (! empty($contact)) {
                                                    $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br><b>' . __('contact.customer') . ':</b> ' . ($contact->name ?? 'N/A');
                                                } else {
                                                    $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br><b>' . __('contact.customer') . ':</b> N/A';
                                                }
                                            } else {
                                                $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b>';
                                            }
                                        } elseif ($row->transaction->type == 'shortage_bulk_payment') {
                                            $details = __('petro::lang.pump_operator') . ': ' . $pump_operator->name . '<br><b>' . __('petro::lang.shortage_recovered') . ' <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->invoice_no;
                                        } elseif ($row->transaction->type == 'excess_bulk_payment') {
                                            $details = __('petro::lang.pump_operator') . ': ' . $pump_operator->name . '<br><b>' . __('petro::lang.excess_paid') . ' <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->invoice_no;
                                        }
                                    } else {
                                        // Pump operator not found, use generic description
                                        if ($row->transaction->type == 'opening_balance') {
                                            $details = '<b>' . __('petro::lang.pump_operator') . ': Not Found</b> <br><b>' . 'Opening Balance <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->ref_no;
                                        } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'shortage') {
                                            $details   = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br>Pump Operator: Not Found <br><b>Shortage</b><br><b>Payment Ref No : </b>' . $row->payment_ref_no;
                                            $ref_added = true;
                                        } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'excess') {
                                            $details   = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br>Pump Operator: Not Found <br><b>Excess</b><br><b>Payment Ref No: </b>' . $row->payment_ref_no;
                                            $ref_added = true;
                                        } elseif ($row->transaction->type == 'sell') {
                                            $scontact = ContactLedger::where('transaction_id', $row->transaction->id)->first();

                                            if (! empty($scontact)) {
                                                $contact = self::ma002Find(Contact::class, $scontact->contact_id);
                                                $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br><b>' . __('contact.customer') . ':</b> ' . (! empty($contact) ? ($contact->name ?? 'N/A') : 'N/A');
                                            } else {
                                                $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b>';
                                            }
                                        } elseif ($row->transaction->type == 'shortage_bulk_payment') {
                                            $details = __('petro::lang.pump_operator') . ': Not Found<br><b>' . __('petro::lang.shortage_recovered') . ' <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->invoice_no;
                                        } elseif ($row->transaction->type == 'excess_bulk_payment') {
                                            $details = __('petro::lang.pump_operator') . ': Not Found<br><b>' . __('petro::lang.excess_paid') . ' <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->invoice_no;
                                        } else {
                                            $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br>Pump Operator: Not Found';
                                        }
                                    }
                                } else {
                                    // No pump operator ID, use generic description
                                    if ($row->transaction->type == 'opening_balance') {
                                        $details = '<b>' . __('petro::lang.pump_operator') . ': N/A</b> <br><b>' . 'Opening Balance <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->ref_no;
                                    } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'shortage') {
                                        $details   = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br><b>Shortage</b><br><b>Payment Ref No : </b>' . $row->payment_ref_no;
                                        $ref_added = true;
                                    } elseif ($row->transaction->type == 'settlement' && $row->transaction->sub_type == 'excess') {
                                        $details   = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br><b>Excess</b><br><b>Payment Ref No: </b>' . $row->payment_ref_no;
                                        $ref_added = true;
                                    } elseif ($row->transaction->type == 'sell') {
                                        $scontact = ContactLedger::where('transaction_id', $row->transaction->id)->first();

                                        if (! empty($scontact)) {
                                            $contact = self::ma002Find(Contact::class, $scontact->contact_id);
                                            $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b> <br><b>' . __('contact.customer') . ':</b> ' . (! empty($contact) ? ($contact->name ?? 'N/A') : 'N/A');
                                        } else {
                                            $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b>';
                                        }
                                    } elseif ($row->transaction->type == 'shortage_bulk_payment') {
                                        $details = __('petro::lang.pump_operator') . ': N/A<br><b>' . __('petro::lang.shortage_recovered') . ' <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->invoice_no;
                                    } elseif ($row->transaction->type == 'excess_bulk_payment') {
                                        $details = __('petro::lang.pump_operator') . ': N/A<br><b>' . __('petro::lang.excess_paid') . ' <br> </b>' . __('purchase.ref_no') . ': ' . $row->transaction->invoice_no;
                                    } else {
                                        $details = '<b>Settlement No: ' . $row->transaction->invoice_no . '</b>';
                                    }
                                }

                                if (empty(trim(strip_tags($details)))) {
                                    $settlement_no = $this->extractSettlementNoFromNote($row->note ?? '');
                                    if (! empty($settlement_no)) {
                                        $details .= '<b>Settlement No:</b> ' . $settlement_no;
                                        $payment_desc = $this->getPaymentDescriptionForAccountRow($row);
                                        if (! empty($payment_desc)) {
                                            $details .= '<br>' . $payment_desc;
                                        }
                                    } else {
                                        // Handle pumper dashboard payments without settlement info
                                        $note = $row->note ?? '';
                                        if (! empty($note)) {
                                            // Check if note contains pump operator info
                                            if (stripos($note, 'Pumper Dashboard') !== false || stripos($note, 'Pump Operator') !== false) {
                                                $details = $note;
                                            } elseif (stripos($note, 'Daily credit sale') !== false || stripos($note, 'daily_collection') !== false) {
                                                // For daily credit sales and daily collections, show the note
                                                $details = $note;
                                                // Try to get pump operator info from related records
                                                if (stripos($note, 'Daily credit sale') !== false && ! empty($row->transaction_id)) {
                                                    $transaction = self::ma002Find(Transaction::class, $row->transaction_id);
                                                    if ($transaction && ! empty($transaction->pump_operator_id)) {
                                                        $pump_operator = self::ma002Find(PumpOperator::class, $transaction->pump_operator_id);
                                                        if ($pump_operator) {
                                                            $details .= '<br><b>Pump Operator:</b> ' . $pump_operator->name;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if (! empty($row->deleted_by)) {
                                    $details .= '<br><b class="btn text-danger">' . __('lang_v1.deleted') . ' ' . $this->commonUtil->format_date($row->deleted_by, false) . '<b>';
                                }

                                $purchase_line = PurchaseLine::leftjoin('products', 'purchase_lines.product_id', 'products.id')->where('transaction_id', $row->transaction->id)->first();
                                if (! empty($purchase_line)) {
                                    $details .= '<br><b>Product: </b>' . $purchase_line['name'];
                                }
                            }

                            if (empty($row->transaction) && $sub_type != 'deposit') {
                                // Handle customer_loan sub_type without transaction
                                if ($sub_type == 'customer_loan' && ! empty($row->note)) {
                                    // Check if note contains formatted description
                                    if (stripos($row->note, 'Loan to customer') !== false || stripos($row->note, 'Settlement No:') !== false) {
                                        // Note already contains formatted description, use it directly
                                        $details = str_replace(['<br>', '<br/>', '<br />'], '<br>', $row->note);
                                        $details = nl2br($details);
                                    } else {
                                        // Extract settlement number and customer name from note
                                        $settlement_no = $this->extractSettlementNoFromNote($row->note);
                                        $customer_name = null;
                                        if (preg_match('/Customer:\s*([^\n\r<]+)/i', $row->note, $matches)) {
                                            $customer_name = trim($matches[1]);
                                        }

                                        $details = '<b>Settlement No:</b> ' . ($settlement_no ?? 'N/A') . '<br>';
                                        $details .= '<b>Loan to customer</b><br>';
                                        $details .= '<b>Customer:</b> ' . ($customer_name ?? 'N/A') . '<br>';
                                    }
                                } else {
                                    $contact = self::ma002Find(Contact::class, $payment_for);
                                    if (! empty($contact)) {
                                        $update_post_dated_cheque = is_array($row)
                                            ? ($row['update_post_dated_cheque'] ?? null)
                                            : ($row->update_post_dated_cheque ?? null);

                                        // is a customer payment
                                        if ($update_post_dated_cheque == 1) {
                                            $details .= '<b>Customer</b> ' . ($contact->name ?? 'N/A') . '
                                                <br>  Ref No:' . $row->payment_ref_no;
                                            $ref_added = true;
                                            $details .= '<br>' . ($contact->name ?? 'N/A');
                                        } else {
                                            if (! empty($row->auto_transfer)) {
                                                $details .= '<br>Ref No:' . $row->payment_ref_no;
                                                $ref_added = true;
                                            } else {
                                                $payment_ref_no = is_array($row)
                                                    ? ($row['payment_ref_no'] ?? null)
                                                    : ($row->payment_ref_no ?? null);

                                                $details .= '<br><b>Customer Payment<br> </b> Ref No:' . ($payment_ref_no ?? 'N/A');
                                                $ref_added = true;
                                                $details .= '<br>' . ($contact->name ?? 'N/A');

                                            }
                                        }
                                    }
                                }

                                if ($account_name == 'Accounts Receivable') {
                                    $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);


                                    $transaction_id = is_array($row) ? ($row['transaction_id'] ?? null) : ($row->transaction_id ?? null);

                                    if (! empty($transaction_id)) {
                                        $trans = Transaction::where('id', $transaction_id)->first();

                                        if (! empty($trans)) {
                                            $details .= '<b>' . __('contact.customer') . ':</b> ' . ($trans->contact->name ?? 'N/A') . '<br>';
                                        }
                                    }
                                    $details .= '<br> ' . $row->note;

                                    if (! empty($tp_id)) {
                                        $pair_txn = AccountTransaction::leftjoin('accounts', 'accounts.id', 'account_transactions.account_id')
                                            ->where('type', 'debit')
                                            ->where('transaction_payment_id', $tp_id)
                                            ->select('accounts.name')
                                            ->first();

                                        if (! empty($pair_txn)) {
                                            $details .= '<br><b>' . __('account.payment_method_account') . ': </b>' . $pair_txn->name;
                                        }
                                    }
                                }

                                if (! empty($cheque_date)) {
                                    // Safely access whether row is array or object
                                    $update_post_dated_cheque = is_array($row)
                                        ? ($row['update_post_dated_cheque'] ?? null)
                                        : ($row->update_post_dated_cheque ?? null);

                                    $operation_date = is_array($row)
                                        ? ($row['operation_date'] ?? null)
                                        : ($row->operation_date ?? null);

                                    $cheque_date_val = is_array($row)
                                        ? ($row['cheque_date'] ?? null)
                                        : ($row->cheque_date ?? null);

                                    if ($update_post_dated_cheque == 1) {
                                        $details .= '<br><span style="color: red;"><b>Post Dated Cheque of</b> '
                                        . $this->commonUtil->format_date($operation_date)
                                            . '</span>';
                                    } else {
                                        $details .= '<br><b>' . __('account.cheque_date') . ': </b>'
                                        . $this->commonUtil->format_date($cheque_date_val);
                                    }
                                }
                            }

                            if (! empty($transaction)) {
                            }
                            if (! empty($transaction) && (is_array($transaction) && $transaction['type'] == 'daily_card_payment')
                            ) {
                                $txn_invoice_no_dc = is_array($transaction) ? ($transaction['invoice_no'] ?? null) : ($transaction->invoice_no ?? null);
                                if (! empty($txn_invoice_no_dc)) {
                                    $details .= '<br><b>Settlement No: </b>' . $txn_invoice_no_dc;
                                }

                                $amount = is_array($row) ? ($row['amount'] ?? null) : ($row->amount ?? null);
                                $details .= ! empty($amount)
                                    ? '<br><b>Daily Collection: </b>' . number_format($amount, $currency_precision, '.', '')
                                    : '';

                                $details .= '<br>Cash Payment';
                                // Try to show the actual customer name if available
                                $customer_name = null;
                                if (! empty($row->transaction) && ! empty($row->transaction->contact)) {
                                    $customer_name = $row->transaction->contact->name ?? null;
                                }
                                if (empty($customer_name) && ! empty($row->contact_id)) {
                                    $contact = self::ma002Find(Contact::class, $row->contact_id);
                                    if ($contact) {
                                        $customer_name = $contact->name;
                                    }
                                }
                                if (! empty($customer_name)) {
                                    $details .= '<br><b>Customer: </b>' . $customer_name;
                                }
                            }

                            // Don't show Payment Ref No for:
                            // 1. Card account transactions from Daily Collection SW
                            // 2. Settlement cash payment transactions from Settlement SW
                            $is_card_account = false;
                            $group_name      = is_array($row) ? ($row['group_name'] ?? '') : ($row->group_name ?? '');
                            $account_name    = is_array($row) ? ($row['account_name'] ?? '') : ($row->account_name ?? '');
                            $sub_type        = is_array($row) ? ($row['subtype'] ?? $row['sub_type'] ?? null) : ($row->subtype ?? $row->sub_type ?? null);

                            // Check if this is a card account (group_name is 'Card' or account is a card account)
                            if (strtolower($group_name) === 'card' ||
                                (strpos(strtolower($account_name), 'card') !== false && strpos(strtolower($account_name), 'credit debit') === false)) {
                                $is_card_account = true;
                            }

                            // Check if this is a daily_card_payment transaction
                            // Get sub_type from multiple possible fields (subtype, sub_type, at_sub_type)
                            $account_sub_type = is_array($row) ? ($row['subtype'] ?? $row['sub_type'] ?? $row['at_sub_type'] ?? null) : ($row->subtype ?? $row->sub_type ?? $row->at_sub_type ?? null);

                            $is_daily_card_payment = false;
                            if (! empty($row->transaction)) {
                                $transaction_type     = is_object($row->transaction) ? ($row->transaction->type ?? null) : ($row->transaction['type'] ?? null);
                                $transaction_sub_type = is_object($row->transaction) ? ($row->transaction->sub_type ?? null) : ($row->transaction['sub_type'] ?? null);
                                if ($transaction_type === 'daily_card_payment' || $account_sub_type === 'daily_card_payment' || $sub_type === 'daily_card_payment') {
                                    $is_daily_card_payment = true;
                                }
                                // Also check for settlement card payments (from Settlement SW / Add payment / Card payment)
                                if ($transaction_type === 'settlement' && ($transaction_sub_type === 'card_payment' || $account_sub_type === 'card_payment' || $sub_type === 'card_payment')) {
                                    $is_daily_card_payment = true;
                                }
                            } elseif ($account_sub_type === 'daily_card_payment' || $account_sub_type === 'card_payment' || $sub_type === 'daily_card_payment' || $sub_type === 'card_payment') {
                                $is_daily_card_payment = true;
                            }

                            // Check if payment method is card (New Logic to hide Payment Ref No for card payments)
                            $payment_method = is_array($row) ? ($row['method'] ?? null) : ($row->method ?? null);
                            if (empty($payment_method) && ! empty($row->transaction)) {
                                $tid = is_array($row->transaction) ? ($row->transaction['id'] ?? null) : ($row->transaction->id ?? null);
                                if ($tid) {
                                    $tp = TransactionPayment::where('transaction_id', $tid)->first();
                                    if ($tp) {
                                        $payment_method = $tp->method;
                                    }
                                }
                            }
                            if ($payment_method === 'card') {
                                $is_daily_card_payment = true;
                            }

                            // Check if this is a settlement_cash_payment transaction
                            $is_settlement_cash_payment = false;
                            if (! empty($row->transaction)) {
                                $transaction_type = is_object($row->transaction) ? ($row->transaction->type ?? null) : ($row->transaction['type'] ?? null);
                                if ($transaction_type === 'settlement_cash_payment' || $sub_type === 'settlement_cash_payment') {
                                    $is_settlement_cash_payment = true;
                                }
                            } elseif ($sub_type === 'settlement_cash_payment') {
                                $is_settlement_cash_payment = true;
                            }

                            // Only show Payment Ref No if it's not excluded
                            // Exclude Payment Ref No for:
                            // 1. Card account transactions from Daily Collection SW (daily_card_payment)
                            // 2. Card account transactions from Settlement SW (settlement + card_payment)
                            // 3. Settlement cash payment transactions
                            if (! empty($row->payment_ref_no) && empty($ref_added) && ! ($is_card_account && $is_daily_card_payment) && ! $is_settlement_cash_payment) {
                                $details .= '<br><b>Payment Ref No: </b>' . $row->payment_ref_no;
                            }
                            if (! empty($row->transaction) && $row->transaction->new_deleted_at) {
                                $user_deleted = self::ma002Find(User::class, $row->transaction->new_deleted_by)->username ?? '';
                                $details      = "<span class='text-danger'> <b>Deleted</b><br><b>PO No: </b>" .
                                $row->transaction->invoice_no . '<br><b>Supplier: </b>' . $row->transaction->contact->name . '<br><b>By: </b>' . $user_deleted . '<br> <b>at:</b> ' . $this->transactionUtil->format_date($row->transaction->new_deleted_at, true) . '</span>';
                            }

                            $details .= ! empty($row->daily_collection) ? '<br><b>Daily Collection: </b>' . $row->daily_collection : '';
                            $transaction = null;

                            // Extract transaction safely
                            if (is_object($row) && isset($row->transaction)) {
                                $transaction = $row->transaction;
                            } elseif (is_array($row) && isset($row['transaction'])) {
                                $transaction = $row['transaction'];
                            }

                            // Proceed only if transaction exists
                            if (! empty($transaction) && (
                                (is_object($transaction) && $transaction->type == 'settlement') ||
                                (is_array($transaction) && $transaction['type'] == 'settlement')
                            )) {
                                $txn_invoice_no  = is_object($transaction) ? ($transaction->invoice_no ?? null) : ($transaction['invoice_no'] ?? null);
                                $txn_sub_type    = is_object($transaction) ? ($transaction->sub_type ?? null) : ($transaction['sub_type'] ?? null);

                                if (! empty($txn_invoice_no)) {
                                    $details .= '<br><b>Settlement No: </b>' . $txn_invoice_no;
                                }

                                // Label based on sub_type
                                if ($txn_sub_type === 'cash_payment') {
                                    $details .= '<br>Cash Payment';
                                } elseif (empty($txn_sub_type)) {
                                    $details .= '<br>Cash Payment';
                                }

                                // Try to show the actual customer name if available
                                $customer_name = null;
                                if (! empty($row->transaction) && ! empty($row->transaction->contact)) {
                                    $customer_name = $row->transaction->contact->name ?? null;
                                }
                                if (empty($customer_name) && ! empty($row->contact_id)) {
                                    $contact = self::ma002Find(Contact::class, $row->contact_id);
                                    if ($contact) {
                                        $customer_name = $contact->name;
                                    }
                                }
                                if (! empty($customer_name)) {
                                    $details .= '<br><b>Customer: </b>' . $customer_name;
                                }
                            }
                            $discount_account_id = $this->transactionUtil->account_exist_return_id('Sales Discount');
                            if (! empty($discount_account_id) && $account_id == $discount_account_id) {
                                $details .= '<br><b>Sales Discount for the Bill No. </b>' . $transaction->invoice_no;
                            }

                            if (! empty($transaction) && (
                                (is_object($transaction) && $transaction->is_credit_sale == 1) ||
                                (is_array($transaction) && isset($transaction['is_credit_sale']) && $transaction['is_credit_sale'] == 1)
                            )) {


                                $settlement_no = null;

                                $acct = AccountTransaction::where('transaction_id', $transaction->id)
                                    ->whereNotNull('shift_number')
                                    ->first();

                                if ($acct) {
                                    $voucher = DailyVoucher::where('shift_id', $acct->shift_number)->first();
                                    if ($voucher) {
                                        $settlement_no = $voucher->settlement_no;
                                    }
                                }

                                if (! empty($settlement_no)) {
                                    $details .= '<br><b>Settlement No: </b>' . $settlement_no;
                                }

                                $amount = $row->amount ?? null;
                                if (!empty($amount) && !empty($transaction)) {
                                    $transaction_id = is_object($transaction) ? ($transaction->id ?? null) : ($transaction['id'] ?? null);
                                    $transaction_type = is_object($transaction) ? ($transaction->type ?? null) : ($transaction['type'] ?? null);
                                    $transaction_sub_type = is_object($transaction) ? ($transaction->sub_type ?? null) : ($transaction['sub_type'] ?? null);
                                    $transaction_is_settlement = (int) (is_object($transaction) ? ($transaction->is_settlement ?? 0) : ($transaction['is_settlement'] ?? 0));
                                    $account_id = is_array($row) ? ($row['account_id'] ?? null) : ($row->account_id ?? null);

                                    static $sales_income_account_ids_display = null;
                                    if ($sales_income_account_ids_display === null) {
                                        $sales_income_account_ids_display = Account::where('business_id', request()->session()->get('user.business_id'))
                                            ->where('name', 'like', '%Sales Income%')
                                            ->pluck('id')
                                            ->toArray();
                                    }

                                    $account_name = is_array($row) ? ($row['account_name'] ?? null) : ($row->account_name ?? null);
                                    $is_sales_income = (!empty($account_name) && stripos($account_name, 'Sales Income') !== false)
                                        || (!empty($account_id) && in_array($account_id, $sales_income_account_ids_display));

                                    if (
                                        $is_sales_income &&
                                        in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'], true) &&
                                        ($transaction_sub_type === 'settlement' || $transaction_is_settlement === 1)
                                    ) {
                                        // Settlement Sales Income postings are already stored per line/sub-category.
                                    } elseif ($is_sales_income && in_array($transaction_type, ['sell', 'fpos_sale', 'tpos_sale', 'route_operation'], true) && !is_null($transaction_id)) {
                                        $transaction_data = DB::table('transactions')
                                            ->where('id', $transaction_id)
                                            ->select('discount_type', 'discount_amount', 'final_total')
                                            ->first();

                                        if ($transaction_data) {
                                            $final_total = abs(floatval($transaction_data->final_total ?? 0));
                                            $stored_amount = abs(floatval($amount));

                                            if (abs($stored_amount - $final_total) > 0.01) {
                                                $discount_type_val = $transaction_data->discount_type ?? null;
                                                $discount_amount_val = $transaction_data->discount_amount ?? null;

                                                if (!is_null($discount_type_val) && !is_null($discount_amount_val) && floatval($discount_amount_val) > 0) {
                                                    $total_before_discount = 0;
                                                    $sell_lines = DB::table('transaction_sell_lines')
                                                        ->where('transaction_id', $transaction_id)
                                                        ->get();
                                                    foreach ($sell_lines as $line) {
                                                        $unit_price_inc_tax = floatval($line->unit_price_inc_tax ?? 0);
                                                        $quantity = floatval($line->quantity ?? 0);
                                                        $total_before_discount += $unit_price_inc_tax * $quantity;
                                                    }

                                                    if ($total_before_discount > 0) {
                                                        if ($discount_type_val === 'percentage') {
                                                            $discount = $total_before_discount * (floatval($discount_amount_val) / 100);
                                                            $amount_after_discount = $total_before_discount - $discount;
                                                            $amount = ($amount / $total_before_discount) * $amount_after_discount;
                                                        } elseif ($discount_type_val === 'fixed') {
                                                            $discount = floatval($discount_amount_val);
                                                            $amount_after_discount = $total_before_discount - $discount;
                                                            $amount = ($amount / $total_before_discount) * $amount_after_discount;
                                                        } else {
                                                            $amount = $final_total;
                                                        }
                                                    } else {
                                                        $amount = $final_total;
                                                    }
                                                } else {
                                                    $amount = $final_total;
                                                }
                                            }
                                        }
                                    }
                                }

                                $details .= ! empty($amount)
                                    ? '<br><b>Credit Sale Amount: </b>' . number_format($amount, $currency_precision, '.', '')
                                    : '';
                            }

                        }

                        if ($sub_type === 'cheque_realize') {
                            $transaction_payment_model = null;
                            $resolved_payee_name = '';
                            $resolved_bank_name = '';
                            $resolved_cheque_number = !empty($dep_trans_cheque_number) ? $dep_trans_cheque_number : $cheque_number;

                            $tp_id_value = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                            if (!empty($tp_id_value)) {
                                $transaction_payment_model = self::ma002Find(TransactionPayment::class, $tp_id_value);
                            } elseif (!empty($row->transaction_payment_id)) {
                                $transaction_payment_model = self::ma002Find(TransactionPayment::class, $row->transaction_payment_id);
                            }

                            $source_transaction = null;
                            if (!empty($transaction_payment_model) && !empty($transaction_payment_model->transaction_id)) {
                                $source_transaction = Transaction::with('contact')->find($transaction_payment_model->transaction_id);
                            } elseif (!empty($row->transaction_id)) {
                                $source_transaction = Transaction::with('contact')->find($row->transaction_id);
                            }

                            if (!empty($source_transaction)) {
                                if (!empty($source_transaction->contact)) {
                                    $resolved_payee_name = $source_transaction->contact->name;
                                } elseif (!empty($source_transaction->expense_for)) {
                                    $resolved_payee_name = $source_transaction->expense_for;
                                } elseif (!empty($source_transaction->created_by)) {
                                    $created_by_user = self::ma002Find(User::class, $source_transaction->created_by);
                                    if (!empty($created_by_user)) {
                                        $resolved_payee_name = trim(implode(' ', array_filter([
                                            $created_by_user->surname ?? null,
                                            $created_by_user->first_name ?? null,
                                            $created_by_user->last_name ?? null,
                                            $created_by_user->username ?? null,
                                        ])));
                                    }
                                } elseif (!empty($source_transaction->expense_category_id)) {
                                    $expense_category = self::ma002Find(ExpenseCategory::class, $source_transaction->expense_category_id);
                                    if (!empty($expense_category)) {
                                        $resolved_payee_name = $expense_category->name;
                                    }
                                }
                            }

                            if (!empty($transaction_payment_model)) {
                                if (!empty($transaction_payment_model->related_account_id)) {
                                    $related_account = self::ma002Find(Account::class, $transaction_payment_model->related_account_id);
                                    $resolved_bank_name = $related_account->name ?? '';
                                }

                                if (empty($resolved_bank_name) && !empty($transaction_payment_model->bank_name)) {
                                    $resolved_bank_name = $transaction_payment_model->bank_name;
                                }

                                if (empty($resolved_cheque_number) && !empty($transaction_payment_model->cheque_number)) {
                                    $resolved_cheque_number = $transaction_payment_model->cheque_number;
                                }
                            }

                            if (empty($resolved_payee_name) && !empty($row->note)) {
                                $note_lines = preg_split("/\r\n|\n|\r/", strip_tags($row->note));
                                $resolved_payee_name = trim($note_lines[0] ?? '');
                            }

                            if (empty($resolved_bank_name) && !empty($row->note)) {
                                $note_lines = preg_split("/\r\n|\n|\r/", strip_tags($row->note));
                                foreach ($note_lines as $note_line) {
                                    $trimmed_line = trim($note_line);
                                    if (stripos($trimmed_line, 'Post dated Cheque Issued from Bank ') === 0) {
                                        $resolved_bank_name = trim(substr($trimmed_line, strlen('Post dated Cheque Issued from Bank ')));
                                        break;
                                    }
                                }
                            }

                            $isIssuedPdChequeAccountBook = $account_id == $this->moduleUtil->account_exist_return_id('Issued Post Dated Cheques');
                            $isIssuedBankChequeAccountBook = !empty($resolved_payee_name) && !$isIssuedPdChequeAccountBook;

                            if ($isIssuedPdChequeAccountBook) {
                                $details = '<b>' . e($resolved_payee_name ?: 'N/A') . '</b>';
                                $details .= '<br>Post dated Cheque Issued from Bank ' . e($resolved_bank_name ?: 'N/A');
                            } elseif ($isIssuedBankChequeAccountBook) {
                                $details = '<b>' . e($resolved_payee_name) . '</b><br>Post dated Cheque Issued';
                            }

                            if (!empty($resolved_cheque_number) && !$isIssuedPdChequeAccountBook && !$isIssuedBankChequeAccountBook) {
                                $details .= '<br><b>' . __('cheque.cheque_number') . ':</b> ' . e($resolved_cheque_number);
                            }
                        }

                        if (empty(trim(strip_tags($details)))) {
                            $note = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');
                            if (! empty($note)) {
                                $details = nl2br(e($note));
                            }
                        }

                        if (empty(trim(strip_tags($details))) && ! empty($transaction)) {
                            $transaction_type = is_object($transaction) ? ($transaction->type ?? null) : ($transaction['type'] ?? null);
                            $transaction_ref_no = is_object($transaction) ? ($transaction->ref_no ?? null) : ($transaction['ref_no'] ?? null);
                            $transaction_invoice_no = is_object($transaction) ? ($transaction->invoice_no ?? null) : ($transaction['invoice_no'] ?? null);
                            $transaction_contact = is_object($transaction) ? ($transaction->contact ?? null) : ($transaction['contact'] ?? null);
                            $transaction_contact_name = is_object($transaction_contact)
                                ? ($transaction_contact->name ?? null)
                                : ($transaction_contact['name'] ?? null);

                            $fallback_lines = [];
                            if (! empty($transaction_type)) {
                                $fallback_lines[] = '<b>Transaction:</b> ' . e(ucwords(str_replace('_', ' ', $transaction_type)));
                            }
                            if (! empty($transaction_invoice_no)) {
                                $fallback_lines[] = '<b>Invoice No:</b> ' . e($transaction_invoice_no);
                            } elseif (! empty($transaction_ref_no)) {
                                $fallback_lines[] = '<b>Ref No:</b> ' . e($transaction_ref_no);
                            }
                            if (! empty($transaction_contact_name)) {
                                $fallback_lines[] = '<b>Contact:</b> ' . e($transaction_contact_name);
                            }

                            $details = implode('<br>', $fallback_lines);
                        }

                        if (empty(trim(strip_tags($details))) && ! empty($payment_for)) {
                            $contact = self::ma002Find(Contact::class, $payment_for);
                            if (! empty($contact)) {
                                $details = '<b>Contact:</b> ' . e($contact->name);
                            }
                        }

                        if (empty(trim(strip_tags($details))) && ! empty($payment_ref_no)) {
                            $details = '<b>Payment Ref No:</b> ' . e($payment_ref_no);
                        }

                        return $details;
                    })
                    ->addColumn('realize_date', function ($row) use ($paymentForContactTypes) {
                        $sub_type = is_array($row) ? ($row['sub_type'] ?? null) : ($row->sub_type ?? null);
                        $at_sub_type = is_array($row) ? ($row['at_sub_type'] ?? null) : ($row->at_sub_type ?? null);
                        $operation_date = is_array($row) ? ($row['operation_date'] ?? null) : ($row->operation_date ?? null);
                        $transaction = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);
                        $transaction_sub_type = is_object($transaction)
                            ? ($transaction->sub_type ?? null)
                            : (is_array($transaction) ? ($transaction['sub_type'] ?? null) : null);

                        // S763: for Supplier Payment rows, the Transaction Date is
                        // the date selected in the Supplier payment form (paid_on),
                        // not the time/date when the ledger row happened to be
                        // inserted. This applies to both the selected Payment Account
                        // and Accounts Payable.
                        $payment_for = is_array($row) ? ($row['payment_for'] ?? null) : ($row->payment_for ?? null);
                        $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);
                        $paid_on = is_array($row) ? ($row['paid_on'] ?? null) : ($row->paid_on ?? null);
                        $paymentForType = ! empty($payment_for)
                            ? ($paymentForContactTypes[(int) $payment_for] ?? '')
                            : '';
                        if (! empty($tp_id)
                            && in_array($paymentForType, ['supplier', 'both'], true)
                            && ! empty($paid_on)) {
                            return $this->commonUtil->format_date($paid_on, false);
                        }

                        // Cash/card deposits can have a parent transaction date of "today".
                        // For account books, the Transaction Date column must show the actual account movement date.
                        if (
                            in_array($sub_type, ['deposit'], true)
                            || in_array($at_sub_type, ['deposit'], true)
                            || in_array($transaction_sub_type, ['cash_deposit', 'card_payment'], true)
                        ) {
                            return $operation_date ? $this->commonUtil->format_date($operation_date, false) : '';
                        }

                        // First try to get transaction_date from the selected field (works even when transaction is deleted)
                        $dt = is_array($row) ? ($row['transaction_date'] ?? null) : ($row->transaction_date ?? null);

                        // If not available, try from transaction relationship
                        if (empty($dt) && ! empty($transaction)) {
                            $dt = is_object($transaction)
                                ? ($transaction->transaction_date ?? null)
                                : (is_array($transaction) ? ($transaction['transaction_date'] ?? null) : null);
                        }

                        // Fall back to operation_date if transaction_date is still empty
                        if (empty($dt)) {
                            $dt = $operation_date;
                        }

                        return $dt ? $this->commonUtil->format_date($dt, false) : '';
                    })
                    ->addColumn('cheque_date', function ($row) {
                        $cheque_date = is_array($row)
                            ? ($row['cheque_date'] ?? null)
                            : ($row->cheque_date ?? null);

                        return $this->commonUtil->format_date($cheque_date, false);
                    })
                    ->addColumn('is_deleted_expense', function ($row) {
                        // Helper column to identify deleted expense entries
                        $new_deleted_at = is_array($row) ? ($row['new_deleted_at'] ?? null) : ($row->new_deleted_at ?? null);

                        // Check account_transaction's new_deleted_at
                        if (!empty($new_deleted_at)) {
                            return '1';
                        }

                        // Check transaction relationship
                        $transaction = is_array($row) ? ($row['transaction'] ?? null) : ($row->transaction ?? null);

                        if (!empty($transaction)) {
                            // Get transaction type
                            $transaction_type = is_object($transaction) ? ($transaction->type ?? null) : ($transaction['type'] ?? null);

                            // Check if it's an expense transaction
                            if ($transaction_type === 'expense') {
                                // Check for soft delete (deleted_at)
                                $deleted_at = is_object($transaction) ? ($transaction->deleted_at ?? null) : ($transaction['deleted_at'] ?? null);
                                if (!empty($deleted_at)) {
                                    return '1';
                                }

                                // Check for new_deleted_at (used by some modules)
                                $new_deleted_at_txn = is_object($transaction) ? ($transaction->new_deleted_at ?? null) : ($transaction['new_deleted_at'] ?? null);
                                if (!empty($new_deleted_at_txn)) {
                                    return '1';
                                }
                            }
                        }

                        return '0';
                    })
                    ->addColumn('action', function ($row) use ($is_iframe, $card_account_id, $id) {
                        if ($is_iframe == 1) {
                            return '';
                        }

                        $html = '';

                        // Safe access for note
                        $note_html = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');

                        if ($id == $card_account_id) {
                            $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);

                            $card_type = TransactionPayment::leftJoin('account_transactions', 'transaction_payments.id', 'account_transactions.transaction_payment_id')
                                ->leftJoin('accounts', 'transaction_payments.card_type', 'accounts.id')
                                ->where('transaction_payments.id', $tp_id)
                                ->select('accounts.name', 'accounts.id')
                                ->first();

                            if (! empty($card_type)) {
                                $note_html = $card_type->name;
                            }
                        }

                        // Safe access for cheque_date
                        $cheque_date = is_array($row) ? ($row['cheque_date'] ?? null) : ($row->cheque_date ?? null);
                        $cheque_html = ! empty($cheque_date) ? __('lang_v1.cheque_date') . ': ' . $this->commonUtil->format_date($cheque_date) : '';

                        // Safe access for attachment
                        $attachment      = is_array($row) ? ($row['attachment'] ?? '') : ($row->attachment ?? '');
                        $attachment_html = '';
                        if (! empty($attachment)) {
                            if (strpos($attachment, 'jpg') || strpos($attachment, 'jpeg') || strpos($attachment, 'png')) {
                                $attachment_html = '<li><a href="#"
                                    data-href="' . action('AccountController@imageModal', ['title' => 'View', 'url' => url($attachment)]) . '"
                                    class="btn-modal"
                                    data-container=".view_modal">' . __('messages.view') . ' ' . __('lang_v1.image') . '</a></li>';
                            } else {
                                $attachment_html = '<li><a class="hide-in-iframe" href="' . url($attachment) . '">' . __('lang_v1.download') . ' ' . __('lang_v1.image') . '</a></li>';
                            }
                        }

                        // Safe access for added_by
                        $added_by = is_array($row) ? ($row['added_by'] ?? '') : ($row->added_by ?? '');

                        $html = '<div class="hide-in-iframe btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                        __('messages.actions') .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (! empty($note_html)) {
                            $html .= '<li><a class="note_btn" data-string="' . $note_html . '">' . __('lang_v1.note') . '</a></li>';
                        }

                        if (! empty($cheque_html)) {
                            $html .= '<li><a class="note_btn" data-string="' . $cheque_html . '">' . __('lang_v1.cheque_date') . '</a></li>';
                        }

                        $html .= '<li><a class="note_btn" data-string="' . __('lang_v1.added_by') . ': ' . $added_by . '">' . __('lang_v1.added_by') . '</a></li>';
                        $html .= $attachment_html;

                        if (class_exists(\App\Services\Authorization\SuperAdminImpersonation::class)
                            && \App\Services\Authorization\SuperAdminImpersonation::isActive(auth()->user(), request())) {
                            $id_safe = is_array($row) ? ($row['id'] ?? null) : ($row->id ?? null);
                            $html .= '<li><a data-href="' . action('AccountController@editAccountTransaction', [$id_safe]) . '" data-container=".at_modal" class="btn-modal edit_at_button"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    })
                    ->editColumn('attachment', function ($row) {
                        $action = '';
                        if (! empty($row->attachment)) {
                            if (strpos($row->attachment, 'jpg') || strpos($row->attachment, 'jpeg') || strpos($row->attachment, 'png')) {
                                $action = '<a href="#"
                                    data-href="' . action('AccountController@imageModal', ['title' => 'View', 'url' => url($row->attachment)]) . '"
                                    class="btn-modal btn-xs btn btn-primary"
                                    data-container=".view_modal">' . __('messages.view') . '</a>';
                            } else {
                                $action = '<a class="btn btn-default hide-in-iframe btn-xs" href="' . url($row->attachment) . '"><i class="fa fa-donwload"></i> ' . __('lang_v1.download') . '</a>';
                            }
                        }

                        return $action;
                    })
                    ->editColumn('note', function ($row) use ($card_account_id, $id) {
                        $note  = is_array($row) ? ($row['note'] ?? '') : ($row->note ?? '');
                        $tp_id = is_array($row) ? ($row['tp_id'] ?? null) : ($row->tp_id ?? null);

                        $html = $note;

                        if ($id == $card_account_id) {
                            $card_type = TransactionPayment::leftJoin('account_transactions', 'transaction_payments.id', 'account_transactions.transaction_payment_id')
                                ->leftJoin('accounts', 'transaction_payments.card_type', 'accounts.id')
                                ->where('transaction_payments.id', $tp_id)
                                ->select('accounts.name', 'accounts.id')
                                ->first();

                            if (! empty($card_type)) {
                                $html = $card_type->name;
                            }
                        }

                        if (! empty($html)) {
                            return '<button type="button" class="btn btn-xs note_btn" style="background: #8F3A84; color:#fff;" data-string="' . $html . '">' . __('lang_v1.note') . '</button>';
                        }

                        return '';
                    })
                    ->setRowAttr([

                        'class' => function ($row) {
                            if (! empty($row->deleted_at)) {
                                return 'deleted-row';
                            } else {
                                return '';
                            }
                        },
                    ])
                    ->addColumn('account_transaction_id', function ($row) {
                        return (int) $row->id;
                    })
                    ->addColumn('reconcile_state', function ($row) {
                        return (int) ((bool) ($row->reconcile_status ?? 0));
                    })
                    ->removeColumn('id')
                    ->removeColumn('is_closed')
                    // Compatibility-only key for old cached DataTables layouts.
                    // The visible Reconcile Status column remains removed.
                    ->editColumn('reconcile_status', function ($row) {
                        return '';
                    })
                    ->rawColumns(['opening_balance', 'note', 'credit', 'debit', 'balance', 'sub_type', 'action', 'attachment', 'description'])
                    ->editColumn('created_at', function ($row) {
                        $createdAt = is_array($row) ? ($row['created_at'] ?? null) : ($row->created_at ?? null);

                        return ! empty($createdAt)
                            ? $this->commonUtil->format_date($createdAt, true)
                            : '';
                    })

                    ->make(true);
            } catch (\Exception $e) {
                Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
                Log::emergency('Exception trace: ' . $e->getTraceAsString());

                // Return a proper DataTables-compatible error response
                // For client-side processing (serverSide: false), return empty data array
                return response()->json([
                    'data' => [],
                    'error' => __('messages.something_went_wrong') . ': ' . $e->getMessage()
                ], 500);
            }
        }

        $card_type_accounts = Account::where('business_id', $business_id)
            ->where('asset_type', $card_group_id)
            ->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')
            ->pluck('name', 'id');

        // The Account Book view does not use the cheque-number dropdown. Avoid
        // building it on every page request.
        $cheque_numbers = [];

        // Slip numbers are required only for a child card account. Limit the
        // query to this account instead of scanning every account transaction
        // in the business.
        $slipNos = [];
        if ((int) ($this_account->parent_account_id ?? 0) === (int) $card_account_id) {
            $slipNos = AccountTransaction::where('account_id', $id)
                ->where('business_id', $business_id)
                ->whereNotNull('slip_no')
                ->distinct()
                ->orderBy('slip_no')
                ->pluck('slip_no', 'slip_no')
                ->all();
        }

        // Customer and supplier filters use the Finance-owned Select2 endpoint.
        // Do not load every contact before the Account Book table can start.
        $customers = [];
        $suppliers = [];

        $account = $this_account;

        // Hide footer total row in Cash Account book (breakdown already in table; total is redundant)
        $cash_group_id   = AccountGroup::getGroupByName('Cash Account', true);
        $is_cash_account  = $cash_group_id && (int) $account->asset_type === (int) $cash_group_id;

        // dd($cheque_numbers);

        if (isset($account) && $account->is_main_account == 0) {
            // dd('1');
            return view('finance::account.show')
                ->with(compact('is_iframe', 'slipNos', 'account_access', 'account', 'card_account_id', 'card_type_accounts', 'id', 'cheque_in_hand_group_id', 'bank_group_id', 'cheque_return_account_id', 'cheque_numbers', 'customers', 'suppliers', 'is_cash_account', 'account_book_start_date', 'account_book_end_date'));
        } else {
            return view('finance::account.main_account_book')
                ->with(compact('is_iframe', 'slipNos', 'account_access', 'account', 'card_account_id', 'card_type_accounts', 'id', 'cheque_in_hand_group_id', 'bank_group_id', 'cheque_return_account_id', 'cheque_numbers', 'customers', 'suppliers', 'is_cash_account', 'account_book_start_date', 'account_book_end_date'));
        }
    }

    public function getMainAccountBook($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) (request()->session()->get('user.business_id') ?: request()->session()->get('business.id'));
        abort_if($businessId <= 0, 403, 'Business session is not available.');

        $mainAccount = Account::where('business_id', $businessId)
            ->where('id', (int) $id)
            ->firstOrFail(['id']);

        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');
        $transactionType = request()->input('type');

        $accounts = Account::query()
            ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->where('accounts.parent_account_id', $mainAccount->id)
            ->where('accounts.business_id', $businessId)
            ->whereNull('accounts.deleted_at')
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.account_number',
                'ats.name as account_type_name',
                'pat.name as parent_account_type_name',
            ])
            ->selectSub(function ($balanceQuery) use ($startDate, $endDate, $transactionType): void {
                $balanceQuery->from('account_transactions as atb')
                    ->leftJoin('transaction_payments as tpb', 'tpb.id', '=', 'atb.transaction_payment_id')
                    ->whereColumn('atb.account_id', 'accounts.id')
                    ->whereNull('atb.deleted_at')
                    ->where(function ($paymentQuery): void {
                        $paymentQuery->whereNull('atb.transaction_payment_id')
                            ->orWhereNull('tpb.deleted_at');
                    })
                    ->when(! empty($startDate), function ($dateQuery) use ($startDate): void {
                        $dateQuery->where('atb.operation_date', '>=', $startDate . ' 00:00:00');
                    })
                    ->when(! empty($endDate), function ($dateQuery) use ($endDate): void {
                        $dateQuery->where('atb.operation_date', '<=', $endDate . ' 23:59:59');
                    })
                    ->when(in_array($transactionType, ['debit', 'credit'], true), function ($typeQuery) use ($transactionType): void {
                        $typeQuery->where('atb.type', $transactionType);
                    })
                    ->selectRaw("COALESCE(SUM(CASE WHEN atb.type = 'credit' THEN -1 * atb.amount ELSE atb.amount END), 0)");
            }, 'debit_normal_balance')
            ->orderBy('accounts.name', 'asc');

        return DataTables::of($accounts)
            ->addColumn('balance', function ($row) {
                $balance = (float) ($row->debit_normal_balance ?? 0);
                $typeName = strtolower((string) ($row->parent_account_type_name ?: $row->account_type_name));

                if (str_contains($typeName, 'liabilit') || str_contains($typeName, 'equity') || str_contains($typeName, 'income')) {
                    $balance *= -1;
                }

                return '<span class="display_currency balance" data-currency_symbol="false" data-orig-value="' .
                    $balance . '">' . $balance . '</span>';
            })
            ->editColumn('name', function ($row) {
                return '<a class="account-link" data-account-id="' . (int) $row->id . '" href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '">' . e($row->name) . '</a>';
            })
            ->editColumn('account_number', function ($row) {
                return '<a class="account-link" data-account-id="' . (int) $row->id . '" href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '">' . e((string) $row->account_number) . '</a>';
            })
            ->rawColumns(['balance', 'name', 'account_number'])
            ->make(true);
    }

    /**
     * Get individual account transactions for accounts with no sub-accounts
     * when accessed via main account book route
     */

    private function getIndividualAccountTransactions($account_id)
    {
        $business_id = request()->session()->get('user.business_id');
        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');
        $transaction_type = request()->input('type');

        // Get the account details
        $account = Account::where('business_id', $business_id)
            ->where('id', $account_id)
            ->first();

        if (!$account) {
            return response()->json(['data' => []]);
        }

        // Get account transactions
        $transactions = AccountTransaction::join('accounts as A', 'account_transactions.account_id', '=', 'A.id')
            ->leftJoin('transaction_payments AS TP', function ($join) {
                $join->on('TP.id', '=', 'account_transactions.transaction_payment_id');
            })
            ->leftJoin('users AS u', 'account_transactions.created_by', '=', 'u.id')
            ->leftJoin('account_types as ats', 'A.account_type_id', '=', 'ats.id')
            ->leftJoin('account_groups as ag', 'A.asset_type', '=', 'ag.id')
            ->leftJoin('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('A.id', $account_id)
            ->whereNull('account_transactions.deleted_at')
            ->where(function ($query) {
                $query->whereNull('account_transactions.transaction_payment_id')
                    ->orWhereNull('TP.deleted_at');
            });

        // Apply date filters
        if (!empty($start_date) && !empty($end_date)) {
            /*
             * MA-002: filter on the TRANSACTION date, not the entry date.
             *
             * account_transactions.operation_date is meant to hold the date of
             * the underlying transaction, and on the tenant I checked it does
             * for 558 of 569 rows. But 28 code paths write date('Y-m-d H:i:s')
             * or now() into it instead - some legitimately, such as a reversal
             * raised today against an old purchase, and some not.
             *
             * The effect is that a purchase dated 3 August sits in the book
             * under 5 August, and a date range that should include it does not.
             *
             * The book now prefers transactions.transaction_date and falls back
             * to operation_date only where there is no parent transaction -
             * opening balances and manual entries, which have no other date.
             *
             * Fixed HERE rather than in the 28 write paths: one place, it
             * cannot change any stored value, and reversal entries keep their
             * own correct dates.
             */
            $transactions->whereBetween(
                \DB::raw('COALESCE(transactions.transaction_date, account_transactions.operation_date)'),
                [$start_date . ' 00:00:00', $end_date . ' 23:59:59']
            );
        }

        // Apply transaction type filter
        if (!empty($transaction_type)) {
            $transactions->where('account_transactions.type', $transaction_type);
        }

        $transactions = $transactions->select([
                'account_transactions.id',
                'account_transactions.operation_date',
                'account_transactions.type',
                'account_transactions.amount',
                'account_transactions.note',
                'account_transactions.reconcile_status',
                'A.account_number',
                'A.name'
            ])
            // MA-002: ordered by transaction date for the same reason - the book
            // should read in the order things actually happened, not the order
            // they were keyed in.
            ->orderBy(\DB::raw('COALESCE(transactions.transaction_date, account_transactions.operation_date)'), 'desc')
            ->get();

        // Format transactions for main account book format
        $data = [];
        $running_balance = 0;

        // Get opening balance if date range is specified
        if (!empty($start_date)) {
            $running_balance = Account::getAccountBalance($account_id, null, $start_date, true, false, false);
        }

        foreach ($transactions as $transaction) {
            $debit = $transaction->type == 'debit' ? $transaction->amount : 0;
            $credit = $transaction->type == 'credit' ? $transaction->amount : 0;

            // Calculate running balance
            $account_type_name = optional($account->account_type)->name;
            if ($account_type_name == "Assets" || $account_type_name == "Expenses" || $account_type_name == "Current Assets" || $account_type_name == "Fixed Assets") {
                $running_balance = $running_balance + $debit - $credit;
            } else {
                $running_balance = $running_balance + $credit - $debit;
            }

            $data[] = [
                'account_number' => $transaction->account_number,
                'name' => $transaction->name,
                'balance' => $running_balance
            ];
        }

        // Return single account entry with balance
        if (!empty($data)) {
            $final_balance = end($data)['balance'];
            return response()->json([
                'data' => [[
                    'account_number' => $account->account_number,
                    'name' => $account->name,
                    'balance' => $final_balance
                ]]
            ]);
        }

        // Return account with zero balance if no transactions
        return response()->json([
            'data' => [[
                'account_number' => $account->account_number,
                'name' => $account->name,
                'balance' => 0
            ]]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */

    public function accountBookRedirect(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
        $accountId = (int) ($request->input('account_id') ?: $request->input('id'));

        if ($accountId > 0) {
            $exists = Account::where('business_id', $businessId)->where('id', $accountId)->exists();
            if ($exists) {
                return redirect()->to(route('finance.list-accounts.live.account_book.show', ['id' => $accountId], false));
            }
        }

        return redirect()->route('finance.list-accounts.live');
    }

    /**
     * Small Select2 endpoint for Account Book customer/supplier filters.
     * Large contact lists are not loaded while opening the Account Book page.
     */

    public function accountBookContactOptions(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
        $contactType = $request->query('type') === 'supplier' ? 'supplier' : 'customer';
        $term = trim((string) $request->query('q', ''));

        $types = $contactType === 'supplier' ? ['supplier', 'both'] : ['customer', 'both'];
        $contacts = Contact::query()
            ->where('business_id', $businessId)
            ->whereIn('type', $types)
            ->where('active', 1)
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%' . $term . '%';
                $query->where(function ($search) use ($like): void {
                    $search->where('name', 'like', $like)
                        ->orWhere('contact_id', 'like', $like)
                        ->orWhere('supplier_business_name', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'contact_id', 'supplier_business_name']);

        $results = $contacts->map(function ($contact) use ($contactType): array {
            $text = (string) $contact->name;
            if ($contactType === 'supplier' && ! empty($contact->supplier_business_name)) {
                $text .= ' (' . $contact->supplier_business_name . ')';
            }
            if (! empty($contact->contact_id)) {
                $text .= ' - ' . $contact->contact_id;
            }

            return ['id' => (int) $contact->id, 'text' => $text];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Lazy Select2 values for the Cheques Opening Balance tab.
     */

    public function accountBookData($id, Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
        abort_if($businessId <= 0, 403, 'Business session is not available.');

        try {
            $payload = app(AccountBookDataService::class)->datatable(
                (int) $id,
                $businessId,
                $request
            );

            return response()->json($payload);
        } catch (\Throwable $optimizedException) {
            // The optimized reader is preferred, but Account Book must remain
            // operational across older tenant schemas and partially migrated
            // databases. Fall back to the established AJAX branch in show()
            // instead of leaving DataTables permanently in Processing state.
            Log::warning('Finance optimized Account Book reader failed; using legacy reader', [
                'account_id' => (int) $id,
                'business_id' => $businessId,
                'message' => $optimizedException->getMessage(),
                'file' => $optimizedException->getFile(),
                'line' => $optimizedException->getLine(),
            ]);

            try {
                $request->headers->set('X-Requested-With', 'XMLHttpRequest');
                $request->headers->set('Accept', 'application/json');

                return $this->show((int) $id, $request);
            } catch (\Throwable $legacyException) {
                Log::error('Finance Account Book optimized and legacy readers both failed', [
                    'account_id' => (int) $id,
                    'business_id' => $businessId,
                    'optimized_message' => $optimizedException->getMessage(),
                    'legacy_message' => $legacyException->getMessage(),
                    'legacy_file' => $legacyException->getFile(),
                    'legacy_line' => $legacyException->getLine(),
                ]);

                $clientMessage = config('app.debug')
                    ? 'Unable to load the Account Book data: ' . $legacyException->getMessage()
                    : 'Unable to load the Account Book data.';

                return response()->json([
                    'draw' => (int) $request->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => $clientMessage,
                ], 500);
            }
        }
    }

    public function chequeOpeningFilterOptions(Request $request)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
        abort_if($businessId <= 0, 403, 'Business session is not available.');

        $fieldMap = [
            'bank' => 'tp.bank_name',
            'cheque' => 'tp.cheque_number',
            'amount' => 'tp.amount',
        ];
        $field = (string) $request->query('field', 'bank');
        abort_unless(isset($fieldMap[$field]), 422, 'Invalid filter field.');

        $column = $fieldMap[$field];
        $term = trim((string) $request->query('q', ''));

        $values = DB::table('transactions as t')
            ->join('transaction_payments as tp', 'tp.transaction_id', '=', 't.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'cheque_opening_balance')
            ->whereNull('t.deleted_at')
            ->whereNull('tp.deleted_at')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->when($term !== '', function ($query) use ($column, $term): void {
                $query->whereRaw('CAST(' . $column . ' AS CHAR) LIKE ?', ['%' . $term . '%']);
            })
            ->distinct()
            ->orderBy($column)
            ->limit(50)
            ->pluck($column);

        return response()->json([
            'results' => $values->map(static function ($value): array {
                $text = (string) $value;
                return ['id' => $text, 'text' => $text];
            })->values(),
        ]);
    }

    public function getDescription($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        $transaction_sell_line = PropertySellLine::where('transaction_id', $id)->first();
        $property              = Property::leftjoin('property_blocks', 'properties.id', 'property_blocks.property_id')
            ->where('properties.id', $transaction_sell_line->property_id)->where('property_blocks.id', $transaction_sell_line->block_id)->first();
        $data['name']     = '<br><b>Project Name: </b>' . $property->name;
        $data['block_no'] = '<br><b>Block Number: </b>' . $property->block_number;

        return $data;
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */

    private function getPaymentDescriptionForAccountRow($row)
    {
        $transaction = null;
        if (is_object($row)) {
            $transaction = $row->transaction ?? null;
        } elseif (is_array($row)) {
            $transaction = $row['transaction'] ?? null;
        }

        $type = null;
        if (! empty($transaction)) {
            $type = is_object($transaction) ? ($transaction->type ?? null) : ($transaction['type'] ?? null);
        }

        switch ($type) {
            case 'settlement_cash_payment':
            case 'daily_collection':
            case 'settlement_cash':
                return 'Cash Payment';
            case 'settlement_card_payment':
            case 'daily_card_payment':
                return 'Card Payment';
            case 'daily_credit_payment':
                return 'Cheque Payment';
            default:
                return '';
        }
    }

/**
 * Get settlement number from daily_cards table using slip_no
 */

    private function extractSettlementNoFromNote($note)
    {
        if (empty($note)) {
            return null;
        }
        // Settlement No: ST1 or similar
        if (preg_match('/Settlement\s*No\.?\s*[:#-]?\s*([A-Za-z0-9\-]+)/i', $note, $matches)) {
            return $matches[1];
        }
        // Settlement #ST1
        if (preg_match('/settlement\s*#\s*([A-Za-z0-9\-]+)/i', $note, $matches)) {
            return $matches[1];
        }
        // Pattern to match "Direct Settlement: SET-SWXXX"
        if (preg_match('/Direct Settlement:\s*([A-Za-z0-9\-]+)/', $note, $matches)) {
            return $matches[1];
        }

        // Alternative pattern if the format is different
        if (preg_match('/SET-SW[A-Za-z0-9\-]+/', $note, $matches)) {
            return $matches[0];
        }
        // Generic ST-prefixed settlement numbers
        if (preg_match('/ST[A-Za-z0-9\-]+/', $note, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private function getSettlementNoFromDailyCards($slip_no)
    {
        if (empty($slip_no)) {
            return null;
        }

        $daily_card = DB::table('daily_cards')
            ->where('slip_no', $slip_no)
            ->select('settlement_no')
            ->first();

        return $daily_card->settlement_no ?? null;
    }

/**
 * Get settlement number for credit payments with multiple fallbacks
 */

    private function getSettlementNoForCreditPayment($row)
    {
        // First try: Extract from note field
        if (! empty($row->note)) {
            $settlement_no = $this->extractSettlementNoFromNote($row->note);
            if ($settlement_no) {
                return $settlement_no;
            }
        }

        // Second try: Get from daily_cards using slip_no
        if (! empty($row->slip_no)) {
            $settlement_no = $this->getSettlementNoFromDailyCards($row->slip_no);
            if ($settlement_no) {
                return $settlement_no;
            }
        }

        // Third try: Check transaction ref_no or invoice_no
        if (! empty($row->transaction->ref_no) && preg_match('/SET-SW[A-Za-z0-9\-]+/', $row->transaction->ref_no, $matches)) {
            return $matches[0];
        }

        if (! empty($row->transaction->invoice_no) && preg_match('/SET-SW[A-Za-z0-9\-]+/', $row->transaction->invoice_no, $matches)) {
            return $matches[0];
        }

        return 'N/A';
    }

    /**
     * Show the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse|Response
     */
    /**
     * Finance-owned, server-side Account Book data endpoint.
     *
     * Only the requested page is returned, so large Finished Goods and
     * Accounts Payable books do not load every historical row into PHP.
     */
    /**
     * Resolve old/cached Account Book URLs that omitted the account id.
     */

    public function getNotes($id)
    {
        if (! $this->userCan('account.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $business_id   = session()->get('user.business_id');
            $account_notes = Account::join('account_transactions', 'accounts.id', 'account_transactions.account_id')
                ->where('business_id', $business_id)
                ->where('accounts.id', $id)
                ->NotClosed()
                ->select('account_transactions.operation_date', 'account_transactions.note')
                ->get();

            // modified by iftekhar
            return view('finance::account.notes')
                ->with(compact('account_notes'));
        }
    }
}
