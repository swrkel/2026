<?php

namespace Modules\Finance\Http\Controllers\Accounts;

use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountTransaction;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ListDepositTransferController extends Controller
{
    /**
     * Standalone Finance module List Deposits & Transfers report.
     *
     * This controller intentionally keeps the report outside the legacy
     * app/Http/Controllers/AccountController.php file. The query mirrors the
     * old working report logic: read debit account_transactions rows with
     * sub_type deposit/fund_transfer and use transfer_transaction_id to find
     * the from-account side of the pair.
     */
    public function index(Request $request)
    {
        if (! $request->ajax()) {
            return redirect('/finance/account?ldt_tab=1');
        }

        try {
            $business_id = (int) $request->session()->get('user.business_id');
            $schema = AccountTransaction::query()->getModel()->getConnection()->getSchemaBuilder();
            $hasPaymentParent = $schema->hasColumn('transaction_payments', 'parent_id');

            $query = AccountTransaction::query()
                ->leftJoin('accounts as account_to', 'account_to.id', '=', 'account_transactions.account_id')
                ->leftJoin('account_transactions as account_transactions_from', 'account_transactions.transfer_transaction_id', '=', 'account_transactions_from.id')
                ->leftJoin('accounts as account_from', 'account_from.id', '=', 'account_transactions_from.account_id')
                ->leftJoin('transaction_payments', 'account_transactions.transaction_payment_id', '=', 'transaction_payments.id')
                // IS2067 #3: a deposited customer cheque can be linked to its
                // customer either through the source transaction OR directly
                // through transaction_payments.payment_for. Keep both paths.
                ->leftJoin('transactions', function ($join) {
                    $join->on(
                        'transactions.id',
                        '=',
                        \DB::raw('COALESCE(account_transactions.transaction_id, transaction_payments.transaction_id)')
                    );
                })
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('contacts as payment_contacts', 'transaction_payments.payment_for', '=', 'payment_contacts.id')
                ->leftJoin('users', 'account_transactions.created_by', '=', 'users.id');

            // Bulk customer payments can keep the customer on the parent
            // payment. Join it only on schemas that actually have parent_id so
            // older tenant databases remain compatible.
            if ($hasPaymentParent) {
                $query->leftJoin('transaction_payments as parent_payment', 'transaction_payments.parent_id', '=', 'parent_payment.id')
                    ->leftJoin('contacts as parent_payment_contacts', 'parent_payment.payment_for', '=', 'parent_payment_contacts.id');
            }

            $customerNameSql = $hasPaymentParent
                ? "COALESCE(NULLIF(contacts.name, ''), NULLIF(payment_contacts.name, ''), NULLIF(parent_payment_contacts.name, ''), '')"
                : "COALESCE(NULLIF(contacts.name, ''), NULLIF(payment_contacts.name, ''), '')";

            $query->where('account_transactions.type', 'debit')
                ->whereIn('account_transactions.sub_type', ['deposit', 'fund_transfer'])
                ->where('account_transactions.business_id', $business_id)
                ->where('account_to.business_id', $business_id)
                ->where(function ($q) use ($business_id) {
                    $q->whereNull('account_from.id')
                      ->orWhere('account_from.business_id', $business_id);
                })
                ->select([
                    'account_transactions.id',
                    'account_transactions.operation_date',
                    // IS2054 #2: fallback when operation_date holds a zero date.
                    'account_transactions.created_at',
                    'account_transactions.sub_type',
                    'account_transactions.amount',
                    'account_transactions.cheque_number',
                    'account_transactions.created_by',
                    'account_transactions.account_id as to_account_id',
                    'account_transactions_from.account_id as from_account_id',
                    'account_from.name as from_account',
                    'account_to.name as to_account',
                    'users.username',
                    \DB::raw($customerNameSql . ' as customer_name'),
                ]);


            [$start_date, $end_date] = $this->resolveDateRange($request);
            if (! empty($start_date) && ! empty($end_date)) {
                /*
                 | IS2054 #2: a zero operation_date must not hide the row.
                 |
                 | A row holding 0000-00-00 fails both comparisons, so filtering by
                 | date dropped those deposits from the list altogether - they could
                 | not be seen at all, let alone show a date.
                 |
                 | Such rows are matched on created_at instead, which is the date the
                 | deposit was actually entered and is what the column now displays
                 | for them. Rows with a real operation_date are filtered exactly as
                 | before.
                 */
                $query->where(function ($dateQuery) use ($start_date, $end_date) {
                    $dateQuery->where(function ($validDate) use ($start_date, $end_date) {
                        $validDate->whereDate('account_transactions.operation_date', '>=', $start_date)
                                  ->whereDate('account_transactions.operation_date', '<=', $end_date);
                    })->orWhere(function ($zeroDate) use ($start_date, $end_date) {
                        $zeroDate->where(function ($isZero) {
                            $isZero->whereNull('account_transactions.operation_date')
                                   ->orWhere('account_transactions.operation_date', 'like', '0000-00-00%');
                        })
                        ->whereDate('account_transactions.created_at', '>=', $start_date)
                        ->whereDate('account_transactions.created_at', '<=', $end_date);
                    });
                });
            }

            if ($this->hasFilter($request->input('sub_type'))) {
                $query->where('account_transactions.sub_type', $request->input('sub_type'));
            }

            if ($this->hasFilter($request->input('from_account_id'))) {
                $query->where('account_transactions_from.account_id', $request->input('from_account_id'));
            }

            if ($this->hasFilter($request->input('to_account_id'))) {
                $query->where('account_transactions.account_id', $request->input('to_account_id'));
            }

            if ($this->hasFilter($request->input('user_id'))) {
                $query->where('account_transactions.created_by', $request->input('user_id'));
            }

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $url = url('/finance/edit-deposit-transfer/' . $row->id);
                    return '<button data-href="' . e($url) . '" data-container=".account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>';
                })
                ->editColumn('operation_date', function ($row) {
                    /*
                     |------------------------------------------------------------------
                     | IS2054 #2: the Date column was blank on some deposits.
                     |------------------------------------------------------------------
                     |
                     | operation_date holds MySQL's zero date on rows written by the
                     | older save path:
                     |
                     |     0000-00-00 00:00:00
                     |
                     | That is not empty, so the ! empty() test passed and the value
                     | was handed to format_date(), which cannot parse it and returns
                     | nothing - leaving the cell blank with no clue why.
                     |
                     | A zero date now falls back to created_at, which every row has
                     | and which is the date the deposit was actually entered. If that
                     | is unusable too the cell shows a dash rather than nothing, so a
                     | missing date is visible instead of looking like a rendering
                     | fault.
                     |
                     | Display only - no stored value is changed.
                     */
                    $raw = (string) ($row->operation_date ?? '');
                    $isUnusable = $raw === ''
                        || \Illuminate\Support\Str::startsWith($raw, '0000-00-00');

                    if ($isUnusable) {
                        $raw = (string) ($row->created_at ?? '');
                    }

                    if ($raw === '' || \Illuminate\Support\Str::startsWith($raw, '0000-00-00')) {
                        return '-';
                    }

                    try {
                        // IS2067 #1: this column is explicitly Date & Time.
                        // format_date() drops the time component, so format the
                        // stored transaction timestamp with the business date
                        // and time preferences instead.
                        $date = Carbon::parse($raw);
                        $dateFormat = session('business.date_format', 'd/m/Y');
                        $timeFormat = (int) session('business.time_format', 24) === 24
                            ? 'H:i'
                            : 'h:i A';

                        return $date->format($dateFormat . ' ' . $timeFormat);
                    } catch (\Throwable $e) {
                        return '-';
                    }
                })
                ->editColumn('customer_name', function ($row) {
                    return ! empty($row->customer_name) ? e($row->customer_name) : '-';
                })
                ->editColumn('sub_type', function ($row) {
                    return $row->sub_type === 'fund_transfer' ? __('lang_v1.transfer') : ucfirst((string) $row->sub_type);
                })
                ->editColumn('amount', function ($row) {
                    $amount = (float) $row->amount;
                    return '<span class="display_currency finance-ldt-amount" data-currency_symbol="false" data-orig-value="' . $amount . '">' . @num_format($amount) . '</span>';
                })
                // Keep the former key as a compatibility alias for any cached JS.
                ->addColumn('amount_formatted', function ($row) {
                    $amount = (float) $row->amount;
                    return '<span class="display_currency finance-ldt-amount" data-currency_symbol="false" data-orig-value="' . $amount . '">' . @num_format($amount) . '</span>';
                })
                ->editColumn('from_account', function ($row) {
                    return ! empty($row->from_account) ? e($row->from_account) : '-';
                })
                ->editColumn('to_account', function ($row) {
                    return ! empty($row->to_account) ? e($row->to_account) : '-';
                })
                ->editColumn('cheque_number', function ($row) {
                    return ! empty($row->cheque_number) ? e($row->cheque_number) : '';
                })
                ->editColumn('username', function ($row) {
                    return ! empty($row->username) ? e($row->username) : '-';
                })
                ->rawColumns(['action', 'amount', 'amount_formatted'])
                ->make(true);
        } catch (\Throwable $e) {
            Log::error('Finance List Deposit Transfer AJAX failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to load List Deposits & Transfers. Please check Laravel log for Finance List Deposit Transfer AJAX failed.',
            ], 200);
        }
    }

    private function hasFilter($value): bool
    {
        return ! in_array($value, [null, '', 'all', 'All', 'null', 'NULL', 'undefined', 'None', 'none', 0, '0'], true);
    }

    private function resolveDateRange(Request $request): array
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        if (! empty($start) && ! empty($end)) {
            return [$this->normaliseDate($start), $this->normaliseDate($end)];
        }

        $range = $request->input('list_deposit_transfer_date_range');
        if (empty($range)) {
            return [null, null];
        }

        $parts = preg_split('/\s*(~| to | - )\s*/i', $range);
        if (count($parts) < 2) {
            return [null, null];
        }

        return [$this->normaliseDate($parts[0]), $this->normaliseDate($parts[1])];
    }

    private function normaliseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse(trim($date))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
