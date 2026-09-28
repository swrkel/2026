<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\AccountType;
use App\Business;
use App\BusinessLocation;
use App\Currency;
use App\DefaultAccountGroup;
use App\Product;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\VariationLocationDetails;
use App\Services\Documents\GlobalPdfService;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Superadmin\Entities\DefaultNotificationTemplate;
use Modules\Superadmin\Entities\DefaultTaxRate;
use Modules\Superadmin\Entities\Subscription;

class HomeController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->businessUtil    = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil      = $moduleUtil;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $business      = Business::select('id', 'default_store')->findOrFail($business_id);
        $default_store = $business->default_store;

        if (empty($default_store) && $this->moduleUtil->isSubscribed(request()->session()->get('business.id')) && auth()->user()->hasRole('Admin#' . request()->session()->get('business.id'))) {
            $output = [
                'success' => 0,
                'msg'     => __('lang_v1.select_the_default_store'),
            ];

            return redirect('business/settings')->with('status', $output);
        }

        if (auth()->user()->hasRole('dsr_officer')) {
            return redirect('/dsr/report');
        }

        $user_id      = request()->session()->get('user.id');
        $subscription = Subscription::active_subscription($business_id);
        // $currency = Currency::where('id', request()->session()->get('business.currency_id'))->first();
        $business_locations = BusinessLocation::forDropdown($business_id);
        // Location-wise currency setup. Always scope by the current business and,
        // when supplied, the selected dashboard location.
        $currency_location_query = BusinessLocation::with('currency')
            ->where('business_id', $business_id);
        if (request()->filled('location_id')) {
            $currency_location_query->where('id', request()->location_id);
        }
        $bussiness_currency = $currency_location_query->select('id', 'currency_id')->first();
        $currency           = ($bussiness_currency && $bussiness_currency->currency)
            ? $bussiness_currency->currency
            : Currency::where('id', request()->session()->get('business.currency_id'))->first();
        if (is_null($currency)) {
            $currency = new Currency();
        }
        // CORE-STAB-20260713: Never run schema/data repair jobs during a normal dashboard GET.
        // These routines write many rows and previously made /home slow or appear empty while waiting.
        // They must be run from installation/migration/maintenance commands instead.

        if (session()->get('business.is_patient')) {
            return redirect('patient');
        }
        if (session()->get('business.is_hospital') || session()->get('business.is_laboratory')) {
            return redirect('hospital');
        }
        $home_dashboard      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'home_dashboard');

        // CORE-STAB-20260713: The main dashboard is a core tenant page.
        // Package metadata must not leave an authorised business administrator with a blank /home page.
        $is_business_admin = auth()->user()->hasRole('Admin#' . $business_id) || auth()->user()->can('superadmin');
        if ($is_business_admin) {
            $home_dashboard = true;
        }
        $enable_petro_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module');
        /**
         * @author:Afes Oktavianus
         * @since: 25-08-2021
         * @Req :3413
         */

        if (request()->ajax()) {
            $filter      = request()->filter;
            $type        = request()->type;
            $location_id = request()->location_id;

            $allowed_filters = ['today', 'yesterday', 'week', 'month', 'year', 'financial-year'];
            $filter          = in_array($filter, $allowed_filters, true) ? $filter : 'today';
            $type            = $type === 'sub' ? 'sub' : 'category';
            $bargraph        = [];
            $divide          = 0;
            $today           = Carbon::today();

            switch ($filter) {
                case 'yesterday':
                    $current_start = $today->copy()->subDay();
                    $current_end   = $current_start->copy();
                    $previous_start = $today->copy()->subDays(2);
                    $previous_end   = $previous_start->copy();
                    $prev_title     = '2 Days ';
                    $bargraph[]     = ['start' => $current_start->toDateString(), 'end' => $current_end->toDateString()];
                    break;

                case 'week':
                    $current_start  = $today->copy()->startOfWeek(Carbon::MONDAY);
                    $current_end    = $today->copy()->endOfWeek(Carbon::SUNDAY);
                    $previous_start = $current_start->copy()->subWeek();
                    $previous_end   = $current_end->copy()->subWeek();
                    $prev_title     = 'Week ';

                    for ($cursor = $current_start->copy(); $cursor->lte($current_end); $cursor->addDay()) {
                        $bargraph[] = ['start' => $cursor->toDateString(), 'end' => $cursor->toDateString()];
                    }
                    break;

                case 'month':
                    $current_start  = $today->copy()->startOfMonth();
                    $current_end    = $today->copy()->endOfMonth();
                    $previous_start = $today->copy()->subMonthNoOverflow()->startOfMonth();
                    $previous_end   = $today->copy()->subMonthNoOverflow()->endOfMonth();
                    $prev_title     = 'Month ';

                    for ($cursor = $current_start->copy(); $cursor->lte($current_end); $cursor->addDays(3)) {
                        $chunk_end  = $cursor->copy()->addDays(2);
                        if ($chunk_end->gt($current_end)) {
                            $chunk_end = $current_end->copy();
                        }
                        $bargraph[] = ['start' => $cursor->toDateString(), 'end' => $chunk_end->toDateString()];
                    }
                    break;

                case 'year':
                    $divide         = 1;
                    $current_start  = $today->copy()->startOfYear();
                    $current_end    = $today->copy()->endOfYear();
                    $previous_start = $current_start->copy()->subYear();
                    $previous_end   = $current_end->copy()->subYear();
                    $prev_title     = 'Year ';

                    for ($cursor = $current_start->copy(); $cursor->lte($current_end); $cursor->addMonth()) {
                        $bargraph[] = [
                            'start' => $cursor->copy()->startOfMonth()->toDateString(),
                            'end'   => $cursor->copy()->endOfMonth()->toDateString(),
                        ];
                    }
                    break;

                case 'financial-year':
                    $divide        = 1;
                    $fy_start_date = request()->session()->get('financial_year.start');
                    $fy_end_date   = request()->session()->get('financial_year.end');
                    try {
                        if (empty($fy_start_date) || empty($fy_end_date)) {
                            throw new \InvalidArgumentException('Financial year dates are not available.');
                        }
                        $current_start = Carbon::parse($fy_start_date);
                        $current_end   = Carbon::parse($fy_end_date);
                    } catch (\Throwable $exception) {
                        $current_start = $today->copy()->startOfYear();
                        $current_end   = $today->copy()->endOfYear();
                    }
                    if ($current_end->lt($current_start)) {
                        $current_end = $current_start->copy()->addYear()->subDay();
                    }
                    $previous_start = $current_start->copy()->subYear();
                    $previous_end   = $current_end->copy()->subYear();
                    $prev_title     = 'Financial Year ';

                    for ($cursor = $current_start->copy()->startOfMonth(); $cursor->lte($current_end); $cursor->addMonth()) {
                        $period_start = $cursor->copy()->startOfMonth();
                        $period_end   = $cursor->copy()->endOfMonth();
                        if ($period_start->lt($current_start)) {
                            $period_start = $current_start->copy();
                        }
                        if ($period_end->gt($current_end)) {
                            $period_end = $current_end->copy();
                        }
                        $bargraph[] = ['start' => $period_start->toDateString(), 'end' => $period_end->toDateString()];
                    }
                    break;

                case 'today':
                default:
                    $current_start  = $today->copy();
                    $current_end    = $today->copy();
                    $previous_start = $today->copy()->subDay();
                    $previous_end   = $previous_start->copy();
                    $prev_title     = 'Day ';
                    $bargraph[]     = ['start' => $current_start->toDateString(), 'end' => $current_end->toDateString()];
                    break;
            }

            $start      = $current_start->toDateString();
            $end        = $current_end->toDateString();
            $start_prev = $previous_start->toDateString();
            $end_prev   = $previous_end->toDateString();

            $period_titles = [
                'today'          => ['current' => 'Today', 'previous' => 'Last Day'],
                'yesterday'      => ['current' => 'Yesterday', 'previous' => '2 Days Ago'],
                'week'           => ['current' => 'This Week', 'previous' => 'Last Week'],
                'month'          => ['current' => 'This Month', 'previous' => 'Last Month'],
                'year'           => ['current' => 'This Year', 'previous' => 'Last Year'],
                'financial-year' => ['current' => 'This Financial Year', 'previous' => 'Last Financial Year'],
            ];
            $selected_period_title = $period_titles[$filter] ?? $period_titles['today'];
            $current_title         = $selected_period_title['current'];
            $previous_title        = $selected_period_title['previous'];
            $date_label            = $start === $end ? $start : $start . ' - ' . $end;

            $cashacc = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Cash Account')->select('accounts.id')->get()->pluck('id');

            $card_account = $this->transactionUtil->account_exist_return_id('Cards (Credit Debit) Account');

            // GET LINKED CARD ACCOUNT IDS
            $cardsacc = $this->getLinkedCardsIds($card_account);

            /*
             * HOME-FIN-20260905:
             * The six summary cards use the Finance ledger as their single
             * source of truth.  Do not mix transaction-table estimates with
             * account-book figures: that is what made the dashboard disagree
             * with Finance / List Accounts.  All date/location rules are now
             * applied to account_transactions and their Finance accounts.
             */
            $finance_metrics = $this->getDashboardFinanceMetrics(
                (int) $business_id,
                $start,
                $end,
                $location_id
            );

            $sales           = $finance_metrics['sales'];
            $credit_received = $finance_metrics['credit_received'];
            $purchases       = $finance_metrics['purchases'];
            $expense_total   = $finance_metrics['expenses'];
            $stocks          = $finance_metrics['stocks'];
            $credit_given    = $finance_metrics['credit_given'];

            $finance_metrics_prev = $this->getDashboardFinanceMetrics(
                (int) $business_id,
                $start_prev,
                $end_prev,
                $location_id
            );
            $credit_given_prev = $finance_metrics_prev['credit_given'];

            $cashtrans      = $this->totalDebitsTotalCredits($business_id, $cashacc, $start, $end, $location_id)['debit'];
            $cashtrans_prev = $this->totalDebitsTotalCredits($business_id, $cashacc, $start_prev, $end_prev, $location_id)['debit'];

            $cardtrans      = $this->totalDebitsTotalCredits($business_id, $cardsacc, $start, $end, $location_id)['debit'];
            $cardtrans_prev = $this->totalDebitsTotalCredits($business_id, $cardsacc, $start_prev, $end_prev, $location_id)['debit'];

            $shortage_query = Transaction::select('transaction_payments.final_total')
                ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')

                ->where('transactions.business_id', $business_id)->where('type', 'settlement')->where('sub_type', 'shortage')

                ->when(! empty($location_id), function ($query) use ($location_id) {
                    $query->where('transactions.location_id', $location_id);
                })

                ->whereIn('transactions.payment_status', ['paid', 'partial'])

                ->whereDate('transaction_date', '>=', $start)

                ->whereDate('transaction_date', '<=', $end);

            $shortage = $shortage_query->sum('final_total');

            $shortage_query_prev = Transaction::select('transaction_payments.final_total')
                ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')

                ->where('transactions.business_id', $business_id)->where('type', 'settlement')->where('sub_type', 'shortage')

                ->when(! empty($location_id), function ($query) use ($location_id) {
                    $query->where('transactions.location_id', $location_id);
                })

                ->whereIn('transactions.payment_status', ['paid', 'partial'])

                ->whereDate('transaction_date', '>=', $start_prev)

                ->whereDate('transaction_date', '<=', $end_prev);

            $shortage_prev = $shortage_query_prev->sum('final_total');

            $pie_chart      = [round($cashtrans, 2), round($cardtrans, 2), round($credit_given, 2), round($shortage, 2)];
            $pie_chart_prev = [round($cashtrans_prev, 2), round($cardtrans_prev, 2), round($credit_given_prev, 2), round($shortage_prev, 2)];

            $chartdata = [];
            foreach ($bargraph as $bar) {
                $bar_metrics = $this->getDashboardFinanceMetrics(
                    (int) $business_id,
                    $bar['start'],
                    $bar['end'],
                    $location_id
                );

                $stocksN = $bar_metrics['stocks'] / 1000;
                if ($divide == "1") {
                    $stocksN *= 1000;
                }

                $chartdata[] = [
                    "year"      => $bar['end'],
                    "purchases" => $bar_metrics['purchases'],
                    "sales"     => $bar_metrics['sales'],
                    "stocks"    => $stocksN,
                    "expenses"  => $bar_metrics['expenses'],
                    "color"     => "#311B92",
                    "color2"    => "#2E7D32",
                    "color3"    => "#B56101",
                    "color4"    => "#D32F2F",
                ];
            }

            //  $bar_chart = $this->getBarchart($filter,$start,$end);

            $layered = $this->getLayeredchart($filter, $start, $end, $start_prev, $end_prev, $divide, $location_id);

            $comparison_rows = array_slice(json_decode($layered, true) ?: [], 0, 4);
            $comparison      = [
                'labels'   => array_values(array_map(function ($row) {
                    return $row['type'] ?? '';
                }, $comparison_rows)),
                'current'  => array_values(array_map(function ($row) {
                    return (float) ($row['This'] ?? 0);
                }, $comparison_rows)),
                'previous' => array_values(array_map(function ($row) {
                    return (float) ($row['Last'] ?? 0);
                }, $comparison_rows)),
            ];

            if ($type == "sub") {
                $topcats = DB::table('transaction_sell_lines')
                    ->join('transactions', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                    ->join('products', 'products.id', '=', 'transaction_sell_lines.product_id')
                    ->join('categories', 'categories.id', '=', 'products.sub_category_id')
                    ->select('categories.name', DB::raw('SUM(transaction_sell_lines.quantity*transaction_sell_lines.unit_price) as valued'))
                    ->groupBy('categories.id')
                    ->where('transactions.business_id', $business_id)
                    ->when(! empty($location_id), function ($query) use ($location_id) {
                        $query->where('transactions.location_id', $location_id);
                    })
                    ->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end)
                    ->orderBy('valued', 'desc')
                    ->limit(4)
                    ->get()->toArray();
            } else {

                $topcats = DB::table('transaction_sell_lines')
                    ->join('transactions', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                    ->join('products', 'products.id', '=', 'transaction_sell_lines.product_id')
                    ->join('categories', 'categories.id', '=', 'products.category_id')
                    ->select('categories.name', DB::raw('SUM(transaction_sell_lines.quantity*transaction_sell_lines.unit_price) as valued'))
                    ->groupBy('categories.id')
                    ->where('transactions.business_id', $business_id)
                    ->when(! empty($location_id), function ($query) use ($location_id) {
                        $query->where('transactions.location_id', $location_id);
                    })
                    ->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end)
                    ->orderBy('valued', 'desc')
                    ->limit(4)
                    ->get()->toArray();
            }

            $topcatsVal = [];
            $topcatsLab = [];
            foreach ($topcats as $one) {
                $topcatsLab[] = $one->name;
                $topcatsVal[] = (int) $one->valued;
            }

            return response()->json(
                [
                    "sales" => $currency->symbol . " " . number_format($sales, 2, $currency->decimal_separator, $currency->thousand_separator),

                    "credit_received"           => $currency->symbol . " " . number_format($credit_received, 2, $currency->decimal_separator, $currency->thousand_separator),
                    "purchases"                 => $currency->symbol . " " . number_format($purchases, 2, $currency->decimal_separator, $currency->thousand_separator),
                    "expenses"                  => $currency->symbol . " " . number_format($expense_total, 2, $currency->decimal_separator, $currency->thousand_separator),
                    "stocks"                    => $currency->symbol . " " . number_format($stocks, 2, $currency->decimal_separator, $currency->thousand_separator),

                    "credit_given"              => $currency->symbol . " " . number_format($credit_given, 2, $currency->decimal_separator, $currency->thousand_separator),
                    "bar_chart"                  => json_encode($chartdata),

                    "pie_chart"                 => $pie_chart,

                    "pie_curr"                  => $pie_chart,

                    "pie_prev"                  => $pie_chart_prev,

                    "prev_title"                => $prev_title,

                    "layered"                   => $layered,
                    "comparison"                => $comparison,
                    "current_title"             => $current_title,
                    "previous_title"            => $previous_title,
                    "date_label"                => $date_label,
                    "chart_scale_suffix"        => '',

                    "topcatsVal"                => $topcatsVal,

                    "topcatsLab"                => $topcatsLab,

                ]
            );
        }

        $enable_petro_dashboard          = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module');
        $enable_petro_task_management    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_task_management');
        $enable_petro_pump_management    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_pump_management');
        $enable_petro_management_testing = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_management_testing');
        $enable_petro_meter_reading      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_meter_reading');
        $enable_petro_meter_resetting    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_meter_resetting');
        $enable_petro_pump_dashboard     = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_pump_dashboard');
        $enable_petro_pumper_management  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_pumper_management');
        $enable_petro_daily_collection   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_daily_collection');
        $enable_petro_settlement         = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_settlement');
        $enable_petro_list_settlement    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_list_settlement');
        $enable_petro_dip_management     = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_dip_management');
        $report_module                   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_module');
        $product_report                  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'product_report');
        $payment_status_report           = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'payment_status_report');
        $report_daily                    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_daily');
        $report_daily_summary            = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_daily_summary');
        $report_profit_loss              = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_profit_loss');
        $report_credit_status            = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_credit_status');
        $activity_report                 = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'activity_report');
        $contact_report                  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'contact_report');
        $trending_product                = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'trending_product');
        $user_activity                   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'user_activity');
        $report_verification             = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_verification');
        $report_table                    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_table');
        $report_staff_service            = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_staff_service');
        $report_register                 = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'report_register');
        $contact_module                  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'contact_module');
        $membership_module               = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'membership_module');
        $contact_supplier                = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'contact_supplier');
        $contact_customer                = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'contact_customer');
        $contact_group_customer          = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'contact_group_customer');
        $contact_group_supplier          = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'contact_group_supplier');
        $import_contact                  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'import_contact');
        $customer_reference              = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'customer_reference');
        $customer_statement              = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'customer_statement');
        $customer_payment                = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'customer_payment');
        $outstanding_received            = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'outstanding_received');
        $issue_payment_detail            = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'issue_payment_detail');

        $edit_received_outstanding = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_received_outstanding');
        $pos_sale                  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pos_sale');

        $cheque_templates       = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'cheque_templates');
        $write_cheque           = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'write_cheque');
        $manage_stamps          = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manage_stamps');
        $manage_payee           = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'manage_payee');
        $cheque_number_list     = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'cheque_number_list');
        $deleted_cheque_details = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'deleted_cheque_details');
        $printed_cheque_details = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'printed_cheque_details');
        $default_setting        = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'default_setting');

        $pump_operator_dashboard     = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');
        $property_module             = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'property_module');
        $disable_all_other_module_vr = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'disable_all_other_module_vr');

        if ($disable_all_other_module_vr && ! auth()->user()->can('superadmin')) {
            return redirect()->to('visitor-module/visitor');
        }

        if (! auth()->user()->can('dashboard.data')) {
            Log::info('authenticated user with dashboard.data access');
            return view('home.index')->with(compact(
                'home_dashboard',
                'property_module',
                'enable_petro_module',
                'enable_petro_dashboard',
                'enable_petro_task_management',
                'enable_petro_pump_management',
                'enable_petro_management_testing',
                'enable_petro_meter_reading',
                'enable_petro_meter_resetting',
                'enable_petro_pump_dashboard',
                'enable_petro_pumper_management',
                'enable_petro_daily_collection',
                'enable_petro_settlement',
                'enable_petro_list_settlement',
                'enable_petro_dip_management',
                'report_module',
                'product_report',
                'payment_status_report',
                'report_daily',
                'report_daily_summary',
                'report_profit_loss',
                'report_credit_status',
                'activity_report',
                'contact_report',
                'trending_product',
                'user_activity',
                'report_verification',
                'report_table',
                'report_staff_service',
                'report_register',
                'contact_module',
                'contact_supplier',
                'contact_customer',
                'contact_group_customer',
                'contact_group_supplier',
                'import_contact',
                'customer_reference',
                'customer_statement',
                'customer_payment',
                'outstanding_received',
                'issue_payment_detail',
                'edit_received_outstanding',
                'pos_sale',
                'subscription',
                'cheque_templates',
                'write_cheque',
                'manage_stamps',
                'manage_payee',
                'cheque_number_list',
                'deleted_cheque_details',
                'printed_cheque_details',
                'default_setting',
                'currency',
                'business_locations'
            ));
        }
        Log::info('authenticated user with no dashboard.data access');
        return view('home.index', compact(
            /*'help_explanations',*/
            'home_dashboard',
            /*'date_filters',*/
            /*'sells_chart_1',
            'sells_chart_2',*/
            /*'widgets',*/
            /*'customer_name_payment',
            'all_locations',*/
            'pump_operator_dashboard',
            'property_module',
            'enable_petro_module',
            'enable_petro_dashboard',
            'enable_petro_task_management',
            'enable_petro_pump_management',
            'enable_petro_management_testing',
            'enable_petro_meter_reading',
            'enable_petro_meter_resetting',
            'enable_petro_pump_dashboard',
            'enable_petro_pumper_management',
            'enable_petro_daily_collection',
            'enable_petro_settlement',
            'enable_petro_list_settlement',
            'enable_petro_dip_management',
            /*'register_success',*/
            'report_module',
            'product_report',
            'payment_status_report',
            'report_daily',
            'report_daily_summary',
            'report_profit_loss',
            'report_credit_status',
            'activity_report',
            'contact_report',
            'trending_product',
            'user_activity',
            'report_verification',
            'report_table',
            'report_staff_service',
            'report_register',
            'contact_module',
            'contact_supplier',
            'contact_customer',
            'contact_group_customer',
            'contact_group_supplier',
            'import_contact',
            'customer_reference',
            'customer_statement',
            'customer_payment',
            'outstanding_received',
            'issue_payment_detail',
            'edit_received_outstanding',
            'pos_sale',
            'subscription',
            'cheque_templates',
            'write_cheque',
            'manage_stamps',
            'manage_payee',
            'cheque_number_list',
            'deleted_cheque_details',
            'printed_cheque_details',
            'default_setting',
            'currency',
            'business_locations',
            'membership_module'
        ));
    }

    public function updateReferences()
    {
        $payments = TransactionPayment::whereRaw("REGEXP_SUBSTR(payment_ref_no, '^[0-9]')")->get();
        foreach ($payments as $pmt) {
            $pmt->previous_prefix_no = $pmt->payment_ref_no;
            $pmt->payment_ref_no     = $this->transactionUtil->generateCustomPrefix() . "-" . $pmt->payment_ref_no;
            $pmt->save();
        }
    }

    public function synchAccountGroups()
    {

        $account_grps = DefaultAccountGroup::all();
        $business_id  = request()->session()->get('user.business_id');

        foreach ($account_grps as $acc) {
            $default_account_group_exist = AccountGroup::where('business_id', $business_id)->where('name', $acc->name)->first();
            if (empty($default_account_group_exist)) {
                $account_type = AccountType::where('business_id', $business_id)->where('default_account_type_id', $acc->account_type_id)->first();
                if (empty($account_type)) {
                    $account_type = AccountType::where('business_id', $business_id)->where('name', $acc->name)->first();
                }

                $data = [
                    'business_id'              => $business_id,
                    'name'                     => $acc->name,
                    'account_type_id'          => ! empty($account_type) ? $account_type->id : null,
                    'note'                     => $acc->note,
                    'show_status'              => $acc->show_status,
                    'default_account_group_id' => $acc->id,
                ];

                AccountGroup::create($data);
            }
        }
    }

    private function synsTaxSettings()
    {
        $settings    = ['enable_inline_tax', 'tax_number_2', 'tax_label_2', 'tax_number_1', 'tax_label_1'];
        $values      = DB::table('system')->whereIn('key', $settings)->get();
        $business_id = request()->session()->get('user.business_id');
        foreach ($values as $val) {
            DB::table('business')->where('id', $business_id)->whereNull($val->key)->update([$val->key => $val->value]);
        }

        $tax_rates = DefaultTaxRate::all();

        foreach ($tax_rates as $rate) {
            $data = [
                'business_id'    => $business_id,
                'name'           => $rate->name,
                'amount'         => $rate->amount,
                'created_by'     => $rate->created_by,
                'default_tax_id' => $rate->id,
                'is_tax_group'   => $rate->is_tax_group,
                'for_tax_group'  => $rate->for_tax_group,
            ];
            TaxRate::updateOrCreate([
                'business_id' => $business_id,
                'name'                                 => $rate->name
            ], $data);
        }
    }

    public function notSubscribed()
    {
        $business_id = request()->session()->get('user.business_id');

        $subscription    = Subscription::current_subscription($business_id);
        $package_details = $subscription->package_details;

        $message          = $package_details['notsubscribed_message_content'];
        $font_family      = $package_details['ns_font_family'];
        $font_color       = $package_details['ns_font_color'];
        $font_size        = $package_details['ns_font_size'];
        $background_color = $package_details['ns_background_color'];

        $message = ! empty($message) ? $message : "You have not subscribed to this module!";

        return view('home.not-subscribed', compact('message', 'font_family', 'font_color', 'font_size', 'background_color'));
    }

    public function getLinkedCardsIds($parent_id)
    {
        $cards = Account::where('parent_account_id', $parent_id)->pluck('id');
        return $cards;
    }

    public function getLayeredchart($filter, $start, $end, $start_prev, $end_prev, $divide, $location_id = null)
    {

        $business_id = request()->session()->get('user.business_id');

        $current_metrics = $this->getDashboardFinanceMetrics(
            (int) $business_id,
            $start,
            $end,
            $location_id
        );
        $previous_metrics = $this->getDashboardFinanceMetrics(
            (int) $business_id,
            $start_prev,
            $end_prev,
            $location_id
        );

        $sales                = $current_metrics['sales'];
        $sales_prev           = $previous_metrics['sales'];
        $purchases            = $current_metrics['purchases'];
        $purchases_prev       = $previous_metrics['purchases'];
        $expenses             = $current_metrics['expenses'];
        $expenses_prev        = $previous_metrics['expenses'];
        $stocks               = $current_metrics['stocks'];
        $stocks_prev          = $previous_metrics['stocks'];
        $credit_given         = $current_metrics['credit_given'];
        $credit_given_prev    = $previous_metrics['credit_given'];
        $credit_received      = $current_metrics['credit_received'];
        $credit_received_prev = $previous_metrics['credit_received'];

        // {
        //   "country": "USA",
        //   "year2004": 3.5,
        //   "year2005": 4.2
        // },

        $chartdata = [
            ["type" => "Purchases", "This" => $purchases, "Last" => $purchases_prev, "color" => '#bfbffd', "color2" => '#7474F0'],

            ["type" => "Sales", "This" => $sales, "Last" => $sales_prev, "color" => '#bfbffd', "color2" => '#7474F0'],

            ["type" => "Stocks", "This" => $stocks, "Last" => $stocks_prev, "color" => '#bfbffd', "color2" => '#7474F0'],

            ["type" => "Expenses", "This" => $expenses, "Last" => $expenses_prev, "color" => '#bfbffd', "color2" => '#7474F0'],

            ["type" => "Credit Given", "This" => $credit_given, "Last" => $credit_given_prev, "color" => '#bfbffd', "color2" => '#7474F0'],

            ["type" => "Credit Received", "This" => $credit_received, "Last" => $credit_received_prev, "color" => '#bfbffd', "color2" => '#7474F0'],
        ];

        return json_encode($chartdata);
    }

    /**
     * Return Accounts Receivable opening-balance debits that fall inside the
     * selected dashboard period. These entries must be excluded from
     * "Credit Given" because they are carried-forward balances, not credit
     * sales created during the selected period.
     *
     * The previous query used >= start AND >= end, which effectively reduced
     * a multi-day range to the end date and produced inconsistent dashboard
     * totals. Keep this helper strictly business/date/location scoped.
     */
    public function getcreditOpeningBalance($business_id, $start_date, $id, $end_date, $location_id = null)
    {
        if (empty($id) || $id->isEmpty()) {
            return 0.0;
        }

        $query = DB::table('account_transactions')
            ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('account_transactions.account_id', $id)
            ->where('account_transactions.type', 'debit')
            ->where('transactions.type', 'opening_balance')
            ->whereNull('account_transactions.deleted_at')
            ->whereBetween('account_transactions.operation_date', [
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59',
            ]);

        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        return (float) $query->sum('account_transactions.amount');
    }

    public function getTotalPurchases($business_id, $location_id = null, $start_date, $end_date)
    {

        $query = Transaction::leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->select(
                'final_total',
                DB::raw("(final_total - tax_amount) as total_exc_tax"),
                DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid"),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
                'shipping_charges'
            )
            ->groupBy('transactions.id');

        if (! empty($start_date)) {
            $query->whereDate('transaction_date', '>=', $start_date);
        }

        if (! empty($end_date)) {
            $query->whereDate('transaction_date', '<=', $end_date);
        }

        //Filter by the location
        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $p = $query->select(['contacts.name as cname', 'transactions.transaction_date', 'final_total as amount'])->get();

        return abs($p->sum('amount'));
    }

    /**
     * IS2060: $location_id added.
     *
     * The dashboard has a Business Location selector, and Purchases and Stocks
     * already honour it - getTotalPurchases() takes $location_id. Sales, Credit
     * given and Credit received come through here, which had no location filter
     * at all, so those three cards showed EVERY location's figures no matter
     * what was selected.
     *
     * account_transactions has no location column of its own; it reaches one
     * through the transaction it belongs to. The join below is therefore a LEFT
     * join with the filter written so that rows with no linked transaction are
     * still counted when no location is selected, and excluded when one is -
     * an inner join would silently drop every account transaction that is not
     * tied to a sale or purchase, such as a manual journal.
     */
    public function totalDebitsTotalCredits($business_id, $id, $start_date, $end_date, $location_id = null)
    {
        return $this->dashboardFinanceTotals(
            (int) $business_id,
            collect($id)->filter()->map(fn ($accountId) => (int) $accountId)->values()->all(),
            $start_date,
            $end_date,
            $location_id
        );
    }

    /**
     * HOME-FIN-20260905: Finance-ledger source for all six Home Dashboard cards.
     *
     * The dashboard used to mix four different sources: sales/receivables from
     * account_transactions, purchases from transactions, expenses from the core
     * expense report, and stock from a Finance account balance.  Those sources
     * cannot be expected to reconcile.  This method intentionally derives every
     * card from the same Finance account ledger used by List Accounts.
     */
    private function getDashboardFinanceMetrics(
        int $businessId,
        ?string $startDate,
        ?string $endDate,
        $locationId = null
    ): array {
        $locationId = $this->normalizeDashboardLocationId($locationId);

        $salesAccountIds = $this->dashboardSalesAccountIds($businessId);
        $receivableAccountIds = $this->dashboardReceivableAccountIds($businessId);
        $expenseAccountIds = $this->dashboardExpenseAccountIds($businessId);
        $finishedGoodsAccountIds = $this->dashboardStockAccountIds(
            $businessId,
            ['Finished Goods Account']
        );
        $purchaseStockAccountIds = $this->dashboardStockAccountIds(
            $businessId,
            ['Finished Goods Account', 'Raw Material Account', 'Other Stocks', 'Goods in Transit']
        );

        $salesMovement = $this->dashboardFinanceTotals(
            $businessId,
            $salesAccountIds,
            $startDate,
            $endDate,
            $locationId
        );

        $purchaseMovement = $this->dashboardFinanceTotals(
            $businessId,
            $purchaseStockAccountIds,
            $startDate,
            $endDate,
            $locationId,
            ['purchase', 'purchase_return']
        );

        $expenseMovement = $this->dashboardFinanceTotals(
            $businessId,
            $expenseAccountIds,
            $startDate,
            $endDate,
            $locationId
        );

        // Stock is a balance as at the END of the selected period, not the
        // period movement.  That is how the Finance account book is read.
        $stockMovement = $this->dashboardFinanceTotals(
            $businessId,
            $finishedGoodsAccountIds,
            null,
            $endDate,
            $locationId
        );

        $receivableMovement = $this->dashboardFinanceTotals(
            $businessId,
            $receivableAccountIds,
            $startDate,
            $endDate,
            $locationId
        );

        $openingReceivable = $this->dashboardFinanceTotals(
            $businessId,
            $receivableAccountIds,
            $startDate,
            $endDate,
            $locationId,
            ['opening_balance']
        );

        return [
            // Income accounts have a normal credit balance. Returns/reversals
            // posted as debits reduce the selected-period Sales amount.
            'sales' => (float) ($salesMovement['credit'] - $salesMovement['debit']),

            // Purchases are the net Finance stock-account movement generated by
            // purchase/purchase-return transactions. This keeps the figure in
            // the ledger instead of reading transactions.final_total directly.
            'purchases' => (float) ($purchaseMovement['debit'] - $purchaseMovement['credit']),

            // Finished Goods is an asset, therefore debit less credit.
            'stocks' => (float) ($stockMovement['debit'] - $stockMovement['credit']),

            // Operating expense accounts are debit-normal. COGS is deliberately
            // excluded from this card because stock/COGS is represented separately.
            'expenses' => (float) ($expenseMovement['debit'] - $expenseMovement['credit']),

            // New receivable debits are credit given. Opening balances carried
            // into the system are not credit granted during this period.
            'credit_given' => (float) ($receivableMovement['debit'] - $openingReceivable['debit']),
            'credit_received' => (float) $receivableMovement['credit'],
        ];
    }

    /**
     * Aggregate Finance movements for a set of accounts.  The deletion and
     * location rules mirror Finance account reporting: soft-deleted ledger rows
     * are ignored; deleted payment rows are ignored; account ownership controls
     * business scope; transaction/journal/account location is used as fallback
     * for historical rows that do not carry account_transactions.location_id.
     */
    private function dashboardFinanceTotals(
        int $businessId,
        array $accountIds,
        ?string $startDate = null,
        ?string $endDate = null,
        $locationId = null,
        ?array $transactionTypes = null
    ): array {
        $accountIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        if ($accountIds === []) {
            return ['debit' => 0.0, 'credit' => 0.0];
        }

        $locationId = $this->normalizeDashboardLocationId($locationId);

        $query = DB::table('account_transactions as HAT')
            ->join('accounts as HA', 'HA.id', '=', 'HAT.account_id')
            ->leftJoin('transaction_payments as HTP', 'HTP.id', '=', 'HAT.transaction_payment_id')
            ->leftJoin('transactions as HTX', 'HTX.id', '=', 'HAT.transaction_id')
            ->where('HA.business_id', $businessId)
            ->whereIn('HA.id', $accountIds)
            ->whereNull('HAT.deleted_at')
            ->whereNull('HA.deleted_at')
            ->where(function ($accountEnabled) {
                $accountEnabled->where('HA.disabled', 0)->orWhereNull('HA.disabled');
            })
            ->where(function ($postingBusiness) use ($businessId) {
                $postingBusiness->where('HAT.business_id', $businessId)
                    ->orWhereNull('HAT.business_id')
                    ->orWhere('HAT.business_id', 0);
            })
            ->where(function ($paymentRow) {
                $paymentRow->whereNull('HAT.transaction_payment_id')
                    ->orWhereNull('HTP.deleted_at');
            });

        static $dashboardHasJournals = null;
        static $dashboardHasDirectLocation = null;

        if ($dashboardHasJournals === null) {
            $dashboardHasJournals = Schema::hasTable('journals')
                && Schema::hasColumn('journals', 'location_id');
        }
        if ($dashboardHasDirectLocation === null) {
            $dashboardHasDirectLocation = Schema::hasColumn('account_transactions', 'location_id');
        }

        $hasJournals = $dashboardHasJournals;
        if ($hasJournals) {
            $query->leftJoin('journals as HJ', 'HJ.id', '=', 'HAT.journal_entry');
        }

        if (! empty($startDate)) {
            $query->whereDate('HAT.operation_date', '>=', $startDate);
        }
        if (! empty($endDate)) {
            $query->whereDate('HAT.operation_date', '<=', $endDate);
        }

        if (! empty($transactionTypes)) {
            $query->whereIn('HTX.type', $transactionTypes);
        }

        if (! empty($locationId)) {
            $hasDirectLocation = $dashboardHasDirectLocation;

            $query->where(function ($locationScope) use ($locationId, $hasDirectLocation, $hasJournals) {
                if ($hasDirectLocation) {
                    $locationScope->where('HAT.location_id', $locationId)
                        ->orWhere(function ($legacyPosting) use ($locationId, $hasJournals) {
                            $legacyPosting->whereNull('HAT.location_id')
                                ->where(function ($fallbackLocation) use ($locationId, $hasJournals) {
                                    $fallbackLocation->where('HTX.location_id', $locationId);

                                    if ($hasJournals) {
                                        $fallbackLocation->orWhere(function ($journalLocation) use ($locationId) {
                                            $journalLocation->whereNull('HAT.transaction_id')
                                                ->where('HJ.location_id', $locationId);
                                        });
                                    }

                                    $fallbackLocation->orWhere(function ($directAccount) use ($locationId) {
                                        $directAccount->whereNull('HAT.transaction_id')
                                            ->whereNull('HAT.journal_entry')
                                            ->where(function ($accountLocation) use ($locationId) {
                                                $accountLocation->where('HA.location_id', $locationId)
                                                    ->orWhere('HA.location_id', 'all')
                                                    ->orWhereNull('HA.location_id')
                                                    ->orWhere('HA.location_id', 0);
                                            });
                                    });
                                });
                        });
                } else {
                    $locationScope->where('HTX.location_id', $locationId);

                    if ($hasJournals) {
                        $locationScope->orWhere(function ($journalLocation) use ($locationId) {
                            $journalLocation->whereNull('HAT.transaction_id')
                                ->where('HJ.location_id', $locationId);
                        });
                    }

                    $locationScope->orWhere(function ($directAccount) use ($locationId) {
                        $directAccount->whereNull('HAT.transaction_id')
                            ->whereNull('HAT.journal_entry')
                            ->where(function ($accountLocation) use ($locationId) {
                                $accountLocation->where('HA.location_id', $locationId)
                                    ->orWhere('HA.location_id', 'all')
                                    ->orWhereNull('HA.location_id')
                                    ->orWhere('HA.location_id', 0);
                            });
                    });
                }
            });
        }

        $row = $query->selectRaw(
            "COALESCE(SUM(CASE WHEN HAT.type = 'debit' THEN HAT.amount ELSE 0 END), 0) AS debit_total"
        )->selectRaw(
            "COALESCE(SUM(CASE WHEN HAT.type = 'credit' THEN HAT.amount ELSE 0 END), 0) AS credit_total"
        )->first();

        return [
            'debit' => (float) ($row->debit_total ?? 0),
            'credit' => (float) ($row->credit_total ?? 0),
        ];
    }

    private function dashboardSalesAccountIds(int $businessId): array
    {
        static $cache = [];
        if (array_key_exists($businessId, $cache)) {
            return $cache[$businessId];
        }

        $groupIds = DB::table('account_groups')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(TRIM(name)) = ?', ['sales income group'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($groupIds) {
                if ($groupIds !== []) {
                    $query->whereIn('asset_type', $groupIds);
                }

                $method = $groupIds === [] ? 'whereRaw' : 'orWhereRaw';
                $query->{$method}('LOWER(TRIM(name)) LIKE ?', ['sales income%']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $cache[$businessId] = $this->expandDashboardChildAccounts($businessId, $ids);
    }

    private function dashboardReceivableAccountIds(int $businessId): array
    {
        static $cache = [];
        if (array_key_exists($businessId, $cache)) {
            return $cache[$businessId];
        }

        $ids = DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['accounts receivable'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $cache[$businessId] = $this->expandDashboardChildAccounts($businessId, $ids);
    }

    private function dashboardExpenseAccountIds(int $businessId): array
    {
        static $cache = [];
        if (array_key_exists($businessId, $cache)) {
            return $cache[$businessId];
        }

        $expenseTypeIds = $this->dashboardAccountTypeIds($businessId, 'Expenses');

        $query = DB::table('accounts as HEA')
            ->leftJoin('account_groups as HEG', 'HEG.id', '=', 'HEA.asset_type')
            ->where('HEA.business_id', $businessId)
            ->whereNull('HEA.deleted_at')
            ->where(function ($enabled) {
                $enabled->where('HEA.disabled', 0)->orWhereNull('HEA.disabled');
            })
            ->where(function ($notCogs) {
                $notCogs->whereNull('HEG.name')
                    ->orWhereRaw('LOWER(TRIM(HEG.name)) <> ?', ['cogs account group']);
            })
            ->whereRaw('LOWER(TRIM(HEA.name)) NOT LIKE ?', ['cost of goods sold%'])
            ->whereRaw('LOWER(TRIM(HEA.name)) NOT LIKE ?', ['cogs%']);

        if ($expenseTypeIds !== []) {
            $query->whereIn('HEA.account_type_id', $expenseTypeIds);
        } else {
            // Legacy fallback for tenants whose account-type masters are not
            // complete yet. The account itself is still a Finance account.
            $query->whereRaw('LOWER(HEA.name) LIKE ?', ['%expense%']);
        }

        return $cache[$businessId] = $query->pluck('HEA.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function dashboardStockAccountIds(int $businessId, array $groupNames): array
    {
        $normalizedGroups = array_values(array_unique(array_map(
            fn ($name) => strtolower(trim((string) $name)),
            $groupNames
        )));

        static $cache = [];
        $cacheKey = $businessId . ':' . implode('|', $normalizedGroups);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $groupIds = DB::table('account_groups')
            ->where('business_id', $businessId)
            ->whereIn(DB::raw('LOWER(TRIM(name))'), $normalizedGroups)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($enabled) {
                $enabled->where('disabled', 0)->orWhereNull('disabled');
            });

        $query->where(function ($scope) use ($groupIds, $normalizedGroups) {
            $hasCondition = false;
            if ($groupIds !== []) {
                $scope->whereIn('asset_type', $groupIds);
                $hasCondition = true;
            }

            foreach ($normalizedGroups as $groupName) {
                if ($hasCondition) {
                    $scope->orWhereRaw('LOWER(TRIM(name)) = ?', [$groupName]);
                } else {
                    $scope->whereRaw('LOWER(TRIM(name)) = ?', [$groupName]);
                    $hasCondition = true;
                }
            }
        });

        $ids = $query->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $cache[$cacheKey] = $this->expandDashboardChildAccounts($businessId, $ids);
    }

    private function dashboardAccountTypeIds(int $businessId, string $typeName): array
    {
        static $cache = [];
        $cacheKey = $businessId . ':' . strtolower(trim($typeName));
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $ids = DB::table('account_types')
            ->where(function ($businessScope) use ($businessId) {
                $businessScope->where('business_id', $businessId)
                    ->orWhereNull('business_id');
            })
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($typeName))])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $all = array_values(array_unique($ids));
        $frontier = $all;
        while ($frontier !== []) {
            $children = DB::table('account_types')
                ->where(function ($businessScope) use ($businessId) {
                    $businessScope->where('business_id', $businessId)
                        ->orWhereNull('business_id');
                })
                ->whereIn('parent_account_type_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff(array_unique($children), $all));
            if ($children === []) {
                break;
            }

            $all = array_values(array_unique(array_merge($all, $children)));
            $frontier = $children;
        }

        return $cache[$cacheKey] = $all;
    }

    private function expandDashboardChildAccounts(int $businessId, array $accountIds): array
    {
        $all = array_values(array_unique(array_filter(array_map('intval', $accountIds))));
        $frontier = $all;

        while ($frontier !== []) {
            $children = DB::table('accounts')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereIn('parent_account_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff(array_unique($children), $all));
            if ($children === []) {
                break;
            }

            $all = array_values(array_unique(array_merge($all, $children)));
            $frontier = $children;
        }

        return $all;
    }

    private function normalizeDashboardLocationId($locationId): ?int
    {
        if ($locationId === null || $locationId === '' || $locationId === 'all') {
            return null;
        }

        $locationId = (int) $locationId;

        return $locationId > 0 ? $locationId : null;
    }

    public function getTotals()
    {
        if (request()->ajax()) {
            $start         = request()->start;
            $end           = request()->end;
            $business_id   = request()->session()->get('user.business_id');
            return $output = Cache::remember("home_output_{$start}_{$end}_{$business_id}", 300, function () use ($start, $end, $business_id) {
                $purchase_details  = $this->transactionUtil->getPurchaseTotals($business_id, $start, $end);
                $sell_details      = $this->transactionUtil->getSellTotals($business_id, $start, $end);
                $transaction_types = [
                    'purchase_return',
                    'stock_adjustment',
                    'sell_return',
                ];
                $transaction_totals = $this->transactionUtil->getTransactionTotals(
                    $business_id,
                    $transaction_types,
                    $start,
                    $end
                );
                $total_purchase_inc_tax        = ! empty($purchase_details['total_purchase_inc_tax']) ? $purchase_details['total_purchase_inc_tax'] : 0;
                $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];
                $total_adjustment              = $transaction_totals['total_adjustment'];
                $total_purchase                = $total_purchase_inc_tax - $total_purchase_return_inc_tax - $total_adjustment;
                $output                        = $purchase_details;
                $output['total_purchase']      = $total_purchase;
                $output['total_purchase_due']  = ! empty($allpurchase_details['total_purchase_due']) ? $allpurchase_details['total_purchase_due'] : 0;
                $total_sell_inc_tax            = ! empty($sell_details['total_sell_inc_tax']) ? $sell_details['total_sell_inc_tax'] : 0;
                $total_sell_return_inc_tax     = ! empty($transaction_totals['total_sell_return_inc_tax']) ? $transaction_totals['total_sell_return_inc_tax'] : 0;
                $output['total_sell']          = $total_sell_inc_tax - $total_sell_return_inc_tax;
                $output['total_sell_due']      = ! empty($allsell_details['total_sell_due']) ? $allsell_details['total_sell_due'] : 0;
                $output['invoice_due']         = $sell_details['invoice_due'];
                return $output;
            });
        }
    }

    /**
     * Retrieves sell products whose available quntity is less than alert quntity.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProductStockAlert()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $query       = VariationLocationDetails::join(
                'product_variations as pv',
                'variation_location_details.product_variation_id',
                '=',
                'pv.id'
            )
                ->join(
                    'variations as v',
                    'variation_location_details.variation_id',
                    '=',
                    'v.id'
                )
                ->join(
                    'products as p',
                    'variation_location_details.product_id',
                    '=',
                    'p.id'
                )
                ->leftjoin(
                    'business_locations as l',
                    'variation_location_details.location_id',
                    '=',
                    'l.id'
                )
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('p.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->where('p.is_inactive', 0)
                ->whereRaw('variation_location_details.qty_available <= p.alert_quantity');
            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('variation_location_details.location_id', $permitted_locations);
            }
            $products = $query->select(
                'p.name as product',
                'p.type',
                'pv.name as product_variation',
                'v.name as variation',
                'l.name as location',
                'variation_location_details.qty_available as stock',
                'u.short_name as unit'
            )
                ->groupBy('variation_location_details.id')
                ->orderBy('stock', 'asc');
            return Datatables::of($products)
                ->editColumn('product', function ($row) {
                    if ($row->type == 'single') {
                        return $row->product;
                    } else {
                        return $row->product . ' - ' . $row->product_variation . ' - ' . $row->variation;
                    }
                })
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;
                    return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $stock . '</span> ' . $row->unit;
                })
                ->removeColumn('unit')
                ->removeColumn('type')
                ->removeColumn('product_variation')
                ->removeColumn('variation')
                ->rawColumns([2])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchasePaymentDues()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today       = \Carbon::now()->format("Y-m-d H:i:s");
            $query       = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                ->leftJoin(
                    'transaction_payments as tp',
                    'transactions.id',
                    '=',
                    'tp.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.payment_status', '!=', 'paid')
                ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(c.pay_term_type = 'days', c.pay_term_number, 30 * c.pay_term_number) DAY), '$today') <= 7");
            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            $dues = $query->select(
                'transactions.id as id',
                'c.name as supplier',
                'ref_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                ->groupBy('transactions.id');
            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = ! empty($row->total_paid) ? $row->total_paid : 0;
                    $due        = $row->final_total - $total_paid;
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        $due . '</span>';
                })
                ->editColumn('ref_no', function ($row) {
                    if (auth()->user()->can('purchase.view')) {
                        return '<a href="#" data-href="' . action('PurchaseController@show', [$row->id]) . '"
                                    class="btn-modal" data-container=".view_modal">' . $row->ref_no . '</a>';
                    }
                    return $row->ref_no;
                })
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([1, 2])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesPaymentDues()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today       = \Carbon::now()->format("Y-m-d H:i:s");
            $query       = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                ->leftJoin(
                    'transaction_payments as tp',
                    'transactions.id',
                    '=',
                    'tp.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.payment_status', '!=', 'paid')
                ->whereNotNull('transactions.pay_term_number')
                ->whereNotNull('transactions.pay_term_type')
                ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");
            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            $dues = $query->select(
                'transactions.id as id',
                'c.name as customer',
                'transactions.invoice_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                ->groupBy('transactions.id');
            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = ! empty($row->total_paid) ? $row->total_paid : 0;
                    $due        = $row->final_total - $total_paid;
                    return '<span class="display_currency" data-currency_symbol="true">' .
                        $due . '</span>';
                })
                ->editColumn('invoice_no', function ($row) {
                    if (auth()->user()->can('sell.view')) {
                        return '<a href="#" data-href="' . action('SellController@show', [$row->id]) . '"
                                    class="btn-modal" data-container=".view_modal">' . $row->invoice_no . '</a>';
                    }
                    return $row->invoice_no;
                })
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([1, 2])
                ->make(false);
        }
    }

    public function loadMoreNotifications()
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'DESC')->paginate(10);
        if (request()->input('page') == 1) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        $notifications_data = [];
        foreach ($notifications as $notification) {
            $data = $notification->data;
            if (in_array($notification->type, [\App\Notifications\RecurringInvoiceNotification::class])) {
                $msg        = '';
                $icon_class = '';
                $link       = '';
                if (
                    $notification->type ==
                    \App\Notifications\RecurringInvoiceNotification::class
                ) {
                    $msg = ! empty($data['invoice_status']) && $data['invoice_status'] == 'draft' ?
                        __(
                            'lang_v1.recurring_invoice_error_message',
                            ['product_name' => $data['out_of_stock_product'], 'subscription_no' => ! empty($data['subscription_no']) ? $data['subscription_no'] : '']
                        ) :
                        __(
                            'lang_v1.recurring_invoice_message',
                            ['invoice_no' => ! empty($data['invoice_no']) ? $data['invoice_no'] : '', 'subscription_no' => ! empty($data['subscription_no']) ? $data['subscription_no'] : '']
                        );
                    $icon_class = ! empty($data['invoice_status']) && $data['invoice_status'] == 'draft' ? "fa fa-exclamation-triangle text-warning" : "fa fa-recycle text-green";
                    $link       = action('SellPosController@listSubscriptions');
                }
                $notifications_data[] = [
                    'msg'        => $msg,
                    'icon_class' => $icon_class,
                    'link'       => $link,
                    'read_at'    => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            } else {
                $module_notification_data = $this->moduleUtil->getModuleData('parse_notification', $notification);
                if (! empty($module_notification_data)) {
                    foreach ($module_notification_data as $module_data) {
                        if (! empty($module_data)) {
                            $notifications_data[] = $module_data;
                        }
                    }
                }
            }
        }
        if (request()->input('page') == 1 && app('modules')->has('Superadmin')) {
            $business_id = request()->session()->get('user.business_id');
            if ($business_id) {
                $active_subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
                $has_expired = \Modules\Superadmin\Entities\Subscription::where('business_id', $business_id)
                    ->approved()
                    ->whereDate('end_date', '<', \Carbon\Carbon::today()->toDateString())
                    ->exists();
                if (empty($active_subscription) && $has_expired) {
                    array_unshift($notifications_data, [
                        'msg'        => __('superadmin::lang.subscription_expired_notification'),
                        'icon_class' => 'fa fa-exclamation-circle text-red',
                        'link'       => action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index'),
                        'read_at'    => null,
                        'created_at' => '',
                    ]);
                }
            }
        }

        return view('layouts.partials.notification_list', compact('notifications_data'));
    }

    private function __chartOptions($title)
    {
        return [
            'yAxis'  => [
                'title' => [
                    'text' => $title,
                ],
            ],
            'legend' => [
                'align'         => 'right',
                'verticalAlign' => 'top',
                'floating'      => true,
                'layout'        => 'vertical',
            ],
        ];
    }

    public function loginPayroll(Request $request)
    {
        $connection = DB::connection('mysql2');
        $users      = $connection->select("SELECT id,business_id FROM users WHERE email='" . \Auth::user()->email . "'");
        if (! empty($users)) {
            $user_id = $users[0]->id;
        } else {
            $user_id = $connection->table('users')->insertGetId([
                'email'          => \Auth::user()->email,
                'first_name'     => \Auth::user()->first_name,
                'last_name'      => \Auth::user()->last_name,
                'password'       => \Auth::user()->password,
                'status_id'      => 1,
                'is_in_employee' => 1,
                'business_id'    => \Auth::user()->business_id,
                'created_at'     => date("Y-m-d H:i:s"),
                'updated_at'     => date("Y-m-d H:i:s"),
            ]);
            if ($user_id) {
                if (\Auth::user()->getRoleNameAttribute() == "Admin") {
                    $userRole = $connection->table('role_user')->insert([
                        'user_id' => $user_id,
                        'role_id' => 1,
                    ]);
                } else {
                    $userRole = $connection->table('role_user')->insert([
                        'user_id' => $user_id,
                        'role_id' => 4,
                    ]);
                }
            }
        }
        return response()->json(['login_url' => env('PAYROLL_LOGIN') . "/" . base64_encode($user_id)]);
    }
    public function getCategories()
    {
        $business_id = request()->session()->get('user.business_id');
        $categories = \App\Category::select('id', 'name')
            ->where('parent_id', 0)
            ->where('business_id', $business_id)
            ->distinct()
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function getLocations()
    {
        $business_id = request()->session()->get('user.business_id');
        $locations   = BusinessLocation::forDropdown($business_id);
        return response()->json($locations);
    }

    /**
     * Get products based on category
     */
    // public function getProducts(Request $request)
    // {
    //     $query = \App\Product::select('id', 'name', 'sku as code')
    //         ->where('type', 'single');
    //     if ($request->filled('category_id')) {
    //         $query->where('category_id', $request->category_id);
    //     }

    //     if ($request->filled('search')) {
    //         $query->where(function ($q) use ($request) {
    //             $q->where('name', 'like', '%' . $request->search . '%')
    //                 ->orWhere('sku', 'like', '%' . $request->search . '%');
    //         });
    //     }
    //     $products = $query->limit(50)->get();
    //     return response()->json($products);
    // }

    public function getProducts(Request $request)
    {
        $business_id = session()->get('user.business_id');
        $query = \App\Product::select(
            'products.id',
            'products.name',
            'products.sku as code'
        )
            ->where('products.type', 'single');

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        // Filter by location WITHOUT JOIN (important)
        if ($request->filled('location_id')) {
            $query->whereExists(function ($sub) use ($request) {
                $sub->selectRaw(1)
                    ->from('variation_location_details as vld')
                    ->whereColumn('vld.product_id', 'products.id')
                    ->where('vld.location_id', $request->location_id);
            });
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('products.name', 'like', '%' . $request->search . '%')
                    ->orWhere('products.sku', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->limit(50)->get());
    }

    /**
     * Get reorder report data
     */
    public function getReorderReportData(Request $request)
    {
        $query = $this->buildReorderReportQuery($request)
            ->whereRaw('COALESCE(stock.current_qty, 0) <= COALESCE(products.alert_quantity, 0)')
            ->orderBy('current_qty', 'asc')
            ->orderBy('products.name', 'asc');

        return $this->reorderReportResponse($query);
    }

    /**
     * Get old reorder list (products that were restocked above alert level)
     */
    public function getOldReorderList(Request $request)
    {
        $query = $this->buildReorderReportQuery($request)
            ->whereRaw('COALESCE(stock.current_qty, 0) > COALESCE(products.alert_quantity, 0)')
            ->orderBy('products.name', 'asc');

        return $this->reorderReportResponse($query);
    }

    private function buildReorderReportQuery(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $stockSubquery = VariationLocationDetails::query()
            ->select('product_id', FacadesDB::raw('SUM(qty_available) as current_qty'))
            ->when($request->filled('location_id'), function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            })
            ->groupBy('product_id');

        $latestPurchaseLineSubquery = FacadesDB::table('purchase_lines as pl')
            ->join('transactions as t', function ($join) {
                $join->on('t.id', '=', 'pl.transaction_id')
                    ->where('t.type', 'purchase')
                    ->where('t.status', 'received');
            })
            ->select('pl.product_id', FacadesDB::raw('MAX(pl.id) as latest_purchase_line_id'))
            ->groupBy('pl.product_id');

        $latestPurchaseSubquery = FacadesDB::table('purchase_lines as pl')
            ->joinSub($latestPurchaseLineSubquery, 'latest_purchase_lines', function ($join) {
                $join->on('pl.id', '=', 'latest_purchase_lines.latest_purchase_line_id');
            })
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->leftJoin('contacts as c', 't.contact_id', '=', 'c.id')
            ->select([
                'pl.product_id',
                'pl.purchase_price_inc_tax as last_purchase_price',
                FacadesDB::raw('COALESCE(c.name, "Unknown Supplier") as last_supplier'),
            ]);

        $query = Product::query()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('categories as sub_cat', 'products.sub_category_id', '=', 'sub_cat.id')
            ->leftJoinSub($stockSubquery, 'stock', function ($join) {
                $join->on('products.id', '=', 'stock.product_id');
            })
            ->leftJoinSub($latestPurchaseSubquery, 'latest_purchase', function ($join) {
                $join->on('products.id', '=', 'latest_purchase.product_id');
            })
            ->where('products.business_id', $business_id)
            ->where('products.type', 'single')
            ->whereNotNull('products.alert_quantity')
            ->where('products.alert_quantity', '>', 0);

        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        if ($request->filled('product_id')) {
            $query->where('products.id', $request->product_id);
        }

        return $query->select([
            'products.id',
            'products.name',
            'products.sku as code',
            'categories.name as category',
            'sub_cat.name as sub_category',
            'products.alert_quantity',
            FacadesDB::raw('COALESCE(stock.current_qty, 0) as current_qty'),
            FacadesDB::raw('COALESCE(latest_purchase.last_purchase_price, 0) as last_purchase_price'),
            FacadesDB::raw('COALESCE(latest_purchase.last_supplier, "Unknown Supplier") as last_supplier'),
        ]);
    }

    private function reorderReportResponse($query)
    {
        $products = $query->paginate(50);

        $data = $products->map(function ($product) {
            return [
                'id'                  => $product->id,
                'code'                => $product->code,
                'name'                => $product->name,
                'category'            => $product->category,
                'sub_category'        => $product->sub_category ?? 'N/A',
                'current_qty'         => number_format((float) $product->current_qty, 2),
                'alert_qty'           => number_format((float) $product->alert_quantity, 2),
                'last_purchase_price' => number_format((float) $product->last_purchase_price, 2),
                'last_supplier'       => $product->last_supplier ?: 'Unknown Supplier',
            ];
        });

        return response()->json([
            'data'       => $data,
            'pagination' => $products->links()->render(),
            'total'      => $products->total(),
        ]);
    }

    /**
     * Export reorder report
     */
    public function exportReorderReport(Request $request)
    {
        $format = $request->get('format', 'csv');
        $query  = \App\Product::leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($request) {
                $join->on('products.id', '=', 'vld.product_id');
                if ($request->filled('location_id')) {
                    $join->where('vld.location_id', $request->location_id);
                }
            })
            ->leftJoin('variations as pur', function ($join) {
                $join->on('products.id', '=', 'pur.product_id')
                    ->whereRaw('pur.id = (SELECT MAX(id) FROM variations WHERE product_id = products.id)');
            })
            ->leftJoin('contacts as suppliers', 'pur.product_id', '=', 'suppliers.id')
            ->leftJoin('merged_sub_categories as sub_categories', 'sub_categories.business_id', '=', 'products.business_id')
            ->whereRaw('COALESCE(vld.qty_available, 0) <= COALESCE(products.alert_quantity, 0)')
            ->where('products.alert_quantity', '>', 0);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('products.category_id', $request->category_id);
        }

        if ($request->filled('product_id')) {
            $query->where('products.id', $request->product_id);
        }

        $products = $query->select([
            'products.name',
            'products.sku as code',
            'categories.name as category',
            'sub_categories.sub_categories as sub_category',
            'products.alert_quantity',
            \DB::raw('COALESCE(vld.qty_available, 0) as current_qty'),
            \DB::raw('COALESCE(pur.default_purchase_price, 0) as last_purchase_price'),
            'suppliers.name as last_supplier',
        ])->get();

        if ($format == 'csv') {
            $filename = 'products-reorder-level-' . date('Y-m-d') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
            $callback = function () use ($products) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'Product Code',
                    'Product Name',
                    'Category',
                    'Sub Category',
                    'Last Purchase Price',
                    'Last Supplier',
                    'Current Qty',
                    'Alert Qty',
                ]);
                // CSV data
                foreach ($products as $product) {
                    fputcsv($file, [
                        $product->code,
                        $product->name,
                        $product->category ?? '',
                        $product->sub_category ?? '',
                        number_format($product->last_purchase_price, 2),
                        $product->last_supplier ?? '',
                        number_format($product->current_qty, 2),
                        number_format($product->alert_quantity, 2),
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        } elseif ($format == 'excel') {
            $filename = 'products-reorder-level-' . date('Y-m-d') . '.xlsx';

            $excel = Excel::create($filename, function ($excel) use ($products) {
                $excel->sheet('Products', function ($sheet) use ($products) {
                    $sheet->row(1, [
                        'Product Code',
                        'Product Name',
                        'Category',
                        'Sub Category',
                        'Last Purchase Price',
                        'Last Supplier',
                        'Current Qty',
                        'Alert Qty',
                    ]);

                    foreach ($products as $index => $product) {
                        $sheet->row($index + 2, [
                            $product->code,
                            $product->name,
                            $product->category ?? '',
                            $product->sub_category ?? '',
                            number_format($product->last_purchase_price, 2),
                            $product->last_supplier ?? '',
                            number_format($product->current_qty, 2),
                            number_format($product->alert_quantity, 2),
                        ]);
                    }
                });
            });

            return $excel->download('xlsx');
        } elseif ($format == 'pdf') {
            $html = view('home.exports.products', ['products' => $products])->render();

            return app(GlobalPdfService::class)->download(
                $html,
                'products-reorder-level-' . date('Y-m-d') . '.pdf',
                ['format' => 'A4-L'],
                [
                    'page_title' => 'Products Reorder Level',
                    'date_range' => date('d/m/Y'),
                ]
            );
        }
        return response()->json(['message' => 'Format not supported yet', "format" => $format], 400);
    }
}
