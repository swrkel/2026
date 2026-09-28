<?php



namespace App\Http\Controllers;



use App\User;

use App\Account;

use App\Contact;

use App\TaxRate;

use App\Business;
use App\AccountType;
use App\System;
use App\Transaction;
use App\ContactLedger;
use App\ExpenseCategory;

use App\BusinessLocation;

use App\Utils\ModuleUtil;

use App\AccountTransaction;

use App\TransactionPayment;

use App\Utils\BusinessUtil;

use Illuminate\Http\Request;

use App\NotificationTemplate;

use App\Utils\TransactionUtil;

use App\Utils\NotificationUtil;

use Modules\Fleet\Entities\Fleet;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use Illuminate\Support\Facades\Auth;
use Modules\Finance\Services\FinanceBudgetControlService;
use Illuminate\Support\Facades\Gate;

use App\Providers\AppServiceProvider;

use Modules\Petro\Entities\PetroDailyShift;
use Modules\Superadmin\Entities\Package;

use Yajra\DataTables\Facades\DataTables;

use Modules\Fleet\Entities\RouteOperation;
use Modules\Property\Entities\PaymentOption;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Essentials\Entities\EssentialsEmployee;

class ExpenseController extends Controller

{

    protected $transactionUtil;

    protected $moduleUtil;

    protected $notificationUtil;

    protected $businessUtil;

    protected $dummyPaymentLine;

    /**

     * Constructor

     *

     * @param TransactionUtil $transactionUtil

     * @return void

     */

    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)

    {

        $this->transactionUtil = $transactionUtil;

        $this->moduleUtil = $moduleUtil;

        $this->notificationUtil = $notificationUtil;

        $this->businessUtil = $businessUtil;



        $this->dummyPaymentLine = [

            'method' => '',
            'amount' => 0,
            'note' => '',
            'card_transaction_number' => '',
            'card_number' => '',
            'card_type' => '',
            'card_holder_name' => '',
            'card_month' => '',
            'card_year' => '',
            'card_security' => '',
            'cheque_number' => '',
            'cheque_date' => '',
            'bank_account_number' => '',

            'is_return' => 0,
            'transaction_no' => ''

        ];
    }
    private function __payment_status($status)
    {

        // dd($status);
        if ($status == 'partial') {
            return 'bg-aqua';
        } elseif ($status == 'due') {
            return 'bg-yellow';
        } elseif ($status == 'paid') {
            return 'bg-light-green';
        } elseif ($status == 'overdue') {
            return 'bg-red';
        } elseif ($status == 'partial-overdue') {
            return 'bg-red';
        } elseif ($status == 'pending') {
            return 'bg-info';
        } elseif ($status == 'over-payment') {
            return 'bg-light-green';
        } elseif ($status == 'price-later') {
            return 'bg-orange';
        }
    }


    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index()

    {

        // return 123;
        if (!Gate::forUser(auth()->user())->check('expense.access')) {

            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            $expenses = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->leftjoin(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',

                    'bl.id'

                )
                ->leftjoin('contacts', function ($join) {
                    $join->on('contacts.id', '=', 'transactions.contact_id')
                         ->orOn('contacts.id', '=', 'ec.payee_id');
                })

                ->leftJoin('tax_rates as tr', 'transactions.tax_id', '=', 'tr.id')

                ->leftJoin('users AS U', 'transactions.expense_for', '=', 'U.id')

                ->leftJoin('essentials_employees AS EE', 'transactions.expense_for', '=', 'EE.id')

                ->leftJoin('users AS m', 'transactions.created_by', '=', 'm.id')

                ->leftjoin('transaction_payments AS TP', function ($join) {

                    $join->on('transactions.id', 'TP.transaction_id');
                })

                ->leftjoin('users as deleted', 'transactions.deleted_by', 'deleted.id')

                ->where('transactions.business_id', $business_id)

                ->where(function ($query) {

                    $query->whereIn('transactions.type', ['expense', 'ro_advance', 'ro_salary'])->orWhere('sub_type', 'expense');
                })

                ->withTrashed()

                ->select(
                    'deleted.username as deletedBy',

                    'transactions.id',

                    'transactions.document',

                    'transaction_date',

                    'ref_no',

                    'contacts.name as payee_name',

                    'ec.name as category',

                    'payment_status',

                    'additional_notes',

                    'final_total',

                    'is_settlement',

                    'bl.name as location_name',

                    DB::raw('GROUP_CONCAT(DISTINCT TP.method ORDER BY TP.id SEPARATOR ", ") as method'),

                    'TP.cheque_date',

                    'TP.cheque_number',

                    'TP.account_id',
                    'transactions.business_id',
                    DB::raw("CONCAT(COALESCE(U.surname, ''),' ',COALESCE(U.first_name, ''),' ',COALESCE(EE.name,'')) as expense_for"),

                    DB::raw("CONCAT(tr.name ,' (', tr.amount ,' )') as tax"),

                    DB::raw('COALESCE(SUM(CASE WHEN TP.amount > 0 AND TP.method != "credit_expense" THEN TP.amount ELSE 0 END), 0) as amount_paid'),

                    DB::raw("CONCAT(COALESCE(m.surname, ''),' ',COALESCE(m.first_name, ''),' ',COALESCE(m.last_name,'')) as created_by")

                )

                ->groupBy('transactions.id');



            //Add condition for expense for,used in sales representative expense report & list of expense

            if (request()->has('expense_for') && !empty(request()->get('expense_for'))) {

                $expense_for = request()->get('expense_for');

                if (!empty($expense_for)) {

                    $expenses->where('transactions.expense_for', $expense_for);
                }
            }

            if (request()->has('payee_name') && !empty(request()->get('payee_name'))) {

                $payee_name = request()->get('payee_name');

                if (!empty($payee_name)) {

                    $expenses->where('ec.payee_id', $payee_name);
                }
            }



            //Add condition for location,used in sales representative expense report & list of expense

            if (request()->has('location_id') && !empty(request()->get('location_id'))) {

                $location_id = request()->get('location_id');

                if (!empty($location_id)) {

                    $expenses->where('transactions.location_id', $location_id);
                }
            }



            //Add condition for expense category, used in list of expense,

            if (request()->has('expense_category_id') && !empty(request()->get('expense_category_id'))) {

                $expense_category_id = request()->get('expense_category_id');

                if (!empty($expense_category_id)) {

                    $expenses->where('transactions.expense_category_id', $expense_category_id);
                }
            }



            //Add condition for start and end date filter, uses in sales representative expense report & list of expense

            if (!empty(request()->start_date) && !empty(request()->end_date)) {

                $start = request()->start_date;

                $end =  request()->end_date;

                $expenses->whereDate('transactions.transaction_date', '>=', $start)

                    ->whereDate('transactions.transaction_date', '<=', $end);
            }



            //Add condition for expense category, used in list of expense,

            if (request()->has('expense_category_id') && !empty(request()->get('expense_category_id'))) {

                $expense_category_id = request()->get('expense_category_id');

                if (!empty($expense_category_id)) {

                    $expenses->where('transactions.expense_category_id', $expense_category_id);
                }
            }

            //Add condition for payment methods

            if (request()->has('method') && !empty(request()->get('method'))) {

                $method = request()->get('method');

                if (!empty($method)) {

                    $expenses->where(DB::raw('GROUP_CONCAT(DISTINCT TP.method ORDER BY TP.id SEPARATOR ", ") as method'), $method);
                }
            }

            if (request()->has('fleet_id') && !empty(request()->get('fleet_id'))) {

                $fleet_id = request()->get('fleet_id');

                if (!empty($fleet_id)) {

                    $expenses->where('transactions.fleet_id', $fleet_id);
                }
            }



            $user = auth()->user();
            $permitted_locations = (is_object($user) && method_exists($user, 'permitted_locations'))
                ? $user->permitted_locations()
                : 'all';

            if ($permitted_locations != 'all') {

                $expenses->whereIn('transactions.location_id', $permitted_locations);
            }



            //Add condition for payment status for the list of expense

            if (request()->has('payment_status') && !empty(request()->get('payment_status'))) {

                $payment_status = request()->get('payment_status');

                if (!empty($payment_status)) {

                    $expenses->where('transactions.payment_status', $payment_status);
                }
            }



            $expenses->orderBy('transactions.transaction_date', 'desc')->orderBy('transactions.id', 'desc');

            return Datatables::of($expenses)

                ->addColumn(

                    'action',

                    '<div class="btn-group">

                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 

                            data-toggle="dropdown" aria-expanded="false"> @lang("messages.actions")<span class="caret"></span><span class="sr-only">Toggle Dropdown

                                </span>

                        </button>

                    <ul class="dropdown-menu dropdown-menu-left" role="menu">
                    
                    @if(empty($deletedBy))

                    @can("expense.update")

                    <li><a href="{{action(\'ExpenseController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>

                    @endcan

                    @if($document)

                        <li><a href="{{ url(\'uploads/documents/\' . $document)}}" 

                        download=""><i class="fa fa-download" aria-hidden="true"></i> @lang("purchase.download_document")</a></li>

                        @if(isFileImage($document))

                            <li><a href="#" data-href="{{ url(\'uploads/documents/\' . $document)}}" class="view_uploaded_document"><i class="fa fa-picture-o" aria-hidden="true"></i>@lang("lang_v1.view_document")</a></li>

                        @endif

                    @endif

                    @can("expense.delete")

                        <li><a data-href="{{action(\'ExpenseController@destroy\', [$id])}}" class="delete_expense"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>

                    @endcan

                    <li class="divider"></li> 

                    @if($payment_status != "paid")

                        @can("add.payments")

                            <li><a href="#" data-href="{{action("TransactionPaymentController@addPayment", [$id])}}" class="add_payment_modal"><i class="fa fa-money" aria-hidden="true"></i> @lang("purchase.add_payment")</a></li>

                        @endcan

                    @endif
                    
                    @endif
                    
                    <li><a href="{{action("TransactionPaymentController@print", [$id])}}" class="view_payment_modal"><i class="fa fa-money" aria-hidden="true" ></i> @lang("purchase.view_print")</a></li>

                    <li><a href="{{action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal"><i class="fa fa-money" aria-hidden="true" ></i> @lang("purchase.view_payments")</a></li>

                    </ul></div>'

                )

                ->removeColumn('id')

                ->editColumn(

                    'final_total',

                    function ($row) {
                        $html = '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . (empty($row->deletedBy) ? $row->final_total : 0) . '">' . $this->transactionUtil->num_f($row->final_total) . '</span>';


                        if ($this->moduleUtil->hasThePermissionInSubscription(request()->session()->get('user.business_id'), 'individual_expense')) {
                            if (strtotime($this->transactionUtil->__getVatEffectiveDate(request()->session()->get('user.business_id'))) <= strtotime($row->transaction_date)) {
                                $html .= '<br><a href="#" data-href="' . action('\Modules\Vat\Http\Controllers\VatController@updateSingleVats', ['transaction_id' => $row->id]) . '" class="regenerate-vat text-danger">' . __("superadmin::lang.regenerate_vat") . '</a>';
                            }
                        }

                        return $html;
                    }

                )

                ->editColumn(

                    'for_sum_total',

                    '{{empty($deletedBy) ? $final_total : 0}}'

                )

                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')

                ->editColumn('ref_no', function ($row) {

                    $ref = $row->ref_no . " " . $row->deletedBy;



                    if ($row->is_settlement) {

                        $settlement_expense = SettlementExpensePayment::where('transaction_id', $row->id)->first();

                        if (!empty($settlement_expense)) {

                            $ref .= '<br><b>Reference No: </b>' . $settlement_expense->reference_no . '<br><b>Reason: </b>' . $settlement_expense->reason;
                        }
                    }

                    return $ref;
                })
                ->editColumn('location_name', function ($row) {


                    return '<td class="clickable_td sorting_1">' . $row->location_name . '</td>';
                })
                ->editColumn('payment_status', function ($row) {
                    return '<a href="' . action("TransactionPaymentController@show", [$row->id]) . '" class="view_payment_modal payment-status no-print" data-orig-value="' . $row->payment_status . '" data-status-name="' . __('lang_v1.' . $row->payment_status) . '"><span class="label ' . $this->__payment_status($row->payment_status) . '">' . __('lang_v1.' . $row->payment_status) . '</span></a><span class="print_section">' . __('lang_v1.' . $row->payment_status) . '</span>';
                })

                ->addColumn('payment_due', function ($row) {

                    $due = empty($row->deletedBy) ? ($row->final_total - $row->amount_paid) : 0;

                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</span>';
                })
                ->addColumn('notes', function ($row) {
                    $routeOperation = RouteOperation::where("transaction_id", $row->id)->first();
                    $id = $routeOperation ? $routeOperation->id : null;
                    return $id ? '<button type="button" class="btn btn-primary btn-modal pull-center" id="add_expense_btn" data-href="' . action('\Modules\Fleet\Http\Controllers\RouteOperationController@addExpense', [$id]) . '" data-container=".fleet_model"> <i class="fa fa-plus"></i> ' . __('fleet::lang.notes') . '</button>' : "Route Not Found";
                })

                ->addColumn('payment_method', function ($row) {

                    $html = '';

                    if ($row->payment_status == 'due') {

                        return '--';
                    }



                    $method = strtolower((string) $row->method);
                    if ($method == 'bank_transfer' || $method == 'direct_bank_deposit' || $method == 'bank' || $method == 'cheque') {
                        $html .= ucfirst(str_replace("_", " ", $row->method));

                        $bank_acccount = Account::find($row->account_id);
                        if (!empty($bank_acccount)) {
                            $html .= '<br><b>Bank Name:</b> ' . $bank_acccount->name . '</br>';
                        }
                        if (!empty($row->cheque_number)) {
                            $html .= '<b>Cheque Number:</b> ' . $row->cheque_number . '</br>';
                        }
                        if (!empty($row->cheque_date)) {
                            $html .= '<b>Cheque Date:</b> ' . $this->transactionUtil->format_date($row->cheque_date) . '</br>';
                        }
                    } else {
                        $html .= ucfirst(str_replace("_", " ", $row->method));
                    }

                    return $html;
                })

                ->setRowAttr([
                    'class' => function ($row) {
                        if (!empty($row->deletedBy)) {
                            return 'deleted-row';
                        } else {
                            return '';
                        }
                    },
                    'title' => function ($row) {
                        if (!empty($row->deletedBy)) {
                            return __('sale.deleted_by') . " " . $row->deletedBy;
                        } else {
                            return '';
                        }
                    },
                ])

                ->rawColumns(['final_total', 'action', 'payment_status', 'payment_due', 'ref_no', 'location_name', 'payment_method'])

                ->make(true);
        }



        $business_id = request()->session()->get('user.business_id');



        $categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');



        $users = User::forDropdown($business_id, false, true, true);
        $employees = EssentialsEmployee::pluck('name', 'id');
        $payee_names = DB::table('contacts')
            ->where('business_id', $business_id)
            ->distinct()
            ->orderBy('id', 'DESC')
            ->pluck('name', 'id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('expense.index')

            ->with(compact('categories', 'business_locations', 'users', 'payee_names', 'employees'));
    }

    public function routeperationExpenses($id)

    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {

            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            $expenses = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->leftjoin(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',

                    'bl.id'

                )
                ->leftjoin('contacts', function ($join) {
                    $join->on('contacts.id', '=', 'transactions.contact_id')
                         ->orOn('contacts.id', '=', 'ec.payee_id');
                })

                ->leftJoin('tax_rates as tr', 'transactions.tax_id', '=', 'tr.id')

                ->leftJoin('users AS U', 'transactions.expense_for', '=', 'U.id')

                ->leftJoin('users AS m', 'transactions.created_by', '=', 'm.id')

                ->leftjoin('transaction_payments AS TP', function ($join) {

                    $join->on('transactions.id', 'TP.transaction_id')->where('TP.amount', '>', 0);
                })

                ->where('transactions.business_id', $business_id)
                ->where('transactions.parent_transaction_id', $id)

                ->where(function ($query) {

                    $query->whereIn('transactions.type', ['expense', 'ro_advance', 'ro_salary'])->orWhere('sub_type', 'expense');
                })

                ->select(

                    'transactions.id',

                    'transactions.document',

                    'transaction_date',

                    'ref_no',

                    'contacts.name as payee_name',

                    'ec.name as category',

                    'payment_status',

                    'additional_notes',

                    'final_total',

                    'is_settlement',

                    'bl.name as location_name',

                    DB::raw('GROUP_CONCAT(DISTINCT TP.method ORDER BY TP.id SEPARATOR ", ") as method'),

                    'TP.cheque_date',

                    'TP.cheque_number',

                    'TP.account_id',
                    'transactions.business_id',
                    DB::raw("CONCAT(COALESCE(U.surname, ''),' ',COALESCE(U.first_name, ''),' ',COALESCE(U.last_name,'')) as expense_for"),

                    DB::raw("CONCAT(tr.name ,' (', tr.amount ,' )') as tax"),

                    DB::raw('COALESCE(SUM(CASE WHEN TP.amount > 0 AND TP.method != "credit_expense" THEN TP.amount ELSE 0 END), 0) as amount_paid'),

                    DB::raw("CONCAT(COALESCE(m.surname, ''),' ',COALESCE(m.first_name, ''),' ',COALESCE(m.last_name,'')) as created_by")

                )

                ->groupBy('transactions.id');



            //Add condition for expense for,used in sales representative expense report & list of expense

            if (request()->has('expense_for') && !empty(request()->get('expense_for'))) {

                $expense_for = request()->get('expense_for');

                if (!empty($expense_for)) {

                    $expenses->where('transactions.expense_for', $expense_for);
                }
            }

            if (request()->has('payee_name') && !empty(request()->get('payee_name'))) {

                $payee_name = request()->get('payee_name');

                if (!empty($payee_name)) {

                    $expenses->where('ec.payee_id', $payee_name);
                }
            }



            //Add condition for location,used in sales representative expense report & list of expense

            if (request()->has('location_id') && !empty(request()->get('location_id'))) {

                $location_id = request()->get('location_id');

                if (!empty($location_id)) {

                    $expenses->where('transactions.location_id', $location_id);
                }
            }



            //Add condition for expense category, used in list of expense,

            if (request()->has('expense_category_id') && !empty(request()->get('expense_category_id'))) {

                $expense_category_id = request()->get('expense_category_id');

                if (!empty($expense_category_id)) {

                    $expenses->where('transactions.expense_category_id', $expense_category_id);
                }
            }



            //Add condition for start and end date filter, uses in sales representative expense report & list of expense

            if (!empty(request()->start_date) && !empty(request()->end_date)) {

                $start = request()->start_date;

                $end =  request()->end_date;

                $expenses->whereDate('transactions.transaction_date', '>=', $start)

                    ->whereDate('transactions.transaction_date', '<=', $end);
            }



            //Add condition for expense category, used in list of expense,

            if (request()->has('expense_category_id') && !empty(request()->get('expense_category_id'))) {

                $expense_category_id = request()->get('expense_category_id');

                if (!empty($expense_category_id)) {

                    $expenses->where('transactions.expense_category_id', $expense_category_id);
                }
            }

            //Add condition for payment methods

            if (request()->has('method') && !empty(request()->get('method'))) {

                $method = request()->get('method');

                if (!empty($method)) {

                    $expenses->where(DB::raw('GROUP_CONCAT(DISTINCT TP.method ORDER BY TP.id SEPARATOR ", ") as method'), $method);
                }
            }

            if (request()->has('fleet_id') && !empty(request()->get('fleet_id'))) {

                $fleet_id = request()->get('fleet_id');

                if (!empty($fleet_id)) {

                    $expenses->where('transactions.fleet_id', $fleet_id);
                }
            }



            $user = auth()->user();
            $permitted_locations = (is_object($user) && method_exists($user, 'permitted_locations'))
                ? $user->permitted_locations()
                : 'all';

            if ($permitted_locations != 'all') {

                $expenses->whereIn('transactions.location_id', $permitted_locations);
            }



            //Add condition for payment status for the list of expense

            if (request()->has('payment_status') && !empty(request()->get('payment_status'))) {

                $payment_status = request()->get('payment_status');

                if (!empty($payment_status)) {

                    $expenses->where('transactions.payment_status', $payment_status);
                }
            }



            return Datatables::of($expenses)

                ->addColumn(

                    'action',

                    '<div class="btn-group">

                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 

                            data-toggle="dropdown" aria-expanded="false"> @lang("messages.actions")<span class="caret"></span><span class="sr-only">Toggle Dropdown

                                </span>

                        </button>

                    <ul class="dropdown-menu dropdown-menu-left" role="menu">

                    @can("expense.update")

                    <li><a href="{{action(\'ExpenseController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>

                    @endcan

                    @if($document)

                        <li><a href="{{ url(\'uploads/documents/\' . $document)}}" 

                        download=""><i class="fa fa-download" aria-hidden="true"></i> @lang("purchase.download_document")</a></li>

                        @if(isFileImage($document))

                            <li><a href="#" data-href="{{ url(\'uploads/documents/\' . $document)}}" class="view_uploaded_document"><i class="fa fa-picture-o" aria-hidden="true"></i>@lang("lang_v1.view_document")</a></li>

                        @endif

                    @endif

                    @can("expense.delete")

                        <li><a data-href="{{action(\'ExpenseController@destroy\', [$id])}}" class="delete_expense"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>

                    @endcan

                    <li class="divider"></li> 

                    @if($payment_status != "paid")

                        @can("add.payments")

                            <li><a href="#" data-href="{{action("TransactionPaymentController@addPayment", [$id])}}" class="add_payment_modal"><i class="fa fa-money" aria-hidden="true"></i> @lang("purchase.add_payment")</a></li>

                        @endcan

                    @endif

                    <li><a href="{{action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal"><i class="fa fa-money" aria-hidden="true" ></i> @lang("purchase.view_payments")</a></li>

                    </ul></div>'

                )

                ->removeColumn('id')

                ->editColumn(

                    'final_total',

                    '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'

                )

                ->editColumn(

                    'for_sum_total',

                    '{{$final_total}}'

                )

                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')

                ->editColumn('ref_no', function ($row) {

                    $ref = $row->ref_no;



                    if ($row->is_settlement) {

                        $settlement_expense = SettlementExpensePayment::where('transaction_id', $row->id)->first();

                        if (!empty($settlement_expense)) {

                            $ref .= '<br><b>Reference No: </b>' . $settlement_expense->reference_no . '<br><b>Reason: </b>' . $settlement_expense->reason;
                        }
                    }

                    return $ref;
                })
                ->editColumn('location_name', function ($row) {


                    return '<td class="clickable_td sorting_1">' . $row->location_name . '</td>';
                })
                ->editColumn('payment_status', function ($row) {
                    return '<a href="' . action("TransactionPaymentController@show", [$row->id]) . '" class="view_payment_modal payment-status no-print" data-orig-value="' . $row->payment_status . '" data-status-name="' . __('lang_v1.' . $row->payment_status) . '"><span class="label ' . $this->__payment_status($row->payment_status) . '">' . __('lang_v1.' . $row->payment_status) . '</span></a><span class="print_section">' . __('lang_v1.' . $row->payment_status) . '</span>';
                })

                ->addColumn('payment_due', function ($row) {

                    $due = $row->final_total - $row->amount_paid;

                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</span>';
                })

                ->addColumn('payment_method', function ($row) {

                    $html = '';

                    if ($row->payment_status == 'due') {

                        return '--';
                    }



                    $method = strtolower((string) $row->method);
                    if ($method == 'bank_transfer' || $method == 'direct_bank_deposit' || $method == 'bank' || $method == 'cheque') {
                        $html .= ucfirst(str_replace("_", " ", $row->method));

                        $bank_acccount = Account::find($row->account_id);
                        if (!empty($bank_acccount)) {
                            $html .= '<br><b>Bank Name:</b> ' . $bank_acccount->name . '</br>';
                        }
                        if (!empty($row->cheque_number)) {
                            $html .= '<b>Cheque Number:</b> ' . $row->cheque_number . '</br>';
                        }
                        if (!empty($row->cheque_date)) {
                            $html .= '<b>Cheque Date:</b> ' . $this->transactionUtil->format_date($row->cheque_date) . '</br>';
                        }
                    } else {
                        $html .= ucfirst(str_replace("_", " ", $row->method));
                    }

                    return $html;
                })

                ->rawColumns(['final_total', 'action', 'payment_status', 'payment_due', 'ref_no', 'location_name', 'payment_method'])

                ->make(true);
        }
    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */


    /**
     * Tenant-safe Daily Shift dropdown for Expense Add/Edit.
     *
     * The Expense page must not fail when the optional DailyCollectionSW module route is
     * not registered. This local endpoint returns the same select2 format and keeps the
     * existing Expense page independent.
     */
    public function getDailyShiftOptions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id') ?: $request->session()->get('business.id');
        $operator_id = (int) $request->input('pump_operator_id');

        if (empty($business_id)) {
            return response()->json(['results' => []]);
        }

        try {
            $rows = collect();

            if (\Illuminate\Support\Facades\Schema::hasTable('petro_daily_shifts')) {
                $query = DB::table('petro_daily_shifts');

                if (\Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'business_id')) {
                    $query->where(function ($q) use ($business_id) {
                        $q->where('business_id', $business_id)->orWhereNull('business_id');
                    });
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'type')) {
                    $query->where(function ($q) {
                        $q->where('type', 'daily_collection_sw')->orWhereNull('type');
                    });
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'status')) {
                    $query->whereIn('status', [0, 1]);
                }

                $operatorColumns = array_values(array_filter([
                    \Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'pump_operator_assigned') ? 'pump_operator_assigned' : null,
                    \Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'pump_operators_assigned') ? 'pump_operators_assigned' : null,
                    \Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'pump_operator_pending') ? 'pump_operator_pending' : null,
                    \Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'pump_operators_pending') ? 'pump_operators_pending' : null,
                    \Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'pump_operator_id') ? 'pump_operator_id' : null,
                ]));

                if (! empty($operatorColumns) && $operator_id > 0) {
                    $query->where(function ($q) use ($operatorColumns, $operator_id) {
                        foreach ($operatorColumns as $column) {
                            if ($column === 'pump_operator_id') {
                                $q->orWhere($column, $operator_id);
                            } else {
                                $q->orWhereRaw('FIND_IN_SET(?, ' . $column . ')', [$operator_id]);
                            }
                        }
                    });
                }

                $shiftColumn = \Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'shift_no') ? 'shift_no' : 'id';

                if (\Illuminate\Support\Facades\Schema::hasColumn('petro_daily_shifts', 'status')) {
                    $query->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END');
                }

                $rows = $query->orderBy('id', 'desc')->pluck($shiftColumn)->filter()->values();
            }

            if ($operator_id > 0 && \Illuminate\Support\Facades\Schema::hasTable('pump_operator_assignments')) {
                $assignmentQuery = DB::table('pump_operator_assignments')
                    ->where('pump_operator_id', $operator_id);

                if (\Illuminate\Support\Facades\Schema::hasColumn('pump_operator_assignments', 'business_id')) {
                    $assignmentQuery->where(function ($q) use ($business_id) {
                        $q->where('business_id', $business_id)->orWhereNull('business_id');
                    });
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('pump_operator_assignments', 'status')) {
                    $assignmentQuery->where(function ($q) {
                        $q->where('status', 'open')->orWhere('status', 1)->orWhereNull('status');
                    });
                }

                foreach (['shift_number', 'shift_no', 'shift_id'] as $assignmentColumn) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('pump_operator_assignments', $assignmentColumn)) {
                        $rows = $rows->merge(
                            $assignmentQuery->clone()
                                ->orderBy('id', 'desc')
                                ->pluck($assignmentColumn)
                                ->filter()
                                ->values()
                        );
                    }
                }
            }

            $results = $rows->filter()->unique()->values()->map(function ($shift_no) {
                return ['id' => $shift_no, 'text' => $shift_no];
            })->all();

            return response()->json(['results' => $results]);
        } catch (\Throwable $e) {
            \Log::warning('Expense daily shift options failed: ' . $e->getMessage());
            return response()->json(['results' => []]);
        }
    }

    public function create()

    {

        if (!Gate::forUser(auth()->user())->check('expense.create')) {

            abort(403, 'Unauthorized action.');
        }



        $business_id = request()->session()->get('user.business_id');



        //Check if subscribed or not

        if (!$this->moduleUtil->isSubscribed($business_id)) {

            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }



        $account_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        $payment_line = $this->dummyPaymentLine;
        $first_location = BusinessLocation::where('business_id', $business_id)->first();

        $payment_types = !empty($first_location) ? $this->transactionUtil->payment_types($first_location->id, true, false, false, false, true, "is_expense_enabled") : [];
        $payment_types = $this->expensePaymentTypes($payment_types);

        $accounts = [];

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $current_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Current Assets')->first();

        $current_liability_account_type = AccountType::where('business_id', $business_id)->where('name', 'Current Liabilities')->first();

        $current_liability_account_type_id = !empty($current_liability_account_type) ? $current_liability_account_type->id : 0;

        $expense_accounts = [];

        $expense_account_id = null;

        $payee_name = Contact::select('name')->where('business_id', $business_id)->first();



        if ($account_module) {

            if (!empty($expense_account_type_id)) {

                $expense_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
                    ->where('accounts.business_id', $business_id)
                    ->where(function ($query) use ($expense_account_type_id) {
                        $query->where('account_groups.name', 'CPC')
                            ->orWhere('accounts.account_type_id', $expense_account_type_id->id)
                            ->orWhere('accounts.name', 'like', '%Expense%');
                    })
                    ->select('accounts.id', 'accounts.name')
                    ->orderBy('accounts.name')
                    ->get()->pluck('name', 'id');
            }
        } else {

            $expense_account = Account::where('name', 'Expenses')->where('business_id', $business_id)->first();
            $expense_account_id = !empty($expense_account) ? $expense_account->id : null;

            $expense_accounts = Account::where('business_id', $business_id)->where('name', 'Expenses')->pluck('name', 'id');
        }

        if ($expense_accounts->isEmpty()) {
            $expense_accounts = Account::where('business_id', $business_id)
                ->where(function ($query) use ($expense_account_type_id) {
                    if (!empty($expense_account_type_id)) {
                        $query->where('account_type_id', $expense_account_type_id->id);
                    }
                    $query->orWhere('name', 'like', '%Expense%');
                })
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $current_liabilities_accounts =  Account::where('business_id', $business_id)->where('account_type_id', $current_liability_account_type_id)->pluck('name', 'id');



        $business_locations = BusinessLocation::forDropdown($business_id);

        $contacts = Contact::contactDropdown($business_id, false, false);

        $expense_categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $users = User::forDropdown($business_id, true, true);
        $employees = EssentialsEmployee::pluck('name', 'id');

        $fleets = Fleet::where('business_id', $business_id)->pluck('vehicle_number', 'id');



        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);



        $ref_count = $this->transactionUtil->onlyGetReferenceCount('expense', null, false);

        //Generate reference number

        $ref_no = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);





        $temp_data = DB::table('temp_data')->where('business_id', $business_id)->select('add_expense_data')->first();

        if (!empty($temp_data)) {

            $temp_data = json_decode($temp_data->add_expense_data);
        }

        if (!request()->session()->get('business.popup_load_save_data')) {

            $temp_data = [];
        }

        $cash_account = Account::where('business_id', $business_id)->where('name', 'Cash')->first();
        $cash_account_id = !empty($cash_account) ? $cash_account->id : null;



        $fleet_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'fleet_module');

        $bank_group_accounts = Account::leftJoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_groups.name', 'Bank Account')
            ->pluck('accounts.name', 'accounts.id');

        // Remove "Issued Post Dated Cheques"
        $bank_group_accounts = $bank_group_accounts->reject(function ($name) {
            return trim($name) === "Issued Post Dated Cheques";
        });

        // Debugging to check the filtered collection


        $cpc_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_groups.name', 'CPC')
            ->pluck('accounts.name', 'accounts.id');

        $dailyCashShiftNumbers = PetroDailyShift::where('business_id', $business_id)
            // ->where(function($query) {
            //     $query ->WhereRaw('CHAR_LENGTH(pump_operator_pending) >= 1');
            // })
            ->where('status', 0)
            ->pluck('shift_no');
        $dailyCashShiftNumbers = $dailyCashShiftNumbers->unique()->toArray();

        return view('expense.create')

            ->with(compact(
                'cpc_accounts',
                'dailyCashShiftNumbers',
                'bank_group_accounts',
                'cash_account_id',

                'cash_account_id',

                'ref_no',

                'account_module',

                'accounts',

                'expense_accounts',

                'payment_types',

                'payment_line',

                'expense_categories',

                'business_locations',

                'users',

                'employees',

                'fleets',

                'fleet_module',

                'taxes',

                'temp_data',

                'contacts',

                'current_liabilities_accounts',

                'expense_account_id',
                'payee_name'

            ));
    }



    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */

    public function storeold(Request $request)

    {
        // dd('123');

        if (!Gate::forUser(auth()->user())->check('expense.create')) {

            abort(403, 'Unauthorized action.');
        }



        try {

            $business_id = $request->session()->get('user.business_id');



            DB::table('temp_data')->where('business_id', $business_id)->update(['add_expense_data' => '']);

            //Check if subscribed or not

            if (!$this->moduleUtil->isSubscribed($business_id)) {

                return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
            }



            //Validate document size

            $request->validate([

                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)

            ]);



            $transaction_data = $request->only(['is_vat', 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'fleet_id', 'additional_notes', 'expense_category_id', 'tax_id', 'contact_id']);
            // S374 visibility guard: every expense saved from Add Expense must be returned by
        // List Expenses immediately after redirect.
        $transaction_data['type'] = 'expense';
        $transaction_data['sub_type'] = $transaction_data['sub_type'] ?? 'expense';
        $transaction_data['status'] = $transaction_data['status'] ?? 'final';
        $transaction_data['transaction_date'] = $transaction_data['transaction_date'] ?? $request->expense_transaction_date;
            $has_reviewed = $this->transactionUtil->hasReviewed($transaction_data['transaction_date']);

            if (!empty($has_reviewed)) {
                $output              = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($transaction_data['transaction_date'], $transaction_data['transaction_date']);


            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't add an expense for an already reviewed date",
                ];

                return Redirect::to('expenses')->with('status', $output);
            }





            $user_id = $request->session()->get('user.id');

            $transaction_data['business_id'] = $business_id;

            $transaction_data['created_by'] = $user_id;

            $transaction_data['type'] = 'expense';

            $transaction_data['status'] = 'final';

            $transaction_data['payment_status'] = 'due';

            $transaction_data['expense_account'] = $request->expense_account;

            $transaction_data['controller_account'] = $request->controller_account;
            // dd($transaction_data['controller_account']);
            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

            $transaction_data['final_total'] = $this->transactionUtil->num_uf(

                $transaction_data['final_total']

            );



            $transaction_data['total_before_tax'] = $transaction_data['final_total'];

            if (!empty($transaction_data['tax_id'])) {

                $tax_details = TaxRate::find($transaction_data['tax_id']);

                $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);

                $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
            }



            //Update reference count

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

            //Generate reference number

            if (empty($transaction_data['ref_no'])) {

                $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
            }



            //upload document

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            if (!empty($document_name)) {

                $transaction_data['document'] = $document_name;
            }



            if ($request->has('is_recurring')) {

                $transaction_data['is_recurring'] = 1;

                $transaction_data['recur_interval'] = !empty($request->input('recur_interval')) ? $request->input('recur_interval') : 1;

                $transaction_data['recur_interval_type'] = $request->input('recur_interval_type');

                $transaction_data['recur_repetitions'] = $request->input('recur_repetitions');

                $transaction_data['subscription_repeat_on'] = $request->input('recur_interval_type') == 'months' && !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : null;
            }

            // dump($transaction_data,'$transaction_data');



            DB::beginTransaction();

            $transaction = Transaction::create($transaction_data);

            // add VAT components
            $this->transactionUtil->calculateAndUpdateVAT($transaction);


            $transaction_id =  $transaction->id;
            // dd($request->payment[0],'payment 00');

            $tp = null;

            if (!empty($request->payment[0])) {

                $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);


                $inputs['paid_on'] = $transaction->transaction_date;

                $inputs['transaction_id'] = $transaction->id;

                $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;


                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

                $amount = $inputs['amount'];


                // dd( $amount ,$inputs['method'] , $amount > 0 && $inputs['method'] != 'credit_expense');
                if ($amount > 0 && $inputs['method'] != 'credit_expense') {

                    // Check payment method (Cash, Credit, etc.)
                    if ($inputs['method'] == 'cash') {
                        // If the full amount is paid
                        if ($amount >= $transaction->final_total) {
                            $transaction->payment_status = 'paid'; // Set status to paid
                        } else {
                            $transaction->payment_status = 'partial'; // Set status to partial if it's less than the total
                        }
                    }

                    $transaction->save();
                    // dd($transaction);
                    $inputs['created_by'] = auth()->user()->id;

                    $inputs['payment_for'] = $transaction->contact_id;
                    // $transaction->save();
                    // dd($inputs['method'] , $transaction->payment_status , $transaction ,$transaction->save());
                    $prefix_type = 'expense_payment';
                    if ($transaction->type == 'expense') {
                        $prefix_type = 'expense_payment';
                    }

                    // $transaction->controller_account = !empty($inputs['controller_account']) ? $inputs['controller_account'] : null;




                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    // dd($ref_count);
                    //Generate reference number

                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);



                    $inputs['business_id'] = $business_id;

                    $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');


                    // post dated cheque input
                    $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

                    if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
                        $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                        $bank_account = !empty($inputs['related_account_id']) ? Account::find($inputs['related_account_id']) : null;
                        $bank_name = !empty($bank_account) ? $bank_account->name : '';
                        $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                        Log::info('Expense PD cheque payment prepared', [
                            'transaction_id' => $transaction->id,
                            'ref_no' => $transaction->ref_no,
                            'payment_method' => $inputs['method'] ?? null,
                            'account_id' => $inputs['account_id'] ?? null,
                            'account_name' => optional(Account::find($inputs['account_id'] ?? null))->name,
                            'related_account_id' => $inputs['related_account_id'] ?? null,
                            'related_account_name' => optional($bank_account)->name,
                            'post_dated_cheque' => $inputs['post_dated_cheque'] ?? 0,
                            'update_post_dated_cheque' => $inputs['update_post_dated_cheque'] ?? 0,
                            'cheque_number' => $inputs['cheque_number'] ?? null,
                            'cheque_date' => $inputs['cheque_date'] ?? null,
                            'note' => $inputs['note'] ?? null,
                        ]);
                    }

                    $inputs['is_return'] =  0; //added by 

                    unset($inputs['transaction_no_1']);

                    unset($inputs['transaction_no_2']);

                    unset($inputs['transaction_no_3']);

                    unset($inputs['controller_account']);


                    $cheque_nos = "";
                    // dd($request->select_cheques);
                    if (!empty($request->select_cheques)) {
                        foreach ($request->select_cheques as $select_cheque) {
                            if (!empty($select_cheque)) {
                                $account_transaction = AccountTransaction::find($select_cheque);

                                $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);
                                // dd($transaction_payment);
                                if (!empty($transaction_payment)) {
                                    $amount = $this->transactionUtil->num_uf($account_transaction->amount);
                                    if (!empty($amount)) {
                                        $credit_data = [
                                            'amount' => $amount,
                                            'account_id' => $account_transaction->account_id,
                                            'transaction_id' => $transaction->id,
                                            'type' => 'credit',
                                            'sub_type' => null,
                                            'operation_date' => $transaction_data['transaction_date'],
                                            'created_by' => session()->get('user.id'),
                                            'transaction_payment_id' => $transaction_payment->id,
                                            'note' => null,
                                            'attachment' => null
                                        ];
                                        $credit = AccountTransaction::createAccountTransaction($credit_data);

                                        $cheque_nos .= !empty($transaction_payment->cheque_number) ? $transaction_payment->cheque_number . "," : "";

                                        $transaction_payment->is_deposited = 1;
                                        $transaction_payment->save();
                                    }
                                }
                            }
                        }

                        $inputs['cheque_number'] = $cheque_nos;
                    }

                    $tp = TransactionPayment::create($inputs);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);
                    // dd($tp,'tp tp tp');


                    //update payment status

                    $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $inputs['method']);
                    // dd('store');
                }
            }

            $this->addAccountTransaction($transaction, $request, $business_id, $tp);

            $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Created a new expense: " . $transaction_data['ref_no'], "module" => "expense"];
            $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);


            $accountName = Account::find($request->expense_account);
            $expense_category = ExpenseCategory::find($request->expense_category_id)->name ?? '';
            $sms_data = array(
                'transaction_date' => $this->transactionUtil->format_date($transaction_data['transaction_date']),
                'ref' => $transaction_data['ref_no'],
                'amount' => $this->transactionUtil->num_f($transaction->final_total),
                'account' => !empty($accountName) ? $accountName->name : "",
                'staff' => auth()->user()->username,
                'expense_category' => $expense_category,
            );
            $this->notificationUtil->sendGeneralNotification('expense_created', $sms_data);



            DB::commit();

            $output = [

                'success' => 1,

                'msg' => __('expense.expense_add_success')

            ];

            if ($request->is_print == 1) {
                return Redirect::route('expense-print', [$transaction_id]);
            }
        } catch (\Exception $e) {

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());



            $output = [

                'success' => 0,

                'msg' => __('messages.something_went_wrong')

            ];
        }



        return Redirect::to('expenses')->with('status', $output);
    }


    public function store(Request $request)
{
    if (!Gate::forUser(auth()->user())->check('expense.create')) {
        abort(403, 'Unauthorized action.');
    }
    try {
        $business_id = $request->session()->get('user.business_id');
        DB::table('temp_data')->where('business_id', $business_id)->update(['add_expense_data' => '']);

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }

        //Validate document size
        $request->validate([
            'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)
        ]);

        $transaction_data = $request->only([
            'is_vat',
            'ref_no',
            'shift_number',
            'transaction_date',
            'location_id',
            'final_total',
            'expense_for',
            'fleet_id',
            'additional_notes',
            'expense_category_id',
            'tax_id',
            'contact_id'
        ]);
        $expense_items = collect($request->input('expense_items', []))
            ->map(function ($item) {
                return [
                    'expense_category_id' => !empty($item['expense_category_id']) ? (int) $item['expense_category_id'] : null,
                    'amount' => isset($item['amount']) ? (float) str_replace(',', '', $item['amount']) : 0,
                    'expense_account' => !empty($item['expense_account']) ? (int) $item['expense_account'] : null,
                    'is_vat' => isset($item['is_vat']) ? (int) $item['is_vat'] : 0,
                    'tax_id' => !empty($item['tax_id']) ? (int) $item['tax_id'] : null,
                    'ref_no' => $item['ref_no'] ?? null,
                    'additional_notes' => $item['additional_notes'] ?? null,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['expense_category_id']) && $item['amount'] > 0;
            })
            ->values();

        // IS1539 root save fix:
        // The Add Expense screen now submits detail rows. If the summary fields were not
        // synchronized by JavaScript before clicking Save / Save & Print, the old code tried
        // to save blank category/account/total values and the transaction was rolled back.
        // Build the required summary values on the server as well so both buttons save reliably.
        if ($expense_items->count() > 0) {
            $first_expense_item = $expense_items->first();
            $calculated_total = $expense_items->sum('amount');

            if (empty($transaction_data['expense_category_id']) && !empty($first_expense_item['expense_category_id'])) {
                $transaction_data['expense_category_id'] = $first_expense_item['expense_category_id'];
                $request->merge(['expense_category_id' => $first_expense_item['expense_category_id']]);
            }

            if ((empty($transaction_data['final_total']) || (float) str_replace(',', '', $transaction_data['final_total']) <= 0) && $calculated_total > 0) {
                $transaction_data['final_total'] = $calculated_total;
                $request->merge(['final_total' => $calculated_total]);
            }

            if (empty($request->expense_account) && !empty($first_expense_item['expense_account'])) {
                $request->merge(['expense_account' => $first_expense_item['expense_account']]);
            }

            if (!isset($transaction_data['is_vat']) && isset($first_expense_item['is_vat'])) {
                $transaction_data['is_vat'] = $first_expense_item['is_vat'];
                $request->merge(['is_vat' => $first_expense_item['is_vat']]);
            }

            if (empty($transaction_data['tax_id']) && !empty($first_expense_item['tax_id'])) {
                $transaction_data['tax_id'] = $first_expense_item['tax_id'];
                $request->merge(['tax_id' => $first_expense_item['tax_id']]);
            }

            if (empty($transaction_data['additional_notes']) && !empty($first_expense_item['additional_notes'])) {
                $transaction_data['additional_notes'] = $first_expense_item['additional_notes'];
                $request->merge(['additional_notes' => $first_expense_item['additional_notes']]);
            }
        }

        $is_multi_expense_submit = $expense_items->count() > 1;
        $transaction_data['transaction_date'] = $transaction_data['transaction_date'] ?? $request->expense_transaction_date;
        $has_reviewed = $this->transactionUtil->hasReviewed($transaction_data['transaction_date']);

        if (!empty($has_reviewed)) {
            $output              = [
                'success' => 0,
                'msg'     => __('lang_v1.review_first'),
            ];

            return Redirect::back()->with(['status' => $output]);
        }

        $reviewed = $this->transactionUtil->get_review($transaction_data['transaction_date'], $transaction_data['transaction_date']);

        if (!empty($reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => "You can't add an expense for an already reviewed date",
            ];

            return Redirect::to('expenses')->with('status', $output);
        }

        // ============= ADD INSUFFICIENT BALANCE CHECK HERE =============
        // Get cash accounts to check for insufficient balance
        $accsForWhichToCheckInsufficientBalances = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('account_groups.name', 'Cash Account')
            ->where('accounts.business_id', $business_id)
            ->pluck('accounts.id')
            ->toArray();
        
        // Check payments for insufficient balance in cash accounts
        $payments = $request->input('payment') ?? [];
        foreach ($payments as $payment_arr) {
            $method = strtolower((string) ($payment_arr['method'] ?? ''));
            if ($method === 'credit_expense') {
                continue;
            }
            $accountId   = $payment_arr['account_id'] ?? null;
            $amountToPay = $payment_arr['amount'] ?? 0;

            if (empty($accountId) || (float) $amountToPay <= 0) {
                continue;
            }

            if (in_array($accountId, $accsForWhichToCheckInsufficientBalances)) {
                $balance = Account::getAccountBalance($accountId);

                if ($balance < $amountToPay) {
                    $output = [
                        'success' => 0,
                        'msg'     => "Insufficient balance in the selected cash account",
                    ];
                    return Redirect::to('expenses')->with('status', $output);
                }
            }
        }
        // ============= END OF INSUFFICIENT BALANCE CHECK =============

        if ($is_multi_expense_submit) {
            $user_id = $request->session()->get('user.id');
            $base_transaction_data = $transaction_data;
            $base_transaction_data['business_id'] = $business_id;
            $base_transaction_data['created_by'] = $user_id;
            $base_transaction_data['type'] = 'expense';
            $base_transaction_data['status'] = 'final';
            $base_transaction_data['payment_status'] = 'due';
            $base_transaction_data['controller_account'] = $request->controller_account;
            $base_transaction_data['transaction_date'] = $this->transactionUtil->uf_date($base_transaction_data['transaction_date'], true);
            $base_transaction_data['tax_id'] = null;
            $base_transaction_data['tax_amount'] = 0;

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $base_transaction_data['document'] = $document_name;
            }

            $original_payment = $request->input('payment.0', []);
            $original_payment_method = $original_payment['method'] ?? 'credit_expense';
            $created_transaction_ids = [];

            DB::beginTransaction();
            foreach ($expense_items as $index => $expense_item) {
                $item_transaction_data = $base_transaction_data;
                $item_amount = $this->transactionUtil->num_uf($expense_item['amount']);
                $item_transaction_data['expense_category_id'] = $expense_item['expense_category_id'];
                $item_transaction_data['expense_account'] = $expense_item['expense_account'] ?: $request->expense_account;
                $item_transaction_data['is_vat'] = $expense_item['is_vat'];
                $item_transaction_data['tax_id'] = !empty($expense_item['tax_id']) ? $expense_item['tax_id'] : null;
                $item_transaction_data['additional_notes'] = !empty($expense_item['additional_notes']) ? $expense_item['additional_notes'] : ($request->additional_notes ?? null);
                $item_transaction_data['final_total'] = $item_amount;
                $item_transaction_data['total_before_tax'] = $item_amount;

                if (!empty($expense_item['ref_no'])) {
                    $item_transaction_data['ref_no'] = $expense_item['ref_no'];
                } elseif (!empty($request->ref_no)) {
                    $item_transaction_data['ref_no'] = $request->ref_no . '-' . ($index + 1);
                } else {
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');
                    $item_transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
                }

                if (!empty($item_transaction_data['tax_id'])) {
                    $tax_details = TaxRate::find($item_transaction_data['tax_id']);
                    if (!empty($tax_details)) {
                        $item_transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($item_amount, $tax_details->amount);
                        $item_transaction_data['tax_amount'] = $item_amount - $item_transaction_data['total_before_tax'];
                    }
                }

                $transaction = Transaction::create($item_transaction_data);
                $this->transactionUtil->calculateAndUpdateVAT($transaction);

                $row_payment = $original_payment;
                $row_payment['method'] = $original_payment_method;
                $row_payment['amount'] = $item_amount;
                $row_payment['controller_account'] = $request->controller_account;
                $row_payment['cheque_date'] = !empty($row_payment['cheque_date']) ? $row_payment['cheque_date'] : $transaction->transaction_date;
                $row_payment['created_by'] = $user_id;
                $row_payment['payment_for'] = $transaction->contact_id;
                $row_payment['business_id'] = $business_id;
                $row_payment['paid_on'] = $transaction->transaction_date;
                $row_payment['transaction_id'] = $transaction->id;
                $row_payment['is_return'] = 0;

                $tp = null;
                if ($item_amount > 0) {
                    $row_payment = $this->prepareExpensePdChequePaymentInputs($row_payment, $transaction->expense_category_id);
                    $row_payment['amount'] = $item_amount;
                    $row_payment['created_by'] = $user_id;
                    $row_payment['payment_for'] = $transaction->contact_id;
                    $row_payment['business_id'] = $business_id;
                    $row_payment['paid_on'] = $transaction->transaction_date;
                    $row_payment['transaction_id'] = $transaction->id;
                    $row_payment['is_return'] = 0;
                    $row_payment = $this->normalizeExpensePaymentPdChequeInputs($row_payment);
                    $tp = TransactionPayment::create($row_payment);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);

                    if ($row_payment['method'] != 'credit_expense') {
                        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total, $row_payment['method']);
                    }
                }

                $row_request = new Request();
                $row_request->replace([
                    'expense_account' => $item_transaction_data['expense_account'],
                    'final_total' => $item_amount,
                    'payment' => [$row_payment]
                ]);
                $this->addAccountTransaction($transaction, $row_request, $business_id, $tp);

                FinanceBudgetControlService::checkBudgetUsage(
                    $business_id,
                    $transaction->location_id,
                    $transaction->expense_account,
                    $transaction->final_total,
                    'transactions',
                    $transaction->id
                );

                $created_transaction_ids[] = $transaction->id;
            }

            $newReview = [
                "created_by" => $user_id,
                "description" => "Created multiple expenses in one submit",
                "module" => "expense"
            ];
            $this->transactionUtil->reviewChange($base_transaction_data['transaction_date'], $newReview);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('expense.expense_add_success')
            ];

            if ($request->is_print == 1 && !empty($created_transaction_ids[0])) {
                return Redirect::route('expense-print', [$created_transaction_ids[0]]);
            }

            return Redirect::to('expenses')->with('status', $output);
        }


        $user_id = $request->session()->get('user.id');

        $transaction_data['business_id'] = $business_id;

        $transaction_data['created_by'] = $user_id;

        $transaction_data['type'] = 'expense';

        $transaction_data['status'] = 'final';

        $transaction_data['payment_status'] = 'due';

        $transaction_data['expense_account'] = $request->expense_account;

        $transaction_data['controller_account'] = $request->controller_account;
        // dd($transaction_data['controller_account']);
        $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

        $transaction_data['final_total'] = $this->transactionUtil->num_uf(

            $transaction_data['final_total']

        );

        $transaction_data['total_before_tax'] = $transaction_data['final_total'];

        if (!empty($transaction_data['tax_id'])) {

            $tax_details = TaxRate::find($transaction_data['tax_id']);

            $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);

            $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
        }

        //Update reference count

        $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

        //Generate reference number

        if (empty($transaction_data['ref_no'])) {

            $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
        }

        //upload document

        $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');

        if (!empty($document_name)) {

            $transaction_data['document'] = $document_name;
        }

        if ($request->has('is_recurring')) {

            $transaction_data['is_recurring'] = 1;

            $transaction_data['recur_interval'] = !empty($request->input('recur_interval')) ? $request->input('recur_interval') : 1;

            $transaction_data['recur_interval_type'] = $request->input('recur_interval_type');

            $transaction_data['recur_repetitions'] = $request->input('recur_repetitions');

            $transaction_data['subscription_repeat_on'] = $request->input('recur_interval_type') == 'months' && !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : null;
        }

        DB::beginTransaction();
        $transaction = Transaction::create($transaction_data);
        // add VAT components
        $this->transactionUtil->calculateAndUpdateVAT($transaction);
        $transaction_id =  $transaction->id;
        $tp = null;

        if (!empty($request->payment[0])) {
            $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);

            $inputs['paid_on'] = $transaction->transaction_date;

            $inputs['transaction_id'] = $transaction->id;

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;


            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $amount = $inputs['amount'];

            if ($amount > 0) {
                if ($inputs['method'] != 'credit_expense') {
                    // Check payment method (Cash, Credit, etc.)
                    if ($inputs['method'] == 'cash') {
                        // If the full amount is paid
                        if ($amount >= $transaction->final_total) {
                            $transaction->payment_status = 'paid'; // Set status to paid
                        } else {
                            $transaction->payment_status = 'partial'; // Set status to partial if it's less than the total
                        }
                    }

                    $transaction->save();
                    $inputs['created_by'] = auth()->user()->id;

                    $inputs['payment_for'] = $transaction->contact_id;
                    $prefix_type = 'expense_payment';
                    if ($transaction->type == 'expense') {
                        $prefix_type = 'expense_payment';
                    }

                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    //Generate reference number

                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);



                    $inputs['business_id'] = $business_id;

                    $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');


                    // post dated cheque input
                    $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

                    if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
                        $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                        $bank_account = !empty($inputs['related_account_id']) ? Account::find($inputs['related_account_id']) : null;
                        $bank_name = !empty($bank_account) ? $bank_account->name : '';
                        $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                    }

                    $inputs['is_return'] =  0; //added by 

                    unset($inputs['transaction_no_1']);

                    unset($inputs['transaction_no_2']);

                    unset($inputs['transaction_no_3']);

                    unset($inputs['controller_account']);


                    $cheque_nos = "";
                    // dd($request->select_cheques);
                    if (!empty($request->select_cheques)) {
                        foreach ($request->select_cheques as $select_cheque) {
                            if (!empty($select_cheque)) {
                                $account_transaction = AccountTransaction::find($select_cheque);

                                $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);
                                // dd($transaction_payment);
                                if (!empty($transaction_payment)) {
                                    $amount = $this->transactionUtil->num_uf($account_transaction->amount);
                                    if (!empty($amount)) {
                                        $credit_data = [
                                            'amount' => $amount,
                                            'account_id' => $account_transaction->account_id,
                                            'transaction_id' => $transaction->id,
                                            'type' => 'credit',
                                            'sub_type' => null,
                                            'operation_date' => $transaction_data['transaction_date'],
                                            'created_by' => session()->get('user.id'),
                                            'transaction_payment_id' => $transaction_payment->id,
                                            'note' => null,
                                            'attachment' => null
                                        ];
                                        $credit = AccountTransaction::createAccountTransaction($credit_data);

                                        $cheque_nos .= !empty($transaction_payment->cheque_number) ? $transaction_payment->cheque_number . "," : "";

                                        $transaction_payment->is_deposited = 1;
                                        $transaction_payment->save();
                                    }
                                }
                            }
                        }

                        $inputs['cheque_number'] = $cheque_nos;
                    }
                    $inputs['shift_number'] = $transaction_data['shift_number'] ?? null;
                    $tp = TransactionPayment::create($inputs);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);


                    //update payment status

                    $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $inputs['method']);
                } else {
                    // Special credit expense payment logic
                    $inputs['created_by'] = auth()->user()->id;
                    $inputs['payment_for'] = $transaction->contact_id;
                    $inputs['business_id'] = $business_id;

                    // Generate proper reference number (don't use time())
                    $prefix_type = 'expense_payment';
                    // $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    // $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                    // Essential fields for any payment
                    $inputs['paid_on'] = $transaction->transaction_date;
                    $inputs['transaction_id'] = $transaction->id;
                    // $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                    $inputs['method'] = 'credit_expense';
                    $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;

                    // Document handling if exists
                    // $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

                    // Default values
                    $inputs['is_return'] = 0;
                    $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

                    $tp = TransactionPayment::create($inputs);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);

                    // Update payment status
                    // $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                }
            }
        }

        $this->addAccountTransaction($transaction, $request, $business_id, $tp);

        FinanceBudgetControlService::checkBudgetUsage(
            $business_id,
            $transaction->location_id,
            $transaction->expense_account,
            $transaction->final_total,
            'transactions',
            $transaction->id
        );

        $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Created a new expense: " . $transaction_data['ref_no'], "module" => "expense"];
        $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);


        $accountName = Account::find($request->expense_account);
        $expense_category = ExpenseCategory::find($request->expense_category_id)->name ?? '';
        $sms_data = array(
            'transaction_date' => $this->transactionUtil->format_date($transaction_data['transaction_date']),
            'ref' => $transaction_data['ref_no'],
            'amount' => $this->transactionUtil->num_f($transaction->final_total),
            'account' => !empty($accountName) ? $accountName->name : "",
            'staff' => auth()->user()->username,
            'expense_category' => $expense_category,
        );
        DB::commit();

        // Notification must not rollback the saved expense. In previous builds, any SMS/
        // notification configuration issue after clicking Save or Save & Print could throw an
        // exception before DB::commit(), so the expense disappeared from List Expenses.
        try {
            $this->notificationUtil->sendGeneralNotification('expense_created', $sms_data);
        } catch (\Throwable $notify_exception) {
            Log::warning('Expense saved but notification failed. Transaction ID: ' . $transaction_id . ' Message: ' . $notify_exception->getMessage());
        }

        $output = [

            'success' => 1,

            'msg' => __('expense.expense_add_success')

        ];

        if ($request->is_print == 1) {
            return Redirect::route('expense-print', [$transaction_id]);
        }
    } catch (\Exception $e) {

        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());



        $output = [

            'success' => 0,

            'msg' => __('messages.something_went_wrong')

        ];
    }



    return Redirect::to('expenses')->with('status', $output);
}
    /**
     * Make Print of Save Expense
     *
     *  
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
     **/
    public function print($transaction_id)
    {
        $id = $transaction_id;
        $transaction = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->where('transactions.id', $transaction_id)
            ->withTrashed()
            ->with(['contact', 'business', 'transaction_for'])
            ->first();

        $transaction_type = $transaction->type;

        $payments_query = TransactionPayment::where('transaction_id', $transaction_id);

        $accounts_enabled = false;

        if ($this->moduleUtil->isModuleEnabled('account')) {

            $accounts_enabled = true;

            $payments_query->with(['payment_account']);
        }

        $payments = $payments_query->get();

        $ref_nos = TransactionPayment::where('transaction_id', $transaction_id)->whereNotNull('payment_ref_no')->distinct('payment_ref_no')->pluck('payment_ref_no', 'payment_ref_no');

        $payment_types = $this->transactionUtil->payment_types();

        $on_account_ofs = PaymentOption::where('business_id', $transaction->business_id)->pluck('payment_option', 'id');

        $users = User::where('business_id', $transaction->business_id)->pluck('username', 'id');

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $business_locations = BusinessLocation::where('business_id', $business_id)->first();
        $show_payment_note_in_print = (int) System::getProperty('expense_show_payment_note_in_print_' . $business_id) === 1;
        $show_expense_note_in_print = (int) System::getProperty('expense_show_expense_note_in_print_' . $business_id) === 1;


        return view('expense.invoice', compact(

            'transaction',

            'payments',

            'payment_types',

            'ref_nos',

            'id',

            'accounts_enabled',

            'users',

            'business',

            'business_locations',

            'on_account_ofs',
            'show_payment_note_in_print',
            'show_expense_note_in_print'

        ));
    }


    /**

     * Add Account Transactions

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function addAccountTransaction($transaction, $request, $business_id,  $tp)

    {
        // dd('addAccountTransaction');
        $final_total = $this->transactionUtil->num_uf($request->final_total);

        if (!empty($request->expense_account)) {
            $ob_transaction_data = [

                'amount' => $final_total,

                'account_id' => $request->expense_account,

                'type' => 'debit',

                'sub_type' => 'expense',

                'operation_date' => $transaction->transaction_date,

                'created_by' => Auth::user()->id,

                'transaction_id' => $transaction->id,

                'transaction_payment_id' => !empty($tp) ? $tp->id : null,

                'post_dated_cheque' =>  0
            ];
            AccountTransaction::createAccountTransaction($ob_transaction_data);
        }

        $payment = $request->payment[0];
        // dump($payment,'payment');
        $payment['amount'] = $this->transactionUtil->num_uf($payment['amount']);


        $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->first();
        $account_payable_id = !empty($payment['controller_account'])
            ? $payment['controller_account']
            : (!empty($account_payable) ? $account_payable->id : null);

        // S403: Do not rollback a saved expense only because the tenant has not
        // configured an Accounts Payable account.  Earlier code used first()->id
        // which threw an exception and the newly-added expense was not visible in
        // List Expenses.  Keep the expense/payment saved and skip only the AP leg
        // when no payable account is available.
        if (empty($account_payable_id)) {
            Log::warning('Expense account transaction skipped Accounts Payable leg because no Accounts Payable account is configured. Business ID: ' . $business_id . ' Transaction ID: ' . $transaction->id);
        }
        // dd($account_payable_id,'00');
        $ap_transaction_data = [

            'operation_date' => $transaction->transaction_date,

            'created_by' => Auth::user()->id,

            'transaction_id' => $transaction->id,

            'transaction_payment_id' => !empty($tp) ? $tp->id : null,

            'post_dated_cheque' => !empty($tp) ? $tp->post_dated_cheque : 0,

            'update_post_dated_cheque' => !empty($tp) ? $tp->update_post_dated_cheque : 0,

            'operation_date' =>  $transaction->transaction_date

        ];

        if ($payment['method'] == 'credit_expense') {
            $payment['amount'] = 0;
        }

        // dd($request->select_cheques);
        // dd($request);
        if (!empty($request->select_cheques)) {
            // If partial amount paid with cheques, put the balance in accounts payable
            if ($payment['amount'] < $final_total) {
                $ap_transaction_data['amount'] = $final_total - $payment['amount'];
                if (!empty($account_payable_id)) {
                    $ap_transaction_data['account_id'] = $account_payable_id;
                    $ap_transaction_data['type'] = 'credit';
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
        } else {
            //if no amount paid; insert to Account Payable
            if ($payment['amount'] == 0) {
                $ap_transaction_data['amount'] = $final_total;
                if (!empty($account_payable_id)) {
                    $ap_transaction_data['account_id'] = $account_payable_id;
                    $ap_transaction_data['type'] =  'credit';
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
            //if partial amount paid; insert paid amount to the payment account and the balance in account payable
            else if ($payment['amount'] < $final_total) {
                $ap_transaction_data['amount'] = $payment['amount'];  //paid amount
                $ap_transaction_data['account_id'] = !empty($tp) ? $tp->account_id : null;
                $ap_transaction_data['type'] =  'credit';
                if (!empty($ap_transaction_data['account_id'])) {
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }

                $ap_transaction_data['amount'] = $final_total - $payment['amount']; //unpaid amount
                if (!empty($account_payable_id)) {
                    $ap_transaction_data['account_id'] = $account_payable_id;
                    $ap_transaction_data['type'] = 'credit';
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
            // if full amount paid; insert amount in payment account
            else if ($payment['amount'] == $final_total) {
                $ap_transaction_data['amount'] = $payment['amount'];
                $ap_transaction_data['account_id'] = !empty($tp) ? $tp->account_id : null;
                $ap_transaction_data['type'] = 'credit';
                if (!empty($ap_transaction_data['account_id'])) {
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
        }
    }



    /**

     * Add Account Transactions

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function reverseAccountTransaction($transaction, $request, $business_id)

    {
        // dd($transaction->id);
        // dd(AccountTransaction::where('transaction_id', $transaction->id)->get());
        // dd($transaction, $request, $business_id);
        $records = AccountTransaction::where('transaction_id', $transaction->id);

        // dd($records->count()); // Shows how many rows match before deletion
        // dd($records->forceDelete());
        $records->forceDelete(); // Actually deletes them

        // AccountTransaction::where('transaction_id', $transaction->id)->forcedelete();

    }





    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        //

    }



    /**

     * Show the form for editing the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function edit($id)

    {
        // dd('12223');

        if (!Gate::forUser(auth()->user())->check('expense.update')) {

            abort(403, 'Unauthorized action.');
        }



        $business_id = request()->session()->get('user.business_id');



        //Check if subscribed or not

        if (!$this->moduleUtil->isSubscribed($business_id)) {

            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }



        $business_locations = BusinessLocation::forDropdown($business_id);



        $expense_categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $expense = Transaction::where('business_id', $business_id)

            ->where('id', $id)->with(['purchase_lines'])

            ->first();



        $users = User::forDropdown($business_id, true, true);
        $employees = EssentialsEmployee::pluck('name', 'id');

        $fleets = Fleet::where('business_id', $business_id)->pluck('vehicle_number', 'id');

        $first_location = BusinessLocation::where('business_id', $business_id)->first();

        $payment_types = !empty($first_location) ? $this->transactionUtil->payment_types($first_location->id, true, false, false, false, true, "is_expense_enabled") : [];
        $payment_types = $this->expensePaymentTypes($payment_types);

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $account_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        $payment_line = $this->dummyPaymentLine;

        $accounts = [];

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $current_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Current Assets')->first();

        $current_liability_account_type = AccountType::where('business_id', $business_id)->where('name', 'Current Liabilities')->first();

        $current_liability_account_type_id = !empty($current_liability_account_type) ? $current_liability_account_type->id : 0;



        $contacts = Contact::contactDropdown($business_id, false, false);

        $expense_accounts = [];



        if ($account_module) {

            if (!empty($expense_account_type_id)) {

                $expense_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
                    ->where('accounts.business_id', $business_id)
                    ->where(function ($query) use ($expense_account_type_id) {
                        $query->where('account_groups.name', 'CPC')
                            ->orWhere('accounts.account_type_id', $expense_account_type_id->id)
                            ->orWhere('accounts.name', 'like', '%Expense%');
                    })
                    ->select('accounts.id', 'accounts.name')
                    ->orderBy('accounts.name')
                    ->get()->pluck('name', 'id');
            }
        } else {

            $expense_accounts = Account::where('business_id', $business_id)->where('name', 'Expenses')->pluck('name', 'id');
        }

        if ($expense_accounts->isEmpty()) {
            $expense_accounts = Account::where('business_id', $business_id)
                ->where(function ($query) use ($expense_account_type_id) {
                    if (!empty($expense_account_type_id)) {
                        $query->where('account_type_id', $expense_account_type_id->id);
                    }
                    $query->orWhere('name', 'like', '%Expense%');
                })
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $current_liabilities_accounts =  Account::where('business_id', $business_id)->where('account_type_id', $current_liability_account_type_id)->pluck('name', 'id');

        $cash_account = Account::where('business_id', $business_id)->where('name', 'Cash')->first();
        $cash_account_id = !empty($cash_account) ? $cash_account->id : null;

        return view('expense.edit')

            ->with(compact(

                'cash_account_id',

                'expense',

                'expense_categories',

                'business_locations',

                'users',

                'employees',

                'fleets',

                'taxes',

                'payment_types',

                'account_module',

                'payment_line',

                'accounts',

                'current_liabilities_accounts',

                'expense_accounts',

                'contacts'

            ));
    }



    /**

     * Update the specified resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @param  int  $id

     * @return \Illuminate\Http\Response
 
     */
    public function update1(Request $request, $id)

    {
        // dump($request->payment[0]['method']); 
        // dd($request->all(),$id, 'testing');
        $paymentMethod = $request->payment[0]['method'];
        $transaction_id = '1';
        // $transaction->final_total ='2';
        $this->transactionUtil->updatePaymentStatus($transaction_id, '2', $paymentMethod);
    }

    public function update_old(Request $request, $id)

    {
        dd($request->all());

        if (!Gate::forUser(auth()->user())->check('expense.update')) {

            abort(403, 'Unauthorized action.');
        }



        try {

            //Validate document size

            $request->validate([

                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)

            ]);



            $transaction_data = $request->only(['is_vat', 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'additional_notes', 'expense_category_id', 'tax_id', 'contact_id', 'expense_account']);
            $transaction_data['transaction_date'] = $transaction_data['transaction_date'] ?? $request->expense_transaction_date;
            $has_reviewed = $this->transactionUtil->hasReviewed($transaction_data['transaction_date']);

            if (!empty($has_reviewed)) {
                $output              = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($transaction_data['transaction_date'], $transaction_data['transaction_date']);

            // dd($reviewed,'$reviewed');
            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't modify an expense for an already reviewed date",
                ];

                return Redirect::to('expenses')->with('status', $output);
            }




            $business_id = $request->session()->get('user.business_id');



            //Check if subscribed or not

            if (!$this->moduleUtil->isSubscribed($business_id)) {

                return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
            }



            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

            $transaction_data['final_total'] = $this->transactionUtil->num_uf(

                $transaction_data['final_total']

            );



            //upload document

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            if (!empty($document_name)) {

                $transaction_data['document'] = $document_name;
            }



            $transaction_data['total_before_tax'] = $transaction_data['final_total'];

            if (!empty($transaction_data['tax_id'])) {

                $tax_details = TaxRate::find($transaction_data['tax_id']);

                $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);

                $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
            }

            DB::beginTransaction();



            $transaction = Transaction::findOrFail($id);

            $expense = $transaction;

            $prevTot = $expense->final_total;
            $prevRef = $expense->ref_no;
            // dump($prevTot,$prevRef);
            // dd($transaction,'$business_id',$expense);

            $transaction_data['is_recurring'] = $request->has('is_recurring') ? 1 : $transaction->is_recurring;

            $transaction_data['recur_interval'] = $request->has('is_recurring') && !empty($request->input('recur_interval')) ? $request->input('recur_interval') : $transaction->recur_interval;

            $transaction_data['recur_interval_type'] = !empty($request->input('recur_interval_type')) ? $request->input('recur_interval_type') : $transaction->recur_interval_type;

            $transaction_data['recur_repetitions'] = !empty($request->input('recur_repetitions')) ? $request->input('recur_repetitions') : $transaction->recur_repetitions;

            $transaction_data['subscription_repeat_on'] = !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : $transaction->subscription_repeat_on;



            $transaction->update($transaction_data);


            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            $accountName = null;
            $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'expense_changed')->first();
            if (!empty($msg_template)) {
                $msg = $msg_template->sms_body;

                $msg = str_replace('{account}', !empty($accountName) ? $accountName->name : "", $msg);
                $msg = str_replace('{amount}', $this->transactionUtil->num_f($transaction->final_total), $msg);
                $msg = str_replace('{ref}', $transaction->ref_no, $msg);
                $msg = str_replace('{staff}', auth()->user()->username, $msg);

                $phones = [];
                if (!empty($business->sms_settings)) {
                    $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                }

                if (!empty($phones)) {
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',', $phones),
                        'sms_body' => $msg
                    ];

                    $response = $this->businessUtil->sendSms($data, 'expense_changed');
                }
            }




            $transaction_id =  $transaction->id;



            $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);


            $inputs['paid_on'] = $transaction->transaction_date;

            $inputs['transaction_id'] = $transaction->id;



            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $transaction->contact_id;

            // post dated cheque input
            $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

            if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
                $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                $bank_account = !empty($inputs['related_account_id']) ? Account::find($inputs['related_account_id']) : null;
                $bank_name = !empty($bank_account) ? $bank_account->name : '';
                $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                Log::info('Expense PD cheque update prepared', [
                    'transaction_id' => $transaction->id,
                    'ref_no' => $transaction->ref_no,
                    'payment_method' => $inputs['method'] ?? null,
                    'account_id' => $inputs['account_id'] ?? null,
                    'account_name' => optional(Account::find($inputs['account_id'] ?? null))->name,
                    'related_account_id' => $inputs['related_account_id'] ?? null,
                    'related_account_name' => optional($bank_account)->name,
                    'post_dated_cheque' => $inputs['post_dated_cheque'] ?? 0,
                    'update_post_dated_cheque' => $inputs['update_post_dated_cheque'] ?? 0,
                    'cheque_number' => $inputs['cheque_number'] ?? null,
                    'cheque_date' => $inputs['cheque_date'] ?? null,
                    'note' => $inputs['note'] ?? null,
                ]);
            }



            $prefix_type = 'expense_payment';

            if ($transaction->type == 'expense') {
                $prefix_type = 'expense_payment';
            }



            $transaction->controller_account = !empty($inputs['controller_account']) ? $inputs['controller_account'] : null;

            $transaction->save();



            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);



            $inputs['business_id'] = $business_id;

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');



            $inputs['is_return'] =  0; //added by ahmed

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;


            unset($inputs['transaction_no_1']);

            unset($inputs['transaction_no_2']);

            unset($inputs['transaction_no_3']);

            unset($inputs['controller_account']);

            $tp = null;

            $this->reverseAccountTransaction($transaction, $request, $business_id); // reverse previous account transaction


            $cheque_nos = "";
            if (!empty($request->select_cheques)) {
                foreach ($request->select_cheques as $select_cheque) {
                    if (!empty($select_cheque)) {
                        $account_transaction = AccountTransaction::find($select_cheque);

                        $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);

                        if (!empty($transaction_payment)) {
                            $amount = $this->transactionUtil->num_uf($account_transaction->amount);
                            if (!empty($amount)) {
                                $credit_data = [
                                    'amount' => $amount,
                                    'account_id' => $account_transaction->account_id,
                                    'transaction_id' => $transaction->id,
                                    'type' => 'credit',
                                    'sub_type' => null,
                                    'operation_date' => $transaction_data['transaction_date'],
                                    'created_by' => session()->get('user.id'),
                                    'transaction_payment_id' => $transaction_payment->id,
                                    'note' => null,
                                    'attachment' => null
                                ];
                                $credit = AccountTransaction::createAccountTransaction($credit_data);

                                $cheque_nos .= !empty($transaction_payment->cheque_number) ? $transaction_payment->cheque_number . "," : "";

                                $transaction_payment->is_deposited = 1;
                                $transaction_payment->save();
                            }
                        }
                    }
                }

                $inputs['cheque_number'] = $cheque_nos;
            }


            if ($inputs['method'] != 'credit_expense') {
                $tp = TransactionPayment::updateOrCreate(['transaction_id' => $transaction_id], $inputs);
                $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);
            }


            //update payment status
            $paymentMethod = $request->payment[0]['method'];

            $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $paymentMethod);
            // dump('001');


            dd('1001');
            if ($transaction_data['final_total'] != $prevTot) {
                $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Changed expense amount from: " . $this->transactionUtil->num_f($prevTot) . " to: " . $this->transactionUtil->num_f($transaction_data['final_total']) . " for expense " . $expense->ref_no, "module" => "expense"];
                $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);
            }

            dd('00111');
            if ($transaction_data['ref_no'] != $prevRef) {
                $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Changed expense reference no from : " . $prevRef . " to: " . $transaction_data['ref_no'] . " for expense " . $expense->ref_no, "module" => "expense"];
                $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);
            }

            $this->transactionUtil->calculateAndUpdateVAT($transaction);

            DB::commit();

            $output = [

                'success' => 1,

                'msg' => __('expense.expense_update_success')

            ];
            if ($request->is_print == 1) {
                return Redirect::route('expense-print', [$transaction_id]);
            }
            $this->addAccountTransaction($transaction, $request, $business_id, $tp); // add new transactions

        } catch (\Exception $e) {

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());



            $output = [

                'success' => 0,

                'msg' => __('messages.something_went_wrong')

            ];
        }



        return Redirect::to('expenses')->with('status', $output);
    }
    public function update(Request $request, $id)
    {

        if (!Gate::forUser(auth()->user())->check('expense.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)
            ]);

            $transaction_data = $request->only(['is_vat', 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'additional_notes', 'expense_category_id', 'tax_id', 'contact_id', 'expense_account']);
            $transaction_data['transaction_date'] = $transaction_data['transaction_date'] ?? $request->expense_transaction_date;

            $has_reviewed = $this->transactionUtil->hasReviewed($transaction_data['transaction_date']);
            if (!empty($has_reviewed)) {
                return Redirect::back()->with([
                    'status' => ['success' => 0, 'msg' => __('lang_v1.review_first')]
                ]);
            }

            $reviewed = $this->transactionUtil->get_review($transaction_data['transaction_date'], $transaction_data['transaction_date']);
            if (!empty($reviewed)) {
                return Redirect::to('expenses')->with('status', [
                    'success' => 0,
                    'msg' => "You can't modify an expense for an already reviewed date"
                ]);
            }

            $business_id = $request->session()->get('user.business_id');
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
            }

            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['final_total'] = $this->transactionUtil->num_uf($transaction_data['final_total']);

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $transaction_data['document'] = $document_name;
            }

            $transaction_data['total_before_tax'] = $transaction_data['final_total'];
            if (!empty($transaction_data['tax_id'])) {
                $tax_details = TaxRate::find($transaction_data['tax_id']);
                $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);
                $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
            }

            DB::beginTransaction();


            $transaction = Transaction::findOrFail($id);
            $expense = $transaction;
            $prevTot = $expense->final_total;
            $prevRef = $expense->ref_no;

            $transaction_data['is_recurring'] = $request->has('is_recurring') ? 1 : $transaction->is_recurring;
            $transaction_data['recur_interval'] = $request->has('is_recurring') && !empty($request->input('recur_interval')) ? $request->input('recur_interval') : $transaction->recur_interval;
            $transaction_data['recur_interval_type'] = !empty($request->input('recur_interval_type')) ? $request->input('recur_interval_type') : $transaction->recur_interval_type;
            $transaction_data['recur_repetitions'] = !empty($request->input('recur_repetitions')) ? $request->input('recur_repetitions') : $transaction->recur_repetitions;
            $transaction_data['subscription_repeat_on'] = !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : $transaction->subscription_repeat_on;

            $transaction->update($transaction_data);

            // Detect changes
            $old_payment_method = $expense->payment_lines->first()->method ?? null;
            $new_payment_method = $request->payment[0]['method'];

            $old_expense_account = $expense->expense_account;
            $new_expense_account = $transaction_data['expense_account'];

            if ($old_payment_method !== $new_payment_method && $new_payment_method === 'credit_expense') {
                $transaction->payment_status = 'due';
                $transaction->update();
            }
            $business = Business::find($business_id);
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

            $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'expense_changed')->first();
            if (!empty($msg_template)) {
                $msg = $msg_template->sms_body;
                $msg = str_replace('{account}', '', $msg);
                $msg = str_replace('{amount}', $this->transactionUtil->num_f($transaction->final_total), $msg);
                $msg = str_replace('{ref}', $transaction->ref_no, $msg);
                $msg = str_replace('{staff}', auth()->user()->username, $msg);

                $phones = [];
                if (!empty($business->sms_settings)) {
                    $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                }

                if (!empty($phones)) {
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',', $phones),
                        'sms_body' => $msg
                    ];
                    $this->businessUtil->sendSms($data, 'expense_changed');
                }
            }

            $transaction_id = $transaction->id;
            $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);
            // dd($inputs);

            $inputs['paid_on'] = $transaction->transaction_date;
            $inputs['transaction_id'] = $transaction->id;
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
            $inputs['created_by'] = auth()->user()->id;
            $inputs['payment_for'] = $transaction->contact_id;
            $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

            $transaction->controller_account = $inputs['controller_account'] ?? null;
            $transaction->save();
            // dd($transaction);

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense_payment');

            $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber('expense_payment', $ref_count);
            //  dd($ref_count);
            $inputs['business_id'] = $business_id;
            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            $inputs['is_return'] = 0;
            $inputs['cheque_date'] = $inputs['cheque_date'] ?? $transaction->transaction_date;

            unset($inputs['transaction_no_1'], $inputs['transaction_no_2'], $inputs['transaction_no_3'], $inputs['controller_account']);

            $this->reverseAccountTransaction($transaction, $request, $business_id);
            // dd($test);
            // Handle cheque linking
            $cheque_nos = "";
            if (!empty($request->select_cheques)) {
                foreach ($request->select_cheques as $select_cheque) {
                    $account_transaction = AccountTransaction::find($select_cheque);
                    $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);

                    if (!empty($transaction_payment)) {
                        $amount = $this->transactionUtil->num_uf($account_transaction->amount);
                        if (!empty($amount)) {
                            $credit_data = [
                                'amount' => $amount,
                                'account_id' => $account_transaction->account_id,
                                'transaction_id' => $transaction->id,
                                'type' => 'credit',
                                'operation_date' => $transaction_data['transaction_date'],
                                'created_by' => session()->get('user.id'),
                                'transaction_payment_id' => $transaction_payment->id
                            ];
                            AccountTransaction::createAccountTransaction($credit_data);
                            $cheque_nos .= $transaction_payment->cheque_number . ",";
                            $transaction_payment->is_deposited = 1;
                            $transaction_payment->save();
                        }
                    }
                }
                $inputs['cheque_number'] = rtrim($cheque_nos, ',');
            }

            $tp = null;
            if ($inputs['method'] != 'credit_expense') {
                $tp = TransactionPayment::updateOrCreate(['transaction_id' => $transaction_id], $inputs);
                $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);
            }

            // update payment status
            $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $inputs['method']);

            // update note/description if expense account changed
            if ($old_expense_account != $new_expense_account) {
                $new_account_name = Account::find($new_expense_account)->name ?? '';
                $inputs['note'] = 'Expense for: ' . $new_account_name;
            }

            $this->addAccountTransaction($transaction, $request, $business_id, $tp);

            // change review notes
            if ($transaction_data['final_total'] != $prevTot) {
                $this->transactionUtil->reviewChange($transaction_data['transaction_date'], [
                    "created_by" => auth()->id(),
                    "description" => "Changed expense amount from: " . $this->transactionUtil->num_f($prevTot) . " to: " . $this->transactionUtil->num_f($transaction_data['final_total']) . " for expense " . $expense->ref_no,
                    "module" => "expense"
                ]);
            }

            if ($transaction_data['ref_no'] != $prevRef) {
                $this->transactionUtil->reviewChange($transaction_data['transaction_date'], [
                    "created_by" => auth()->id(),
                    "description" => "Changed expense reference no from : $prevRef to: " . $transaction_data['ref_no'] . " for expense " . $expense->ref_no,
                    "module" => "expense"
                ]);
            }

            $this->transactionUtil->calculateAndUpdateVAT($transaction);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('expense.expense_update_success')];

            if ($request->is_print == 1) {
                return Redirect::route('expense-print', [$transaction_id]);
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return Redirect::to('expenses')->with('status', $output);
    }


    /**

     * Remove the specified resource from storage.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function destroy($id)

    {
        if (!Gate::forUser(auth()->user())->check('expense.delete')) {

            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {

                $business_id = request()->session()->get('user.business_id');
                $expense = Transaction::where('business_id', $business_id)->where('id', $id)
                    ->first();

                $has_reviewed = $this->transactionUtil->hasReviewed($expense->transaction_date);

                if (!empty($has_reviewed)) {
                    $output              = [
                        'success' => 0,
                        'msg'     => __('lang_v1.review_first'),
                    ];

                    return Redirect::back()->with(['status' => $output]);
                }


                $reviewed = $this->transactionUtil->get_review($expense->transaction_date, $expense->transaction_date);


                if (!empty($reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg'     => "You can't delete an expense for an already reviewed date",
                    ];

                    return $output;
                }


                $changes = DB::table('reviewed_changes')
                    ->where('business_id', $business_id)
                    ->whereDate('date', date('Y-m-d', strtotime($expense->transaction_date)))
                    ->select('id')
                    ->first();
                if (!empty($changes)) {
                    // dd($changes);
                    $reviewID = $changes->id;
                    $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Deleted an expense: " . $expense->ref_no, "module" => "expense"];

                    DB::table('reviewed_changes_description')->insert($newReview);
                } else {

                    $reviewID = DB::table('reviewed_changes')->insertGetId([
                        'business_id' => $business_id,
                        'date' => $expense->transaction_date
                    ]);


                    $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Deleted an expense: " . $expense->ref_no, "module" => "expense"];

                    DB::table('reviewed_changes_description')->insert($newReview);
                }
                $transaction = $expense;
                $contact = Contact::find($transaction->contact_id);

                if (!empty($contact)) {
                    $this->notificationUtil->autoSendNotification($transaction->business_id, 'supplier_expense_deleted', $transaction, $contact, true);
                }

                $business_id = request()->session()->get('user.business_id');
                $business = Business::where('id', $business_id)->first();
                $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
                $accountName = null;
                $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'expense_deleted')->first();

                if (!empty($msg_template)) {

                    $msg = $msg_template->sms_body;

                    $msg = str_replace('{account}', !empty($accountName) ? $accountName->name : "", $msg);
                    $msg = str_replace('{amount}', $this->transactionUtil->num_f($expense->final_total), $msg);
                    $msg = str_replace('{ref}', $expense->ref_no, $msg);
                    $msg = str_replace('{staff}', auth()->user()->username, $msg);

                    $phones = [];
                    if (!empty($business->sms_settings)) {
                        $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                    }

                    if (!empty($phones)) {
                        $data = [
                            'sms_settings' => $sms_settings,
                            'mobile_number' => implode(',', $phones),
                            'sms_body' => $msg
                        ];

                        $response = $this->businessUtil->sendSms($data, 'expense_delete');
                    }
                }

                $expense->deleted_by = auth()->user()->id;
                $expense->save();

                //Get original account transactions BEFORE updating them
                $accountTransactions = AccountTransaction::where('transaction_id', $expense->id)->get();
                
                //Mark original account transactions as deleted
                //This allows them to be displayed in red with strikethrough
                AccountTransaction::where('transaction_id', $expense->id)
                    ->update([
                        'new_deleted_at' => now(),
                        'new_deleted_by' => auth()->id()
                    ]);
                
                //Still create reverse entries for accounting balance purposes, but mark them so they can be filtered out from display
                $reverseRecords = [];
                foreach ($accountTransactions as $t) {
                    $reverseRecords[] = [
                        'transaction_id' => $expense->id,
                        'account_id' => $t->account_id,
                        'amount' => $t->amount,
                        'type' => $t->type == 'debit' ? 'credit' : 'debit',
                        'operation_date' => now(),
                        'transaction_payment_id' => $t->transaction_payment_id,
                        'related_account_id' => $t->related_account_id,
                        'created_by' => auth()->id(),
                        'sub_type' => 'expense_reverse' // Mark reverse entries so we can filter them out
                    ];
                }
                AccountTransaction::insert($reverseRecords);

                $output = [

                    'success' => true,

                    'msg' => __("expense.expense_delete_success")

                ];
            } catch (\Exception $e) {
                Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [

                    'success' => false,

                    'msg' => __("messages.something_went_wrong")

                ];
            }



            return $output;
        }
    }



    /**
     * EXP315: Expenses have a system-only payment method that is not configured
     * in Super Admin > Payment Methods. Ensure it is always available on
     * Expense add/edit screens and dynamic location dropdowns.
     */
    private function expensePaymentTypes($payment_types)
    {
        if ($payment_types instanceof \Illuminate\Support\Collection) {
            $payment_types = $payment_types->toArray();
        }

        if (!is_array($payment_types)) {
            $payment_types = [];
        }

        unset($payment_types['credit_sale'], $payment_types['location_id']);
        $payment_types['credit_expense'] = 'Credit Expenses';

        return $payment_types;
    }

    public function getPaymentMethodByLocationDropDown($location_id)

    {

        $payment_methods = $this->transactionUtil->payment_types($location_id, true, false, false, false, true, "is_expense_enabled");
        $payment_methods = $this->expensePaymentTypes($payment_methods);

        return $this->transactionUtil->createDropdownHtml($payment_methods, 'Please Select');
    }

    private function normalizePdChequeFlagValue($value): int
    {
        if (is_array($value)) {
            return collect($value)->contains(function ($item) {
                return !empty($item);
            }) ? 1 : 0;
        }

        return !empty($value) ? 1 : 0;
    }

    private function normalizeExpensePaymentPdChequeInputs(array $inputs): array
    {
        $inputs['post_dated_cheque'] = $this->normalizePdChequeFlagValue($inputs['post_dated_cheque'] ?? 0);
        $inputs['update_post_dated_cheque'] = $this->normalizePdChequeFlagValue($inputs['update_post_dated_cheque'] ?? 0);

        return $inputs;
    }

    private function prepareExpensePdChequePaymentInputs(array $inputs, ?int $expenseCategoryId = null): array
    {
        $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

        if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
            $originalAccountId = $inputs['related_account_id'] ?? ($inputs['account_id'] ?? null);
            $inputs['related_account_id'] = $originalAccountId;
            $inputs['account_id'] = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

            $expense_category_name = !empty($expenseCategoryId)
                ? optional(ExpenseCategory::find($expenseCategoryId))->name
                : null;
            $bank_account = !empty($originalAccountId) ? Account::find($originalAccountId) : null;
            $bank_name = !empty($bank_account) ? $bank_account->name : '';

            $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
        }

        return $inputs;
    }

    private function enforceIssuedPdChequePayment(Transaction $transaction, ?TransactionPayment $tp): ?TransactionPayment
    {
        if (
            empty($tp)
            || $transaction->type !== 'expense'
            || (empty($tp->post_dated_cheque) && empty($tp->update_post_dated_cheque))
        ) {
            return $tp;
        }

        $issuedAccountId = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

        if ((int) $tp->account_id !== (int) $issuedAccountId) {
            if (empty($tp->related_account_id)) {
                $tp->related_account_id = $tp->account_id;
            }

            $tp->account_id = $issuedAccountId;

            if (empty($tp->note)) {
                $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                $bank_account = !empty($tp->related_account_id) ? Account::find($tp->related_account_id) : null;
                $bank_name = !empty($bank_account) ? $bank_account->name : '';
                $tp->note = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
            }

            $tp->save();

            Log::info('Expense PD cheque payment account corrected', [
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => $tp->id,
                'account_id' => $tp->account_id,
                'account_name' => optional(Account::find($tp->account_id))->name,
                'related_account_id' => $tp->related_account_id,
                'related_account_name' => optional(Account::find($tp->related_account_id))->name,
            ]);
        }

        return $tp->fresh();
    }
}


