<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Account;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\AccountType;
use App\AccountGroup;
use App\ContactGroup;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\AccountTransaction;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use LDAP\Result;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Utils\ContactUtil;
use Spatie\Activitylog\Models\Activity;
use Modules\Customers\Services\CustomerPaymentActionService;

class CustomerStandalonePaymentController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $contactUtil;
    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, ContactUtil $contactUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
    }


    private function activePackageDetails(int $businessId): array
    {
        $details = optional(Subscription::active_subscription($businessId))->package_details;
        if (is_string($details)) {
            $decoded = json_decode($details, true);
            $details = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        return is_array($details) ? $details : [];
    }

    private function nextPaymentReferenceSequence(int $businessId, ?string $paidInType = null): int
    {
        $query = DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at');

        if ($paidInType !== null) {
            $query->where('paid_in_type', $paidInType);
        }

        $reference = (string) $query->orderByDesc('id')->value('payment_ref_no');
        preg_match('/(\d+)\s*$/', $reference, $matches);

        return ((int) ($matches[1] ?? 0)) + 1;
    }

    /**
     * Stable grouping key used by the Customer Payments list.
     *
     * Customer-page payments are represented by their parent payment. Other
     * payments are grouped by payment reference. Payments without either value
     * remain separate by their own ID.
     */
    private function paymentGroupExpression(string $alias): string
    {
        return "CASE
            WHEN {$alias}.paid_in_type = 'customer_page' AND {$alias}.parent_id IS NOT NULL
                THEN CONCAT('parent:', {$alias}.parent_id)
            WHEN {$alias}.payment_ref_no IS NOT NULL AND {$alias}.payment_ref_no <> ''
                THEN CONCAT('reference:', {$alias}.payment_ref_no)
            ELSE CONCAT('payment:', {$alias}.id)
        END";
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        $business_details = Business::findOrFail($business_id);
        $paid_in_types = [
            'customer_page' => 'Customer Page',
            'all_sale_page' => 'All Sale Page',
            'settlement' => 'Settlement',
            'customer_bulk' => 'Customer Bulk',
            'customer_simple' => 'Customer Simple'
        ];
        $latest_ref_number = 1;
        $latest_ref_number_PP = 1;
        $latest_ref_number_CPB = 1;
        $latest_ref_number_CPS = 1;

        if (request()->ajax()) {
            $request = request();

            /*
             * Build one representative payment row per logical payment group.
             * This replaces the former GROUP BY on the full listing query,
             * which fails on tenant connections using ONLY_FULL_GROUP_BY.
             */
            /*
             |------------------------------------------------------------------
             | LA-1145: payments made against the customer's balance were invisible
             |------------------------------------------------------------------
             |
             | This started as an INNER JOIN from transaction_payments to
             | transactions on grouped_tp.transaction_id.
             |
             | CustomerPaymentActionService::createTransactionPayment() - the code
             | behind Customer Register -> Pay due Amount / Advance Payment, and
             | the bulk and statement payment paths - never sets transaction_id.
             | A payment against the customer's BALANCE is not a payment against
             | one invoice, so it records the customer in `payment_for` and leaves
             | transaction_id NULL.
             |
             | An inner join on a NULL column matches nothing, so every one of
             | those payments was silently dropped from this listing. That is the
             | reported "Customer payment details are not loading".
             |
             | Now: the join to transactions is a LEFT join, the contact is taken
             | from the transaction when there is one and from payment_for when
             | there is not, and business scoping comes from the payment row
             | itself rather than from a transaction that may not exist.
             |
             | The invoice-linked conditions (payment_status, transaction type)
             | are only applied to rows that actually have a transaction, so
             | nothing that used to appear stops appearing.
             */
            $eligibleGroupExpression = $this->paymentGroupExpression('grouped_tp');
            $eligibleGroups = DB::table('transaction_payments as grouped_tp')
                ->leftJoin('transactions as grouped_transactions', 'grouped_transactions.id', '=', 'grouped_tp.transaction_id')
                ->join('contacts as grouped_contacts', function ($join) {
                    $join->on('grouped_contacts.id', '=', DB::raw('COALESCE(grouped_transactions.contact_id, grouped_tp.payment_for)'));
                })
                ->where('grouped_tp.business_id', $business_id)
                ->where('grouped_contacts.type', 'customer')
                ->whereNull('grouped_tp.deleted_at')
                ->where(function ($query) {
                    // Direct customer payments: always eligible, there is no
                    // invoice whose status could qualify them.
                    $query->whereNull('grouped_tp.transaction_id')
                        ->orWhere(function ($invoiceLinked) {
                            $invoiceLinked
                                ->whereIn('grouped_transactions.payment_status', ['paid', 'partial'])
                                ->where(function ($typeCheck) {
                                    $typeCheck->whereIn('grouped_transactions.type', $this->contactUtil->payable_customer_txns)
                                        ->orWhere('grouped_transactions.is_credit_sale', 1);
                                });
                        });
                });

            if ($request->filled('customer_id')) {
                $eligibleGroups->where('grouped_contacts.id', $request->customer_id);
            }

            $shiftNumber = $request->input('daily_shift_no', $request->input('shift_number'));
            if (!empty($shiftNumber)) {
                $eligibleGroups->where('grouped_tp.shift_number', $shiftNumber);
            }

            /*
             * LA-1146: bill_no and location_id live on the TRANSACTION.
             *
             * A payment taken through Customer Register -> Pay Due Amount has no
             * transaction (transaction_id is NULL, the customer is in
             * payment_for), so after the LEFT join both columns are NULL and a
             * plain equality test silently drops every one of those rows.
             *
             * location_id is the one that actually bit: the Location dropdown is
             * populated from BusinessLocation::forDropdown() with no blank
             * option, so a single-location business always submits a location and
             * the filter is ALWAYS on. That is why only Bulk Payments were
             * listed - those allocate to invoices and therefore carry a
             * transaction and a location - while Pay Due Amount entries never
             * appeared at all.
             *
             * A balance payment is not filed under any location or invoice, so it
             * is kept regardless. Filtering by location still narrows the
             * invoice-linked rows exactly as before.
             */
            if ($request->filled('bill_no')) {
                $eligibleGroups->where(function ($query) use ($request) {
                    $query->where('grouped_transactions.invoice_no', $request->bill_no)
                        ->orWhereNull('grouped_tp.transaction_id');
                });
            }
            if ($request->filled('payment_ref_no')) {
                $eligibleGroups->where('grouped_tp.payment_ref_no', $request->payment_ref_no);
            }
            if ($request->filled('cheque_number')) {
                $eligibleGroups->where('grouped_tp.cheque_number', $request->cheque_number);
            }
            if ($request->filled('payment_method')) {
                $eligibleGroups->where('grouped_tp.method', $request->payment_method);
            }
            if ($request->filled('location_id')) {
                $eligibleGroups->where(function ($query) use ($request) {
                    $query->where('grouped_transactions.location_id', $request->location_id)
                        ->orWhereNull('grouped_tp.transaction_id');
                });
            }
            if ($request->filled('paid_in_type')) {
                $eligibleGroups->where('grouped_tp.paid_in_type', $request->paid_in_type);
            } else {
                // Direct-settlement cash/card rows are intentionally excluded from this list.
                $eligibleGroups->where(function ($query) {
                    $query->whereNull('grouped_tp.paid_in_type')
                        ->orWhere('grouped_tp.paid_in_type', '!=', 'settlement');
                });
            }
            if ($request->filled('payment_amount')) {
                $eligibleGroups->where('grouped_tp.amount', $request->payment_amount);
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $eligibleGroups->whereDate('grouped_tp.paid_on', '>=', $request->start_date)
                    ->whereDate('grouped_tp.paid_on', '<=', $request->end_date);
            }

            $eligibleGroups
                ->selectRaw('MAX(grouped_tp.id) as representative_tp_id')
                ->selectRaw($eligibleGroupExpression . ' as payment_group_key')
                ->groupBy(DB::raw($eligibleGroupExpression));

            $referenceGroupExpression = $this->paymentGroupExpression('reference_payments');
            $referenceTotals = DB::table('transaction_payments as reference_payments')
                ->where('reference_payments.business_id', $business_id)
                ->whereNull('reference_payments.deleted_at')
                ->selectRaw($referenceGroupExpression . ' as payment_group_key')
                ->selectRaw('SUM(reference_payments.amount) as reference_total')
                ->groupBy(DB::raw($referenceGroupExpression));

            $interestGroupExpression = $this->paymentGroupExpression('interest_payments');
            $interestTotals = DB::table('transaction_payments as interest_payments')
                ->join('account_transactions as interest_accounts', function ($join) {
                    $join->on('interest_accounts.transaction_payment_id', '=', 'interest_payments.id')
                        ->whereNull('interest_accounts.deleted_at');
                })
                ->where('interest_payments.business_id', $business_id)
                ->whereNull('interest_payments.deleted_at')
                ->selectRaw($interestGroupExpression . ' as payment_group_key')
                ->selectRaw('SUM(COALESCE(interest_accounts.interest, 0)) as total_interest')
                ->groupBy(DB::raw($interestGroupExpression));

            $depositGroupExpression = $this->paymentGroupExpression('deposit_payments');
            $depositDates = DB::table('transaction_payments as deposit_payments')
                ->join('account_transactions as deposit_accounts', function ($join) {
                    $join->on('deposit_accounts.transaction_payment_id', '=', 'deposit_payments.id')
                        ->whereIn('deposit_accounts.sub_type', ['deposit', 'fund_transfer'])
                        ->whereNull('deposit_accounts.deleted_at');
                })
                ->where('deposit_payments.business_id', $business_id)
                ->whereNull('deposit_payments.deleted_at')
                ->selectRaw($depositGroupExpression . ' as payment_group_key')
                ->selectRaw('MAX(deposit_accounts.operation_date) as cheque_deposit_transfer_date')
                ->groupBy(DB::raw($depositGroupExpression));

            $activityTable = (new Activity())->getTable();
            $latestChildChanges = DB::table($activityTable)
                ->where('subject_type', 'App\\TransactionPayment')
                ->where('description', 'customer_changed')
                ->select('subject_id')
                ->selectRaw('MAX(id) as activity_id')
                ->groupBy('subject_id');
            $latestParentChanges = clone $latestChildChanges;

            /*
             * LA-1145: the listing is now driven from transaction_payments.
             *
             * It used to be Transaction::join('transaction_payments as tp',
             * 'transactions.id', '=', 'tp.transaction_id') - transactions as the
             * base table, inner-joined to its payments. A payment with a NULL
             * transaction_id has no transactions row at all, so no join type
             * could have rescued it; the base table itself had to change.
             *
             * transactions, contacts and business_locations are now LEFT joined.
             * For a payment against the customer's balance they are simply
             * absent, so Bill No and Location come back empty for that row -
             * which is correct: there is no invoice and no invoice location.
             */
            $sells = DB::table('transaction_payments as tp')
                ->leftJoin('transactions', 'transactions.id', '=', 'tp.transaction_id')
                ->joinSub($eligibleGroups, 'eligible_groups', function ($join) {
                    $join->on('eligible_groups.representative_tp_id', '=', 'tp.id');
                })
                ->leftJoin('contacts', function ($join) {
                    $join->on('contacts.id', '=', DB::raw('COALESCE(transactions.contact_id, tp.payment_for)'));
                })
                ->leftJoin('users', 'tp.created_by', '=', 'users.id')
                ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->leftJoin('transaction_payments as parent_tp', function ($join) use ($business_id) {
                    $join->on('parent_tp.id', '=', 'tp.parent_id')
                        ->where('parent_tp.business_id', $business_id)
                        ->whereNull('parent_tp.deleted_at');
                })
                ->leftJoinSub($referenceTotals, 'reference_totals', function ($join) {
                    $join->on('reference_totals.payment_group_key', '=', 'eligible_groups.payment_group_key');
                })
                ->leftJoinSub($interestTotals, 'interest_totals', function ($join) {
                    $join->on('interest_totals.payment_group_key', '=', 'eligible_groups.payment_group_key');
                })
                ->leftJoinSub($depositDates, 'deposit_dates', function ($join) {
                    $join->on('deposit_dates.payment_group_key', '=', 'eligible_groups.payment_group_key');
                })
                ->leftJoinSub($latestChildChanges, 'latest_child_change', function ($join) {
                    $join->on('latest_child_change.subject_id', '=', 'tp.id');
                })
                ->leftJoin($activityTable . ' as child_change_activity', 'child_change_activity.id', '=', 'latest_child_change.activity_id')
                ->leftJoinSub($latestParentChanges, 'latest_parent_change', function ($join) {
                    $join->on('latest_parent_change.subject_id', '=', 'tp.parent_id');
                })
                ->leftJoin($activityTable . ' as parent_change_activity', 'parent_change_activity.id', '=', 'latest_parent_change.activity_id')
                // LA-1145: scoped on the payment, not the transaction - a balance
                // payment has no transaction to carry the business_id.
                ->where('tp.business_id', $business_id)
                ->where('contacts.type', 'customer')
                ->whereNull('tp.deleted_at')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'business_locations.name as location_name',
                    'tp.id as tp_id',
                    'tp.paid_on',
                    'tp.method',
                    'interest_totals.total_interest',
                    'reference_totals.reference_total',
                    'parent_tp.amount as parent_amount',
                    'parent_tp.payment_ref_no as parent_payment_ref_no',
                    'tp.parent_id',
                    'tp.cheque_number',
                    'tp.cheque_date',
                    'tp.bank_name',
                    'tp.card_number',
                    'tp.payment_ref_no',
                    'tp.paid_in_type',
                    'tp.created_by',
                    'users.username',
                    'tp.amount as total_paid',
                    'tp.created_at',
                    'tp.transfer_date',
                    'tp.note',
                    'deposit_dates.cheque_deposit_transfer_date',
                    'child_change_activity.properties as child_change_properties',
                    'parent_change_activity.properties as parent_change_properties'
                )
                ->orderByDesc('tp.id');

            $datatable = DataTables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can("view_own_sell_only")) {
                            $html .= '<li><a href="#" data-href="' . route('customers.customer_payment.view', [$row->tp_id]) . '" class="btn-modal-view" data-container=".view_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
                        // Modified by Engr. Alex -- task 7889: Edit button inside action submenu
                        if (auth()->user()->can("sell.payments") || auth()->user()->can("purchase.edit.payments") || auth()->user()->can("add.payments")) {
                            $html .= '<li><a href="#" class="edit_payment"
                                        data-href="' . url('/customers/customer-payments/' . $row->tp_id . '/edit') . '"
                                        data-update-href="' . url('/customers/customer-payments/' . $row->tp_id) . '"
                                        ><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>';
                        }
                        if (auth()->user()->can("list_customer_payments.delete")) {
                            $html .= '<li><a href="#" class="delete_payment"
                                        data-href="' . url('/customers/customer-payments/' . $row->tp_id) . '"
                                        ><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</a></li>';
                        }
                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->addColumn('payment_amount', function ($row) use ($business_details) {
                    $amount = !empty($row->parent_id) && $row->parent_amount !== null
                        ? (float) $row->parent_amount
                        : (float) $row->total_paid;

                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $amount . '">' . $this->productUtil->num_f($amount, false, $business_details, false) . '</span>';
                })->addColumn('name', function ($row) {
                    $display_name = e($row->name);
                    $properties = !empty($row->parent_change_properties)
                        ? $row->parent_change_properties
                        : $row->child_change_properties;
                    $details = $this->extractCustomerChangeDetails($properties);

                    if (!empty($details['old_customer']) || !empty($details['new_customer'])) {
                        $chip_style = 'display:inline-block;padding:4px 8px;border-radius:4px;font-size:11px;font-weight:600;color:#fff;line-height:1.4;';
                        $chips = [];

                        if (!empty($details['old_customer'])) {
                            $chips[] = '<span style="' . $chip_style . 'background-color:#f39c12;">Old: ' . e($details['old_customer']) . '</span>';
                        }

                        if (!empty($details['new_customer'])) {
                            if (!empty($details['old_customer'])) {
                                $chips[] = '<span style="margin:0 4px;color:#00c0ef;font-size:12px;">&#10132;</span>';
                            }
                            $chips[] = '<span style="' . $chip_style . 'background-color:#00a65a;">New: ' . e($details['new_customer']) . '</span>';
                        }

                        $display_name .= '<br><span style="display:inline-flex;align-items:center;gap:4px;margin-top:4px;flex-wrap:wrap;">' . implode('', $chips) . '</span>';
                    }

                    return $display_name;
                })
                ->editColumn('payment_ref_no', function ($row) {
                    $ref = !empty($row->parent_id) && !empty($row->parent_payment_ref_no)
                        ? $row->parent_payment_ref_no
                        : $row->payment_ref_no;

                    return '<a href="#" data-href="' . route('customers.customer_payment.view', [$row->tp_id]) . '" class="btn-modal-view" data-container=".view_modal">' . e($ref) . '</a>';
                })
                ->addColumn('interest', function ($row) use ($business_details) {
                    return $this->productUtil->num_f((float) ($row->total_interest ?? 0), false, $business_details, false);
                })
                ->removeColumn('id')
                ->editColumn('final_total', function ($row) use ($business_details) {
                    // LA-1148: NULL when the payment has no invoice behind it.
                    $finalTotal = $row->final_total ?? 0;

                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $finalTotal . '">' . $this->productUtil->num_f($finalTotal, false, $business_details, false) . '</span>';
                })
                ->editColumn('total_paid', function ($row) use ($business_details) {
                    $totalAmount = !empty($row->parent_id) && $row->parent_amount !== null
                        ? (float) $row->parent_amount
                        : (float) ($row->reference_total ?? $row->total_paid ?? 0);

                    return '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $totalAmount . '">' . $this->productUtil->num_f($totalAmount, false, $business_details, false) . '</span>';
                })
                ->editColumn('transaction_date', function ($row) {
                    return $this->formatListDate($row->transaction_date);
                })
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->editColumn('paid_on', function ($row) {
                    return $this->formatListDate($row->paid_on);
                })
                ->editColumn('method', function ($row) {
                    if ($row->method == 'bank_transfer') {
                        return 'Bank';
                    }
                    if ($row->method == 'card') {
                        if (!empty($row->card_number)) {
                            $htm = '<span class="" >Card <small>' . $row->card_number . '</small></span>';
                            return $htm;
                        }
                    }
                    if ($row->method == 'cheque') {
                        $html = '<span class="" >Cheque <small>' . $row->bank_name . '</small><small> ' . $row->cheque_number . '</small> <small>' . $row->cheque_date . '</small></span>';
                        return $html;
                    }
                    return ucfirst($row->method);
                })
                ->editColumn('cheque_number', function ($row) {
                    return $row->cheque_number . $row->card_number;
                })
                ->editColumn('invoice_no', function ($row) {
                    // LA-1148: NULL for a balance payment - no invoice exists.
                    $invoice_no = $row->invoice_no ?? '';
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fa fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fa fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') . '"><i class="fa fa-recycle"></i></small>';
                    }
                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') . '"><i class="fa fa-recycle"></i></small>';
                    }
                    return $invoice_no;
                })
                ->addColumn('paid_in_type', function ($row) use ($paid_in_types) {
                    if (!empty($row->paid_in_type) && !empty($paid_in_types[$row->paid_in_type])) {
                        return $paid_in_types[$row->paid_in_type];
                    }
                    return '';
                })
                ->editColumn('cheque_deposit_transfer_date', function ($row) {
                    if (!in_array($row->method, ['cheque', 'bank_transfer'], true)) {
                        return '';
                    }

                    $dateToFormat = $row->cheque_deposit_transfer_date ?: $row->transfer_date;
                    if (empty($dateToFormat)) {
                        return '';
                    }

                    try {
                        $date = \Carbon\Carbon::parse($dateToFormat);
                        $dateFormat = session('business.date_format', 'd/m/Y');
                        $timeFormat = session('business.time_format', 24) == 24 ? 'H:i' : 'h:i A';

                        return $date->format($dateFormat . ' ' . $timeFormat);
                    } catch (\Throwable $e) {
                        return $dateToFormat;
                    }
                })
                // Modified by Engr. Alex -- task 7889: note/description column for customer payments list
                ->addColumn('note', function ($row) {
                    return e($row->note ?? '');
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        /*
                         |--------------------------------------------------------
                         | LA-1148: this is what broke the table.
                         |--------------------------------------------------------
                         |
                         | $row->id is transactions.id. Since LA-1145/1146 the
                         | listing also returns payments taken against the
                         | customer's BALANCE (Pay Due Amount, Advance Payment),
                         | which have no transaction at all - transactions is LEFT
                         | joined, so id is NULL for those rows.
                         |
                         | action('SellController@show', [null]) throws
                         | UrlGenerationException "Missing required parameter",
                         | and because setRowAttr runs for EVERY row, one balance
                         | payment in the result aborted the entire response.
                         | DataTables then showed its generic Ajax warning and the
                         | grid stayed empty.
                         |
                         | That is why this only started once the rows came
                         | through: while they were being filtered out there was
                         | nothing here to trip over.
                         |
                         | A balance payment has no sale to open, so it gets no row
                         | link - the same outcome as a user without sell.view.
                         */
                        if (empty($row->id)) {
                            return '';
                        }

                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return action('SellController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ]);
            /*
             |------------------------------------------------------------------
             | LA-1148: make the failure name itself.
             |------------------------------------------------------------------
             |
             | This listing is built from twelve joins and five subqueries. When
             | it throws, DataTables shows only "Ajax error - see
             | datatables.net/tn/7", which says nothing about the cause, and
             | Laravel returns a generic 500 in production. That is why the last
             | two rounds on this screen were guesswork.
             |
             | Now the exact driver message, the compiled SQL and the bindings go
             | to laravel.log, and the message is returned in the JSON `error`
             | field so the warning box on screen states the real cause instead of
             | a documentation link.
             |
             | Deliberately not limited to debug mode: this endpoint is
             | administrative, the payload is a SQL error rather than customer
             | data, and being able to read the fault from the screen is worth
             | more here than hiding it.
             */
            try {
                $rawColumns = ['payment_ref_no', 'name', 'method', 'final_total', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'payment_amount'];

                return $datatable->rawColumns($rawColumns)
                    ->make(true);
            } catch (\Throwable $e) {
                $sql = null;
                $bindings = null;

                try {
                    $sql = $sells->toSql();
                    $bindings = $sells->getBindings();
                } catch (\Throwable $ignore) {
                    // The builder itself may be the thing that is broken.
                }

                Log::error('LA-1148: List Customer Payments query failed.', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'sql' => $sql,
                    'bindings' => $bindings,
                    'filters' => $request->only([
                        'customer_id', 'bill_no', 'payment_ref_no', 'cheque_number',
                        'payment_method', 'location_id', 'paid_in_type', 'payment_amount',
                        'shift_number', 'daily_shift_no', 'start_date', 'end_date',
                    ]),
                ]);

                return response()->json([
                    'draw' => (int) $request->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'List Customer Payments could not be loaded: ' . $e->getMessage(),
                ]);
            }
        }
        $latest_ref_number = $this->nextPaymentReferenceSequence($business_id);
        $latest_ref_number_PP = $this->nextPaymentReferenceSequence($business_id, 'customer_page');
        // S710 v2: both Customer Payments entry tabs show the same configured
        // Customer Settings reference series instead of legacy CPB/CPS counters.
        $customerPaymentReferencePreview = app(\Modules\Customers\Services\CustomerPaymentReferenceService::class)
            ->preview('customer_payments', $business_id, now());
        $latest_ref_number_CPB = $customerPaymentReferencePreview;
        $latest_ref_number_CPS = $customerPaymentReferencePreview;

        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();
        $customer_interest_deduct_option = $business_details->customer_interest_deduct_option;
        $customer_groups = ContactGroup::where('contact_groups.business_id', $business_id)
            ->where('contact_groups.type', 'customer')
            ->pluck('name', 'id');
        $income_accounts = Account::leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_types.business_id', $business_id)
            ->where('account_types.name', 'Income')
            ->select(DB::raw('accounts.name as name, accounts.id as id'))
            ->pluck('name', 'id');

        $account_types = AccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->whereIn('name', ['Assets', 'Liabilities'])
            ->with(['sub_types'])
            ->get();
        $account_types_opts = $account_types->pluck('name', 'id');
        // dd($account_types->toArray());
        $filterdata = [];
        $sub_acn_arr = [];
        $filterdata['subType_']['data'][] = array('id' => "", 'text' => "All", true);
        foreach ($account_types->toArray() as $acunts) {
            $filterdata['subType_' . $acunts['id']]['data'][] = array('id' => "", 'text' => "All", true);
            foreach ($acunts['sub_types'] as $sub_Acn) {
                $filterdata['subType_']['data'][] = array('id' => $sub_Acn['id'], 'text' => $sub_Acn['name']);
                $filterdata['subType_' . $acunts['id']]['data'][] = array('id' => $sub_Acn['id'], 'text' => $sub_Acn['name']);
                $sub_acn_arr[$sub_Acn['id']] = $sub_Acn['name'];
            }
        }
        $account_groups_raw = AccountGroup::where('business_id', $business_id)->whereIn('name', ['Cash Account', "Cheques in Hand (Customer's)", 'Card', 'Bank Account'])->get()->toArray();
        $account_groups = [];
        $filterdata['groupType_']['data'][] = array('id' => "", 'text' => "All", true);
        foreach ($account_groups_raw as $datarow) {
            $filterdata['groupType_' . $datarow['account_type_id']]['data'][] = array('id' => $datarow['id'], 'text' => $datarow['name']);
            $account_groups[$datarow['id']] = $datarow['name'];
        }
        // Customer Payments must remain loadable even when Petro is disabled or
        // its optional shift table is not installed for a tenant.
        $dailyCashShiftNumbers = [];
        if (Schema::hasTable('petro_daily_shifts')) {
            $dailyCashShiftNumbers = DB::table('petro_daily_shifts')
                ->where('business_id', $business_id)
                ->where('status', 0)
                ->whereNotNull('shift_no')
                ->orderByDesc('id')
                ->pluck('shift_no')
                ->unique()
                ->values()
                ->toArray();
        }
        $pacakge_details = $this->activePackageDetails($business_id);

        return view('customers::customer_payments.index')->with(compact(
            'customers',
            'filterdata',
            'business_locations',
            'payment_types',
            'account_types_opts',
            'account_groups',
            'customer_interest_deduct_option',
            'latest_ref_number',
            'latest_ref_number_PP',
            'latest_ref_number_CPB',
            'latest_ref_number_CPS',
            'customer_groups',
            'income_accounts',
            'dailyCashShiftNumbers',
            'pacakge_details'
        ));
    }

    public function printPayment($id)
    {
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);

        try {
            $payment = TransactionPayment::where('business_id', $business_id)
                ->with(['contact', 'transaction.contact'])
                ->findOrFail($id);

            $parent_payment = !empty($payment->parent_id)
                ? TransactionPayment::where('business_id', $business_id)
                    ->with('contact')
                    ->findOrFail($payment->parent_id)
                : $payment;

            $child_payments = TransactionPayment::where('business_id', $business_id)
                ->with(['transaction.contact'])
                ->whereNotNull('transaction_id')
                ->where(function ($query) use ($payment) {
                    if (!empty($payment->parent_id)) {
                        $query->where('parent_id', $payment->parent_id);
                    } else {
                        $query->where('payment_ref_no', $payment->payment_ref_no);
                    }
                })
                ->whereHas('transaction', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->orderBy('id')
                ->get();

            $paymentIds = $child_payments->pluck('id');
            $interestByPayment = $paymentIds->isEmpty()
                ? collect()
                : DB::table('account_transactions')
                    ->whereIn('transaction_payment_id', $paymentIds)
                    ->whereNull('deleted_at')
                    ->select('transaction_payment_id')
                    ->selectRaw('SUM(COALESCE(interest, 0)) as interest_amount')
                    ->groupBy('transaction_payment_id')
                    ->pluck('interest_amount', 'transaction_payment_id');

            foreach ($child_payments as $child_payment) {
                $child_payment->interest_amount = (float) $interestByPayment->get($child_payment->id, 0);
                $child_payment->invoice_no = optional($child_payment->transaction)->invoice_no;
            }

            $parent_payment->total_interest = (float) $interestByPayment->sum();
            if (empty($payment->parent_id)) {
                $parent_payment->amount = (float) $child_payments->sum('amount');
            }
            if (empty($parent_payment->contact)) {
                $parent_payment->setRelation('contact', optional(optional($child_payments->first())->transaction)->contact);
            }

            $company = Business::findOrFail($business_id);
            $receipt['html_content'] = view('customers::customer_payments.partials.print_payment')
                ->with(compact('child_payments', 'parent_payment', 'company'))
                ->render();

            return ['success' => 1, 'receipt' => $receipt];
        } catch (\Throwable $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }
    }

    /**
     * Extracts customer change information from an activity log entry.
     */
    private function extractCustomerChangeDetails($activityOrProperties): array
    {
        $details = [
            'old_customer' => null,
            'new_customer' => null,
            'changed_by' => null,
            'message' => null,
        ];

        if (empty($activityOrProperties)) {
            return $details;
        }

        $properties = $activityOrProperties instanceof Activity
            ? $activityOrProperties->properties
            : $activityOrProperties;

        if (is_string($properties)) {
            $decoded = json_decode($properties, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $properties = $decoded;
            }
        }

        if (is_object($properties) && method_exists($properties, 'toArray')) {
            $properties = $properties->toArray();
        }

        if (is_array($properties)) {
            $details['message'] = $properties['message'] ?? null;

            if (!empty($properties['old_customer_name']) || !empty($properties['new_customer_name'])) {
                $details['old_customer'] = $properties['old_customer_name'] ?? null;
                $details['new_customer'] = $properties['new_customer_name'] ?? null;
                $details['changed_by'] = $properties['changed_by'] ?? null;

                return $details;
            }
        }

        if (is_array($properties) || is_object($properties)) {
            $properties = json_encode($properties);
        }

        if (!is_string($properties)) {
            $properties = (string) $properties;
        }

        $normalized = trim($properties, "\"[]");
        $details['message'] = $normalized;

        if (preg_match('/Changed customer\s+(.*?)\s+\(old customer name\)\s+and to Customer\s+(.*?)\s+\(new customer name\),\s+by the User\s+(.*)$/i', $normalized, $matches_new)) {
            $details['old_customer'] = trim($matches_new[1]);
            $details['new_customer'] = trim($matches_new[2]);
            $details['changed_by'] = trim($matches_new[3], "[]\" ");
        } elseif (preg_match('/from (.+?) \(ID: (\d+)\) to (.+?) \(ID: (\d+)\) by User (.+?)(?:\s*[\"\]]+)?$/', $normalized, $matches_old)) {
            $details['old_customer'] = trim($matches_old[1]);
            $details['new_customer'] = trim($matches_old[3]);
            $details['changed_by'] = trim($matches_old[5], "[]\" ");
        }

        return $details;
    }

    public function viewPayment($id)
    {
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);

        $payment = TransactionPayment::where('business_id', $business_id)
            ->with(['contact', 'transaction.contact'])
            ->findOrFail($id);

        $parent_payment = !empty($payment->parent_id)
            ? TransactionPayment::where('business_id', $business_id)
                ->with('contact')
                ->findOrFail($payment->parent_id)
            : $payment;

        $child_payments = TransactionPayment::where('business_id', $business_id)
            ->with(['transaction.contact'])
            ->whereNotNull('transaction_id')
            ->where(function ($query) use ($payment) {
                if (!empty($payment->parent_id)) {
                    $query->where('parent_id', $payment->parent_id);
                } else {
                    $query->where('payment_ref_no', $payment->payment_ref_no);
                }
            })
            ->whereHas('transaction', function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            })
            ->orderBy('id')
            ->get();

        $paymentIds = $child_payments->pluck('id');
        $interestByPayment = $paymentIds->isEmpty()
            ? collect()
            : DB::table('account_transactions')
                ->whereIn('transaction_payment_id', $paymentIds)
                ->whereNull('deleted_at')
                ->select('transaction_payment_id')
                ->selectRaw('SUM(COALESCE(interest, 0)) as interest_amount')
                ->groupBy('transaction_payment_id')
                ->pluck('interest_amount', 'transaction_payment_id');

        foreach ($child_payments as $child_payment) {
            $child_payment->interest_amount = (float) $interestByPayment->get($child_payment->id, 0);
        }

        $parent_payment->total_interest = (float) $interestByPayment->sum();
        if ($child_payments->isNotEmpty()) {
            $parent_payment->amount = (float) $child_payments->sum('amount');
        }
        if (empty($parent_payment->contact)) {
            $parent_payment->setRelation('contact', optional(optional($child_payments->first())->transaction)->contact);
        }

        $company = Business::findOrFail($business_id);
        $location_id = optional(optional($child_payments->first())->transaction)->location_id;
        $location = $location_id
            ? BusinessLocation::where('business_id', $business_id)->where('id', $location_id)->value('name')
            : null;

        $activitySubjectIds = collect([$payment->id, $payment->parent_id])->filter()->unique()->values();
        $customer_change_logs = Activity::where('subject_type', 'App\TransactionPayment')
            ->whereIn('subject_id', $activitySubjectIds)
            ->where('description', 'customer_changed')
            ->orderByDesc('created_at')
            ->get();

        $receipt_data = view('customers::customer_payments.partials.view_payment')
            ->with(compact('child_payments', 'parent_payment', 'company', 'location', 'customer_change_logs'))
            ->render();

        return view('customers::customer_payments.view')
            ->with(compact('receipt_data', 'parent_payment', 'id'));
    }


    public function customerPaymentInformations($customer, $type)
    {

        $start_date = request()->start ?? null;
        $end_date = request()->end ?? null;
        $bank = request()->bank;
        $post_party_type = request()->post_party_type;
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        $businessCurrencyPrecise = Business::where('id', $business_id)->first()->currency_precision ?? 2;

        if ($type === "amount") {
            // @eng START 19/2
            if ($customer != 'all') {
                $amounts = TransactionPayment::get_customer_wise_unique_amounts($customer, $start_date, $end_date, $bank, $post_party_type);
                return response()->json([
                    'data' => $amounts->map(
                        function ($item) {
                            return $item->amount;
                        }
                    )
                ]);
            }
            $ret = TransactionPayment::query()->whereHas('transaction', function ($query) {
                $query->whereHas('contact', function ($query) {
                    $query->whereNotNull('id');
                });
            })->where('amount', '>', 0);



            if (!empty($start_date)) {
                $ret->whereDate('transaction_payments.paid_on', '>=', $start_date);
            }

            if (!empty($end_date)) {
                $ret->whereDate('transaction_payments.paid_on', '<=', $end_date);
            }

            if (!empty($bank)) {
                $ret->where('transaction_payments.account_id', $bank);
            }


            return response()->json([
                'data' => $ret->distinct()->groupBy('amount')->get()->map(function ($item) use ($businessCurrencyPrecise) {
                    return currencyFormat(number_format($item->amount, $businessCurrencyPrecise, '.', ''));
                })
            ]);
            // @eng END 19/2
        }

        if ($type === "cheque_no") {
            // @eng START 19/2
            if ($customer != 'all') {
                $amounts = TransactionPayment::get_customer_wise_unique_cheque_no($customer, $start_date, $end_date, $bank, $post_party_type);
                return response()->json([
                    'data' => $amounts->map(
                        function ($item) {
                            return $item->cheque_number;
                        }
                    )
                ]);
            }

            $ret = TransactionPayment::query()->whereHas('transaction', function ($query) use ($customer) {
                // $query->whereHas('contact',function($query) use($customer){
                //     //  $query->where('id', $customer);
                //     $query->whereNotNull('id');
                // });
            })->where('cheque_number', '!=', null);


            if (!empty($start_date)) {
                $ret->whereDate('transaction_payments.paid_on', '>=', $start_date);
            }

            if (!empty($end_date)) {
                $ret->whereDate('transaction_payments.paid_on', '<=', $end_date);
            }

            if (!empty($bank)) {
                $ret->where('transaction_payments.account_id', $bank);
            }


            return response()->json([
                'data' => $ret->distinct()->groupBy('cheque_number')->get()->map(function ($item) {
                    return $item->cheque_number;
                })
            ]);
            // @eng END 19/2
        }
    }

    // @eng START 19/2
    public function customerInfoFor($for, $data)
    {
        if ($for == 'cheque_no') {
            if ($data == 'all') {
                $customers = Contact::customersDropdown(request()->session()->get('user.business_id'), false);

                $allAmountsQuery = TransactionPayment::query()->whereHas('transaction', function ($query) {
                    $query->whereHas('contact', function ($query) {
                        $query->whereNotNull('id');
                    });
                })->distinct()->groupBy('amount')->get();

                $allAmounts = $allAmountsQuery->map(function ($item) {
                    return $item->amount;
                });

                return response()->json(['data' => $customers, 'amounts' => $allAmounts]);
            }

            $contact = DB::select(
                "SELECT contacts.* FROM contacts WHERE contacts.id IN 
            (SELECT transactions.contact_id FROM transactions WHERE transactions.id IN 
            (SELECT transaction_id FROM transaction_payments WHERE cheque_number = ?))",
                [$data]
            );

            $amounts = DB::select("SELECT amount FROM transaction_payments WHERE cheque_number = ?", [$data]);
            $retAmounts = array_map(function ($a) {
                return $a->amount;
            }, $amounts);
            return response()->json([
                'data' => [$contact[0]->id => $contact[0]->name . " (" . $contact[0]->contact_id . ")"],
                'amounts' => $retAmounts
            ]);
        } elseif ($for == 'amount') {
            if ($data == 'all') {
                $customers = Contact::customersDropdown(request()->session()->get('user.business_id'), false);

                $allChequesQuery = TransactionPayment::query()->whereHas('transaction', function ($query) {
                    $query->whereHas('contact', function ($query) {
                        //  $query->where('id', $customer);
                        $query->whereNotNull('id');
                    });
                })->where('cheque_number', '!=', null)->distinct()->groupBy('cheque_number')->get();

                $allCheques = $allChequesQuery->map(function ($item) {
                    return $item->cheque_number;
                });

                return response()->json(['data' => $customers, 'cheques' => $allCheques]);
            }

            $contacts = DB::select(
                "SELECT contacts.* FROM contacts WHERE contacts.id IN 
            (SELECT transactions.contact_id FROM transactions WHERE transactions.id IN 
            (SELECT transaction_id FROM transaction_payments WHERE amount = ?))",
                [$data]
            );

            $retContacts = [];
            foreach ($contacts as $c)
                $retContacts[$c->id] = $c->name . " (" . $c->contact_id . ")";

            $cheques = DB::select("SELECT cheque_number FROM transaction_payments WHERE amount = ?", [$data]);
            $retCheques = array_map(function ($cheque) {
                return $cheque->cheque_number;
            }, $cheques);

            return response()->json(['data' => $retContacts, 'cheques' => $retCheques]);
        }
    }
    // @eng END 19/2

    public function CustomerInterest()
    {
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        $business_details = Business::findOrFail($business_id);
        $paid_in_types = [
            'customer_page' => 'Customer Page',
            'all_sale_page' => 'All Sale Page',
            'settlement' => 'Settlement',
            'customer_bulk' => 'Customer Bulk',
            'customer_simple' => 'Customer Simple'
        ];
        $latest_ref_number = 0;
        $latest_ref_number_PP = 0;
        $latest_ref_number_CPB = 0;
        $latest_ref_number_CPS = 0;
        try {
            $latest_ref_number = DB::table('transaction_payments')->orderBy('created_at', 'DESC')->first()->payment_ref_no;
            $latest_ref_number_PP = DB::table('transaction_payments')->where('paid_in_type', 'customer_page')->orderBy('created_at', 'DESC')->first()->payment_ref_no;
            $latest_ref_number_CPB = DB::table('transaction_payments')->where('paid_in_type', 'customer_bulk')->orderBy('created_at', 'DESC')->first()->payment_ref_no;
            $latest_ref_number_CPS = DB::table('transaction_payments')->where('paid_in_type', 'customer_simple')->orderBy('created_at', 'DESC')->first()->payment_ref_no;
        } catch (\Exception $exception) {
        }
        $latest_ref_number = (int) explode('/', $latest_ref_number);
        $latest_ref_number_PP = (int) explode('PP2021/', $latest_ref_number_PP);
        $latest_ref_number_CPB = (int) explode('CPB-', $latest_ref_number_CPB);
        $latest_ref_number_CPS = (int) explode('CPS-', $latest_ref_number_CPS);
        $latest_ref_number += 1;
        $latest_ref_number_PP += 1;
        $customerPaymentReferencePreview = app(\Modules\Customers\Services\CustomerPaymentReferenceService::class)
            ->preview('customer_payments', $business_id, now());
        $latest_ref_number_CPB = $customerPaymentReferencePreview;
        $latest_ref_number_CPS = $customerPaymentReferencePreview;
        if (request()->ajax()) {
            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->leftJoin('users', 'tp.created_by', '=', 'users.id')
                ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->leftJoin(
                    'account_transactions as act',
                    'transactions.id',
                    '=',
                    'act.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('contacts.type', 'customer')
                ->where('act.interest', '!=', null)
                ->whereIn('transactions.payment_status', ['paid', 'partial'])
                ->where(function ($q) {
                    $q->where('transactions.type', 'opening_balance')->orWhere('transactions.is_credit_sale', 1);
                })
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'business_locations.name as location_name',
                    'tp.id as tp_id',
                    'tp.paid_on',
                    'tp.method',
                    'act.id as act_id',
                    'act.interest',
                    'tp.parent_id',
                    'tp.cheque_number',
                    'tp.card_number',
                    'tp.payment_ref_no',
                    'tp.paid_in_type',
                    'tp.created_by',
                    'users.username',
                    'tp.amount as total_paid'
                    //DB::raw('SUM(tp.amount) as total_paid')
                );
            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->bill_no)) {
                $sells->where('transactions.invoice_no', request()->bill_no);
            }
            if (!empty(request()->payment_ref_no)) {
                $sells->where('tp.payment_ref_no', request()->payment_ref_no);
            }
            if (!empty(request()->cheque_number)) {
                $sells->where('tp.cheque_number', request()->cheque_number);
            }
            if (!empty(request()->payment_method)) {
                $sells->where('tp.method', request()->payment_method);
            }
            if (!empty(request()->paid_in_type)) {
                $sells->where('tp.paid_in_type', request()->paid_in_type);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('tp.paid_on', '>=', $start)
                    ->whereDate('tp.paid_on', '<=', $end);
            }
            $sells->orderBy('tp.paid_on', 'desc')->groupBy('tp.id');

            // dd($sells->get());


            $datatable = DataTables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can("view_own_sell_only")) {
                            /*
                             * LA-1148: only offer "View sale" when there IS a
                             * sale. A payment against the customer's balance has
                             * no transaction, so $row->id is NULL and
                             * action(...) would throw UrlGenerationException and
                             * abort the whole response.
                             */
                            if (!empty($row->id)) {
                                $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                            }
                            $html .= '<li><a href="#" data-href="' . action("TransactionPaymentController@edit", [$row->tp_id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>';
                        }
                        $html .= '</ul></div>';
                        return $html;
                    }
                )
                ->addColumn('payment_amount', function ($row) use ($business_details) {
                    if (!empty($row->parent_id)) {
                        $parent_payment = TransactionPayment::where('id', $row->parent_id)->first();
                        if (!empty($parent_payment)) {
                            return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $parent_payment->amount . '">' . $this->productUtil->num_f($parent_payment->amount, false, $business_details, false) . '</span>';
                        } else {
                            return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $this->productUtil->num_f($row->total_paid, false, $business_details, false) . '</span>';
                        }
                    } else {
                        return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $this->productUtil->num_f($row->total_paid, false, $business_details, false) . '</span>';
                    }
                })->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('interest', function ($row) {
                    return $row->interest == Null ? '0.00' : $row->interest;
                })
                ->removeColumn('id')
                ->editColumn('final_total', function ($row) use ($business_details) {
                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . ($row->final_total ?? 0) . '">' . $this->productUtil->num_f(($row->final_total ?? 0), false, $business_details, false) . '</span>';
                })
                ->editColumn('total_paid', function ($row) use ($business_details) {
                    $interest = $row->interest == Null ? '0.00' : $row->interest;
                    if ($row->total_paid == '') {
                        $total_paid_html = '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="0.00">' . $this->productUtil->num_f(0, false, $business_details, false) . '</span>';
                    } else {
                        $total_paid_html = '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="' . ($row->total_paid - $interest) . '">' . $this->productUtil->num_f(($row->total_paid - $interest), false, $business_details, false) . '</span>';
                    }
                    return $total_paid_html;
                })
                ->addColumn('total_amount_payable', function ($row) use ($business_details) {
                    $interest = $row->interest == Null ? 0 : $row->interest;
                    $total_payable = ($row->final_total ?? 0) + $interest;
                    return '<span class="display_currency total-payable" data-currency_symbol="true" data-orig-value="' . $total_payable . '">' . $this->productUtil->num_f($total_payable, false, $business_details, false) . '</span>';
                })
                ->addColumn('total_remaining', function ($row) use ($business_details) {
                    $interest = $row->interest == Null ? 0 : $row->interest;
                    $total_payable = ($row->final_total ?? 0) + $interest;
                    $total_paid = $row->total_paid == '' ? 0 : $row->total_paid;
                    $remaining = $total_payable - $total_paid;
                    return '<span class="display_currency total-remaining" data-currency_symbol="true" data-orig-value="' . $remaining . '">' . $this->productUtil->num_f($remaining, false, $business_details, false) . '</span>';
                })
                ->editColumn('transaction_date', function ($row) {
                    return $this->formatListDate($row->transaction_date);
                })
                ->editColumn('paid_on', function ($row) {
                    return $this->formatListDate($row->paid_on);
                })
                ->editColumn('method', function ($row) {
                    //return $row->method;
                    if ($row->method == 'bank_transfer') {
                        return 'Bank';
                    }
                    if ($row->method == 'card') {
                        if (!empty($row->card_number)) {
                            $htm = '<span class="" >Card <small>' . $row->card_number . '</small></span>';
                            return $htm;
                        }
                    }
                    if ($row->method == 'cheque') {
                        $html = '<span class="" >Cheque <small>' . $row->bank_name . '</small><small> ' . $row->cheque_number . '</small> <small>' . $row->cheque_date . '</small></span>';
                        return $html;
                    }
                    return ucfirst($row->method);
                })
                ->editColumn('cheque_number', function ($row) {
                    if ($row->method == 'bank_transfer' || $row->method == 'cheque') {
                        return $row->cheque_number;
                    }
                    if ($row->method == 'card') {
                        return $row->card_number;
                    }
                    return '';
                })
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = ($row->invoice_no ?? '');
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fa fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') . '"><i class="fa fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') . '"><i class="fa fa-recycle"></i></small>';
                    }
                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') . '"><i class="fa fa-recycle"></i></small>';
                    }
                    return $invoice_no;
                })
                ->addColumn('paid_in_type', function ($row) use ($paid_in_types) {
                    if (!empty($row->paid_in_type) && !empty($paid_in_types[$row->paid_in_type])) {
                        return $paid_in_types[$row->paid_in_type];
                    }
                    return '';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        /*
                         |--------------------------------------------------------
                         | LA-1148: this is what broke the table.
                         |--------------------------------------------------------
                         |
                         | $row->id is transactions.id. Since LA-1145/1146 the
                         | listing also returns payments taken against the
                         | customer's BALANCE (Pay Due Amount, Advance Payment),
                         | which have no transaction at all - transactions is LEFT
                         | joined, so id is NULL for those rows.
                         |
                         | action('SellController@show', [null]) throws
                         | UrlGenerationException "Missing required parameter",
                         | and because setRowAttr runs for EVERY row, one balance
                         | payment in the result aborted the entire response.
                         | DataTables then showed its generic Ajax warning and the
                         | grid stayed empty.
                         |
                         | That is why this only started once the rows came
                         | through: while they were being filtered out there was
                         | nothing here to trip over.
                         |
                         | A balance payment has no sale to open, so it gets no row
                         | link - the same outcome as a user without sell.view.
                         */
                        if (empty($row->id)) {
                            return '';
                        }

                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return action('SellController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ]);
            $rawColumns = ['name', 'method', 'final_total', 'action', 'total_paid', 'total_amount_payable', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'payment_amount'];
            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();
        $customer_interest_deduct_option = $business_details->customer_interest_deduct_option;



        return view('customers::customer_payments.index')->with(compact(
            'customers',
            'business_locations',
            'payment_types',
            'customer_interest_deduct_option',
            'latest_ref_number',
            'latest_ref_number_PP',
            'latest_ref_number_CPB',
            'latest_ref_number_CPS'
        ));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }
    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }
    // Modified by Engr. Alex -- task 7889
    public function edit($id)
    {
        if (
            !auth()->user()->can('purchase.delete.payments') && !auth()->user()->can('purchase.payments') &&
            !auth()->user()->can('purchase.edit.payments') &&
            !auth()->user()->can('add.payments') && !auth()->user()->can('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);

        $payment = TransactionPayment::where('business_id', $business_id)
            ->with(['transaction.contact'])
            ->findOrFail($id);

        /*
         * Payment Report edit: allow the payment's customer to be corrected.
         *
         * `payment_for` is the payment-level customer when it is available.
         * Older/invoice-linked rows can have it empty, so fall back to the
         * transaction customer for the initially selected value.
         */
        $current_customer_id = (int) ($payment->payment_for ?: optional($payment->transaction)->contact_id);
        $current_customer = $current_customer_id
            ? Contact::where('business_id', $business_id)->find($current_customer_id)
            : null;

        $customers = Contact::customersDropdown($business_id, false);
        $customers = $customers instanceof \Illuminate\Support\Collection
            ? $customers->toArray()
            : (array) $customers;

        // Always keep the currently assigned customer visible/selected, even if
        // an older customer is no longer returned by the standard dropdown.
        if ($current_customer && !array_key_exists($current_customer_id, $customers)) {
            $customers[$current_customer_id] = $current_customer->name;
        }

        /*
         * Payment Report edit also needs the Accounting Module payment account
         * dropdown. Reuse the Customers module's existing payment-account mapping
         * so the options follow Super Admin -> Payment Method configuration rather
         * than exposing unrelated accounts.
         */
        $payment_action_service = app(CustomerPaymentActionService::class);
        $payment_methods = $payment_action_service->paymentMethods($business_id);

        // Keep the stored method selectable even if it is a legacy method that is
        // no longer enabled for new payments. Otherwise opening Edit could silently
        // replace the original method.
        if (!empty($payment->method) && !array_key_exists($payment->method, $payment_methods)) {
            $payment_methods[$payment->method] = ucwords(str_replace('_', ' ', $payment->method));
        }

        $payment_account_map = $payment_action_service->paymentAccountMap($business_id);
        if (!isset($payment_account_map[$payment->method])) {
            $payment_account_map[$payment->method] = $payment_action_service->paymentAccountOptions(
                $business_id,
                $payment->method
            );
        }

        $current_account = !empty($payment->account_id)
            ? Account::where('business_id', $business_id)->find($payment->account_id)
            : null;

        // Preserve the original account in the dropdown even when an old payment
        // points to an account outside the current payment-method mapping.
        if ($current_account) {
            $payment_account_map[$payment->method] = $payment_account_map[$payment->method] ?? [];
            if (!array_key_exists($current_account->id, $payment_account_map[$payment->method])) {
                $payment_account_map[$payment->method][$current_account->id] = $current_account->name;
            }
        }

        return response()->json([
            'success' => true,
            'customers' => $customers,
            'payment_methods' => $payment_methods,
            'payment_accounts' => $payment_account_map,
            'payment' => [
                'id'             => $payment->id,
                'customer_id'    => $current_customer_id ?: null,
                'customer_name'  => optional($current_customer)->name,
                'paid_on'        => $payment->paid_on,
                'amount'         => $payment->amount,
                'method'         => $payment->method,
                'note'           => $payment->note,
                'account_id'     => $payment->account_id,
                'account_name'   => optional($current_account)->name,
                'location_id'    => optional($payment->transaction)->location_id,
                'cheque_number'  => $payment->cheque_number,
                'cheque_date'    => $payment->cheque_date,
                'bank_name'      => $payment->bank_name,
                'card_number'    => $payment->card_number,
                'payment_ref_no' => $payment->payment_ref_no,
            ],
        ]);
    }

    // Modified by Engr. Alex -- task 7889
    /**
     * A date for the list columns.
     *
     * LA-1192: the Date column was blank.
     *
     * These columns rendered with the string template
     *     '{{@format_date($paid_on)}}'
     * which puts a Blade DIRECTIVE inside an echo. @format_date compiles to a PHP
     * expression, so nesting it in {{ }} produces no value and the cell came out
     * empty. The identical fault was found on the Finance deposits list
     * (IS2050); the same shape appears wherever this template was copied.
     *
     * Written once here because four columns across two list methods used it, and
     * four copies would be four chances to fix only some of them.
     */
    protected function formatListDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->format(session('business.date_format', 'd/m/Y'));
        } catch (\Throwable $e) {
            // An unreadable stored date shows as stored rather than breaking the row.
            return (string) $value;
        }
    }

    public function update(Request $request, $id)
    {
        if (
            !auth()->user()->can('purchase.delete.payments') && !auth()->user()->can('purchase.payments') &&
            !auth()->user()->can('purchase.edit.payments') &&
            !auth()->user()->can('add.payments') && !auth()->user()->can('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (!request()->ajax()) {
            abort(403);
        }

        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);

        try {
            $payment = TransactionPayment::where('business_id', $business_id)
                ->with('transaction')
                ->findOrFail($id);

            if (!empty($payment->transaction) && (int) $payment->transaction->business_id !== $business_id) {
                abort(404);
            }

            // Prevent edit if cheque has been returned or deposited
            if ($payment->method == 'cheque' || $payment->method == 'bank_transfer') {
                $cheque_return = Transaction::where('business_id', $business_id)
                    ->where('type', 'cheque_return')
                    ->where('contact_id', $payment->payment_for)
                    ->whereExists(function ($q) use ($payment) {
                        $q->select(DB::raw(1))
                            ->from('account_transactions')
                            ->whereColumn('account_transactions.transaction_id', 'transactions.id')
                            ->where('account_transactions.cheque_number', $payment->cheque_number)
                            ->whereNull('account_transactions.deleted_at');
                    })->first();

                if (!empty($cheque_return)) {
                    return ['success' => false, 'msg' => __('lang_v1.cannot_edit_delete_returned_cheque')];
                }

                if ($payment->is_deposited == 1) {
                    return ['success' => false, 'msg' => __('lang_v1.cheque_already_deposited_cannot_delete')];
                }
            }

            $old_customer_id = (int) ($payment->payment_for ?: optional($payment->transaction)->contact_id);
            $old_customer = $old_customer_id
                ? Contact::where('business_id', $business_id)->find($old_customer_id)
                : null;

            $new_customer = null;
            if ($request->filled('customer_id')) {
                $new_customer_id = (int) $request->input('customer_id');
                $new_customer = Contact::where('business_id', $business_id)
                    ->whereIn('type', ['customer', 'both'])
                    ->find($new_customer_id);

                if (!$new_customer) {
                    return [
                        'success' => false,
                        'msg' => 'The selected customer is not available for this business.',
                    ];
                }
            }

            // Never allow an account from another business to be written into a
            // customer payment. This also protects the Accounting Module dropdown
            // when a stale browser tab is submitted.
            if ($request->filled('account_id')) {
                $submitted_account_id = (int) $request->input('account_id');
                $valid_account = Account::where('business_id', $business_id)
                    ->where('id', $submitted_account_id)
                    ->exists();

                if (!$valid_account) {
                    return [
                        'success' => false,
                        'msg' => 'The selected payment account is not available for this business.',
                    ];
                }
            }

            DB::beginTransaction();

            /*
             * URGENT 2026-09-04: an Edit action must be non-destructive.
             *
             * Re-read and lock the payment after the transaction starts. This
             * prevents a concurrent delete/reallocation from racing the edit and
             * gives us one stable row for the entire save operation.
             */
            $payment = TransactionPayment::where('business_id', $business_id)
                ->with('transaction')
                ->lockForUpdate()
                ->findOrFail($id);

            /*
             * Capture every live row that belongs to this logical parent/child
             * payment group. Some legacy core payment helpers can redistribute
             * child rows and even force-delete an allocation. Editing from the
             * Customer Payment Report must NEVER delete a payment/allocation.
             * The snapshot is checked immediately before commit; any unexpected
             * deletion causes a rollback instead of losing the transaction.
             */
            $payment_integrity_snapshot = $this->customerPaymentEditIntegritySnapshot($payment, $business_id);

            $old_amount  = $payment->amount;
            $old_paid_on = $payment->paid_on;
            $old_method  = $payment->method;

            // Update allowed fields
            if ($new_customer) {
                // Store the payment-level customer without changing the linked
                // invoice/transaction owner. This avoids unexpectedly moving an
                // invoice when only the payment attribution is being corrected.
                $payment->payment_for = $new_customer->id;
            }
            $payment->paid_on = $request->input('paid_on', $payment->paid_on);
            $payment->amount  = $request->input('amount', $payment->amount);
            $payment->method  = $request->input('method', $payment->method);
            $payment->note    = $request->input('note', $payment->note);

            // Bank / Online style payments carry editable bank and cheque details.
            // Save those fields before normalisation so changing them in the report
            // edit form is reflected in both transaction_payments and account books.
            if ($this->customerPaymentMethodUsesBankDetails($payment->method)) {
                $payment->bank_name = trim((string) $request->input('bank_name', '')) ?: null;
                $payment->cheque_number = trim((string) $request->input('cheque_number', '')) ?: null;
                $payment->cheque_date = $request->filled('cheque_date')
                    ? $request->input('cheque_date')
                    : null;
            }

            $location_id = optional($payment->transaction)->location_id ?: $request->input('location_id');
            $new_account_id = $request->input('account_id');

            if (empty($new_account_id) || $old_method !== $payment->method) {
                $new_account_id = $this->resolveCustomerPaymentAccountId(
                    $payment->method,
                    $location_id,
                    $payment->business_id,
                    $new_account_id
                );
            }

            if (!empty($new_account_id)) {
                $payment->account_id = $new_account_id;
            }

            $this->normalizeCustomerPaymentMethodFields($payment, $old_method);

            $payment->save();
            $transaction = $payment->transaction;
            $is_sell_like_transaction = !empty($transaction) && in_array($transaction->type, ['sell', 'property_sell']);

            if (!$is_sell_like_transaction) {
                $this->syncCustomerPaymentEntries($payment);
            }

            $contact_ledger_update = [
                'amount' => $payment->amount,
                'operation_date' => $payment->paid_on,
            ];

            // Direct customer payments have no transaction from which the ledger
            // can derive the contact, so keep their ContactLedger contact aligned
            // when the customer is changed. Invoice-linked payments retain the
            // invoice customer's ledger ownership.
            if (
                empty($payment->transaction_id)
                && !empty($payment->payment_for)
                && Schema::hasColumn('contact_ledgers', 'contact_id')
            ) {
                $contact_ledger_update['contact_id'] = $payment->payment_for;
            }

            ContactLedger::where('transaction_payment_id', $payment->id)
                ->update($contact_ledger_update);

            // Sync parent payment total if this is a child payment
            if (!empty($payment->parent_id)) {
                $parent = TransactionPayment::where('business_id', $business_id)->find($payment->parent_id);
                if ($parent) {
                    $parent->amount = TransactionPayment::where('business_id', $business_id)
                        ->where('parent_id', $parent->id)
                        ->sum('amount');
                    $parent->paid_on = $payment->paid_on;
                    $parent->method = $payment->method;
                    $parent->note = $payment->note;
                    $parent->bank_name = $payment->bank_name;
                    $parent->cheque_number = $payment->cheque_number;
                    $parent->cheque_date = $payment->cheque_date;
                    if (!empty($payment->account_id)) {
                        $parent->account_id = $payment->account_id;
                    }
                    $this->normalizeCustomerPaymentMethodFields($parent, $old_method);
                    $parent->save();

                    if (!$is_sell_like_transaction) {
                        $this->syncCustomerPaymentEntries($parent);
                    }

                    ContactLedger::where('transaction_payment_id', $parent->id)
                        ->update([
                            'amount' => $parent->amount,
                            'operation_date' => $parent->paid_on,
                        ]);

                    if ($is_sell_like_transaction) {
                        /*
                         * Do NOT call TransactionUtil::updatePaymentAtOnce() here.
                         * That legacy helper is a redistribution routine and has a
                         * forceDelete() branch for child transaction_payments. It is
                         * appropriate when reallocating a payment, but not when an
                         * operator is merely editing an existing payment row from a
                         * report. Keep every allocation row and only synchronize the
                         * non-amount parent fields to its existing children.
                         */
                        $this->syncCustomerPaymentChildrenWithoutDeleting($parent, $business_id);
                    }
                }

                // IMPORTANT: When a payment has a parent_id, account/ledger entries must be
                // attached to the parent to avoid duplicate rows in account books.
                $this->deleteChildPaymentLedgerEntries($payment->id);
            }

            /*
             * Direct customer payments (Pay Due / Advance / etc.) intentionally
             * have transaction_id = NULL. Calling updatePaymentStatus(NULL) makes
             * the core utility try to read final_total from a non-existent
             * transaction, which is the exact production error reported for
             * payment 37. Only invoice-linked payments have a status to update.
             */
            if (!empty($transaction)) {
                $this->transactionUtil->updatePaymentStatus($transaction->id);
            }

            if ($is_sell_like_transaction) {
                event(new TransactionPaymentUpdated($payment, $transaction->type));
                
                // Update only account rows belonging to this payment (or its
                // parent row). Do not rewrite every payment posted to the invoice.
                $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                $account_payment_ids = array_values(array_filter([
                    (int) $payment->id,
                    !empty($payment->parent_id) ? (int) $payment->parent_id : null,
                ]));

                AccountTransaction::where('transaction_id', $transaction->id)
                    ->where('account_id', $accounts_receivable_id)
                    ->whereIn('transaction_payment_id', $account_payment_ids)
                    ->update(['operation_date' => $payment->paid_on]);

                $payment_account_update = [
                    'amount' => $payment->amount,
                    'operation_date' => $payment->paid_on,
                    'cheque_number' => $payment->cheque_number,
                    'cheque_date' => $payment->cheque_date,
                ];
                if (!empty($payment->account_id)) {
                    $payment_account_update['account_id'] = $payment->account_id;
                }

                AccountTransaction::where('transaction_id', $transaction->id)
                    ->whereIn('transaction_payment_id', $account_payment_ids)
                    ->where('account_id', '!=', $accounts_receivable_id)
                    ->update($payment_account_update);

                // Defensive: if earlier code created duplicates, keep only one debit and one credit
                // entry per payment id, and remove any extra rows.
                $this->dedupePaymentAccountTransactions($payment);
            }

            // Record customer reassignment separately. The Customers payment
            // views already understand `customer_changed` activities and can
            // surface the old/new customer names for audit purposes.
            $new_customer_id = (int) ($payment->payment_for ?: optional($transaction)->contact_id);
            if ($new_customer && $new_customer_id !== $old_customer_id) {
                $customer_change_activity = new Activity();
                $customer_change_activity->log_name = 'Contact Payment';
                $customer_change_activity->description = 'customer_changed';
                $customer_change_activity->subject_id = $id;
                $customer_change_activity->subject_type = 'App\TransactionPayment';
                $customer_change_activity->causer_id = auth()->user()->id;
                $customer_change_activity->causer_type = 'App\User';
                $customer_change_activity->properties = [
                    'message' => 'Customer changed from '
                        . ($old_customer ? $old_customer->name : 'N/A')
                        . ' to ' . $new_customer->name
                        . ' by ' . auth()->user()->username,
                    'old_customer_id' => $old_customer_id ?: null,
                    'new_customer_id' => $new_customer->id,
                    'old_customer_name' => $old_customer ? $old_customer->name : 'N/A',
                    'new_customer_name' => $new_customer->name,
                    'changed_by' => auth()->user()->username,
                ];
                $customer_change_activity->created_at = date('Y-m-d H:i');
                $customer_change_activity->updated_at = date('Y-m-d H:i');
                $customer_change_activity->save();
            }

            // Log edit to Contact User Activity
            $activity_contact_id = (int) ($payment->payment_for ?: optional($transaction)->contact_id);
            $contact = $activity_contact_id
                ? Contact::where('business_id', $business_id)->find($activity_contact_id)
                : null;
            $contact_name = $contact ? $contact->name : 'N/A';

            $changed_msg = "Contact #{$contact_name} - Payment Ref: {$payment->payment_ref_no} edited by "
                . auth()->user()->username
                . " | Old amount: {$old_amount} -> New: {$payment->amount}"
                . " | Old date: {$old_paid_on} -> New: {$payment->paid_on}";

            $activity               = new Activity();
            $activity->log_name     = 'Contact Payment';
            $activity->description  = 'edit';
            $activity->subject_id   = $id;
            $activity->subject_type = 'App\TransactionPayment';
            $activity->causer_id    = auth()->user()->id;
            $activity->causer_type  = 'App\User';
            $activity->properties   = ['message' => $changed_msg];
            $activity->created_at   = date('Y-m-d H:i');
            $activity->updated_at   = date('Y-m-d H:i');
            $activity->save();

            /*
             * Final safety gate: no payment row that existed when Edit started
             * may be soft-deleted, force-deleted, moved to another business, or
             * detached from its original transaction/parent by the edit workflow.
             * Throwing here rolls the whole DB transaction back.
             */
            $this->assertCustomerPaymentEditIntegrity($payment_integrity_snapshot, $business_id);

            DB::commit();

            return ['success' => true, 'msg' => __('purchase.payment_updated_success')];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            /*
             | LA-1192: a missing payment is not "something went wrong".
             |
             | findOrFail() and abort() both throw inside this try block, so a
             | payment belonging to another business - or already deleted in
             | another tab - produced the same generic message as a real failure.
             | The operator was told nothing, and neither was anyone reading the
             | log afterwards.
             */
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return [
                'success' => false,
                'msg' => 'This payment could not be found. It may have been deleted, or it belongs to another business.',
            ];

        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // abort(403) / abort(404) raised deliberately above.
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return [
                'success' => false,
                'msg' => $e->getStatusCode() === 403
                    ? 'You do not have permission to edit this payment.'
                    : 'This payment could not be found.',
            ];

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            /*
             | LA-1192: record enough to identify the cause next time.
             |
             | The message alone was logged, without the file, line or the values
             | being saved - so "Customer payment update failed" gave no way to
             | tell a validation problem from a missing account or a database
             | constraint. This was reported as intermittent, which is exactly the
             | case where the log has to carry the detail.
             |
             | The operator still sees a safe generic message; the specifics go to
             | the log, not the screen.
             */
            \Log::error('Customer payment update failed', [
                'payment_id' => $id,
                'business_id' => $business_id,
                'user_id' => optional(auth()->user())->id,
                'submitted' => $request->only([
                    'customer_id', 'paid_on', 'amount', 'method', 'note', 'account_id',
                    'bank_name', 'cheque_number', 'cheque_date',
                ]),
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**
     * Snapshot the live rows in the logical payment group before an edit.
     *
     * The snapshot intentionally stores only identity fields. Amount, method,
     * date, customer and account are editable; business/transaction/parent
     * identity and row existence are not.
     */
    private function customerPaymentEditIntegritySnapshot(TransactionPayment $payment, int $business_id): array
    {
        $ids = collect([(int) $payment->id]);

        if (!empty($payment->parent_id)) {
            $parent_id = (int) $payment->parent_id;
            $ids->push($parent_id);
            $ids = $ids->merge(
                TransactionPayment::withTrashed()
                    ->where('business_id', $business_id)
                    ->where('parent_id', $parent_id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
            );
        } else {
            $ids = $ids->merge(
                TransactionPayment::withTrashed()
                    ->where('business_id', $business_id)
                    ->where('parent_id', $payment->id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
            );
        }

        $ids = $ids->filter()->map(fn ($value) => (int) $value)->unique()->values();

        return TransactionPayment::withTrashed()
            ->where('business_id', $business_id)
            ->whereIn('id', $ids->all())
            ->whereNull('deleted_at')
            ->get(['id', 'business_id', 'transaction_id', 'parent_id'])
            ->mapWithKeys(function ($row) {
                return [
                    (int) $row->id => [
                        'business_id' => (int) $row->business_id,
                        'transaction_id' => $row->transaction_id === null ? null : (int) $row->transaction_id,
                        'parent_id' => $row->parent_id === null ? null : (int) $row->parent_id,
                    ],
                ];
            })
            ->all();
    }

    /**
     * Synchronize group-wide payment attributes without reallocating amounts or
     * deleting any child row.
     */
    private function syncCustomerPaymentChildrenWithoutDeleting(TransactionPayment $parent, int $business_id): void
    {
        $children = TransactionPayment::where('business_id', $business_id)
            ->where('parent_id', $parent->id)
            ->lockForUpdate()
            ->get();

        foreach ($children as $child) {
            $previous_method = $child->method;

            $child->paid_on = $parent->paid_on;
            $child->method = $parent->method;
            $child->note = $parent->note;
            $child->bank_name = $parent->bank_name;
            $child->cheque_number = $parent->cheque_number;
            $child->cheque_date = $parent->cheque_date;

            if (!empty($parent->account_id)) {
                $child->account_id = $parent->account_id;
            }

            $this->normalizeCustomerPaymentMethodFields($child, $previous_method);
            $child->save();

            // Keep ledger/account dates and methods aligned with the preserved
            // allocation. Amount is deliberately left untouched.
            $this->syncCustomerPaymentEntries($child);

            ContactLedger::where('transaction_payment_id', $child->id)
                ->update([
                    'amount' => $child->amount,
                    'operation_date' => $child->paid_on,
                ]);

            if (!empty($child->transaction_id)) {
                $this->transactionUtil->updatePaymentStatus($child->transaction_id);
            }
        }
    }

    /**
     * Abort and rollback an edit if any payment/allocation vanished or had its
     * immutable identity changed while the edit workflow was running.
     */
    private function assertCustomerPaymentEditIntegrity(array $snapshot, int $business_id): void
    {
        if (empty($snapshot)) {
            throw new \RuntimeException('Customer payment integrity snapshot is empty.');
        }

        $rows = TransactionPayment::withTrashed()
            ->whereIn('id', array_keys($snapshot))
            ->get(['id', 'business_id', 'transaction_id', 'parent_id', 'deleted_at'])
            ->keyBy('id');

        foreach ($snapshot as $payment_id => $before) {
            $after = $rows->get($payment_id);

            if (!$after || !empty($after->deleted_at)) {
                throw new \RuntimeException(
                    'Customer payment edit attempted to delete payment/allocation #' . $payment_id
                );
            }

            $after_transaction_id = $after->transaction_id === null ? null : (int) $after->transaction_id;
            $after_parent_id = $after->parent_id === null ? null : (int) $after->parent_id;

            if (
                (int) $after->business_id !== (int) $business_id ||
                (int) $after->business_id !== (int) $before['business_id'] ||
                $after_transaction_id !== $before['transaction_id'] ||
                $after_parent_id !== $before['parent_id']
            ) {
                throw new \RuntimeException(
                    'Customer payment edit changed immutable payment identity for #' . $payment_id
                );
            }
        }
    }

    /**
     * Deletes account & contact ledger rows linked to a child payment id.
     * This prevents duplicate account book rows when parent_id is used.
     */
    private function deleteChildPaymentLedgerEntries($child_payment_id)
    {
        AccountTransaction::where('transaction_payment_id', $child_payment_id)->delete();
        ContactLedger::where('transaction_payment_id', $child_payment_id)->delete();
    }

    /**
     * Ensure only one debit & one credit account transaction exist per payment id.
     * (Some older flows created multiple account_transactions rows on edit.)
     */
    private function dedupePaymentAccountTransactions(TransactionPayment $payment)
    {
        $entries = AccountTransaction::where('transaction_payment_id', $payment->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'type']);

        if ($entries->isEmpty()) {
            return;
        }

        $keep_ids = collect();
        foreach (['debit', 'credit'] as $type) {
            $first = $entries->firstWhere('type', $type);
            if (!empty($first)) {
                $keep_ids->push($first->id);
            }
        }

        $delete_ids = $entries->pluck('id')->diff($keep_ids)->values()->all();
        if (!empty($delete_ids)) {
            AccountTransaction::whereIn('id', $delete_ids)->delete();
        }
    }

    private function resolveCustomerPaymentAccountId($method, $location_id, $business_id, $requested_account_id = null)
    {
        if (!empty($requested_account_id)) {
            return $requested_account_id;
        }

        $default_account_id = !empty($location_id)
            ? $this->transactionUtil->getDefaultAccountId($method, $location_id)
            : null;

        if (!empty($default_account_id)) {
            return $default_account_id;
        }

        $fallback_names = [];
        if ($method === 'cash') {
            $fallback_names = ['Cash'];
        } elseif ($method === 'cheque') {
            $fallback_names = ['Cheques in Hand'];
        } elseif ($method === 'card') {
            $fallback_names = ['Cards (Credit Debit) Account', 'Cards'];
        }

        if (empty($fallback_names)) {
            return null;
        }

        return Account::where('business_id', $business_id)
            ->whereIn('name', $fallback_names)
            ->value('id');
    }

    private function customerPaymentMethodUsesBankDetails($method): bool
    {
        return in_array(strtolower((string) $method), [
            'bank',
            'online',
            'cheque',
            'bank_transfer',
            'direct_bank_deposit',
            'bank_deposit',
        ], true);
    }

    private function normalizeCustomerPaymentMethodFields(TransactionPayment $payment, $previous_method = null)
    {
        if ($this->customerPaymentMethodUsesBankDetails($payment->method)) {
            // Bank/Online/Cheque methods use the bank detail fields displayed by
            // the Payment Report edit form. They do not use card_number.
            $payment->card_number = null;
            $payment->is_deposited = 0;

            if (
                $payment->method === 'cheque'
                && $previous_method !== 'cheque'
                && empty($payment->cheque_date)
                && !empty($payment->paid_on)
            ) {
                $payment->cheque_date = date('Y-m-d', strtotime($payment->paid_on));
            }
        } elseif ($payment->method === 'cash') {
            $payment->bank_name = null;
            $payment->cheque_number = null;
            $payment->cheque_date = null;
            $payment->card_number = null;
            $payment->related_account_id = null;
            $payment->is_deposited = 0;
        } elseif ($payment->method === 'card') {
            $payment->bank_name = null;
            $payment->cheque_number = null;
            $payment->cheque_date = null;
            $payment->related_account_id = null;
            $payment->is_deposited = 0;
        }
    }

    private function syncCustomerPaymentEntries(TransactionPayment $payment)
    {
        $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        $contact_id = optional($payment->transaction)->contact_id ?: $payment->payment_for;

        // Get customer name for description
        $customer = Contact::find($contact_id);
        $customer_name = $customer ? $customer->name : 'Unknown Customer';
        
        // Create description with customer name
        $description = trim($payment->note);
        if (!empty($description)) {
            $description = "Payment from {$customer_name} - {$description}";
        } else {
            $description = "Payment from {$customer_name}";
        }

        $shared_data = [
            'amount' => $payment->amount,
            'contact_id' => $contact_id,
            'operation_date' => $payment->paid_on,
            'business_id' => $payment->business_id,
            'created_by' => $payment->created_by ?: (auth()->user()->id ?? null),
            'transaction_id' => $payment->transaction_id,
            'transaction_payment_id' => $payment->id,
            'note' => $description,
            'cheque_date' => $payment->cheque_date,
            'cheque_number' => $payment->cheque_number,
            'related_account_id' => $payment->related_account_id,
            'post_dated_cheque' => $payment->post_dated_cheque ?? 0,
            'update_post_dated_cheque' => $payment->update_post_dated_cheque ?? 0,
        ];

        if (!empty($payment->account_id)) {
            $payment_entry_data = array_merge($shared_data, [
                'account_id' => $payment->account_id,
                'type' => 'debit',
                'sub_type' => null,
            ]);

            $this->syncCustomerPaymentAccountEntry(
                $payment,
                function ($query) use ($accounts_receivable_id) {
                    $query->where(function ($subQuery) use ($accounts_receivable_id) {
                        $subQuery->where('type', 'debit');

                        if (!empty($accounts_receivable_id)) {
                            $subQuery->orWhere('account_id', '!=', $accounts_receivable_id);
                        }
                    });
                },
                $payment_entry_data
            );
        }

        if (!empty($accounts_receivable_id)) {
            $receivable_entry_data = array_merge($shared_data, [
                'account_id' => $accounts_receivable_id,
                'type' => 'credit',
                'sub_type' => 'ledger_show',
            ]);

            $this->syncCustomerPaymentAccountEntry(
                $payment,
                function ($query) use ($accounts_receivable_id) {
                    $query->where(function ($subQuery) use ($accounts_receivable_id) {
                        $subQuery->where('account_id', $accounts_receivable_id)
                            ->orWhere(function ($creditQuery) {
                                $creditQuery->where('type', 'credit')
                                    ->where(function ($inner) {
                                        $inner->where('sub_type', 'ledger_show')
                                            ->orWhereNull('sub_type');
                                    });
                            });
                    });
                },
                $receivable_entry_data
            );
        }
    }

    private function syncCustomerPaymentAccountEntry(TransactionPayment $payment, callable $constraint, array $entry_data)
    {
        $query = AccountTransaction::where('transaction_payment_id', $payment->id)
            ->whereNull('deleted_at');

        $constraint($query);

        $entries = $query->orderBy('id')->get();
        $primary_entry = $entries->first();

        if ($primary_entry) {
            $primary_entry->update($entry_data);

            if ($entries->count() > 1) {
                $duplicate_ids = $entries->pluck('id')->slice(1)->all();
                AccountTransaction::whereIn('id', $duplicate_ids)->delete();
            }

            return $primary_entry;
        }

        return AccountTransaction::createAccountTransaction($entry_data);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        if (
            !auth()->user()->can('purchase.delete.payments') && !auth()->user()->can('purchase.payments') &&
            !auth()->user()->can('purchase.edit.payments') &&
            !auth()->user()->can('add.payments') && !auth()->user()->can('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = (int) (request()->session()->get('user.business_id')
                ?: request()->session()->get('business.id')
                ?: optional(auth()->user())->business_id);

            try {
                $payment = TransactionPayment::where('business_id', $business_id)->findOrFail($id);

                // Check if cheque is deposited or transferred - prevent deletion
                if (($payment->method == 'cheque' || $payment->method == 'bank_transfer')) {
                    // Check if this cheque has been returned
                    $cheque_return = Transaction::where('business_id', $business_id)
                        ->where('type', 'cheque_return')
                        ->where('contact_id', $payment->payment_for)
                        ->whereExists(function($query) use ($payment) {
                            $query->select(DB::raw(1))
                                ->from('account_transactions')
                                ->whereColumn('account_transactions.transaction_id', 'transactions.id')
                                ->where('account_transactions.cheque_number', $payment->cheque_number)
                                ->whereNull('account_transactions.deleted_at');
                        })
                        ->first();
                    
                    if (!empty($cheque_return)) {
                        $output = [
                            'success' => false,
                            'msg' => __('lang_v1.cannot_edit_delete_returned_cheque'),
                        ];
                        return $output;
                    }
                    
                    // Check if is_deposited flag is explicitly set to 1
                    // This flag is set when a cheque is deposited to a bank account
                    if ($payment->is_deposited == 1) {
                        $output = [
                            'success' => false,
                            'msg' => __('lang_v1.cheque_already_deposited_cannot_delete'),
                        ];
                        return $output;
                    }

                    // Check if there's a cheque_deposit_bank record linked through account_transactions
                    // This indicates the cheque has been deposited to a bank account
                    $cheque_deposit = DB::table('cheque_deposit_bank')
                        ->where('cheque_number', $payment->cheque_number)
                        ->exists();

                    if ($cheque_deposit) {
                        $output = [
                            'success' => false,
                            'msg' => __('lang_v1.cheque_already_deposited_cannot_delete'),
                        ];
                        return $output;
                    }
                }

                DB::beginTransaction();

                $transaction_id = $payment->transaction_id;
                $transaction = Transaction::where('business_id', $business_id)->findOrFail($transaction_id);

                if (!empty($payment->parent_id)) {

                    $parent_payment = TransactionPayment::where('business_id', $business_id)->find($payment->parent_id);

                    $parent_payment->amount -= $payment->amount;

                    if ($parent_payment->amount <= 0) {
                        $parent_payment_id = $parent_payment->id;
                        $parent_payment_account_id = $parent_payment->account_id;

                        $parent_payment->deleted_by = auth()->user()->id;
                        $parent_payment->save();
                        $parent_payment->delete();

                        if ($transaction->type != 'purchase' && $transaction->sub_type != 'excess' && $transaction->sub_type != 'shortage') {
                            event(new TransactionPaymentDeleted($parent_payment_id, $parent_payment_account_id));
                        }
                    } else {

                        $parent_payment->save();
                        $this->transactionUtil->syncPaymentAccountAndLedgerEntries($parent_payment);
                    }
                }

                $payment_ref = $payment->payment_ref_no;
                $payment_amt = $payment->amount;

                $this->transactionUtil->deleteAccountAndLedgerTransactionReverse($transaction, $id);

                $payment->deleted_by = auth()->user()->id;
                $payment->save();

                $payment->delete();

                //update payment status

                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                $contact_name = Contact::where('business_id', $business_id)->where('id', $transaction->contact_id)->value('name') ?: 'N/A';
                $changed_msg = "Contact #{$contact_name} - Payment Ref: {$payment_ref} Transaction has been deleted by " . auth()->user()->username;

                $activity = new Activity();
                $activity->log_name = "Contact Payment";
                $activity->description = "delete";
                $activity->subject_id = $id;
                $activity->subject_type = "";
                $activity->causer_id = auth()->user()->id;
                $activity->causer_type = 'App\AccountTransaction';
                $activity->properties = $changed_msg;
                $activity->created_at = date('Y-m-d H:i');
                $activity->updated_at = date('Y-m-d H:i');

                // Save the activity
                $activity->save();

                if ($transaction->sub_type == 'excess' || $transaction->sub_type == 'shortage') {

                    $transaction->payment_status = 'due';

                    $transaction->save();
                }

                if ($transaction->type != 'purchase' && $transaction->sub_type != 'excess' && $transaction->sub_type != 'shortage') {

                    event(new TransactionPaymentDeleted($payment->id, $payment->account_id));
                }

                DB::commit();

                $output = [

                    'success' => true,

                    'msg' => __('purchase.payment_deleted_success')

                ];
            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                logger($e);

                $output = [

                    'success' => false,

                    'msg' => __('messages.something_went_wrong')

                ];
            }

            return $output;
        }
    }
}
