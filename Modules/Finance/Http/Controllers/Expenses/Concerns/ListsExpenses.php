<?php

namespace Modules\Finance\Http\Controllers\Expenses\Concerns;

use Modules\Finance\Entities\User;
use App\Account;
use Modules\Finance\Entities\Contact;
use App\TaxRate;
use App\Business;
use App\AccountType;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use App\ContactLedger;
use App\ExpenseCategory;
use Modules\Finance\Entities\BusinessLocation;
use App\Utils\ModuleUtil;
use App\AccountTransaction;
use Modules\Finance\Entities\TransactionPayment;
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
use Illuminate\Routing\Controller;

/**
 * Listing, DataTables and the route-operation expense view.
 *
 * MA-002: split out of ExpenseController, which reached 3,605 lines after the
 * controller was extracted from core. Large controllers are hard to maintain
 * and hard to review, so the methods are grouped by what they do.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at ExpenseController,
 *   action('...ExpenseController@store') still resolves, and $this-> calls
 *   between these methods still work exactly as before. Splitting into
 *   separate controller classes would have meant changing routes and every
 *   action() reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files
 *   to read.
 *
 * The method bodies are byte-identical to what was in ExpenseController.
 * Nothing was rewritten while moving.
 *
 * Methods here: index, routeperationExpenses, getDailyShiftOptions, __payment_status
 */
trait ListsExpenses
{
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
                ->leftJoin('contacts AS transaction_contact', 'transaction_contact.id', '=', 'transactions.contact_id')
                ->leftJoin('contacts AS category_payee', 'category_payee.id', '=', 'ec.payee_id')

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

                    $query->whereIn('transactions.type', ['expense', 'ro_advance', 'ro_salary'])
                        ->orWhere('transactions.sub_type', 'expense');
                })

                ->withTrashed()

                ->select(
                    'deleted.username as deletedBy',

                    'transactions.id',

                    'transactions.document',

                    'transaction_date',

                    'ref_no',

                    DB::raw('COALESCE(transaction_contact.name, category_payee.name) as payee_name'),

                    'ec.name as category',

                    'payment_status',

                    'additional_notes',

                    'final_total',

                    'is_settlement',

                    'bl.name as location_name',

                    DB::raw('GROUP_CONCAT(DISTINCT TP.method ORDER BY TP.id SEPARATOR ", ") as method'),

                    DB::raw('MAX(TP.cheque_date) as cheque_date'),

                    DB::raw('MAX(TP.cheque_number) as cheque_number'),

                    DB::raw('MAX(TP.account_id) as account_id'),
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

                    $expenses->where(function ($query) use ($payee_name) {
                        $query->where('transactions.contact_id', $payee_name)
                            ->orWhere('ec.payee_id', $payee_name);
                    });
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

                    <li><a href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>

                    @endcan

                    @if($document)

                        <li><a href="{{ url(\'uploads/documents/\' . $document)}}" 

                        download=""><i class="fa fa-download" aria-hidden="true"></i> @lang("purchase.download_document")</a></li>

                        @if(isFileImage($document))

                            <li><a href="#" data-href="{{ url(\'uploads/documents/\' . $document)}}" class="view_uploaded_document"><i class="fa fa-picture-o" aria-hidden="true"></i>@lang("lang_v1.view_document")</a></li>

                        @endif

                    @endif

                    @can("expense.delete")

                        <li><a data-href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@destroy\', [$id])}}" class="delete_expense"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>

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

        return view('finance::expense.index')

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

                    <li><a href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>

                    @endcan

                    @if($document)

                        <li><a href="{{ url(\'uploads/documents/\' . $document)}}" 

                        download=""><i class="fa fa-download" aria-hidden="true"></i> @lang("purchase.download_document")</a></li>

                        @if(isFileImage($document))

                            <li><a href="#" data-href="{{ url(\'uploads/documents/\' . $document)}}" class="view_uploaded_document"><i class="fa fa-picture-o" aria-hidden="true"></i>@lang("lang_v1.view_document")</a></li>

                        @endif

                    @endif

                    @can("expense.delete")

                        <li><a data-href="{{action(\'\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@destroy\', [$id])}}" class="delete_expense"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>

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
}
