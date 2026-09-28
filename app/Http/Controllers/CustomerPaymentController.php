<?php

namespace App\Http\Controllers;

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
use LDAP\Result;
use Modules\Superadmin\Entities\Package;
use Yajra\DataTables\Facades\DataTables;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Utils\ContactUtil;
use Modules\Petro\Entities\PetroDailyShift;
use Spatie\Activitylog\Models\Activity;

class CustomerPaymentController extends Controller
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        $business_id = request()->session()->get('user.business_id');
        $business_details = Business::find($business_id);
        $paid_in_types = [
            'customer_page' => 'Customer Page',
            'all_sale_page' => 'All Sale Page',
            'settlement' => 'Settlement',
            'customer_bulk' => 'Customer Bulk',
            'customer_simple' => 'Customer Simple'
        ];
        $latest_ref_number = 0;
        $latest_ref_number_PP = 0;
        // $latest_ref_number_CPB = 0;
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
        // $latest_ref_number_CPB = (int)explode('CPB-', $latest_ref_number_CPB);

        $latest_ref_number_CPB = DB::table('transaction_payments')->where('paid_in_type', 'customer_bulk')->orderBy('created_at', 'DESC')->first();
        $latest_ref_number_CPB = $latest_ref_number_CPB ? $latest_ref_number_CPB->payment_ref_no : "CPB-00";
        $latest_ref_number_CPB = (int) explode('CPB-', $latest_ref_number_CPB)[1];

        $latest_ref_number_CPS = (int) explode('CPS-', $latest_ref_number_CPS);
        $latest_ref_number += 1;
        $latest_ref_number_PP += 1;
        $latest_ref_number_CPB += 1;
        $latest_ref_number_CPS += 1;
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
                ->leftJoin(
                    'account_transactions as deposit_at',
                    function ($join) {
                        $join->on('deposit_at.transaction_payment_id', '=', 'tp.id')
                             ->whereIn('deposit_at.sub_type', ['deposit', 'fund_transfer'])
                             ->whereNull('deposit_at.deleted_at');
                    }
                )
                ->where('transactions.business_id', $business_id)
                ->where('contacts.type', 'customer')
                ->whereNull('tp.deleted_at')
                ->whereIn('transactions.payment_status', ['paid', 'partial'])
                ->where(function ($q) {
                    $q->whereIn('transactions.type', $this->contactUtil->payable_customer_txns)->orWhere('transactions.is_credit_sale', 1);
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
                    'tp.amount as total_paid',
                    'tp.created_at',
                    'tp.transfer_date',
                    // Modified by Engr. Alex -- task 7889
                    'tp.note',
                    DB::raw('MAX(deposit_at.operation_date) as cheque_deposit_transfer_date')
                    //DB::raw('SUM(tp.amount) as total_paid')
                );

            // dd($sells);
            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->shift_number)) {
                $shift_number = request()->shift_number;
                $sells->where('tp.shift_number', $shift_number);
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
            } else {
                // IS1485: Direct settlement cash/card totals must not appear in Customer Bulk Payments list.
                $sells->where(function ($q) {
                    $q->whereNull('tp.paid_in_type')->orWhere('tp.paid_in_type', '!=', 'settlement');
                });
            }
            if (!empty(request()->payment_amount)) {
                $sells->where('tp.amount', request()->payment_amount);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('tp.paid_on', '>=', $start)
                    ->whereDate('tp.paid_on', '<=', $end);
            }
            $sells->groupBy(DB::raw('CASE WHEN tp.paid_in_type = "customer_page" THEN tp.parent_id ELSE tp.payment_ref_no END'))->orderBy('tp.id', 'desc');

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
                            $html .= '<li><a href="#" data-href="' . action("CustomerPaymentController@viewPayment", [$row->tp_id]) . '" class="btn-modal-view" data-container=".view_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
                        // Modified by Engr. Alex -- task 7889: Edit button inside action submenu
                        if (auth()->user()->can("sell.payments") || auth()->user()->can("purchase.edit.payments") || auth()->user()->can("add.payments")) {
                            $html .= '<li><a href="#" class="edit_payment"
                                        data-href="' . action("CustomerPaymentController@edit", [$row->tp_id]) . '"
                                        data-update-href="' . action("CustomerPaymentController@update", [$row->tp_id]) . '"
                                        ><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>';
                        }
                        if (auth()->user()->can("list_customer_payments.delete")) {
                            $html .= '<li><a href="#" class="delete_payment"
                                        data-href="' . action("CustomerPaymentController@destroy", [$row->tp_id]) . '"
                                        ><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</a></li>';
                        }
                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->addColumn('payment_amount', function ($row) use ($business_details) {
                    if (!empty($row->parent_id)) {
                        $parent_payment = TransactionPayment::where('id', $row->parent_id)->first();
                        // logger(json_encode($parent_payment));
                        if (!empty($parent_payment)) {
                            return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $parent_payment->amount . '">' . $this->productUtil->num_f($parent_payment->amount, false, $business_details, false) . '</span>';
                        } else {
                            return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $this->productUtil->num_f($row->total_paid, false, $business_details, false) . '</span>';
                        }
                    } else {
                        return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $this->productUtil->num_f($row->total_paid, false, $business_details, false) . '</span>';
                    }
                })->addColumn('name', function ($row) {
                    $display_name = e($row->name);

                    $activity = Activity::where('subject_type', 'App\\TransactionPayment')
                        ->where('description', 'customer_changed')
                        ->where(function ($query) use ($row) {
                            $query->where('subject_id', $row->tp_id);
                            if (!empty($row->parent_id)) {
                                $query->orWhere('subject_id', $row->parent_id);
                            }
                        })
                        ->latest('created_at')
                        ->first();

                    if ($activity) {
                        $details = $this->extractCustomerChangeDetails($activity);

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
                    }

                    return $display_name;
                })
                ->editColumn('payment_ref_no', function ($row) {
                    if (!empty($row->parent_id)) {
                        $parent_payment = TransactionPayment::where('id', $row->parent_id)->first();
                        if (!empty($parent_payment)) {
                            $ref = $parent_payment->payment_ref_no;
                        } else {
                            $ref = $row->payment_ref_no;
                        }
                    } else {
                        $ref = $row->payment_ref_no;
                    }

                    return '<a href="#" data-href="' . action("CustomerPaymentController@viewPayment", [$row->tp_id]) . '" class="btn-modal-view" data-container=".view_modal">' . $ref . '</a>';
                })
                ->addColumn('interest', function ($row) use ($business_details) {
                    $totalAmount = DB::table('transaction_payments')
                        ->join('account_transactions', 'transaction_payments.id', '=', 'account_transactions.transaction_payment_id')
                        ->where('payment_ref_no', $row->payment_ref_no)
                        ->sum('account_transactions.interest');


                    return $this->productUtil->num_f($totalAmount, false, $business_details, false);
                })
                ->removeColumn('id')
                ->editColumn('final_total', function ($row) use ($business_details) {
                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->final_total . '">' . $this->productUtil->num_f($row->final_total, false, $business_details, false) . '</span>';
                })
                ->editColumn('total_paid', function ($row) use ($business_details) {


                    if (!empty($row->parent_id)) {
                        $parent_payment = TransactionPayment::where('id', $row->parent_id)->first();
                        if (!empty($parent_payment)) {
                            $totalAmount = $parent_payment->amount;
                        } else {
                            $totalAmount = DB::table('transaction_payments')
                                ->where('payment_ref_no', $row->payment_ref_no)
                                ->sum('amount');
                        }
                    } else {
                        $totalAmount = DB::table('transaction_payments')
                            ->where('payment_ref_no', $row->payment_ref_no)
                            ->sum('amount');
                    }

                    $total_paid_html = '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . ($totalAmount) . '">' . $this->productUtil->num_f(($totalAmount), false, $business_details, false) . '</span>';


                    return $total_paid_html;
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->editColumn('paid_on', '{{@format_date($paid_on)}}')
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
                    $invoice_no = $row->invoice_no;
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
                    // Show deposit/transfer date for cheques and bank transfers
                    if ($row->method == 'cheque' || $row->method == 'bank_transfer') {
                        $dateToFormat = null;
                        
                        // First check for deposit/transfer date from account_transactions (from join)
                        if (!empty($row->cheque_deposit_transfer_date)) {
                            $dateToFormat = $row->cheque_deposit_transfer_date;
                        }
                        // If not found in join, query directly for this payment
                        else {
                            $depositTransaction = AccountTransaction::where('transaction_payment_id', $row->tp_id)
                                ->whereIn('sub_type', ['deposit', 'fund_transfer'])
                        ->whereNotNull('transfer_transaction_id')
                                ->whereNull('deleted_at')
                                ->orderBy('operation_date', 'desc')
                                ->first();
                            
                            if (!empty($depositTransaction) && !empty($depositTransaction->operation_date)) {
                                $dateToFormat = $depositTransaction->operation_date;
                            }
                            // Fallback to transfer_date from transaction_payments table
                            elseif (!empty($row->transfer_date)) {
                                $dateToFormat = $row->transfer_date;
                            }
                        }
                        
                        if ($dateToFormat) {
                            try {
                                $date = \Carbon\Carbon::parse($dateToFormat);
                                $dateFormat = session('business.date_format', 'd/m/Y');
                                $timeFormat = session('business.time_format', 24) == 24 ? 'H:i' : 'h:i A';
                                return $date->format($dateFormat . ' ' . $timeFormat);
                            } catch (\Exception $e) {
                                return $dateToFormat;
                            }
                        }
                    }
                    return '';
                })
                // Modified by Engr. Alex -- task 7889: note/description column for customer payments list
                ->addColumn('note', function ($row) {
                    return e($row->note ?? '');
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return action('SellController@show', [$row->id]);
                        } else {
                            return '';
                        }
                    }
                ]);
            $rawColumns = ['payment_ref_no', 'name', 'method', 'final_total', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'payment_amount'];
            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }
        $business_id = request()->session()->get('business.id');
        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();
        $package_manage = Package::where('only_for_business', $business_id)->first();
        $customer_interest_deduct_option = $business_details->customer_interest_deduct_option;
        $customer_groups = ContactGroup::where('contact_groups.business_id', $business_id)
            ->where('contact_groups.type', 'customer')
            ->pluck('name', 'id');
        $income_accounts = Account::leftJoin('account_types', 'accounts.account_type_id', 'account_types.id')
            ->where('account_types.name', 'Income')
            ->select(DB::raw('accounts.name as name, accounts.id as id'))
            ->pluck('name', 'id');

        $account_type_query = AccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id');
        $account_types_opts = $account_type_query->pluck('name', 'id');
        $account_type_query->with(['sub_types']);
        if (0 == 0) {
            $account_type_query->where(function ($q) {
                $q->where('name', 'Assets')->orWhere('name', 'Liabilities');
            });
        }
        $account_types = $account_type_query->get();
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
        $dailyCashShiftNumbers = PetroDailyShift::where('status', 0)
            ->pluck('shift_no');
        $dailyCashShiftNumbers = $dailyCashShiftNumbers->unique()->toArray();

        return view('customer_payments.index')->with(compact(
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
            'dailyCashShiftNumbers'
        ));
    }

    public function printPayment($id)
    {
        $business_id = request()->session()->get('business.id');

        try {

            $payment = TransactionPayment::where('id', $id)->with('contact')->first();
            if (!empty($payment->parent_id)) {
                $parent_payment = TransactionPayment::where('id', $payment->parent_id)->with('contact')->first();
                $child_payments = Transaction::leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                    ->select('tp.*', 'tp.id as tp_id', 'transactions.*')
                    ->where('transactions.business_id', $business_id)
                    ->where('tp.parent_id', $payment->parent_id)
                    ->with('contact')->get();
            } else {
                $parent_payment = $payment;
                $parent_payment->amount = DB::table('transaction_payments')
                    ->where('payment_ref_no', $payment->payment_ref_no)
                    ->sum('amount');

                $child_payments = Transaction::leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                    ->select('tp.*', 'tp.id as tp_id', 'transactions.*')
                    ->where('transactions.business_id', $business_id)
                    ->where('tp.payment_ref_no', $payment->payment_ref_no)
                    ->with('contact')->get();
            }

            // Fetch interest amounts for each payment
            foreach ($child_payments as $child_payment) {
                $interest = DB::table('account_transactions')
                    ->where('transaction_payment_id', $child_payment->tp_id)
                    ->whereNotNull('interest')
                    ->whereNull('deleted_at')
                    ->sum('interest');
                $child_payment->interest_amount = $interest ?? 0;
            }

            // Calculate total interest for parent payment
            $total_interest = DB::table('account_transactions')
                ->whereIn('transaction_payment_id', $child_payments->pluck('tp_id')->toArray())
                ->whereNotNull('interest')
                ->whereNull('deleted_at')
                ->sum('interest');

            $parent_payment->total_interest = $total_interest ?? 0;

            $company = Business::find($business_id);

            $receipt['html_content'] = view('customer_payments.partials.print_payment')
                ->with(compact(
                    'child_payments',
                    'parent_payment',
                    'company'
                ))->render();

            $output = ['success' => 1, 'receipt' => $receipt];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => trans("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
        return $output;
    }

    /**
     * Extracts customer change information from an activity log entry.
     */
    private function extractCustomerChangeDetails(?Activity $activity): array
    {
        $details = [
            'old_customer' => null,
            'new_customer' => null,
            'changed_by' => null,
            'message' => null,
        ];

        if (empty($activity)) {
            return $details;
        }

        $properties = $activity->properties;

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

        $business_id = request()->session()->get('business.id');

        // Fetch initial payment with contact relationship


        // $payment = TransactionPayment::with('contact')->findOrFail($id);
        $payment = TransactionPayment::with(['contact', 'transaction'])->findOrFail($id);


        // Initialize variables
        $parent_payment = $payment;
        $child_payments = collect();

        // Check for parent or use ref_no logic


        $child_payments = TransactionPayment::with('transaction')
            ->where(function ($query) use ($payment, $business_id) {
                if (!empty($payment->parent_id)) {
                    $query->where('parent_id', $payment->parent_id);
                } else {
                    $query->where('payment_ref_no', $payment->payment_ref_no);
                }
            })
            ->whereHas('transaction', function ($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->get();

        // Fetch interest amounts for each payment
        foreach ($child_payments as $child_payment) {
            $interest = DB::table('account_transactions')
                ->where('transaction_payment_id', $child_payment->id)
                ->whereNotNull('interest')
                ->whereNull('deleted_at')
                ->sum('interest');
            $child_payment->interest_amount = $interest ?? 0;
        }

        // Calculate total interest for parent payment
        $total_interest = DB::table('account_transactions')
            ->whereIn('transaction_payment_id', $child_payments->pluck('id')->toArray())
            ->whereNotNull('interest')
            ->whereNull('deleted_at')
            ->sum('interest');

        $parent_payment->total_interest = $total_interest ?? 0;

        // Calculate total amount from all child payments
        $parent_payment->amount = $child_payments->sum('amount');

        // Fetch company info
        $company = Business::find($business_id);

        // Determine location_id
        $location_id = $child_payments->first()->location_id ?? null;

        if (empty($location_id) && !empty($parent_payment->transaction_id)) {
            // $location_id = Transaction::where('id', $parent_payment->transaction_id)->value('location_id');
            $location_id = $child_payments->first()->transaction->location_id ?? null;
        }

        // Get location name
        // $location = $location_id ? BusinessLocation::where('id', $location_id)->value('name') : null;
        $location = optional(BusinessLocation::find($location_id))->name;

        // Fetch activity logs for customer changes
        $customer_change_logs = Activity::where('subject_type', 'App\TransactionPayment')
            ->where('subject_id', $payment->id)
            ->where('description', 'customer_changed')
            ->orderBy('created_at', 'desc')
            ->get();

        // Render receipt view
        // $receipt_data = view('customer_payments.partials.view_payment')
        //     ->with(compact('child_payments', 'parent_payment', 'company', 'location'))
        //     ->render();
        $receipt_data = cache()->remember('view_payment_' . $id . $business_id, now()->addMinutes(2), function () use ($child_payments, $parent_payment, $company, $location, $customer_change_logs) {
            return view('customer_payments.partials.view_payment')
                ->with(compact('child_payments', 'parent_payment', 'company', 'location', 'customer_change_logs'))
                ->render();
        });

        return view('customer_payments.view')
            ->with(compact('receipt_data', 'parent_payment', 'id'));
    }


    public function customerPaymentInformations($customer, $type)
    {

        $start_date = request()->start ?? null;
        $end_date = request()->end ?? null;
        $bank = request()->bank;
        $post_party_type = request()->post_party_type;
        $business_id = request()->session()->get('user.business_id');
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
        $business_id = request()->session()->get('user.business_id');
        $business_details = Business::find($business_id);
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
        $latest_ref_number_CPB += 1;
        $latest_ref_number_CPS += 1;
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
                            $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
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
                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->final_total . '">' . $this->productUtil->num_f($row->final_total, false, $business_details, false) . '</span>';
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
                    $total_payable = $row->final_total + $interest;
                    return '<span class="display_currency total-payable" data-currency_symbol="true" data-orig-value="' . $total_payable . '">' . $this->productUtil->num_f($total_payable, false, $business_details, false) . '</span>';
                })
                ->addColumn('total_remaining', function ($row) use ($business_details) {
                    $interest = $row->interest == Null ? 0 : $row->interest;
                    $total_payable = $row->final_total + $interest;
                    $total_paid = $row->total_paid == '' ? 0 : $row->total_paid;
                    $remaining = $total_payable - $total_paid;
                    return '<span class="display_currency total-remaining" data-currency_symbol="true" data-orig-value="' . $remaining . '">' . $this->productUtil->num_f($remaining, false, $business_details, false) . '</span>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('paid_on', '{{@format_date($paid_on)}}')
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
                    $invoice_no = $row->invoice_no;
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
        $business_id = request()->session()->get('business.id');
        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();
        $package_manage = Package::where('only_for_business', $business_id)->first();
        $customer_interest_deduct_option = $business_details->customer_interest_deduct_option;



        return view('customer_payments.index')->with(compact(
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

        $payment = TransactionPayment::with(['transaction.contact'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'payment' => [
                'id'             => $payment->id,
                'paid_on'        => $payment->paid_on,
                'amount'         => $payment->amount,
                'method'         => $payment->method,
                'note'           => $payment->note,
                'account_id'     => $payment->account_id,
                'location_id'    => optional($payment->transaction)->location_id,
                'cheque_number'  => $payment->cheque_number,
                'bank_name'      => $payment->bank_name,
                'card_number'    => $payment->card_number,
                'payment_ref_no' => $payment->payment_ref_no,
            ],
        ]);
    }

    // Modified by Engr. Alex -- task 7889
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

        try {
            DB::beginTransaction();

            $payment = TransactionPayment::with('transaction')->findOrFail($id);

            // Prevent edit if cheque has been returned or deposited
            if ($payment->method == 'cheque' || $payment->method == 'bank_transfer') {
                $cheque_return = Transaction::where('type', 'cheque_return')
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

            $old_amount  = $payment->amount;
            $old_paid_on = $payment->paid_on;
            $old_method  = $payment->method;

            // Update allowed fields
            $payment->paid_on = $request->input('paid_on', $payment->paid_on);
            $payment->amount  = $request->input('amount', $payment->amount);
            $payment->method  = $request->input('method', $payment->method);
            $payment->note    = $request->input('note', $payment->note);

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

            ContactLedger::where('transaction_payment_id', $payment->id)
                ->update([
                    'amount' => $payment->amount,
                    'operation_date' => $payment->paid_on,
                ]);

            // Sync parent payment total if this is a child payment
            if (!empty($payment->parent_id)) {
                $parent = TransactionPayment::find($payment->parent_id);
                if ($parent) {
                    $parent->amount = TransactionPayment::where('parent_id', $parent->id)->sum('amount');
                    $parent->paid_on = $payment->paid_on;
                    $parent->method = $payment->method;
                    $parent->note = $payment->note;
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
                        $this->transactionUtil->updatePaymentAtOnce($parent, $transaction->type);
                    }
                }

                // IMPORTANT: When a payment has a parent_id, account/ledger entries must be
                // attached to the parent to avoid duplicate rows in account books.
                $this->deleteChildPaymentLedgerEntries($payment->id);
            }

            $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

            if ($is_sell_like_transaction) {
                event(new TransactionPaymentUpdated($payment, $transaction->type));
                
                // Update existing account transactions instead of creating new ones
                $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                AccountTransaction::where('transaction_id', $transaction->id)
                    ->where('account_id', $accounts_receivable_id)
                    ->whereNotNull('transaction_payment_id')
                    ->update(['operation_date' => $payment->paid_on]);
                    
                // Update payment method account transactions
                AccountTransaction::where('transaction_id', $transaction->id)
                    ->whereNotNull('transaction_payment_id')
                    ->where('account_id', '!=', $accounts_receivable_id)
                    ->update([
                        'amount' => $payment->amount,
                        'operation_date' => $payment->paid_on,
                    ]);

                // Defensive: if earlier code created duplicates, keep only one debit and one credit
                // entry per payment id, and remove any extra rows.
                $this->dedupePaymentAccountTransactions($payment);
            }

            // Log edit to Contact User Activity
            $contact      = $transaction ? Contact::find($transaction->contact_id) : null;
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

            DB::commit();

            return ['success' => true, 'msg' => __('purchase.payment_updated_success')];

        } catch (\Exception $e) {
            DB::rollBack();
            logger($e);
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
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

    private function normalizeCustomerPaymentMethodFields(TransactionPayment $payment, $previous_method = null)
    {
        if ($payment->method === 'cash') {
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
        } elseif ($payment->method === 'bank_transfer') {
            $payment->card_number = null;
            $payment->is_deposited = 0;
        } elseif ($payment->method === 'cheque') {
            $payment->card_number = null;
            if ($previous_method !== 'cheque' && empty($payment->cheque_date) && !empty($payment->paid_on)) {
                $payment->cheque_date = date('Y-m-d', strtotime($payment->paid_on));
            }
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

            try {
                DB::beginTransaction();
                $payment = TransactionPayment::findOrFail($id);

                // Check if cheque is deposited or transferred - prevent deletion
                if (($payment->method == 'cheque' || $payment->method == 'bank_transfer')) {
                    // Check if this cheque has been returned
                    $cheque_return = Transaction::where('type', 'cheque_return')
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

                $transaction_id = $payment->transaction_id;
                $transaction = Transaction::findOrFail($transaction_id);

                if (!empty($payment->parent_id)) {

                    $parent_payment = TransactionPayment::find($payment->parent_id);

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

                $changed_msg = "Contact #" . (Contact::find($transaction->contact_id))->name . " - Payment Ref: {$payment_ref} Transaction has been deleted by " . auth()->user()->username;

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
