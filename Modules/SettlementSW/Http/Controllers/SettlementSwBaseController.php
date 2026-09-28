<?php
namespace Modules\SettlementSW\Http\Controllers;

use Modules\SettlementSW\Services\SettlementSwTables;
use Modules\SettlementSW\Services\SettlementSwLegacyMap;
use Modules\SettlementSW\Services\SettlementSwSubscription;
use Modules\SettlementSW\Services\SettlementSwPermission;
use Modules\SettlementSW\Services\SettlementSwVatAdapter;
use Modules\SettlementSW\Services\SettlementSwSchema;
use Modules\SettlementSW\Services\SettlementSwLog;

use Modules\SettlementSW\Entities\SettlementSwAccount as Account;
use Modules\SettlementSW\Entities\SettlementSwAccountTransaction as AccountTransaction;
use Modules\SettlementSW\Entities\SettlementSwAccountType as AccountType;
use Modules\SettlementSW\Entities\SettlementSwAccountGroup as AccountGroup;
use Modules\SettlementSW\Entities\SettlementSwBusiness as Business;
use Modules\SettlementSW\Entities\SettlementSwBusinessLocation as BusinessLocation;
use Modules\SettlementSW\Entities\SettlementSwCategory as Category;
use Modules\SettlementSW\Entities\SettlementSwContact as Contact;
use Modules\SettlementSW\Entities\SettlementSwContactLedger as ContactLedger;
use Modules\SettlementSW\Entities\SettlementSwExpenseCategory as ExpenseCategory;
use Modules\SettlementSW\Entities\SettlementSwProduct as Product;
use Modules\SettlementSW\Entities\SettlementSwStore as Store;
use Modules\SettlementSW\Entities\SettlementSwTransaction as Transaction;
use Modules\SettlementSW\Entities\SettlementSwTransactionPayment as TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Modules\SettlementSW\Entities\SettlementSwVariation as Variation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\SettlementSW\Entities\SettlementSwWorkShift as WorkShift;
use Modules\SettlementSW\Entities\CustomerPayment;
use Modules\SettlementSW\Entities\DailyCard;
use Modules\SettlementSW\Entities\DailyCollection;
use Modules\SettlementSW\Entities\DailyVoucher;
use Modules\SettlementSW\Entities\DayEnd;
use Modules\SettlementSW\Entities\FuelTank;
use Modules\SettlementSW\Entities\MeterSale;
use Modules\SettlementSW\Entities\OtherIncome;
use Modules\SettlementSW\Entities\OtherSale;
use Modules\SettlementSW\Entities\SettlementSwDailyShift;
use Modules\SettlementSW\Entities\Pump;
use Modules\SettlementSW\Entities\PumperDayEntry;
use Modules\SettlementSW\Entities\PumpOperator;
use Modules\SettlementSW\Entities\PumpOperatorAssignment;
use Modules\SettlementSW\Entities\PumpOperatorCommission;
use Modules\SettlementSW\Entities\PumpOperatorOtherSale;
use Modules\SettlementSW\Entities\Settlement;
use Modules\SettlementSW\Entities\SettlementCardPayment;
use Modules\SettlementSW\Entities\SettlementCashDeposit;
use Modules\SettlementSW\Entities\SettlementCashPayment;
use Modules\SettlementSW\Entities\SettlementChequePayment;
use Modules\SettlementSW\Entities\SettlementCreditSalePayment;
use Modules\SettlementSW\Entities\SettlementExpensePayment;
use Modules\SettlementSW\Services\SettlementPaymentReconciler;
use Yajra\DataTables\DataTables;

class SettlementSwBaseController extends Controller
{

    /**
     * Settlement SW tenant/business resolver. Keeps this module independent from
     * external controllers and avoids failures when either session key is missing.
     */
    private function settlementSwBusinessId()
    {
        return request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(auth()->user())->business_id;
    }


    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $notificationUtil;

    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)
    {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

        $this->notificationUtil = $notificationUtil;

    }

    public function index()
    {

        $business_id = $this->settlementSwBusinessId();

        // Manage Sidebar/package builds have used three keys for this module.
        // Accept the module's current key and the two historical keys so a
        // legitimately enabled tenant is not rejected with a 403.
        $subscriptionKeys = array_unique(array_filter([
            config('settlementsw.subscription_permission_key', 'enable_settlement_sw_module'),
            'settlement_sw_module',
            config('settlementsw.legacy_subscription_permission_key', 'enable_petro_module'),
        ]));
        $hasModuleAccess = collect($subscriptionKeys)->contains(function ($key) use ($business_id) {
            return $this->moduleUtil->hasThePermissionInSubscription($business_id, $key);
        });

        if (! $hasModuleAccess) {

            abort(403, 'Unauthorized Access');

        }

        if (request()->ajax()) {

            $business_id = $this->settlementSwBusinessId();

            if (request()->ajax()) {

                $query = Settlement::leftJoin('business_locations', 'settlements.location_id', '=', 'business_locations.id')

                    ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')

                    ->leftJoin('pump_operator_assignments', function($join) {
                        $join->on('settlements.id', '=', 'pump_operator_assignments.settlement_id');
                    })

                    ->leftJoin('daily_collections', function($join) {
                        $join->on('settlements.id', '=', 'daily_collections.settlement_id')
                            ->whereNotNull('daily_collections.settlement_id');
                    })

                    ->leftJoin(config('settlementsw.vat.transaction_table', 'transactions') . ' as sw_transactions', function ($join) use ($business_id) {
                        $join->on('sw_transactions.invoice_no', '=', 'settlements.settlement_no')
                            ->where('sw_transactions.business_id', '=', $business_id)
                            ->where('sw_transactions.type', '=', config('settlementsw.vat.sale_transaction_type', 'sell'));
                    })

                    ->leftJoin('users as sw_created_by', 'sw_created_by.id', '=', 'sw_transactions.created_by')

                    ->where('settlements.business_id', $business_id)

                    ->where('settlements.settlement_no', 'LIKE', 'SET-SW%')

                    ->select([

                        'pump_operators.name as pump_operator_name',

                        'business_locations.name as location_name',

                        'settlements.id',
                        DB::raw('MAX(settlements.settlement_no) as settlement_no'),
                        DB::raw('MAX(settlements.business_id) as business_id'),
                        DB::raw('MAX(settlements.transaction_date) as transaction_date'),
                        DB::raw('MAX(settlements.finish_date) as finish_date'),
                        DB::raw('MAX(settlements.location_id) as location_id'),
                        DB::raw('MAX(settlements.pump_operator_id) as pump_operator_id'),
                        DB::raw('MAX(settlements.bulk_store_product) as bulk_store_product'),
                        DB::raw('MAX(settlements.work_shift) as work_shift'),
                        DB::raw('MAX(settlements.note) as note'),
                        DB::raw('MAX(settlements.cash_denomination) as cash_denomination'),
                        DB::raw('MAX(settlements.total_amount) as total_amount'),
                        DB::raw('MAX(settlements.status) as status'),
                        DB::raw('MAX(settlements.is_edit) as is_edit'),
                        DB::raw('MAX(settlements.created_at) as created_at'),
                        DB::raw('MAX(settlements.updated_at) as updated_at'),
                        DB::raw('MAX(sw_transactions.id) as vat_transaction_id'),
                        DB::raw('MAX(sw_created_by.username) as created_by_name'),

                        DB::raw('COALESCE(
                            NULLIF(GROUP_CONCAT(DISTINCT CASE WHEN daily_collections.shift_number IS NOT NULL AND daily_collections.shift_number != "" AND daily_collections.shift_number != "0" THEN daily_collections.shift_number END ORDER BY daily_collections.shift_number SEPARATOR ","), ""),
                            NULLIF(GROUP_CONCAT(DISTINCT CASE WHEN pump_operator_assignments.shift_number IS NOT NULL AND pump_operator_assignments.shift_number != 0 THEN pump_operator_assignments.shift_number END ORDER BY CAST(pump_operator_assignments.shift_number AS UNSIGNED) SEPARATOR ","), "")
                        ) as shift_number'),

                    ])

                    ->with([
                        'meter_sales.pump',
                        'other_sales', 
                        'other_incomes',
                        'customer_payments',
                        'cash_payments',
                        'card_payments',
                        'credit_sale_payments',
                        'loan_payments',
                        'cheque_payments',
                        'expense_payments',
                        'shortage_payments',
                        'excess_payments',
                        'customer_loans',
                        'cash_deposits',
                        'drawings_payments'
                    ]);
                \App\Utils\PetroPdIsolationUtil::excludeSettlements($query);

                $query->groupBy('settlements.id');

                $query->orderBy('settlements.id', 'desc');

                $first = null;

                $first = Settlement::where('business_id', $business_id)->where('status', 0)->orderBy('id', 'desc')->first();

                $delete_settlement = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'delete_settlement');

                $edit_settlement = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_settlement');

                $edit_settlement_no_change = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_settlement_no_change');

                $workShiftNames = WorkShift::query()->pluck('shift_name', 'id');

                $settlements = Datatables::of($query)

                    ->addColumn(

                        'action',

                        function ($row) use ($first, $delete_settlement, $edit_settlement, $edit_settlement_no_change, $business_id) {

                            $html = '';

                            if ($row->status == 1) {

                                if (Str::startsWith($row->settlement_no, 'SET-SW')) {

                                    $html .= '<a class="btn  btn-danger btn-sm" href="' . route('settlement-sw.create') . '">' . __("settlementsw::lang.finish_settlement") . '</a>';

                                } else {

                                    $html .= '<a class="btn  btn-danger btn-sm" href="' . route('settlement-sw.create') . '">' . __("settlementsw::lang.finish_settlement") . '</a>';

                                }

                            } else if ($row->is_edit == 1) {

                                $html .= '<a class="btn  btn-warning btn-sm" href="' . route('settlement-sw.edit', [$row->id]) . '">' . __("settlementsw::lang.finish_editting") . '</a>';

                            } else {

                                $html .= '<div class="btn-group">



                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"



                                    data-toggle="dropdown" aria-expanded="false">' .

                                __("messages.actions") .

                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown



                                    </span>



                                </button>



                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                                $html .= '<li><a data-href="' . route('settlement-sw.show', [$row->id]) . '" class="btn-modal" data-container=".settlement_modal"><i class="fa fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';

                                if (app(SettlementSwPermission::class)->canUpdate() && $edit_settlement) {

                                    $html .= '<li><a href="' . route('settlement-sw.edit', [$row->id]) . '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';

                                }

                                if (app(SettlementSwPermission::class)->canUpdate() && $edit_settlement_no_change) {

                                    $html .= '<li><a href="' . route('settlement-sw.edit', [$row->id]) . '?no_change=1" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' . __("settlementsw::lang.edit_no_change") . '</a></li>';

                                }

                                $vatAdapter = app(SettlementSwVatAdapter::class);
                                $vatTransaction = ! empty($row->vat_transaction_id)
                                    ? (object) ['id' => (int) $row->vat_transaction_id]
                                    : null;
                                if ($vatAdapter->shouldShowRegenerateAction($this->moduleUtil, $this->transactionUtil, $business_id, $row, $vatTransaction)) {
                                    $vatUrl = $vatAdapter->regenerateUrl($vatTransaction->id);
                                    if (! empty($vatUrl)) {
                                        $html .= '<li><a href="#" data-href="' . $vatUrl . '" class="regenerate-vat"><i class="fa fa-pencil"></i> ' . $vatAdapter->regenerateLabel() . '</a></li>';
                                    }
                                }

                                if (! empty($first) && $first->id == $row->id && $delete_settlement && app(SettlementSwPermission::class)->canDelete()) {

                                    // commented By M Usman for hiding Delete Action

                                    $html .= '<li><a href="' . route('settlement-sw.destroy', [$row->id]) . '" class="delete_settlement_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';

                                }

                                $html .= '<li><a data-href="' . url("/settlement-sw/print/{$row->id}") . '" class="print_settlement_button"><i class="fa fa-print"></i> ' . __("settlementsw::lang.print") . '</a></li>';

                                $html .= '</ul></div>';

                            }

                            return $html;

                        }

                    )

                    ->editColumn('status', function ($row) {

                        if ($row->status == 0) {

                            return '<span class="label label-success">Completed</span>';

                        } else {

                            return '<span class="label label-danger">Pending</span>';

                        }

                    })

                    ->addColumn('pump_nos', function ($row) {
                        return $row->meter_sales
                            ->pluck('pump.pump_no')
                            ->filter()
                            ->unique()
                            ->implode(', ');
                    })

                    ->editColumn('shift', function ($row) use ($workShiftNames) {
                        return collect($row->work_shift ?? [])
                            ->map(function ($shiftId) use ($workShiftNames) {
                                return $workShiftNames->get($shiftId);
                            })
                            ->filter()
                            ->implode(',');
                    })

                    ->addColumn('created_by', function ($row) {
                        return $row->created_by_name ?: '';
                    })

                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')

                ->addColumn('total_sale_amount', function ($row) {
                    $meter_total = $row->meter_sales->sum('sub_total');
                    $other_sales_total = $row->other_sales->sum('sub_total');
                    $other_income_total = $row->other_incomes->sum('sub_total');
                    
                    $total = $meter_total + $other_sales_total + $other_income_total;
                    
                    return '<span class="total_sale_amount" data-orig-value="' . $total . '">' . number_format($total, 2) . '</span>';
                })

                // ->editColumn('total_amount', '{{@num_format($total_amount)}}')

                // ->editColumn('total_amount', function ($row) {

                //     $adjusted_total = $row->total_amount;

                //     if (! empty($other_sales_discount)) {

                //         if ($row->other_sales && $row->other_sales->count() > 0) {

                //             $other_sales_discount = $row->other_sales->sum('discount_amount');

                //             $adjusted_total -= $other_sales_discount;

                //         }

                //     }

                //     return '<span class="total_amount">' . number_format($adjusted_total, 2, '.', ',') . '</span>';

                // })

                    ->editColumn('total_amount', function ($row) use ($business_id) {
                        // Show total sale amount instead of total payment amount
                        $meter_total = $row->meter_sales->sum('sub_total');
                        $other_sales_total = $row->other_sales->sum('sub_total');
                        $other_income_total = $row->other_incomes->sum('sub_total');
                        
                        $total = $meter_total + $other_sales_total + $other_income_total;

                        return '<span class="total_amount">' . number_format($total, 2) . '</span>';
                    })

                    ->editColumn('shift_number', function ($row) {
                        return ! empty($row->shift_number) && trim((string) $row->shift_number) !== ''
                            ? $row->shift_number
                            : 'N/A';
                    })

                    ->setRowAttr([

                        'data-href' => function ($row) {

                            return route('settlement-sw.show', [$row->id]);

                        },

                    ])

                    ->removeColumn('id');

                return $settlements->rawColumns(['action', 'status', 'total_amount', 'total_sale_amount'])

                    ->make(true);

            }

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pumpOperatorQuery = PumpOperator::where('business_id', $business_id);
        \App\Utils\PetroPdIsolationUtil::excludeOperators($pumpOperatorQuery, 'pump_operators.is_petro_pd_only');
        $pump_operators = $pumpOperatorQuery->pluck('name', 'id');

        $settlement_nos = Settlement::where('business_id', $business_id)->pluck('settlement_no', 'id');

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        return view('settlementsw::index')->with(compact(

            'business_locations',

            'pump_operators',

            'settlement_nos',

            'message'

        ));

    }


    /**
     * SW_AUDIT_004: Module-local destroy endpoint used by the Settlement SW action dropdown.
     * Keeps delete routing inside Settlement SW instead of depending on external/main controllers.
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $settlement = Settlement::where('business_id', $business_id)->findOrFail($id);
            $settlement->delete();

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_successfully')
            ]);
        } catch (\Exception $e) {
            \Log::error('Settlement SW delete failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function create()
    {

        $reviewed = $this->transactionUtil->get_review(date('Y-m-d'), date('Y-m-d'));

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => "You can't add a settlement for an already reviewed date",

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        $business_id = $this->settlementSwBusinessId();

        $services = Product::where('business_id', $business_id)->forModule(app(SettlementSwLegacyMap::class)->productModuleKey())->where('enable_stock', 0)->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)->forModule(app(SettlementSwLegacyMap::class)->productModuleKey())->pluck('name', 'id');

        $business = Business::where('id', $business_id)->first();

        // Some existing tenant databases predate the standalone SW expense
        // category table.  Do not crash the whole settlement page when that
        // optional table has not yet been installed; use the tenant's current
        // expense_categories table as the compatible source.
        if (SettlementSwSchema::hasTable('settlement_sw_expense_categories')) {
            $expense_categories = ExpenseCategory::where('business_id', $business_id)
                ->pluck('name', 'id');
        } elseif (SettlementSwSchema::hasTable('expense_categories')) {
            $expenseCategoryQuery = DB::table('expense_categories');
            if (SettlementSwSchema::hasColumn('expense_categories', 'business_id')) {
                $expenseCategoryQuery->where('business_id', $business_id);
            }
            if (SettlementSwSchema::hasColumn('expense_categories', 'deleted_at')) {
                $expenseCategoryQuery->whereNull('deleted_at');
            }
            $expense_categories = $expenseCategoryQuery->orderBy('name')->pluck('name', 'id');
        } else {
            $expense_categories = collect();
        }

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $expense_accounts = [];

        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {

            if (! empty($expense_account_type_id)) {

                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');

            }

        }

        $pos_settings = json_decode($business->pos_settings, true);

        $check_qty = ! isset($pos_settings['allow_overselling']) ? true : false;

        $cash_denoms = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types($default_location, false, false, false, false, "is_sale_enabled");

        $customers = Contact::customersDropdown($business_id, false);

        $pumpOperatorQuery = PumpOperator::where('business_id', $business_id);
        \App\Utils\PetroPdIsolationUtil::excludeOperators($pumpOperatorQuery, 'pump_operators.is_petro_pd_only');
        $pump_operators = $pumpOperatorQuery->pluck('name', 'id');

        $items = [];

        $prefix = ! empty($business->ref_no_prefixes['settlement_customer_payment'])

            ? $business->ref_no_prefixes['settlement_customer_payment']

            : 'SW-CP';

        $starting_no = ! empty($business->ref_no_starting_number['settlement_customer_payment'])

            ? (int) $business->ref_no_starting_number['settlement_customer_payment']

            : 1;

        // Get the last settlement with prefix 'SW-CP'

        $last_customer_payment = CustomerPayment::where('business_id', $business_id)

            ->where('customer_payment_no', 'LIKE', $prefix . '%')

            ->whereNotNull('customer_payment_no')

            ->orderBy('id', 'DESC')

            ->first();

        if (! empty($last_customer_payment)) {

            $count = $this->extractLastInteger($last_customer_payment->customer_payment_no);

        } else {

            $count = 0;

        }

        $customer_payment_settlement_no = $prefix . (1 + $count);

        $prefix = ! empty($business->ref_no_prefixes['settlement_expense'])

            ? $business->ref_no_prefixes['settlement_expense']

            : 'SW-EXP';

        $starting_no = ! empty($business->ref_no_starting_number['settlement_expense'])

            ? (int) $business->ref_no_starting_number['settlement_expense']

            : 1;

        // Get the last settlement with prefix 'SW-EXP'

        $last_expense_payment = SettlementExpensePayment::where('business_id', $business_id)

            ->where('expense_number', 'LIKE', $prefix . '%')

            ->orderBy('id', 'DESC')

            ->first();

        if (! empty($last_expense_payment)) {

            $count = $this->extractLastInteger($last_expense_payment->expense_number);

        } else {

            $count = 0;

        }

        $expense_payment_settlement_no = $prefix . (1 + $count);

        $currency_precision = ! empty($business->currency_precision) ? $business->currency_precision : 2;

        $meeter_precision = 3;

        $active_settlement = Settlement::where('status', 1)

            ->where('business_id', $business_id)

            ->where('settlement_no', 'LIKE', 'SET-SW%')

            ->select('settlements.*')

            ->with(['meter_sales', 'other_sales', 'other_incomes', 'customer_payments'])->first();

        $temp_data = DB::table(SettlementSwTables::tempData())->where('business_id', $business_id)->select('settlement_sw_data')->first();

        // $temp_data = DB::table(SettlementSwTables::tempData())->where('business_id', $business_id)->select('settlement_sw_data')->first();

        if (! empty($temp_data)) {

            $temp_data = json_decode($temp_data->settlement_sw_data); //name by mistake it is purchase

        }

        if (! empty($active_settlement) && isset($active_settlement->id)) {

            // 🔁 Replace other_sales

            self::replaceTempDataSectionWithDb($temp_data, 'other_sales', 'other_sales', $active_settlement->id, function ($sale) {

                $prod = Product::find($sale->product_id);

                return [

                    'id'              => $sale->id,

                    'product_id'      => $sale->product_id,

                    'price'           => $sale->price,

                    'qty'             => $sale->qty,

                    'balance_stock'   => $sale->balance_stock,

                    'discount'        => $sale->discount ?? 0,

                    'discount_type'   => $sale->discount_type,

                    'discount_amount' => $sale->discount_amount,

                    'sub_total'       => $sale->sub_total,

                    'product_name'    => (Product::find($sale->product_id))->name ?? null,

                    'code'            => $prod->sku ?? null,

                ];

            });

            // 🔁 Replace meter_sales

            self::replaceTempDataSectionWithDb($temp_data, 'meter_sales', 'meter_sales', $active_settlement->id, function ($sale) {

                $prod = Product::find($sale->product_id);

                $pump = Pump::find($sale->pump_id);

                return [

                    'id'                => $sale->id,

                    'product_name'      => $prod->name ?? null,

                    'code'              => $prod->sku ?? null,

                    'product_id'        => $sale->product_id,

                    'pump_id'           => $sale->pump_id,

                    'pump_no'           => $pump->pump_no,

                    'starting_meter'    => $sale->starting_meter,

                    'closing_meter'     => $sale->closing_meter,

                    'price'             => $sale->price,

                    'qty'               => $sale->qty,

                    'discount'          => $sale->discount,

                    'discount_type'     => $sale->discount_type,

                    'discount_amount'   => $sale->discount_amount,

                    'testing_qty'       => $sale->testing_qty ?? 0,

                    'sub_total'         => $sale->sub_total,

                    'meter_reset_value' => $sale->meter_reset_value ?? 0,

                ];

            });

            // Remplace other_incomes

            self::replaceTempDataSectionWithDb($temp_data, 'other_income', 'other_incomes', $active_settlement->id, function ($income) {

                $service = Product::find($income->product_id);

                return [

                    'id'         => $income->id,

                    'reason'     => $income->reason,

                    'sub_total'  => $income->sub_total,

                    'price'      => $income->price ?? 0,

                    'product_id' => $income->product_id,

                    'service'    => $service->name,

                    'qty'        => $income->qty ?? 0,

                ];

            });

            // Remplace customer_payments

            self::replaceTempDataSectionWithDb($temp_data, 'cust_payments_list', 'customer_payments', $active_settlement->id, function ($payment) {

                $customer_name = Contact::where('id', $payment->customer_id)->value('name');

                return [

                    'id'             => $payment->id,

                    'customer_id'    => $payment->customer_id,

                    'customer_name'  => $customer_name,

                    'amount'         => $payment->amount,

                    'payment_method' => $payment->payment_method,

                ];

            });

        }

        $other_sale_final_total = 0.00;

        $pump_other_sale_final_total = 0.00;

        $combinedOtherSales = [];

        if (! empty($active_settlement) && isset($active_settlement->id)) {

            $already_pumps = MeterSale::where('settlement_no', $active_settlement->id ?? null)->pluck('pump_id')->toArray();

            $pumpQuery = Pump::where('business_id', $business_id)->whereNotIn('id', $already_pumps);
            \App\Utils\PetroPdIsolationUtil::excludePumps($pumpQuery, 'pumps.is_petro_pd_only');
            $pump_nos = $pumpQuery->pluck('pump_name', 'id');

        } else {

            $pumpQuery = Pump::where('business_id', $business_id);
            \App\Utils\PetroPdIsolationUtil::excludePumps($pumpQuery, 'pumps.is_petro_pd_only');
            $pump_nos = $pumpQuery->pluck('pump_name', 'id');

        }

        //other_sale tab

        $stores = Store::forDropdown($business_id, 0, 1, 'sell');

        $fuel_category_id = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();

        $fuel_category_id = ! empty($fuel_category_id) ? $fuel_category_id->id : null;

        $items = $this->transactionUtil->getProductDropDownArray($business_id, $fuel_category_id, app(SettlementSwLegacyMap::class)->productModuleKey());

        $filtered = array_filter($items, function ($value) {

            if (preg_match('/Available Qty\s*:\s*([\d,\.]+)\s/', $value, $matches)) {

                $qty = floatval(str_replace(',', '', $matches[1]));

                return $qty > 0;

            }

            return false;

        });

        $items = $filtered;

        // other income tab

        $services = Product::where('business_id', $business_id)->forModule(app(SettlementSwLegacyMap::class)->productModuleKey())->where('enable_stock', 0)->pluck('name', 'id');

        $subscription = SettlementSwSubscription::active($business_id);

        $package_details = $subscription->package_details;

        $only_walkin = $package_details['only_walkin'] ?? 0;

        if (! empty($only_walkin)) {

            $credit_customers = Contact::customersDropdown($business_id, false, true, 'customer');

        } else {

            $credit_customers = Contact::where('name', '!=', 'Walk-In Customer')->where('active', 1)->where('type', 'customer')->where('business_id', $business_id)->pluck('name', 'id');

        }

        if (is_null($subscription)) {

            $show_shift_no = false;

        } else {

            if ($subscription->customer_credit_notification_type == []) {

                $show_shift_no = false;

            } else {

                $firstDecode = json_decode($subscription->customer_credit_notification_type, true);

                if (is_string($firstDecode)) {

                    $decodedData = json_decode($firstDecode, true);

                    $show_shift_no = in_array("pumper_dashboard", $decodedData) ? true : false;

                } else {

                    $show_shift_no = false;

                }

            }

        }

        $payment_meter_sale_total = ! empty($active_settlement->meter_sales) && isset($active_settlement->id) ? $active_settlement->meter_sales->sum('sub_total') : 0.00;

        $payment_other_sale_total = ! empty($active_settlement->other_sales) && isset($active_settlement->id) ? $active_settlement->other_sales->sum('sub_total') : 0.00;

        $payment_other_sale_discount = ! empty($active_settlement->other_sales) && isset($active_settlement->id) ? $active_settlement->other_sales->sum('discount_amount') : 0.00;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $payment_other_income_total = ! empty($active_settlement->other_incomes) && isset($active_settlement->id) ? $active_settlement->other_incomes->sum('sub_total') : 0.00;

        $payment_customer_payment_total = ! empty($active_settlement->customer_payments) && isset($active_settlement->id) ? $active_settlement->customer_payments->sum('sub_total') : 0.00;

        $wrok_shifts = WorkShift::where('business_id', $business_id)->pluck('shift_name', 'id');

        $bulk_tanks = FuelTank::where('business_id', $business_id)->where('bulk_tank', 1)->pluck('fuel_tank_number', 'id');

        $select_pump_operator_in_settlement = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'select_pump_operator_in_settlement');

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $discount_types = ['fixed' => 'Fixed', 'percentage' => 'Percentage'];

        if (empty($active_settlement) && ! isset($active_settlement->id)) {

            $temp_data = [];

        }

        $currency_precision = $business->currency_precision;

        $subscription = SettlementSwSubscription::active($business_id);

        $pacakge_details = $subscription->package_details;

        $is_other_income = $package_details['settlement_sw_other_income'] ?? 0;

        $is_customer_payments = $package_details['settlement_sw_customer_payments'] ?? 0;

        $is_expenses = $package_details['settlement_sw_expenses'] ?? 0;

        $is_payment = $package_details['settlement_sw_payment'] ?? 0;

        $is_credit_sales = $package_details['sw_credit_sales'] ?? 0;

        $accounts = [];

        $accounts = Account::forDropdown($business_id, true, false, true);

        $location_id = $payment_types['location_id'] ?? $default_location;

        $group_id = $this->moduleUtil->one_payment_type('card', $location_id);

        $account_modules = Account::getAccountByAccountGroupId($group_id);

        $group_id = $this->moduleUtil->one_payment_type('bank_transfer', $location_id);

        $account_modules_bank = Account::getAccountByAccountGroupId($group_id);

        $settlement_no = isset($active_settlement->id) ? $active_settlement->settlement_no : 'SET-SW' . (Settlement::where('business_id', $business_id)

                ->where('settlement_no', 'LIKE', 'SET-SW%')->count() + 1);

        // Get shifts for selection - exclude settled shifts for the same operator
        // Get settled shift numbers for the selected operator (if any)
        $settled_shift_numbers = [];
        if (!empty($active_settlement) && isset($active_settlement->pump_operator_id)) {
            $settled_shift_numbers = DB::table(SettlementSwTables::dailyCollections())
                ->join('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')
            ->where('settlements.business_id', $business_id)
            ->where('settlements.status', 0) // 0 = finished/settled
                ->where('daily_collections.pump_operator_id', $active_settlement->pump_operator_id)
                ->whereNotNull('daily_collections.shift_number')
                ->where('daily_collections.shift_number', '!=', '')
                ->pluck('daily_collections.shift_number')
                ->unique()
            ->toArray();
        }

        $shift_numbers_query = SettlementSwDailyShift::where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->whereNotNull('shift_no')
            ->where('shift_no', '!=', '');

        // Exclude settled shifts for the same operator
        if (!empty($settled_shift_numbers)) {
            $shift_numbers_query->whereNotIn('shift_no', $settled_shift_numbers);
        }

        $shift_numbers = $shift_numbers_query
            ->orderBy('updated_at', 'desc')
            ->pluck('shift_no', 'shift_no')
            ->toArray();

        return view('settlementsw::create')->with(compact(

            'select_pump_operator_in_settlement',

            'message',

            'is_other_income',

            'is_customer_payments',

            'is_payment',

            'is_expenses',

            'business_locations',

            'temp_data',

            'accounts',

            'account_modules',

            'account_modules_bank',

            'payment_types',

            'customer_payment_settlement_no',

            'expense_categories',

            'expense_accounts',

            'expense_payment_settlement_no',

            'currency_precision',

            'customers',

            'pump_operators',

            'wrok_shifts',

            'pump_nos',

            'items',

            'settlement_no',

            'default_location',

            'active_settlement',

            'stores',

            'payment_meter_sale_total',

            'payment_other_sale_total',

            'payment_other_income_total',

            'payment_customer_payment_total',

            'bulk_tanks',

            'services',

            'discount_types',

            'cash_denoms',

            'check_qty',

            'payment_other_sale_discount',

            'show_shift_no',

            'combinedOtherSales',

            'pump_other_sale_final_total',

            'only_walkin',

            'credit_customers',

            'products',

            'shift_numbers'

        ));

    }

    public function canEditSettlement($id)
    {
        $settlement  = Settlement::findOrFail($id);
        $transaction = Transaction::where('invoice_no', $settlement->settlement_no)
            ->where('type', 'sell')
            ->first();

        // Initialize variables
        $paid_customer_loan = 0;
        $deposited_cheques  = 0;

        // see the customer payments marked as paid
        // see the loan to customer already paid for by the customer
        $paid_customer_loan = Transaction::where('invoice_no', $settlement->settlement_no)
            ->where('sub_type', 'customer_loan')
            ->whereIn('payment_status', ['partial', 'paid'])
            ->count();

        // see the cheques already deposited
        if (! empty($transaction)) {
            $deposited_cheques = TransactionPayment::where('transaction_id', $transaction->id)
                ->where('method', 'cheque')
                ->where('is_deposited', 1)
                ->count();
        }

        $can_edit = 1;
        $reasons  = '';

        if ($paid_customer_loan > 0 || $deposited_cheques > 0) {
            $can_edit = 0;
            $reasons .= '<ol>';
            if ($paid_customer_loan > 0) {
                $reasons .= '<li>' . __('settlementsw::lang.paid_customer_loan') . '</li>';
            }
            if ($deposited_cheques > 0) {
                $reasons .= '<li>' . __('settlementsw::lang.deposited_cheque') . '</li>';
            }
            $reasons .= '</ol>';
        }

        return [$can_edit, $reasons];
    }

    public function edit($id)
    {
        SettlementSwLog::info('SettlementSwBaseController@edit called for id: ' . $id);

        $business_id = request()

            ->session()

            ->get('business.id');

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types(

            $default_location,

            false,

            false,

            false,

            false,

            'is_sale_enabled'

        );

        $customers = Contact::customersDropdown(

            $business_id,

            false,

            true,

            'customer'

        );

        $pumpOperatorQuery = PumpOperator::where('business_id', $business_id);
        \App\Utils\PetroPdIsolationUtil::excludeOperators($pumpOperatorQuery, 'pump_operators.is_petro_pd_only');
        $pump_operators = $pumpOperatorQuery->pluck('name', 'id');

        $pumpQuery = Pump::where('business_id', $business_id);
        \App\Utils\PetroPdIsolationUtil::excludePumps($pumpQuery, 'pumps.is_petro_pd_only');
        $pump_nos = $pumpQuery->pluck(

            'pump_name',

            'id'

        );

        $business_details = \App\Business::find($business_id);

        $currency_precision = ! empty($business_details->currency_precision)

            ? $business_details->currency_precision

            : 2;

        $meeter_precision = 3;

        $items = [];

        // \DB::enableQueryLog(); // Start logging queries

        $active_settlement = Settlement::where('id', $id)

            ->select('settlements.*')

            ->with([

                'meter_sales',

                'other_sales',

                'other_incomes',

                'customer_payments',

                'cash_payments',

                'card_payments',

                'credit_sale_payments',

            ])

            ->first();

        // dd(\DB::getQueryLog()); // Dump the logged queries

        if (! $active_settlement || (int) $active_settlement->business_id !== (int) $business_id) {
            abort(404, 'Settlement not found');
        }

        $editProductIds = $active_settlement->meter_sales->pluck('product_id')
            ->merge($active_settlement->other_sales->pluck('product_id'))
            ->merge($active_settlement->other_incomes->pluck('product_id'))
            ->filter()
            ->unique();
        $editProducts = Product::query()
            ->where('business_id', $business_id)
            ->whereIn('id', $editProductIds)
            ->get()
            ->keyBy('id');

        $editPumpIds = $active_settlement->meter_sales->pluck('pump_id')->filter()->unique();
        $editPumps = Pump::query()
            ->where('business_id', $business_id)
            ->whereIn('id', $editPumpIds)
            ->get()
            ->keyBy('id');

        $laterMeterSaleIdsByPump = MeterSale::query()
            ->whereIn('pump_id', $editPumpIds)
            ->whereNotNull('transaction_id')
            ->get(['id', 'pump_id'])
            ->groupBy('pump_id')
            ->map(function ($rows) {
                return $rows->pluck('id')->sort()->values();
            });

        $laterSettlementCounts = $active_settlement->meter_sales->mapWithKeys(function ($meterSale) use ($laterMeterSaleIdsByPump) {
            $count = ($laterMeterSaleIdsByPump->get($meterSale->pump_id, collect()))
                ->filter(function ($id) use ($meterSale) {
                    return (int) $id > (int) $meterSale->id;
                })
                ->count();

            return [$meterSale->id => $count];
        });

        $editContacts = Contact::query()
            ->where('business_id', $business_id)
            ->whereIn('id', $active_settlement->customer_payments->pluck('customer_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $settlement_no = $active_settlement->settlement_no;

        $daily_collections = DailyCollection::where('settlement_id', $id)
            ->where('business_id', $business_id)
            ->get();

        SettlementSwLog::info('Fetched ' . $daily_collections->count() . ' daily collections for settlement id: ' . $id);

        // DAILY CARDS
        $daily_cards = DailyCard::where('settlement_no', $settlement_no)
            ->where('business_id', $business_id)
            ->get();

        SettlementSwLog::info('Fetched ' . $daily_cards->count() . ' daily cards for settlement no: ' . $settlement_no);

        // DAILY VOUCHERS
        $daily_vouchers = DailyVoucher::where('settlement_no', $settlement_no)
            ->where('business_id', $business_id)
            ->get();

        SettlementSwLog::info('Fetched ' . $daily_vouchers->count() . ' daily vouchers for settlement no: ' . $settlement_no);

        // Get shift number for this settlement
        // Priority 1: Get from daily_collections (where the data actually is)
        $shift_numbers_from_daily = DailyCollection::where('settlement_id', $active_settlement->id)
            ->where('business_id', $business_id)
            ->whereNotNull('shift_number')
            ->where('shift_number', '!=', '')
            ->where('shift_number', '!=', '0')
            ->distinct()
            ->pluck('shift_number')
            ->filter(function($value) {
                return !empty($value) && $value !== '0';
            })
            ->unique()
            ->values();
        
        $shift_number = null;
        
        // If we found shift numbers in daily_collections, use them
        if ($shift_numbers_from_daily->isNotEmpty()) {
            $shift_number_value = $shift_numbers_from_daily->implode(',');
            $shift_number = (object)['shift_number' => $shift_number_value];
        } else {
            // Priority 2: If not found in daily_collections, try pump_operator_assignments
        $shift_number = PumpOperatorAssignment::where('settlement_id', $active_settlement->id)
            ->where('pump_operator_id', $active_settlement->pump_operator_id)
            ->whereNotNull('shift_number')
                ->where('shift_number', '!=', 0)
            ->select('shift_number', 'shift_id')
                ->orderBy('id', 'desc')
            ->first();
        
            // Priority 3: If still not found, try without operator filter
        if (empty($shift_number)) {
            $shift_number = PumpOperatorAssignment::where('settlement_id', $active_settlement->id)
                ->whereNotNull('shift_number')
                    ->where('shift_number', '!=', 0)
                ->select('shift_number', 'shift_id')
                ->orderBy('id', 'desc')
                ->first();
            }
        }

        $other_sale_final_total = 0;

        $pump_other_sale_final_total = 0;

        $userOtherDetails = [];

        // dd($active_settlement->other_sales);

        foreach ($active_settlement->other_sales as $ot_item) {

            $product = $editProducts->get($ot_item->product_id);

            $discount_amount = $ot_item->discount_amount ?? 0;

            $withDiscount = ($ot_item->sub_total ?? 0) - $discount_amount;

            $pump_other_sale_final_total += $withDiscount;

            // Prepare formatted array for user-entered sales

            $userOtherDetails[] = [

                'id'            => $ot_item->id,

                'sku'           => ! empty($product) ? $product->sku : '',

                'name'          => ! empty($product) ? $product->name : '',

                'balance_stock' => number_format(

                    $ot_item->balance_stock,

                    4,

                    '.',

                    ','

                ),

                'price'         => number_format($ot_item->price, $currency_precision),

                'qty'           => number_format($ot_item->qty, 4, '.', ','),

                'discount_type' => $ot_item->discount_type,

                'discount'      => number_format(

                    $ot_item->discount,

                    $currency_precision

                ),

                'sub_total'     => number_format(

                    $ot_item->sub_total,

                    $currency_precision

                ),

                'with_discount' => number_format(

                    $withDiscount,

                    $currency_precision

                ),

                'user_check'    => 1, // 1 for user entry

            ];

        }

        $shiftIds = is_array($shift_number) ? array_column($shift_number, 'shift_id') : [];

        $query = PumpOperatorOtherSale::join(

            'products',

            'products.id',

            '=',

            'pump_operator_other_sales.product_id'

        )

            ->leftJoin('variations', 'products.id', 'variations.product_id')

            ->leftJoin(

                'variation_location_details',

                'variations.id',

                'variation_location_details.variation_id'

            )

            ->whereIn('pump_operator_other_sales.shift_id', $shiftIds)

            ->join('pump_operator_assignments', function ($join) {

                $join

                    ->on(

                        'pump_operator_assignments.shift_id',

                        '=',

                        'pump_operator_other_sales.shift_id'

                    )

                    ->where(

                        'pump_operator_assignments.status',

                        'close'

                    )->whereRaw('pump_operator_assignments.id = (

                         SELECT MAX(poa.id)

                         FROM pump_operator_assignments poa

                         WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"

                     )');

            })

            ->select(

                'pump_operator_other_sales.*',

                'products.name as product_name',

                'products.sku as product_sku',

                'pump_operator_assignments.shift_number',

                'qty_available'

            )

            ->groupBy('pump_operator_other_sales.id');

        $pumperOthersaleDetails = [];

        $pumpSales = $query->get();

        foreach ($pumpSales as $pumpSale) {

            $discount_amount = $pumpSale->discount ?? 0;

            $withDiscount = ($pumpSale->sub_total ?? 0) - $discount_amount;

            $pump_other_sale_final_total += $withDiscount;

            // Prepare formatted array for pump-operator-entered sales

            $pumperOthersaleDetails[] = [

                'sku'           => $pumpSale->product_sku,

                'name'          => $pumpSale->product_name,

                'balance_stock' => number_format(

                    $pumpSale->qty_available,

                    4,

                    '.',

                    ','

                ),

                'price'         => number_format($pumpSale->price, $currency_precision),

                'qty'           => number_format($pumpSale->qty, 4, '.', ','),

                'discount_type' => $pumpSale->discount_type,

                'discount'      => number_format(

                    $pumpSale->discount,

                    $currency_precision

                ),

                'sub_total'     => number_format(

                    $pumpSale->sub_total,

                    $currency_precision

                ),

                'with_discount' => number_format(

                    $withDiscount,

                    $currency_precision

                ),

                'user_check'    => 0,

            ];

        }

        $combinedOtherSales = array_merge(

            $userOtherDetails,

            $pumperOthersaleDetails

        );

        $final_other_sale_total =

            $other_sale_final_total + $pump_other_sale_final_total;

        $has_reviewed = $this->transactionUtil->hasReviewed(

            $active_settlement->transaction_date

        );

        if (! empty($has_reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => __('lang_v1.review_first'),

            ];

            return redirect()

                ->back()

                ->with(['status' => $output]);

        }

        $reviewed = $this->transactionUtil->get_review(

            $active_settlement->transaction_date,

            $active_settlement->transaction_date

        );

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => "You can't edit a settlement for an already reviewed date",

            ];

            return redirect()

                ->back()

                ->with(['status' => $output]);

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            $already_pumps = MeterSale::where(

                'settlement_no',

                $active_settlement->id

            )

                ->pluck('pump_id')

                ->toArray();

            if ($active_settlement->meter_sales->count()) {

                $already_pumps = array_diff($already_pumps, [

                    $active_settlement->meter_sales->toArray()[0]['pump_id'],

                ]);

                $already_pumps = array_values($already_pumps);

            }

            $pumpQuery = Pump::where('business_id', $business_id)
                ->whereNotIn('id', $already_pumps);
            \App\Utils\PetroPdIsolationUtil::excludePumps($pumpQuery, 'pumps.is_petro_pd_only');
            $pump_nos = $pumpQuery->pluck('pump_name', 'id');

        } else {

            $pumpQuery = Pump::where('business_id', $business_id);
            \App\Utils\PetroPdIsolationUtil::excludePumps($pumpQuery, 'pumps.is_petro_pd_only');
            $pump_nos = $pumpQuery->pluck(

                'pump_name',

                'id'

            );

        }

        // other_sale tab

        $stores = Store::forDropdown($business_id, 0, 1, 'sell');

        $fuel_category_id = Category::where('business_id', $business_id)

            ->where('name', 'Fuel')

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        // $items = Product::where('category_id', '!=', $fuel_category_id)->where('business_id', $business_id)->pluck('name', 'id');

        $items = $this->transactionUtil->getProductDropDownArray(

            $business_id,

            $fuel_category_id,

            app(SettlementSwLegacyMap::class)->productModuleKey()

        );

        $payment_meter_sale_total = ! empty($active_settlement->meter_sales)

            ? $active_settlement->meter_sales->sum('sub_total')

            : 0.0;

        $payment_other_sale_total = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum('sub_total')

            : 0.0;

        $payment_other_income_total = ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum('sub_total')

            : 0.0;

        $payment_customer_payment_total = ! empty(

            $active_settlement->customer_payments

        )

            ? $active_settlement->customer_payments->sum('sub_total')

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum('discount_amount')

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $wrok_shifts = WorkShift::where('business_id', $business_id)->pluck(

            'shift_name',

            'id'

        );

        $bulk_tanks = FuelTank::where('business_id', $business_id)

            ->where('bulk_tank', 1)

            ->pluck('fuel_tank_number', 'id');

        $services = Product::where('business_id', $business_id)

            ->forModule(app(SettlementSwLegacyMap::class)->productModuleKey())

            ->where('enable_stock', 0)

            ->pluck('name', 'id');

        $discount_types = ['fixed' => 'Fixed', 'percentage' => 'Percentage'];

        $can_edit_details = $this->canEditSettlement($id);

        return view('settlementsw::swsettlement.edit')->with(

            compact(

                'business_locations',

                'payment_types',

                'services',

                'customers',

                'pump_operators',

                'wrok_shifts',

                'pump_nos',

                'items',

                'settlement_no',

                'default_location',

                'active_settlement',

                'stores',

                'payment_meter_sale_total',

                'payment_other_sale_total',

                'payment_other_income_total',

                'payment_customer_payment_total',

                'bulk_tanks',

                'discount_types',

                'can_edit_details',

                'shift_number',

                'combinedOtherSales',

                'pump_other_sale_final_total',
                'daily_collections',
                'daily_cards',
                'daily_vouchers',
                'business_details',
                'editProducts',
                'editPumps',
                'editContacts',
                'laterSettlementCounts'

            )

        );

    }


    /**
     * Bulk lookup data used by Settlement SW show/print views.
     *
     * Views must never query the database inside row loops. This method collects
     * every product, pump, contact, account, expense category, work shift and
     * referenced settlement in a fixed number of tenant-scoped queries.
     */
    private function buildSettlementViewLookups(Settlement $settlement, int $businessId, $additionalCustomerPayments = null): array
    {
        $relation = static function (Settlement $model, string $name) {
            return $model->relationLoaded($name) ? $model->getRelation($name) : collect();
        };

        $meterSales = $relation($settlement, 'meter_sales');
        $otherSales = $relation($settlement, 'other_sales');
        $otherIncomes = $relation($settlement, 'other_incomes');
        $creditSales = $relation($settlement, 'credit_sale_payments');
        $expensePayments = $relation($settlement, 'expense_payments');
        $customerLoans = $relation($settlement, 'customer_loans');
        $loanPayments = $relation($settlement, 'loan_payments');
        $drawingPayments = $relation($settlement, 'drawings_payments');
        $customerPayments = $relation($settlement, 'customer_payments');

        $pumpIds = $meterSales->pluck('pump_id')->filter()->unique()->values();
        $pumps = Pump::query()
            ->where('business_id', $businessId)
            ->whereIn('id', $pumpIds)
            ->get()
            ->keyBy('id');

        $workShiftIds = collect($settlement->work_shift ?? [])->filter()->unique()->values();

        $operatorShiftIds = $relation($settlement, 'daily_collections')
            ->pluck('shift_id')
            ->filter()
            ->unique()
            ->values();

        if ($operatorShiftIds->isEmpty()) {
            $operatorShiftIds = PumpOperatorAssignment::query()
                ->where('business_id', $businessId)
                ->where('settlement_id', $settlement->id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->unique()
                ->values();
        }

        $operatorOtherSales = PumpOperatorOtherSale::query()
            ->where('business_id', $businessId)
            ->whereIn('shift_id', $operatorShiftIds)
            ->get();

        $productIds = $meterSales->pluck('product_id')
            ->merge($otherSales->pluck('product_id'))
            ->merge($otherIncomes->pluck('product_id'))
            ->merge($creditSales->pluck('product_id'))
            ->merge($operatorOtherSales->pluck('product_id'))
            ->filter()
            ->unique()
            ->values();

        $customerIds = $customerPayments->pluck('customer_id')
            ->merge(collect($additionalCustomerPayments)->pluck('customer_id'))
            ->merge($creditSales->pluck('customer_id'))
            ->merge($customerLoans->pluck('customer_id'))
            ->filter()
            ->unique()
            ->values();

        $accountIds = $loanPayments->pluck('loan_account')
            ->merge($drawingPayments->pluck('loan_account'))
            ->filter()
            ->unique()
            ->values();

        $settlementIds = $otherSales->pluck('settlement_no')->filter()->unique()->values();

        return [
            'business' => Business::query()->whereKey($businessId)->first(),
            'pumps' => $pumps,
            'products' => Product::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id'),
            'contacts' => Contact::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $customerIds)
                ->get()
                ->keyBy('id'),
            'expense_categories' => ExpenseCategory::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $expensePayments->pluck('category_id')->filter()->unique())
                ->get()
                ->keyBy('id'),
            'accounts' => Account::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id'),
            'work_shifts' => WorkShift::query()
                ->whereIn('id', $workShiftIds)
                ->get()
                ->keyBy('id'),
            'settlements' => Settlement::query()
                ->where('business_id', $businessId)
                ->whereIn('id', $settlementIds)
                ->get()
                ->keyBy('id'),
            'operator_other_sales' => $operatorOtherSales,
            'operator_other_sales_by_shift' => $operatorOtherSales->groupBy('shift_id'),
        ];
    }

    public function show($id)
    {
        SettlementSwLog::info('inside the show');
        $business_id = $this->settlementSwBusinessId();

        // Fetch settlement with relationships (allow id or settlement_no)
        $settlement = Settlement::with([
            'meter_sales',
            'other_sales',
            'other_incomes',
            'customer_payments',
            'cash_payments',
            'cash_deposits',  // ← FIX: Load cash deposits relationship
            'card_payments',
            'cheque_payments',
            'credit_sale_payments',
            'expense_payments',
            'excess_payments',
            'shortage_payments',
            'loan_payments',
            'drawings_payments',
            'customer_loans',
            'daily_collections',  // ← FIX: Load daily collections
            'daily_cards',        // ← FIX: Load daily cards
        ])
        ->where(function($q) use ($id, $business_id) {
            $q->where(function($qq) use ($id, $business_id) {
                $qq->where('settlements.id', $id)
                   ->where('business_id', $business_id);
            })
            ->orWhere(function($qq) use ($id, $business_id) {
                $qq->where('settlement_no', $id)
                   ->where('business_id', $business_id);
            });
        })
        ->first();

        if (! $settlement) {
            abort(404, 'Settlement not found');
        }

        $pump_operator_id = $settlement->pump_operator_id;
        $settlement_no    = $settlement->settlement_no;
        $settlement_id    = $settlement->id;

        SettlementSwLog::info('Settlement ID: ' . $settlement_id);
        SettlementSwLog::info('Settlement No: ' . $settlement_no);
        SettlementSwLog::info('Pump Operator ID: ' . $pump_operator_id);

        // CUSTOMER PAYMENTS
        $customer_payments_tab = CustomerPayment::leftJoin(SettlementSwTables::contacts(), 'customer_payments.customer_id', 'contacts.id')
            ->where('customer_payments.settlement_no', $settlement_no)
            ->where('customer_payments.business_id', $business_id)
            ->select('customer_payments.*', 'contacts.name as customer_name')
            ->get();

        // MERGE DailyCollection into cash_payments
        $daily_cash = DailyCollection::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->where(function ($query) use ($settlement_id) {
                $query->where('settlement_id', $settlement_id)
                    ->orWhereNull('settlement_id');
            })
            ->get()
            ->map(function ($item) {
                $item->amount = $item->current_amount; // normalize for Blade sum
                return $item;
            });

        $settlement->cash_payments = $settlement->cash_payments->concat($daily_cash);

// MERGE DailyCard into card_payments (already has amount, keep as is)
        $daily_cards = DailyCard::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where(function ($query) use ($settlement_no) {
                $query->where('settlement_no', $settlement_no)
                    ->orWhereNull('settlement_no');
            })
            ->get();

        $settlement->card_payments = $settlement->card_payments->concat($daily_cards);

// MERGE DailyVoucher into credit_sale_payments
        $daily_vouchers = DailyVoucher::where('operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where(function ($query) use ($settlement_no) {
                $query->where('settlement_no', $settlement_no)
                    ->orWhereNull('settlement_no');
            })
            ->get();

        // TOTAL DAILY COLLECTION (for display)
        $total_daily_collection = DailyCollection::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where('settlement_id', $settlement_id)
            ->where('type', 'daily_collection')
            ->sum('current_amount');

        $business      = Business::find($business_id);
        $pump_operator = PumpOperator::find($pump_operator_id);

        // Ensure cash deposits are loaded correctly
        // The relationship matches by id, but Settlement SW stores settlement_no as string (e.g., "SET-SW1")
        // So we need to reload using both formats to ensure all cash deposits are found
        $cash_deposits = SettlementCashDeposit::where(function($q) use ($settlement, $settlement_no) {
            $q->where('settlement_no', $settlement_no) // String format (e.g., "SET-SW1")
              ->orWhere('settlement_no', $settlement->id); // ID format (integer)
        })
                ->where('business_id', $business_id)
        ->get();
        
        // Also include cash deposits from accounting module (AccountTransactions with sub_type='deposit')
        // These are deposits added via Accounting → List Accounts → Cash Deposit
        $account_deposits = AccountTransaction::where('business_id', $business_id)
            ->where('sub_type', 'deposit')
            ->where('type', 'credit') // Credit entries for bank deposits
            ->where(function($q) use ($settlement, $settlement_no) {
                $q->where('note', 'like', '%Settlement No: ' . $settlement_no . '%')
                  ->orWhere('note', 'like', '%Settlement No: ' . $settlement->id . '%');
            })
            ->get();
        
        // Convert account_transactions to SettlementCashDeposit-like objects for display
        $depositAccounts = Account::query()
            ->where('business_id', $business_id)
            ->whereIn('id', $account_deposits->pluck('account_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $fake_deposits = $account_deposits->map(function($txn) use ($depositAccounts) {
            $bank_account = $depositAccounts->get($txn->account_id);
            $deposit = new \stdClass();
            $deposit->id = $txn->id;
            $deposit->settlement_no = null; // Not in settlement_cash_deposits table
            $deposit->amount = $txn->amount;
            $deposit->bank_id = $txn->account_id;
            $deposit->bank_name = $bank_account ? $bank_account->name : 'Unknown Bank';
            $deposit->account_no = $txn->cheque_number ?? '';
            $deposit->time_deposited = $txn->operation_date;
            return $deposit;
        });
        
        // Merge both collections
        $all_cash_deposits = $cash_deposits->merge($fake_deposits);
        
        // Always set the relation to ensure we have the correct cash deposits
        $settlement->setRelation('cash_deposits', $all_cash_deposits);

        // Get shift number for this settlement
        $shift_number = PumpOperatorAssignment::where('settlement_id', $settlement->id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereNotNull('shift_number')
            ->select('shift_number', 'shift_id')
            ->orderBy('id', 'desc')
            ->first();
        
        // If not found, try without operator filter (in case operator changed)
        if (empty($shift_number)) {
            $shift_number = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->whereNotNull('shift_number')
                ->select('shift_number', 'shift_id')
                ->orderBy('id', 'desc')
                ->first();
        }

        // Fallback: derive shift number(s) from shift IDs when still missing
        $shift_number_value = $shift_number->shift_number ?? null;
        if (empty($shift_number_value)) {
            $shiftIdsForDisplay = DailyCollection::where('settlement_id', $settlement->id)
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (empty($shiftIdsForDisplay)) {
                $shiftIdsForDisplay = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->pluck('shift_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }

            if (! empty($shiftIdsForDisplay)) {
                $shiftNos = DB::table(SettlementSwTables::dailyShifts())
                    ->whereIn('id', $shiftIdsForDisplay)
                    ->pluck('shift_no')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                if (! empty($shiftNos)) {
                    $shift_number_value = implode(',', $shiftNos);
                }
            }

            if (empty($shift_number_value)) {
                $shift_number_value = 'N/A';
            }

            $shift_number = (object) ['shift_number' => $shift_number_value];
        }

        $settlementLookups = $this->buildSettlementViewLookups($settlement, $business_id, $customer_payments_tab);

        return view('settlementsw::swsettlement.show', compact(
            'settlement',
            'business',
            'pump_operator',
            'customer_payments_tab',
            'total_daily_collection',
            'shift_number',
            'settlementLookups'
        ));
    }

    public function replaceTempDataSectionWithDb(&$temp_data, string $section, string $table, string $settlement_no, callable $formatter = null)
    {

        if (empty($settlement_no)) {

            return;

        }

        if (empty($temp_data) || ! is_object($temp_data)) {
            $temp_data = (object) [];
        }

        $records = DB::table($table)

            ->where('settlement_no', $settlement_no)

            ->get();

        if ($records->isNotEmpty()) {

            $temp_data->$section = $formatter

                ? $records->map($formatter)->toArray()

                : $records->toArray(); // fallback brut

        } else {

            $temp_data->$section = [];

        }

    }

    public function saveMeterSale(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $business_locations = BusinessLocation::forDropdown($business_id);

            $default_location = current(array_keys($business_locations->toArray()));

            DB::beginTransaction();

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return ['success' => false,

                    'msg'             => __('settlementsw::lang.date_greater_than_day_end'),

                ];

            }

            $pump = Pump::where('id', $request->pump_id)->first();

            $fuel_tank = FuelTank::where('id', $pump->fuel_tank_id)->first();

            $product = Variation::leftjoin('products', 'variations.product_id', 'products.id')

                ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')

                ->where('products.id', $fuel_tank->product_id)

                ->select('sku', 'variations.sell_price_inc_tax as default_sell_price', 'products.name', 'products.id', 'variation_location_details.qty_available')->first();

            $data = [

                'business_id'     => $business_id,

                'settlement_no'   => $settlement_exist->id,

                'product_id'      => $request->product_id,

                'pump_id'         => $request->pump_id,

                'starting_meter'  => $request->starting_meter,

                'closing_meter'   => $pump->bulk_sale_meter == 0 ? $request->closing_meter : '',

                'price'           => $request->price,

                'qty'             => $request->qty,

                'discount'        => $request->discount,

                'discount_type'   => $request->discount_type,

                'discount_amount' => $request->discount_amount,

                'testing_qty'     => $request->testing_qty,

                'sub_total'       => $request->sub_total,

            ];

            $meter_sale = MeterSale::create($data);

            if (! empty($request->is_from_pumper)) {

                logger($request->pumper_entry_id);

                logger($request->assignment_id);

                PumperDayEntry::where('id', $request->pumper_entry_id)

                    ->update(['settlement_no' => $request->settlement_no, 'settlement_added_by' => auth()->user()->id, 'closed_in_settlement' => 1]);

                PumpOperatorAssignment::where('id', $request->assignment_id)->update(['closed_in_settlement' => 1]);

            }

            Settlement::where('id', $settlement_exist->id)->update(['is_edit' => request()->is_edit]);

            // add pump operator commission

            $pump_operator = PumpOperator::find($settlement_exist->pump_operator_id);

            if (! empty($pump_operator)) {

                if (! empty($pump_operator->commission_type) && ! empty($pump_operator->commission_ap)) {

                    $commission_amount = 0;

                    $discounted_amount = $request->discount_amount;

                    if ($pump_operator->commission_type == 'percentage') {

                        $commission_amount = $discounted_amount * $pump_operator->commission_ap / 100;

                    }

                    if ($pump_operator->commission_type == 'fixed') {

                        $commission_amount = $request->qty * $pump_operator->commission_ap;

                    }

                    $commission_data = [

                        'pump_operator_id' => $settlement_exist->pump_operator_id,

                        'meter_sale_id'    => $meter_sale->id,

                        'transaction_date' => $settlement_exist->transaction_date,

                        'amount'           => $commission_amount,

                        'type'             => $pump_operator->commission_type,

                        'value'            => $pump_operator->commission_ap,

                    ];

                    PumpOperatorCommission::create($commission_data);

                }

            }

            Pump::where('id', $request->pump_id)->update(['starting_meter' => $request->starting_meter, 'last_meter_reading' => $request->closing_meter]);

            // Link credit sales from pumper dashboard when meter sale is added (similar to Settlement PD)
            // This ensures credit sales are linked to the settlement and DailyVoucher status changes to completed
            $settlement = $settlement_exist;
            $pump_operator_id = $settlement->pump_operator_id;
            $settlement_no = $settlement->settlement_no;
            
            // Get shift_ids from work_shift (array) or from pump_operator_assignments
            $shift_ids_for_filter = [];
            if (!empty($settlement->work_shift) && is_array($settlement->work_shift)) {
                $shift_ids_for_filter = array_map('intval', $settlement->work_shift);
            } else {
                // Try to get shift_ids from pump_operator_assignments linked to this settlement
                $assignments = \Modules\SettlementSW\Entities\PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                    ->where('settlement_id', $settlement->id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->toArray();
                if (!empty($assignments)) {
                    $shift_ids_for_filter = array_map('intval', $assignments);
                }
            }
            
            SettlementSwLog::info('Settlement SW: Linking Credit Sales from Pumper Dashboard (saveMeterSale)', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement_no,
                'pump_operator_id' => $pump_operator_id,
                'shift_ids_for_filter' => $shift_ids_for_filter,
            ]);
            
            if (!empty($shift_ids_for_filter)) {
                // Get DailyVoucher IDs for the shifts being settled
                $daily_voucher_ids_for_shifts = DailyVoucher::where('operator_id', $pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->pluck('id')
                    ->toArray();
                
                SettlementSwLog::info('Settlement SW: DailyVoucher IDs for Shifts (saveMeterSale)', [
                    'daily_voucher_ids_for_shifts' => $daily_voucher_ids_for_shifts,
                    'count' => count($daily_voucher_ids_for_shifts),
                ]);
                
                // Link SettlementCreditSalePayment records that match these DailyVouchers
                if (!empty($daily_voucher_ids_for_shifts)) {
                    $linked_count = SettlementCreditSalePayment::where('pump_operator_id', $pump_operator_id)
                        ->whereIn('daily_voucher_id', $daily_voucher_ids_for_shifts)
                        ->where(function($q) {
                            $q->whereNull('settlement_no')
                              ->orWhere('settlement_no', '');
                        })
                        ->update(['settlement_no' => $settlement_no]);
                    
                    SettlementSwLog::info('Settlement SW: Credit Sales Linked (by daily_voucher_id) in saveMeterSale', [
                        'linked_count' => $linked_count,
                        'settlement_no' => $settlement_no,
                    ]);
                }
                
                // Also link by matching order_number and customer_id with DailyVouchers
                $daily_vouchers_for_shifts = DailyVoucher::where('operator_id', $pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->get(['id', 'voucher_order_number', 'customer_id']);
                
                SettlementSwLog::info('Settlement SW: DailyVouchers for Matching by order_number (saveMeterSale)', [
                    'count' => $daily_vouchers_for_shifts->count(),
                ]);
                
                foreach ($daily_vouchers_for_shifts as $dv) {
                    if (!empty($dv->voucher_order_number)) {
                        $linked_count = SettlementCreditSalePayment::where('pump_operator_id', $pump_operator_id)
                            ->where('order_number', $dv->voucher_order_number)
                            ->where('customer_id', $dv->customer_id)
                            ->where(function($q) {
                                $q->whereNull('settlement_no')
                                  ->orWhere('settlement_no', '');
                            })
                            ->update(['settlement_no' => $settlement_no]);
                        
                        if ($linked_count > 0) {
                            SettlementSwLog::info('Settlement SW: Credit Sales Linked (by order_number) in saveMeterSale', [
                                'linked_count' => $linked_count,
                                'order_number' => $dv->voucher_order_number,
                                'settlement_no' => $settlement_no,
                            ]);
                        }
                    }
                }
                
                // Update DailyVoucher settlement_no for all credit sales linked to this settlement
                // This marks them as completed/settled
                $daily_vouchers_updated = DailyVoucher::where('operator_id', $pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->update(['settlement_no' => $settlement_no]);
                
                SettlementSwLog::info('Settlement SW: DailyVouchers Updated (saveMeterSale)', [
                    'updated_count' => $daily_vouchers_updated,
                    'settlement_no' => $settlement_no,
                ]);
            } else {
                \Log::warning('Settlement SW: No shift_ids_for_filter - Skipping Credit Sales Linking in saveMeterSale', [
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement_no,
                ]);
            }

            DB::commit();

            $afterDiscount = $request->sub_total; // Default value if no discount is applied

            if ($request->discount_type === 'fixed' && $request->discount > 0) {

                $afterDiscount = $request->sub_total - (float) ($request->discount);

            }

            if ($request->discount_type === 'percentage' && $request->discount > 0) {

                $afterDiscount = $request->sub_total - (($request->sub_total * (float) ($request->discount)) / 100);

            }

            $business = Business::where('id', $business_id)->first();

            $currency_precision = $business->currency_precision;

            $output = [

                'success' => true,

                'msg'     => 'success',

                'data'    => [

                    'meter_sale_id'  => $meter_sale->id,

                    'product_name'   => optional($meter_sale->product)->name,

                    'pump_no'        => optional($pump)->pump_no ?? '',

                    'pump_start'     => number_format((float) $request->starting_meter, 3, '.', ''),

                    'pump_close'     => number_format((float) $request->closing_meter, 3, '.', ''),

                    'unit_price'     => number_format((float) $request->price, $currency_precision, '.', ''),

                    'sold_qty'       => number_format((float) $request->qty, 3, '.', ''),

                    'discount_type'  => $request->discount_type,

                    'discount_val'   => number_format((float) $request->discount, $currency_precision, '.', ''),

                    'testing_qty'    => number_format((float) $request->testing_qty, 3, '.', ''),

                    'total_qty'      => number_format((float) $request->qty + (float) $request->testing_qty, 3, '.', ''),

                    'sub_total'      => number_format((float) $request->sub_total, $currency_precision, '.', ''),

                    'after_discount' => number_format((float) $afterDiscount, $currency_precision, '.', ''),

                    'product'        => $product,

                ],

            ];

        } catch (\Exception $e) {

            echo json_encode($e->getMessage());exit();

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function extractLastInteger($text)
    {

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);

        } else {

            return 0;

        }

    }

    public function createSettlementIfNotExist(Request $request)
    {

        $business_id = $request->session()->get('business.id');

        $business = Business::where('id', $business_id)->first();

        $prefix = ! empty($business->ref_no_prefixes['settlement_sw'])

            ? $business->ref_no_prefixes['settlement_sw']

            : 'SET-SW';

        $count = Settlement::where('business_id', $business_id)

            ->where('settlement_no', 'LIKE', $prefix . '%')

            ->orderBy('id', 'DESC')->first();

        if (! empty($count)) {

            $count = $this->extractLastInteger($count->settlement_no);

        } else {

            $count = 0;

        }

        $settlement_no = $prefix . (1 + $count);

        $settlement_data = [

            'settlement_no'    => $settlement_no,

            'business_id'      => $business_id,

            'transaction_date' => Carbon::parse($request->transaction_date)->format('Y-m-d'),

            'location_id'      => $request->location_id,

            'pump_operator_id' => $request->pump_operator_id,

            'work_shift'       => ! empty($request->work_shift) ? $request->work_shift : [],

            'note'             => $request->note,

            'status'           => 1,

        ];

        $latest_date = DayEnd::where('business_id', $business_id)->get()->last()->day_end_date ?? null;

        if (! empty($latest_date) && strtotime($latest_date) >= strtotime($settlement_data['transaction_date'])) {

            return 406;

        }

        $settlement_exist = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

        if (empty($settlement_exist)) {

            $settlement_exist = Settlement::create($settlement_data);

            DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $request->pump_operator_id)
                ->whereNull('settlement_id')
                ->update(['settlement_id' => $settlement_exist->id]);

            DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $request->pump_operator_id)
                ->whereNull('settlement_no')
                ->update(['settlement_no' => $settlement_exist->settlement_no]);

            DailyVoucher::where('business_id', $business_id)
                ->where('operator_id', $request->pump_operator_id)
                ->whereNull('settlement_no')
                ->update(['settlement_no' => $settlement_exist->settlement_no]);

        }

        return $settlement_exist;

    }

    public function getPumpDetails($pump_id)
    {

        $pump = Pump::where('id', $pump_id)->first();

        $last_meter_reading = $pump->last_meter_reading;

        $last_meter_sale = MeterSale::where('pump_id', $pump_id)->orderBy('id', 'desc')->first();

        if (! empty($last_meter_sale)) {

            $last_meter_reading = ! empty($last_meter_sale->meter_reset_value) ? $last_meter_sale->meter_reset_value : $last_meter_sale->closing_meter;

        }

        $ass = PumpOperatorAssignment::where('pump_id', $pump_id)->where('closed_in_settlement', 0)->orderBy('id', 'desc')->first();

        $po_closing = 0;

        $day_entry = null;

        if (! empty($ass)) {

            $po_closing = $ass->closing_meter;

            $day_entry = PumperDayEntry::where('pump_id', $pump_id)->where('pumper_assignment_id', $ass->id)->where('closed_in_settlement', 0)->first();

        }

        $fuel_tank = FuelTank::where('id', $pump->fuel_tank_id)->first();

        $product = Variation::leftjoin('products', 'variations.product_id', 'products.id')

            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')

            ->where('products.id', $fuel_tank->product_id)

            ->select('sku', 'variations.sell_price_inc_tax as default_sell_price', 'products.name', 'products.id', 'variation_location_details.qty_available')->first();

        $business_id = $this->settlementSwBusinessId();

        $business = Business::where('id', $business_id)->first();

        $currency_precision = $business->currency_precision;

        $product->default_sell_price = number_format($product->default_sell_price, $currency_precision, '.', '');

        $current_balance = $this->transactionUtil->getTankBalanceById($pump->fuel_tank_id);

        return [

            'colsing_value'    => number_format($last_meter_reading, 3, '.', ''),

            'tank_remaing_qty' => $current_balance,

            'product'          => $product,

            'pump_name'        => $pump->pump_name,

            'product_id'       => $product->id,

            'pump_id'          => $pump->id,

            'bulk_sale_meter'  => $pump->bulk_sale_meter,

            'po_closing'       => number_format(($last_meter_reading >= $po_closing ? 0 : $po_closing), 3, '.', ''),

            'po_testing'       => number_format(($last_meter_reading >= $po_closing ? 0 : (! empty($day_entry) ? $day_entry->testing_ltr : 0)), 3, '.', ''),

            'assignment_id'    => ! empty($ass) ? $ass->id : 0,

            'pumper_entry_id'  => ! empty($day_entry) ? $day_entry->id : 0,

        ];

    }

    /**

     * get balance stock of product

     * @param product_id

     * @return Response

     */

    public function getPumps($id)
    {

        try {

            $business_id = $this->settlementSwBusinessId();

            $assigned_pumps = PumpOperatorAssignment::where('pump_operator_id', $id)->where('settlement_id', null)->whereDate('date_and_time', date('Y-m-d'))->pluck('pump_id');

            if (! empty($assigned_pumps) && sizeof($assigned_pumps) > 0) {

                $pumps = Pump::where('business_id', $business_id)->whereIn('id', $assigned_pumps)->pluck('pump_name', 'id');

            } else {

                $pumps = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');

            }

           
            // Get settled shift numbers for this specific operator from daily collections
            $settled_shift_numbers = DB::table(SettlementSwTables::dailyCollections())
                ->join('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')
                ->where('settlements.business_id', $business_id)
                ->where('settlements.status', 0) // 0 = finished/settled
                ->where('daily_collections.pump_operator_id', $id)
                ->whereNotNull('daily_collections.shift_number')
                ->where('daily_collections.shift_number', '!=', '')
                ->pluck('daily_collections.shift_number')
                ->unique()
                ->toArray();

            $shift_numbers = DB::table(SettlementSwTables::dailyShifts())
                ->where('business_id', $business_id)
                ->where(function ($q) use ($id) {
                    $q->where('pump_operator_pending', $id)
                        ->orWhere('pump_operator_assigned', $id);
                })
                ->where('type', 'daily_collection_sw')
                ->whereNotIn('shift_no', $settled_shift_numbers) // Exclude settled shifts
                ->orderBy('id', 'desc')
                ->get()
                ->mapWithKeys(function ($row) {
                    return [$row->id => $row->shift_no];
                })
                ->toArray();

            $output = [

                'success'       => true,

                'pumps'         => $pumps,

                'shift_numbers' => $shift_numbers,

            ];

        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveOtherSale(Request $request)
    {
        try {

            $business_id = $request->session()->get('business.id');

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return ['success' => false,

                    'msg'             => __('settlementsw::lang.date_greater_than_day_end'),

                ];

            }

            $data = [

                'business_id'     => $business_id,

                'settlement_no'   => $settlement_exist->id,

                'store_id'        => $request->store_id,

                'product_id'      => $request->product_id,

                'price'           => $request->price,

                'qty'             => $request->qty,

                'balance_stock'   => $request->balance_stock,

                'discount'        => $request->discount,

                'discount_type'   => $request->discount_type,

                'discount_amount' => $request->discount_amount,

                'sub_total'       => $request->sub_total,

            ];

            $other_sale = OtherSale::create($data);

            Settlement::where('id', $settlement_exist->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'       => true,

                'other_sale_id' => $other_sale->id,

                'msg'           => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            var_dump($e->getMessage());exit();

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;
    }

    public function getBalanceStockById(Request $request, $id)
    {

        try {

            $product = Product::join('variations', 'products.id', '=', 'variations.product_id')

                ->leftJoin('variation_location_details', function ($join) use ($id, $request) {

                    $join->on('variations.id', '=', 'variation_location_details.variation_id')

                        ->where('variation_location_details.product_id', '=', $id)

                        ->where('variation_location_details.location_id', '=', $request->location_id);

                })

                ->leftJoin('variation_store_details', function ($join) use ($request) {

                    $join->on('variations.id', '=', 'variation_store_details.variation_id')

                        ->where('variation_store_details.store_id', '=', $request->store_id);

                })

                ->where('products.id', $id)

                ->select(

                    DB::raw('COALESCE(variation_store_details.qty_available, 0) as qty_available'),

                    DB::raw('COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax'),

                    'products.name',

                    'products.sku'

                )

                ->first();

            $output = [

                'success'       => true,

                'balance_stock' => $product->qty_available,

                'price'         => $product->sell_price_inc_tax,

                'product_name'  => $product->name,

                'code'          => $product->sku,

                'msg'           => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveOtherIncome(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return ['success' => false,

                    'msg'             => __('settlementsw::lang.date_greater_than_day_end'),

                ];

            }

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement_exist ? $settlement_exist->id : '',

                'product_id'    => $request->product_id,

                'qty'           => $request->qty,

                'price'         => $request->qty,

                'reason'        => $request->other_income_reason,

                'sub_total'     => $request->qty,

            ];

            $other_income = OtherIncome::create($data);

// Calculate total qty for the same business

            $total_qty = OtherIncome::where('business_id', $business_id)->sum('qty');

            Settlement::where('id', $settlement_exist->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'         => true,

                'other_income_id' => $other_income->id,

                'msg'             => __('SettlementSW::lang.success'),

                'data'            => [

                    'qty'                            => $other_income->qty,

                    'price'                          => $other_income->price,

                    'reason'                         => $other_income->reason,

                    'total_othe_income_for_business' => $total_qty,

                ],

            ];

        } catch (\Exception $e) {

            echo json_encode($e->getMessage());exit();

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveCustomerPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return ['success' => false,

                    'msg'             => __('settlementsw::lang.date_greater_than_day_end'),

                ];

            }

            $data = [

                'business_id'         => $business_id,

                'settlement_no'       => $settlement_exist ? $settlement_exist->id : '',

                'customer_id'         => $request->customer_id,

                'customer_payment_no' => $request->settlement_customer_payment_no,

                'payment_method'      => $request->payment_method,

                'cheque_date'         => ! empty($request->cheque_date) ? Carbon::parse($request->cheque_date)->format('Y-m-d') : null,

                'cheque_number'       => $request->cheque_number,

                'bank_name'           => $request->bank_name,

                'amount'              => $request->amount,

                'sub_total'           => $request->sub_total,

                'post_dated_cheque'   => $request->post_dated_cheque,

            ];

            DB::beginTransaction();

            $customer_payment = CustomerPayment::create($data);

            Settlement::where('id', $settlement_exist->id)->update(['is_edit' => request()->is_edit]);

            $account_type_id = null;

            if ($request->payment_method == 'cash') {

                $cash_data = [

                    'business_id'         => $business_id,

                    'settlement_no'       => $settlement_exist ? $settlement_exist->settlement_no : '',

                    'amount'              => $request->amount,

                    'customer_id'         => $request->customer_id,

                    'customer_payment_id' => $customer_payment->id,

                ];

                $settlement_cash_payment = app(SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, $settlement_exist ? $settlement_exist->settlement_no : '', 'settlement_cash_payments', $cash_data, 'customer_payment_id');

                $account_type_id = $settlement_cash_payment->id;

            }

            if ($request->payment_method == 'card') {

                $card_data = [

                    'business_id'         => $business_id,

                    'settlement_no'       => $settlement_exist ? $settlement_exist->settlement_no : '',

                    'amount'              => $request->amount,

                    'card_type'           => $request->card_type,

                    'card_number'         => $request->card_number,

                    'customer_id'         => $request->customer_id,

                    'customer_payment_id' => $customer_payment->id,

                ];

                $settlement_card_payment = app(SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, $settlement_exist ? $settlement_exist->settlement_no : '', 'settlement_card_payments', $card_data, 'customer_payment_id');

                $account_type_id = $settlement_card_payment->id;

            }

            if ($request->payment_method == 'cheque') {

                $cheque_data = [

                    'business_id'         => $business_id,

                    'settlement_no'       => $settlement_exist ? $settlement_exist->settlement_no : '',

                    'amount'              => $request->amount,

                    'bank_name'           => $request->bank_name,

                    'cheque_number'       => $request->cheque_number,

                    'cheque_date'         => ! empty($request->cheque_date) ? \Carbon::parse($request->cheque_date)->format('Y-m-d') : null,

                    'customer_id'         => $request->customer_id,

                    'customer_payment_id' => $customer_payment->id,

                ];

                $settlement_cheque_payment = app(SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, $settlement_exist ? $settlement_exist->settlement_no : '', 'settlement_cheque_payments', $cheque_data, 'customer_payment_id');

                $account_type_id = $settlement_cheque_payment->id;

            }

            DB::commit();

            $business = Business::where('id', $business_id)->first();

            $prefix = ! empty($business->ref_no_prefixes['settlement_customer_payment'])

                ? $business->ref_no_prefixes['settlement_customer_payment']

                : 'SW-CP';

            $starting_no = ! empty($business->ref_no_starting_number['settlement_customer_payment'])

                ? (int) $business->ref_no_starting_number['settlement_customer_payment']

                : 1;

            // Get the last settlement with prefix 'SW-CP'

            $last_customer_payment = CustomerPayment::where('business_id', $business_id)

                ->where('customer_payment_no', 'LIKE', $prefix . '%')

                ->whereNotNull('customer_payment_no')

                ->orderBy('id', 'DESC')

                ->first();

            if (! empty($last_customer_payment)) {

                $count = $this->extractLastInteger($last_customer_payment->customer_payment_no);

            } else {

                $count = 0;

            }

            $customer_payment_settlement_no = $prefix . (1 + $count);

            // Get customer name

            $customer_name = Contact::where('id', $request->customer_id)->value('name');

            // Get total payments for the customer

            $total_customer_payments = CustomerPayment::where('customer_id', $request->customer_id)

                ->where('business_id', $business_id)

                ->sum('amount');

            $total_business_payments = CustomerPayment::where('business_id', $business_id)

                ->sum('amount');

            $output = [

                'success'                 => true,

                'customer_payment_id'     => $customer_payment->id,

                'customer_name'           => $customer_name,

                'settlement_no'           => $customer_payment_settlement_no,

                'total_customer_payments' => $total_customer_payments,

                'total_business_payments' => $total_business_payments,

                'amount'                  => $request->amount,

                'payment_method'          => $request->payment_method,

                'account_type_payment'    => $account_type_id,

                'msg'                     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            // echo json_encode($e->getMessage());exit();

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveExpansePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $business = Business::where('id', $business_id)->first();

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement ? $settlement->settlement_no : '',

                'expense_number' => $request->expense_number,

                'category_id'    => $request->category_id,

                'reference_no'   => $request->reference_no,

                'account_id'     => $request->account_id,

                'reason'         => $request->reason,

                'amount'         => $request->amount,

            ];

            //Update reference count

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

            //Generate reference number

            if (empty($request->reference_no)) {

                $data['reference_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);

            }

            $settlement_expense_payment = SettlementExpensePayment::create($data);

            if (isset($settlement)) {

                Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            }

            $prefix = ! empty($business->ref_no_prefixes['settlement_expense'])

                ? $business->ref_no_prefixes['settlement_expense']

                : 'SW-EXP';

            $starting_no = ! empty($business->ref_no_starting_number['settlement_expense'])

                ? (int) $business->ref_no_starting_number['settlement_expense']

                : 1;

            // Get the last settlement with prefix 'SW-EXP'

            $last_expense_payment = SettlementExpensePayment::where('business_id', $business_id)

                ->where('expense_number', 'LIKE', $prefix . '%')

                ->orderBy('id', 'DESC')

                ->first();

            if (! empty($last_expense_payment)) {

                $count = $this->extractLastInteger($last_expense_payment->expense_number);

            } else {

                $count = 0;

            }

            $expense_payment_settlement_no = $prefix . (1 + $count);

            $output = [

                'success'                       => true,

                'expense_number'                => $expense_payment_settlement_no,

                'reference_no'                  => $settlement_expense_payment->reference_no,

                'settlement_expense_payment_id' => $settlement_expense_payment->id,

                'msg'                           => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveCreditSalePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->first();

            if (! $settlement) {
                $created = $this->createSettlementIfNotExist($request);

                if ($created === 406) {
                    return [
                        'success' => false,
                        'msg'     => __('messages.day_end_prevents_settlement'),
                    ];
                }

                if ($created instanceof Settlement) {
                    $settlement = $created;
                }
            }

            $currentSettlementId = $settlement ? $settlement->id : null;

            $orderNumberRules = ['required'];

            if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'same_order_no')) {
                $orderNumberRules[] = Rule::unique('settlement_credit_sale_payments', 'order_number')
                    ->where(function ($query) use ($request, $business_id, $currentSettlementId) {
                        $query->where('customer_id', $request->customer_id)
                            ->where('business_id', $business_id);

                        // Allow duplicates inside the current settlement; only guard against other settlements
                        if ($currentSettlementId) {
                            $query->where('settlement_no', '!=', $currentSettlementId);
                        }

                        return $query;
                    });
            }

            $validator = Validator::make($request->all(), [
                'order_number' => $orderNumberRules,
            ]);

            if ($validator->fails()) {

                return [

                    'success' => false,

                    'msg'     => $validator->errors()->first(),

                ];

            }

            if (! $settlement) {
                return [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $price = $this->productUtil->num_uf($request->price);

            $unit_discount = $this->productUtil->num_uf($request->unit_discount);

            $qty = $this->productUtil->num_uf($request->qty);

            $amount = $this->productUtil->num_uf($request->amount);

            $sub_total = $this->productUtil->num_uf($request->sub_total);

            $total_discount = $this->productUtil->num_uf($request->total_discount);

            $data = [

                'business_id'        => $business_id,

                'settlement_no'      => $settlement->id,

                'customer_id'        => $request->customer_id,

                'product_id'         => $request->product_id,

                'order_number'       => $request->order_number,

                'order_date'         => \Carbon::parse($request->order_date)->format('Y-m-d'),

                'price'              => $price,

                'discount'           => $unit_discount,

                'qty'                => $qty,

                'amount'             => $amount,

                'sub_total'          => $sub_total,

                'total_discount'     => $total_discount,

                'outstanding'        => $this->productUtil->num_uf($request->outstanding),

                'credit_limit'       => $request->credit_limit,

                'customer_reference' => $request->customer_reference,

                'note'               => $request->note,

            ];

            $settlement_credit_sale_payment = app(SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_credit_sale_payments', $data);

            // Calculate total credit sales for this business

            $total_credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)

                ->sum('sub_total');

            $output = [

                'success'                           => true,

                'settlement_credit_sale_payment_id' => $settlement_credit_sale_payment->id,

                'total_credit_sales'                => $total_credit_sales,

                'msg'                               => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            echo json_encode($e->getMessage());

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteMeterSale($id)
    {

        try {

            $meter_sale = MeterSale::where('id', $id)->first();

            Settlement::where('id', $meter_sale->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $meter_sale->discount_amount;

            $starting_meter = $meter_sale->starting_meter;

            $closing_meter = $meter_sale->closing_meter;

            $pump = Pump::where('id', $meter_sale->pump_id)->first();

            $tank_id = $pump->fuel_tank_id;

            FuelTank::where('id', $tank_id)->increment('current_balance', $meter_sale->qty);

            $meter_sale->delete();

            $pump->last_meter_reading = $starting_meter; //reset back to previous starting meter

            $previous_meter_sale = MeterSale::where('pump_id', $pump->id)->orderBy('id', 'desc')->first();

            if (! empty($previous_meter_sale)) {

                $pump->starting_meter = $previous_meter_sale->starting_meter;

            }

            $pump->save();

            $pump_name = $pump->pump_name;

            $pump_id = $pump->id;

            // delete pump operator commission

            PumpOperatorCommission::where('meter_sale_id', $id)->delete();

            $output = [

                'success'   => true,

                'amount'    => $amount,

                'pump_name' => $pump_name,

                'pump_id'   => $pump_id,

                'msg'       => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteOtherSale($id)
    {

        try {

            $other_sale = OtherSale::where('id', $id)->first();

            Settlement::where('id', $other_sale->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $other_sale->sub_total - $other_sale->discount_amount;

            $other_sale->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * Store a newly created settlement in storage.
     * This method is specifically for SettlementSW module and properly updates daily_collection settlement_id
     * and all related records (daily_cards, daily_vouchers, etc.)
     *
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        SettlementSwLog::info('SettlementSwBaseController@store called');

        DB::beginTransaction();
        try {
            $business_id            = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
            \App\Utils\PetroPdIsolationUtil::assertAllowedOutsidePetroPd(
                (int) $business_id,
                $request->input('pump_operator_id'),
                (array) $request->input('pump_id', [])
            );
            $user_id                = auth()->user()->id;
            $incoming_settlement_no = $request->settlement_no;
            $pump_operator_id       = $request->pump_operator_id ?? null;
            $no_change              = $request->no_change ?? null;

            // Normalize shift ids
            $shift_ids = $request->shift_ids
                ? (is_array($request->shift_ids) ? $request->shift_ids : explode(',', $request->shift_ids))
                : [];
            $shift_ids = array_values(array_filter(array_map('intval', $shift_ids)));

            //---------------------------------------------------
            // 1. Load or create settlement (preserve your create logic)
            //---------------------------------------------------
            $settlement = null;
            if (is_numeric($incoming_settlement_no)) {
                $settlement = Settlement::with([
                    'meter_sales',
                    'other_sales',
                    'other_incomes',
                    'customer_payments',
                    'cash_payments',
                    'cash_deposits',
                    'card_payments',
                    'cheque_payments',
                    'credit_sale_payments.product',
                    'expense_payments',
                    'excess_payments',
                    'shortage_payments',
                    'loan_payments',
                    'drawings_payments',
                    'customer_loans',
                    'daily_collections',
                    'daily_cards',
                    'daily_vouchers',
                ])->where('id', $incoming_settlement_no)->where('business_id', $business_id)->first();
            }
            if (! $settlement) {
                $settlement = Settlement::with([
                    'meter_sales',
                    'other_sales',
                    'other_incomes',
                    'customer_payments',
                    'cash_payments',
                    'cash_deposits',
                    'card_payments',
                    'cheque_payments',
                    'credit_sale_payments.product',
                    'expense_payments',
                    'excess_payments',
                    'shortage_payments',
                    'loan_payments',
                    'drawings_payments',
                    'customer_loans',
                    'daily_collections',
                    'daily_cards',
                    'daily_vouchers',
                ])->where('settlement_no', $incoming_settlement_no)->where('business_id', $business_id)->first();
            }

            if (! $settlement) {
                // Create a new settlement following your createSettlementIfNotExist behavior
                $created = $this->createSettlementIfNotExist($request);
                if ($created === 406) {
                    DB::rollBack();
                    return response(['success' => false, 'msg' => __('messages.day_end_prevents_settlement')], 406);
                }
                if ($created instanceof Settlement) {
                    $settlement = $created;
                } else {
                    DB::rollBack();
                    return response(['success' => false, 'msg' => 'Unable to create or fetch settlement'], 500);
                }
            }

            // final guard
            if (! $settlement) {
                DB::rollBack();
                return response(['success' => false, 'msg' => 'Settlement not found or could not be created'], 404);
            }

            // override if pump op passed in request
            $pump_operator_id = $pump_operator_id ?? $settlement->pump_operator_id;

            if ($settlement->is_edit == 0 && $settlement->status == 0 && empty($no_change)) {
                DB::rollBack();
                return ["success" => 0, "msg" => __("settlementsw::lang.no_change_performed")];
            }

            $settlementId  = $settlement->id;
            $settlement_no = $settlement->settlement_no;

            //---------------------------------------------------
            // 2. Resolve shift metadata from Settlement SW daily shifts
            //---------------------------------------------------
            $shiftNumbers = [];
            $shiftDates   = [];

            if (! empty($shift_ids)) {
                $shifts = DB::table(SettlementSwTables::dailyShifts())
                    ->where('business_id', $business_id)
                    ->whereIn('id', $shift_ids)
                    ->get(['id', 'shift_no', 'date']);

                $shiftNumbers = $shifts->pluck('shift_no')->filter()->values()->toArray();

                // Normalize the date values to Y-m-d for date fallbacks if present
                $shiftDates = $shifts->pluck('date')->filter()->map(function ($d) {
                    return date('Y-m-d', strtotime($d));
                })->unique()->values()->toArray();
            }

            //---------------------------------------------------
            // 3. Attach daily_collections -> use shift_id per Q1 (B)
            //---------------------------------------------------
            $dailyCollectionQuery = DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('type', 'daily_collection_sw')
                ->whereNull('settlement_id');

            if (! empty($shift_ids)) {
                $dailyCollectionQuery->whereIn('shift_id', $shift_ids);
            }
            // else leave as all unsettled for operator

            $daily_collections = $dailyCollectionQuery->get();

            foreach ($daily_collections as $dc) {
                DailyCollection::where('id', $dc->id)
                    ->whereNull('settlement_id')
                    ->update([
                        'settlement_id'      => $settlementId,
                        'settlement_date'    => now()->format('Y-m-d'),
                        'balance_collection' => $dc->current_amount,
                    ]);
            }

            //---------------------------------------------------
            // 4. Attach daily_cards -> prefer shift_id then shift_no then created_at
            //---------------------------------------------------
            $dailyCardHasShiftId = SettlementSwSchema::hasColumn('daily_cards', 'shift_id');
            $dailyCardHasShiftNo = SettlementSwSchema::hasColumn('daily_cards', 'shift_no') || SettlementSwSchema::hasColumn('daily_cards', 'shift_number');

            $dailyCardQuery = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->whereNull('settlement_no');

            if (! empty($shift_ids) && $dailyCardHasShiftId) {
                $dailyCardQuery->whereIn('shift_id', $shift_ids);
            } elseif (! empty($shiftNumbers) && $dailyCardHasShiftNo) {
                // shift_no in daily_cards might be stored in 'shift_no' or 'shift_number'
                if (SettlementSwSchema::hasColumn('daily_cards', 'shift_no')) {
                    $dailyCardQuery->whereIn('shift_no', $shiftNumbers);
                } elseif (SettlementSwSchema::hasColumn('daily_cards', 'shift_number')) {
                    $dailyCardQuery->whereIn('shift_number', $shiftNumbers);
                }
            } elseif (! empty($shiftDates)) {
                // fallback on created_at date
                $dailyCardQuery->whereIn(DB::raw('DATE(created_at)'), $shiftDates);
            }

            $daily_cards = $dailyCardQuery->get();
            if ($daily_cards->isNotEmpty()) {
                // update in one query: use where ids
                $dailyCardIds = $daily_cards->pluck('id')->toArray();
                DailyCard::whereIn('id', $dailyCardIds)->whereNull('settlement_no')->update([
                    'settlement_no' => $settlement_no,
                    'updated_at'    => now(),
                ]);
            }

            //---------------------------------------------------
            // 5. Attach daily_vouchers -> prefer shift_id then shift_no then created_at
            //---------------------------------------------------
            $dailyVoucherHasShiftId = SettlementSwSchema::hasColumn('daily_vouchers', 'shift_id');
            $dailyVoucherHasShiftNo = SettlementSwSchema::hasColumn('daily_vouchers', 'shift_no') || SettlementSwSchema::hasColumn('daily_vouchers', 'shift_number');

            $dailyVoucherQuery = DailyVoucher::where('business_id', $business_id)
                ->where('operator_id', $pump_operator_id)
                ->whereNull('settlement_no');

            if (! empty($shift_ids) && $dailyVoucherHasShiftId) {
                $dailyVoucherQuery->whereIn('shift_id', $shift_ids);
            } elseif (! empty($shiftNumbers) && $dailyVoucherHasShiftNo) {
                if (SettlementSwSchema::hasColumn('daily_vouchers', 'shift_no')) {
                    $dailyVoucherQuery->whereIn('shift_no', $shiftNumbers);
                } elseif (SettlementSwSchema::hasColumn('daily_vouchers', 'shift_number')) {
                    $dailyVoucherQuery->whereIn('shift_number', $shiftNumbers);
                }
            } elseif (! empty($shiftDates)) {
                $dailyVoucherQuery->whereIn(DB::raw('DATE(created_at)'), $shiftDates);
            }

            $daily_vouchers = $dailyVoucherQuery->get();
            if ($daily_vouchers->isNotEmpty()) {
                $dailyVoucherIds = $daily_vouchers->pluck('id')->toArray();
                DailyVoucher::whereIn('id', $dailyVoucherIds)->whereNull('settlement_no')->update([
                    'settlement_no' => $settlement_no,
                    'updated_at'    => now(),
                ]);
            }

            //---------------------------------------------------
            // 6. Attach cash/card/cheque/credit-sale payments created directly into settlement_* tables
            //    We use shiftDates as filter if present to avoid incorrectly grabbing unrelated payments.
            //---------------------------------------------------
            // Prepare date filter for settlement_* rows (if shiftDates present)
            if (! empty($shiftDates)) {
                // Cash payments
                SettlementCashPayment::where('business_id', $business_id)
                    ->whereNull('settlement_no')
                    ->whereIn(DB::raw('DATE(created_at)'), $shiftDates)
                    ->update(['settlement_no' => $settlement_no]);

                // Card payments
                SettlementCardPayment::where('business_id', $business_id)
                    ->whereNull('settlement_no')
                    ->whereIn(DB::raw('DATE(created_at)'), $shiftDates)
                    ->update(['settlement_no' => $settlement_no]);

                // Cheque payments
                SettlementChequePayment::where('business_id', $business_id)
                    ->whereNull('settlement_no')
                    ->whereIn(DB::raw('DATE(created_at)'), $shiftDates)
                    ->update(['settlement_no' => $settlement_no]);

                $credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNull('settlement_no')
                    ->whereIn(DB::raw('DATE(created_at)'), $shiftDates)
                    ->get();

                foreach ($credit_sales as $cs) {
                    $cs->settlement_no = $settlement_no;
                    $cs->save(); // triggers Eloquent model, now relationships work
                }

            } else {

                $credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->whereNull('settlement_no')
                    ->get();

                foreach ($credit_sales as $cs) {
                    $cs->settlement_no = $settlement_no;
                    $cs->save(); // triggers Eloquent model, now relationships work
                }

            }

            //---------------------------------------------------
            // 7. Close pumper day entries (filter by shiftDates if available)
            //---------------------------------------------------
            $pdeQuery = PumperDayEntry::where('pump_operator_id', $pump_operator_id)
                ->where(function ($q) use ($settlement_no, $settlementId) {
                    $q->where('settlement_no', $settlement_no)
                        ->orWhere('settlement_no', $settlementId)
                        ->orWhereNull('settlement_no');
                });

            if (! empty($shiftDates)) {
                $pdeQuery->whereIn(DB::raw('DATE(created_at)'), $shiftDates);
            }
            $pdeQuery->update([
                'settlement_no'        => $settlement_no,
                'settlement_added_by'  => $user_id,
                'closed_in_settlement' => 1,
            ]);

            //---------------------------------------------------
            // 8. Mark Settlement SW daily shifts as closed (or update status) for selected shifts
            //---------------------------------------------------
            if (! empty($shift_ids)) {
                DB::table(SettlementSwTables::dailyShifts())->whereIn('id', $shift_ids)->update(['status' => 0]);
            }

            //---------------------------------------------------
            // 9. Create account transactions / sell transactions (FULL logic)
            //    This reuses your createTransaction/createSellTransactions helpers.
            //---------------------------------------------------
            $settlement = Settlement::with([
                'meter_sales',
                'other_sales',
                'other_incomes',
                'cash_payments',
                'card_payments',
                'cheque_payments',
                'credit_sale_payments.product',
                'expense_payments',
                'excess_payments',
                'shortage_payments',
                'loan_payments',
                'drawings_payments',
                'customer_loans',
                'daily_collections',
                'daily_cards',
                'daily_vouchers',
                'cash_deposits',
            ])->find($settlementId);

            // compute totals for meter_sales / other_sales / other_incomes (same logic as create)
            $total_sales_amount = $settlement->meter_sales->sum('sub_total') +
            $settlement->other_sales->sum('sub_total') +
            $settlement->other_incomes->sum('sub_total');

            $total_sales_discount_amount = $settlement->meter_sales->sum('discount_amount') +
            $settlement->other_sales->sum('discount_amount');

            if ($total_sales_amount > 0) {
                // Create main sell transaction (reusing your createTransaction)
                $sell_transaction = $this->createTransaction(
                    $settlement,
                    $total_sales_amount,
                    null,
                    $settlement->pump_operator_id ?? $pump_operator_id,
                    'sell',
                    'settlement',
                    $settlement_no,
                    null,
                    0,
                    $total_sales_discount_amount
                );

                // Create sell lines for meter sales
                foreach ($settlement->meter_sales as $meter_sale) {
                    // avoid duplicate transaction lines
                    if (! empty($meter_sale->transaction_id) && $meter_sale->transaction_id == $sell_transaction->id) {
                        continue;
                    }
                    $fuel_tank_id = Pump::where('id', $meter_sale->pump_id)->value('fuel_tank_id');
                    /*
                     * IS1994: tag this as a meter sale so stock is reduced.
                     *
                     * This passed null. In createSellTransactions() the stock
                     * decrement sits behind
                     *   "if ($product->enable_stock && ! empty($is_other_sale))"
                     * so a null meant fuel meter sales wrote their sell line and
                     * decremented the fuel tank balance, but never reduced
                     * variation_location_details.qty_available - the column
                     * Stock Center's Available shows. Other sales pass true,
                     * which is why only fuel stood still.
                     *
                     * $sale->qty is the SOLD quantity; testing_qty lives in its
                     * own column and is deliberately excluded.
                     */
                    $this->createSellTransactions(
                        $sell_transaction,
                        $meter_sale,
                        $business_id,
                        $settlement->location_id ?? null,
                        $fuel_tank_id,
                        'meter_sale'
                    );
                    MeterSale::where('id', $meter_sale->id)->update(['transaction_id' => $sell_transaction->id]);
                }

                // Create sell lines for other_sales
                foreach ($settlement->other_sales as $other_sale) {
                    $getOtherSale = OtherSale::where('id', $other_sale->id)->first();
                    if ($getOtherSale && ($getOtherSale->transaction_id == null || $getOtherSale->transaction_id != $sell_transaction->id)) {
                        $this->createSellTransactions(
                            $sell_transaction,
                            $other_sale,
                            $business_id,
                            $settlement->location_id ?? null,
                            null,
                            true
                        );
                        OtherSale::where('id', $other_sale->id)->update(['transaction_id' => $sell_transaction->id]);
                    }
                }

                // Create sell lines for other_incomes
                foreach ($settlement->other_incomes as $other_income) {
                    $getOtherIncome = OtherIncome::where('id', $other_income->id)->first();
                    if ($getOtherIncome && ($getOtherIncome->transaction_id == null || $getOtherIncome->transaction_id != $sell_transaction->id)) {
                        $this->createSellTransactions(
                            $sell_transaction,
                            $other_income,
                            $business_id,
                            $settlement->location_id ?? null,
                            null,
                            null
                        );
                        OtherIncome::where('id', $other_income->id)->update(['transaction_id' => $sell_transaction->id]);
                    }
                }

                // refresh and create stock/account transactions using your helper
                $sell_transaction->refresh();
                $sell_transaction->load('sell_lines');

                // create stock accounting entries for the sell (your helper)
                $this->createStockAccountTransactions($sell_transaction);

                // map sell purchase lines if needed
                $this->mapSellPurchaseLines($business_id, $sell_transaction, $settlement);
            }

            //---------------------------------------------------
            // 10. Credit sale transactions (as you had before)
            //---------------------------------------------------
            foreach ($settlement->credit_sale_payments as $credit_sale_payment) {
                if (! empty($credit_sale_payment->transaction_id)) {
                    continue;
                }

                $credit_transaction = $this->createCreditSellTransactions(
                    $settlement,
                    $credit_sale_payment,
                    $settlement->location_id ?? null
                );

                SettlementCreditSalePayment::where('id', $credit_sale_payment->id)->update(['transaction_id' => $credit_transaction->id]);

                $account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                if (! empty($account_id)) {
                    $type        = 'debit';
                    $description = 'Settlement No: ' . $settlement->settlement_no . ' | Credit Sale';
                    if (! empty($credit_sale_payment->note)) {
                        $description .= ' | ' . $credit_sale_payment->note;
                    }
                    $this->createAccountTransaction(
                        $credit_transaction,
                        $type,
                        $account_id,
                        null,
                        'ledger_show',
                        null,
                        0,
                        true,
                        $description
                    );
                }
            }

            //---------------------------------------------------
            // 10A. Link Deposits from Accounting Module (by shift_number)
            //     These are deposits made via Accounting → Manage Account → Cash Deposit
            //---------------------------------------------------
            $depositTransactions = AccountTransaction::where('business_id', $business_id)
                ->where('sub_type', 'deposit')
                ->where('type', 'credit')
                ->where(function($q) {
                    // Only get deposits that haven't been linked to a settlement yet
                    $q->whereNull('note')
                      ->orWhere('note', 'NOT LIKE', '%Settlement No:%');
                });
                
            if (! empty($shift_ids)) {
                $shiftNumbers = DB::table(SettlementSwTables::dailyShifts())
                    ->whereIn('id', $shift_ids)
                    ->pluck('shift_no')
                    ->toArray();

                if (! empty($shiftNumbers)) {
                    $depositTransactions->whereIn('shift_number', $shiftNumbers);
                }
            }
            $depositTransactions = $depositTransactions->get();

            foreach ($depositTransactions as $txn) {
                // Add settlement number to the note fieldF
                $note = $txn->note ?? '';
                $note .= $note ? ' | ' : ''; // add separator if note exists
                $note .= 'Settlement No: ' . $settlement_no;

                $txn->update([
                    'note' => $note,
                ]);

                // Ensure paired debit exists for cash account and update note
                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');

                if (!empty($txn->transfer_transaction_id)) {
                    $pairedTxn = AccountTransaction::find($txn->transfer_transaction_id);
                    if ($pairedTxn) {
                        $pairedNote = $pairedTxn->note ?? '';
                        $pairedNote .= $pairedNote ? ' | ' : '';
                        $pairedNote .= 'Settlement No: ' . $settlement_no;
                        
                        $pairedTxn->update([
                            'note' => $pairedNote,
                        ]);
                    }
                } else {
                    // Create missing debit entry on cash account so cash book shows both sides
                    $debit_cash = AccountTransaction::createAccountTransaction([
                        'amount' => $txn->amount,
                        'account_id' => $cash_account_id,
                        'business_id' => $business_id,
                        'type' => 'debit',
                        'sub_type' => 'deposit',
                        'operation_date' => $txn->operation_date,
                        'created_by' => $txn->created_by,
                        'transaction_id' => $txn->transaction_id,
                        'transaction_payment_id' => $txn->transaction_payment_id,
                        'transfer_transaction_id' => $txn->id,
                        'note' => $note,
                    ]);

                    if ($debit_cash) {
                        $txn->transfer_transaction_id = $debit_cash->id;
                        $txn->save();
                    }
                }
            }

            //---------------------------------------------------
            // 10B. Process Cash Deposits from Settlement SW Tab
            //      These are deposits added via Settlement SW → Cash Deposits tab
            //      Pattern: Credit Bank (money enters), Debit Cash (money leaves)
            //---------------------------------------------------
            foreach ($settlement->cash_deposits as $cash_deposit) {
                // Create transaction for cash deposit
                $deposit_transaction = $this->createTransaction(
                    $settlement,
                    $cash_deposit->amount,
                    null,
                    null,
                    'settlement',
                    'cash_deposit',
                    $settlement_no,
                    $cash_deposit->id
                );

                // Create transaction payment
                $deposit_transaction_payment = $this->createTransactionPayment($deposit_transaction, 'cash');

                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $bank_account_id = $cash_deposit->bank_id;
                
                // Validate bank account exists
                if (empty($bank_account_id) || $bank_account_id == 0) {
                    Log::warning('Cash deposit has invalid bank_id', [
                        'cash_deposit_id' => $cash_deposit->id,
                        'bank_id' => $cash_deposit->bank_id,
                        'settlement_no' => $settlement_no
                    ]);
                    continue; // Skip this deposit if bank_id is invalid
                }

                // Validate accounts exist and belong to business
                $bank_account = Account::where('id', $bank_account_id)
                    ->where('business_id', $business_id)
                    ->first();
                
                if (!$bank_account) {
                    Log::warning('Cash deposit bank account not found or wrong business', [
                        'cash_deposit_id' => $cash_deposit->id,
                        'bank_id' => $bank_account_id,
                        'settlement_no' => $settlement_no,
                        'business_id' => $business_id
                    ]);
                    continue;
                }

                if (empty($cash_account_id)) {
                    Log::warning('Cash account not found', [
                        'cash_deposit_id' => $cash_deposit->id,
                        'settlement_no' => $settlement_no,
                        'business_id' => $business_id
                    ]);
                    continue;
                }

                SettlementSwLog::info('Creating account transactions for cash deposit', [
                    'cash_deposit_id' => $cash_deposit->id,
                    'settlement_no' => $settlement_no,
                    'amount' => $cash_deposit->amount,
                    'bank_account_id' => $bank_account_id,
                    'bank_account_name' => $bank_account->name,
                    'cash_account_id' => $cash_account_id,
                    'business_id' => $business_id
                ]);

                // Credit Bank Account (money enters bank) - sub_type = 'deposit'
                $credit_bank = AccountTransaction::createAccountTransaction([
                    'amount' => $cash_deposit->amount,
                    'account_id' => $bank_account_id,
                    'business_id' => $business_id, // Explicitly set business_id
                    'type' => 'credit',
                    'sub_type' => 'deposit', // Required for account book to recognize it
                    'operation_date' => $deposit_transaction->transaction_date,
                    'created_by' => $deposit_transaction->created_by,
                    'transaction_id' => $deposit_transaction->id,
                    'transaction_payment_id' => $deposit_transaction_payment->id,
                    'note' => "Settlement No: $settlement_no \nCash Deposit",
                ]);

                if (!$credit_bank) {
                    Log::error('Failed to create credit bank account transaction', [
                        'cash_deposit_id' => $cash_deposit->id,
                        'bank_account_id' => $bank_account_id
                    ]);
                    continue;
                }

                // Debit Cash Account (money leaves cash) - sub_type = 'deposit', linked via transfer_transaction_id
                // IMPORTANT: For Cash account, cash deposits must be DEBIT (money going out)
                $debit_cash = AccountTransaction::createAccountTransaction([
                    'amount' => $cash_deposit->amount,
                    'account_id' => $cash_account_id, // Must be Cash account ID
                    'business_id' => $business_id, // Explicitly set business_id
                    'type' => 'debit', // DEBIT for Cash account (money leaving)
                    'sub_type' => 'deposit', // Required for account book to recognize it
                    'operation_date' => $deposit_transaction->transaction_date,
                    'created_by' => $deposit_transaction->created_by,
                    'transaction_id' => $deposit_transaction->id,
                    'transaction_payment_id' => $deposit_transaction_payment->id,
                    'transfer_transaction_id' => $credit_bank->id, // Link the two entries
                    'note' => "Settlement No: $settlement_no \nCash Deposit",
                ]);
                
                // Verify the debit transaction was created correctly
                if ($debit_cash && $debit_cash->type !== 'debit') {
                    Log::error('Cash deposit debit transaction has wrong type', [
                        'cash_deposit_id' => $cash_deposit->id,
                        'expected_type' => 'debit',
                        'actual_type' => $debit_cash->type,
                        'account_id' => $debit_cash->account_id,
                        'cash_account_id' => $cash_account_id
                    ]);
                    // Fix it
                    $debit_cash->type = 'debit';
                    $debit_cash->save();
                }

                if (!$debit_cash) {
                    Log::error('Failed to create debit cash account transaction', [
                        'cash_deposit_id' => $cash_deposit->id,
                        'cash_account_id' => $cash_account_id
                    ]);
                    // Rollback the credit transaction if debit fails
                    if ($credit_bank) {
                        $credit_bank->delete();
                    }
                    continue;
                }

                // Update credit entry with transfer_transaction_id for bidirectional link
                $credit_bank->transfer_transaction_id = $debit_cash->id;
                $credit_bank->save();

                SettlementSwLog::info('Successfully created account transactions for cash deposit', [
                    'cash_deposit_id' => $cash_deposit->id,
                    'credit_bank_id' => $credit_bank->id,
                    'debit_cash_id' => $debit_cash->id,
                    'bank_account_id' => $bank_account_id,
                    'cash_account_id' => $cash_account_id
                ]);
            }

            //---------------------------------------------------
            // 11. Process Expenses - Create Account Transactions
            //---------------------------------------------------
            foreach ($settlement->expense_payments as $expense_payment) {
               
                $expense_transaction = $this->createTransaction(
                    $settlement,
                    $expense_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    'settlement',
                    'expense',
                    $settlement_no
                );

                $expense_transaction->expense_category_id = $expense_payment->category_id;
                $expense_transaction->ref_no = 'Settlement No: ' . $settlement->settlement_no;
                $expense_transaction->expense_account = $expense_payment->account_id;
                $expense_transaction->save();

               
                SettlementExpensePayment::where('id', $expense_payment->id)
                    ->update(['transaction_id' => $expense_transaction->id]);

               
                $expense_transaction_payment = $this->createTransactionPayment($expense_transaction, 'cash');

               
                $this->createAccountTransaction(
                    $expense_transaction,
                    'debit',
                    $expense_payment->account_id,
                    $expense_transaction_payment->id,
                    null,
                    null,
                    0,
                    false,
                    'Settlement No: ' . $settlement_no . ' | Expense: ' . ($expense_payment->note ?? '')
                );

              
                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $this->createAccountTransaction(
                    $expense_transaction,
                    'credit',
                    $cash_account_id,
                    $expense_transaction_payment->id,
                    null,
                    null,
                    0,
                    false,
                    'Settlement No: ' . $settlement_no . ' | Expense Payment'
                );
            }

            //---------------------------------------------------
            // 12. Process Loan Payments - Create Account Transactions
            //---------------------------------------------------
            foreach ($settlement->loan_payments as $loan_payment) {
                // Create transaction for loan payment
                $loan_transaction = $this->createTransaction(
                    $settlement,
                    $loan_payment->amount,
                    null,
                    null,
                    'settlement',
                    'loan_payment',
                    $settlement_no
                );

                $loan_transaction_payment = $this->createTransactionPayment($loan_transaction, 'cash');

                // Proper double-entry accounting for loan payment:
                // When you pay a loan, cash goes out (Credit) and loan liability reduces (Debit)
                
                // CREDIT Cash Account (Asset decreases - money going out)
                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                // Format note for account book display - Show: Settlement No: [settlement_no] on first line, Loan Payment on second line
                $loan_payment_note = "Settlement No: $settlement_no\nLoan Payment";
                
                $this->createAccountTransaction(
                    $loan_transaction,
                    'credit',
                    $cash_account_id,
                    $loan_transaction_payment->id,
                    null,
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment_note
                );

                // DEBIT Loan Account (Liability decreases - loan reduced)
                $this->createAccountTransaction(
                    $loan_transaction,
                    'debit',
                    $loan_payment->loan_account,
                    $loan_transaction_payment->id,
                    null,
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment_note
                );
            }

            //---------------------------------------------------
            // Process Cash Payments - Create Account Transactions
            //---------------------------------------------------
            foreach ($settlement->cash_payments as $cash_payment) {
                // Check if account transaction already exists for this SettlementCashPayment
                // This prevents duplicates when the same payment is processed multiple times
                // Same fix as applied to Settlement PD
                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $operation_date = \Carbon::parse($settlement->transaction_date)->format('Y-m-d');
                
                // CRITICAL: Check if account transaction already exists for this SettlementCashPayment
                // This prevents duplicates when payments are added via modal and then settlement is saved
                $existing_account_transaction = null;
                
                // Method 1: Check if there's already a TransactionPayment linked via CustomerPayment (for payments added via modal)
                if (!$existing_account_transaction && !empty($cash_payment->customer_payment_id)) {
                    $customer_payment = \Modules\SettlementSW\Entities\CustomerPayment::find($cash_payment->customer_payment_id);
                    if ($customer_payment) {
                        $existing_tp = \App\TransactionPayment::where('business_id', $business_id)
                            ->where('amount', $cash_payment->amount)
                            ->where('method', 'cash')
                            ->where(function($q) {
                                $q->where('paid_in_type', 'settlement')
                                  ->orWhereNull('paid_in_type');
                            })
                            ->whereHas('account_transactions', function($q) use ($cash_account_id, $operation_date, $settlement_no) {
                                $q->where('account_id', $cash_account_id)
                                  ->where('type', 'debit')
                                  ->where(function($subQ) {
                                      $subQ->where('sub_type', 'cash_payment')
                                          ->orWhere('sub_type', 'settlement_cash_payment');
                                  })
                                  ->where('operation_date', $operation_date)
                                  ->whereRaw('note LIKE ?', ['%Settlement No: ' . $settlement_no . '%']);
                            })
                            ->first();
                        
                        if ($existing_tp) {
                            $existing_account_transaction = $existing_tp->account_transactions()
                                ->where('account_id', $cash_account_id)
                                ->where('type', 'debit')
                                ->where(function($q) {
                                    $q->where('sub_type', 'cash_payment')
                                      ->orWhere('sub_type', 'settlement_cash_payment');
                                })
                                ->first();
                        }
                    }
                }
                
                // Method 2: Check by SettlementCashPayment ID, amount, date, and settlement_no
                if (!$existing_account_transaction) {
                    $existing_account_transaction = \App\AccountTransaction::where('business_id', $business_id)
                        ->where('account_id', $cash_account_id)
                        ->where('type', 'debit')
                        ->where(function($q) {
                            $q->where('sub_type', 'cash_payment')
                              ->orWhere('sub_type', 'settlement_cash_payment');
                        })
                        ->where('amount', $cash_payment->amount)
                        ->where('operation_date', $operation_date)
                        ->where(function($q) use ($settlement_no, $cash_payment) {
                            $q->whereRaw('note LIKE ?', ['%Settlement No: ' . $settlement_no . '%'])
                              ->where(function($subQ) use ($cash_payment) {
                                  if (!empty($cash_payment->customer_payment_id)) {
                                      $subQ->whereRaw('note LIKE ?', ['%Cash Payment%'])
                                           ->orWhereRaw('note LIKE ?', ['%settlement%']);
                                  }
                                  if (!empty($cash_payment->daily_collection_id)) {
                                      $subQ->orWhereRaw('note LIKE ?', ['%Daily Collection%']);
                                  }
                              });
                        })
                        ->first();
                }
                
                if ($existing_account_transaction) {
                    SettlementSwLog::info('Settlement SW: Skipping duplicate account transaction for SettlementCashPayment', [
                        'settlement_no' => $settlement_no,
                        'cash_payment_id' => $cash_payment->id,
                        'existing_account_transaction_id' => $existing_account_transaction->id,
                        'amount' => $cash_payment->amount,
                        'customer_payment_id' => $cash_payment->customer_payment_id ?? null,
                        'daily_collection_id' => $cash_payment->daily_collection_id ?? null,
                    ]);
                    continue; // Skip creating duplicate transaction
                }
                
                // Create transaction for cash payment
                $cash_transaction = $this->createTransaction(
                    $settlement,
                    $cash_payment->amount,
                    $cash_payment->customer_id,
                    null,
                    'settlement',
                    'cash_payment',
                    $settlement_no,
                    $cash_payment->id
                );

                $cash_transaction_payment = $this->createTransactionPayment($cash_transaction, 'cash');
                
                if (!empty($cash_account_id)) {
                    // Debit Cash Account (Money In)
                    \App\AccountTransaction::createAccountTransaction([
                        'amount' => $cash_payment->amount,
                        'account_id' => $cash_account_id,
                        'business_id' => $business_id,
                        'type' => 'debit',
                        'sub_type' => 'cash_payment',
                        'operation_date' => $cash_transaction->transaction_date,
                        'created_by' => $cash_transaction->created_by,
                        'transaction_id' => $cash_transaction->id,
                        'transaction_payment_id' => $cash_transaction_payment->id,
                        'note' => 'Settlement No: ' . $settlement_no . ' | Cash Payment' . (!empty($cash_payment->note) ? ' | ' . $cash_payment->note : ''),
                    ]);
                }
            }

            //---------------------------------------------------
            // Process Card Payments - Create Account Transactions
            //---------------------------------------------------
            foreach ($settlement->card_payments as $card_payment) {
                // Create transaction for card payment
                $card_transaction = $this->createTransaction(
                    $settlement,
                    $card_payment->amount,
                    $card_payment->customer_id,
                    null,
                    'settlement',
                    'card_payment',
                    $settlement_no,
                    $card_payment->id
                );

                $card_transaction_payment = $this->createTransactionPayment($card_transaction, 'card');

                // Find Card Account
                // Usually Card payments go to a specific Card Account (Asset)
                $card_account_id = null;
                // Try to find a card account tailored to the card type if possible, or generic "Card"
                
                // Assuming we might have a specific account based on card type or just a generic one.
                // For now, let's look for "Card" account group or specific Card account.
                // The Helper might have logic, but here we can try 'Card' or similar.
                $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
                if ($card_group) {
                     $card_account = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->first();
                     if ($card_account) {
                         $card_account_id = $card_account->id;
                     }
                }
                
                if (!empty($card_payment->card_type)) {
                     $card_account_id = $card_payment->card_type;
                } else {
                    // Fallback to "Cards (Credit Debit) Account" if exists
                    if (!$card_account_id) {
                         $card_account_id = $this->transactionUtil->account_exist_return_id('Cards (Credit Debit) Account');
                    }
                }

                if (!empty($card_account_id)) {
                     // Debit Card Account (Money In)
                     AccountTransaction::createAccountTransaction([
                        'amount' => $card_payment->amount,
                        'account_id' => $card_account_id,
                        'business_id' => $business_id,
                        'type' => 'debit',
                        'sub_type' => 'card_payment',
                        'operation_date' => $card_transaction->transaction_date,
                        'created_by' => $card_transaction->created_by,
                        'transaction_id' => $card_transaction->id,
                        'transaction_payment_id' => $card_transaction_payment->id,
                        'note' => 'Settlement No: ' . $settlement_no . ' | Card Payment' . (!empty($card_payment->note) ? ' | ' . $card_payment->note : ''),
                     ]);
                }
            }

            //---------------------------------------------------
            // 12A. Process Cheque Payments - Create Account Transactions
            //      Pattern: Credit Cheques in Hand (cheque received)
            //---------------------------------------------------
            foreach ($settlement->cheque_payments as $cheque_payment) {
                // Create transaction for cheque payment
                $cheque_transaction = $this->createTransaction(
                    $settlement,
                    $cheque_payment->amount,
                    null,
                    null,
                    'settlement',
                    'cheque_payment',
                    $settlement_no,
                    $cheque_payment->id
                );

                $cheque_transaction_payment = $this->createTransactionPayment($cheque_transaction, 'cheque');

                // Get Cheques in Hand account
                $cheque_account_id = $this->transactionUtil->account_exist_return_id('Cheques in Hand');
                
                if (empty($cheque_account_id)) {
                    Log::warning('Cheques in Hand account not found', [
                        'cheque_payment_id' => $cheque_payment->id,
                        'settlement_no' => $settlement_no
                    ]);
                    continue;
                }

                // Credit Cheques in Hand Account (cheque received)
                AccountTransaction::createAccountTransaction([
                    'amount' => $cheque_payment->amount,
                    'account_id' => $cheque_account_id,
                    'business_id' => $business_id,
                    'type' => 'debit',
                    'sub_type' => 'cheque_payment',
                    'operation_date' => $cheque_transaction->transaction_date,
                    'created_by' => $cheque_transaction->created_by,
                    'transaction_id' => $cheque_transaction->id,
                    'transaction_payment_id' => $cheque_transaction_payment->id,
                    'cheque_number' => $cheque_payment->cheque_number ?? null,
                    'note' => 'Settlement No: ' . $settlement_no . ' | Cheque Payment' . (!empty($cheque_payment->note) ? ' | ' . $cheque_payment->note : ''),
                ]);

                SettlementSwLog::info('Successfully created account transaction for cheque payment', [
                    'cheque_payment_id' => $cheque_payment->id,
                    'cheque_account_id' => $cheque_account_id,
                    'amount' => $cheque_payment->amount
                ]);
            }

            //---------------------------------------------------
            // 12B. Process Customer Loans - Create Account Transactions
            //      Pattern: Debit Accounts Receivable (money owed by customer), Credit Cash (money given)
            //---------------------------------------------------
            foreach ($settlement->customer_loans as $customer_loan) {
                // Create transaction for customer loan
                $loan_transaction = $this->createTransaction(
                    $settlement,
                    $customer_loan->amount,
                    $customer_loan->customer_id,
                    null,
                    'settlement',
                    'customer_loan',
                    $settlement_no,
                    $customer_loan->id
                );

                $loan_transaction_payment = $this->createTransactionPayment($loan_transaction, 'cash');

                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

                if (empty($cash_account_id) || empty($accounts_receivable_id)) {
                    Log::warning('Cash or Accounts Receivable account not found', [
                        'customer_loan_id' => $customer_loan->id,
                        'settlement_no' => $settlement_no,
                        'cash_account_id' => $cash_account_id,
                        'accounts_receivable_id' => $accounts_receivable_id
                    ]);
                    continue;
                }

                // Credit Cash Account (money going out)
                $customer_name = 'Walk in customer';
                if (!empty($customer_loan->customer_id)) {
                    $customer = Contact::find($customer_loan->customer_id);
                    if ($customer) {
                        $customer_name = $customer->name;
                    }
                }

                // Format note for account book display - Show: Settlement No: [settlement_no] on first line, 
                // Loan to customer on second line, Customer: [customer_name] on third line, Cash payment: [amount] on fourth line
                $formatted_amount = number_format($customer_loan->amount, 2);
                $loan_note = "Settlement No: $settlement_no\nLoan to customer\nCustomer: $customer_name\nCash payment: $formatted_amount";

                // Credit Cash Account (money going out)
                AccountTransaction::createAccountTransaction([
                    'amount' => $customer_loan->amount,
                    'account_id' => $cash_account_id,
                    'business_id' => $business_id,
                    'type' => 'credit',
                    'sub_type' => 'customer_loan',
                    'operation_date' => $loan_transaction->transaction_date,
                    'created_by' => $loan_transaction->created_by,
                    'transaction_id' => $loan_transaction->id,
                    'transaction_payment_id' => $loan_transaction_payment->id,
                    'note' => $loan_note,
                ]);

                // Debit Accounts Receivable Account (money owed by customer) - using helper to create ledger entry
                // Use same format for Accounts Receivable account book
                $loan_note_ar = "Settlement No: $settlement_no\nLoan to customer\nCustomer: $customer_name\nCash payment: $formatted_amount";
                $this->createAccountTransaction(
                    $loan_transaction,
                    'debit',
                    $accounts_receivable_id,
                    $loan_transaction_payment->id,
                    'ledger_show', // This will create customer ledger entry
                    $customer_loan->customer_id,
                    $customer_loan->amount,
                    false,
                    false,
                    $loan_note_ar
                );

                SettlementSwLog::info('Successfully created account transactions for customer loan', [
                    'customer_loan_id' => $customer_loan->id,
                    'cash_account_id' => $cash_account_id,
                    'accounts_receivable_id' => $accounts_receivable_id,
                    'customer_id' => $customer_loan->customer_id,
                    'amount' => $customer_loan->amount
                ]);
            }

            //---------------------------------------------------
            // 12C. Process Owner Drawings - Create Account Transactions
            //      Pattern: Debit Owner Drawings Account (drawing increases), Credit Cash (money going out)
            //---------------------------------------------------
            foreach ($settlement->drawings_payments as $drawing_payment) {
                // Create transaction for owner drawing
                $drawing_transaction = $this->createTransaction(
                    $settlement,
                    $drawing_payment->amount,
                    null,
                    null,
                    'settlement',
                    'owner_drawing',
                    $settlement_no,
                    $drawing_payment->id
                );

                $drawing_transaction_payment = $this->createTransactionPayment($drawing_transaction, 'cash');

                $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                $owner_drawings_account_id = $drawing_payment->loan_account ?? null; // Assuming loan_account field stores the owner drawings account ID

                if (empty($owner_drawings_account_id)) {
                    // Try to find Owner Drawings account by name
                    $owner_drawings_account = Account::where('business_id', $business_id)
                        ->where(function($q) {
                            $q->where('name', 'LIKE', '%Owner%Drawing%')
                              ->orWhere('name', 'LIKE', '%Drawing%')
                              ->orWhere('name', 'LIKE', '%Owner%');
                        })
                        ->first();
                    
                    if ($owner_drawings_account) {
                        $owner_drawings_account_id = $owner_drawings_account->id;
                    }
                }

                if (empty($cash_account_id) || empty($owner_drawings_account_id)) {
                    Log::warning('Cash or Owner Drawings account not found', [
                        'drawing_payment_id' => $drawing_payment->id,
                        'settlement_no' => $settlement_no,
                        'cash_account_id' => $cash_account_id,
                        'owner_drawings_account_id' => $owner_drawings_account_id
                    ]);
                    continue;
                }

                // Credit Cash Account (money going out)
                AccountTransaction::createAccountTransaction([
                    'amount' => $drawing_payment->amount,
                    'account_id' => $cash_account_id,
                    'business_id' => $business_id,
                    'type' => 'credit',
                    'sub_type' => 'owner_drawing',
                    'operation_date' => $drawing_transaction->transaction_date,
                    'created_by' => $drawing_transaction->created_by,
                    'transaction_id' => $drawing_transaction->id,
                    'transaction_payment_id' => $drawing_transaction_payment->id,
                    'note' => 'Settlement No: ' . $settlement_no . ' | Owner Drawing' . (!empty($drawing_payment->note) ? ' | ' . $drawing_payment->note : ''),
                ]);

                // Debit Owner Drawings Account (drawing increases)
                AccountTransaction::createAccountTransaction([
                    'amount' => $drawing_payment->amount,
                    'account_id' => $owner_drawings_account_id,
                    'business_id' => $business_id,
                    'type' => 'debit',
                    'sub_type' => 'owner_drawing',
                    'operation_date' => $drawing_transaction->transaction_date,
                    'created_by' => $drawing_transaction->created_by,
                    'transaction_id' => $drawing_transaction->id,
                    'transaction_payment_id' => $drawing_transaction_payment->id,
                    'note' => 'Settlement No: ' . $settlement_no . ' | Owner Drawing' . (!empty($drawing_payment->note) ? ' | ' . $drawing_payment->note : ''),
                ]);

                SettlementSwLog::info('Successfully created account transactions for owner drawing', [
                    'drawing_payment_id' => $drawing_payment->id,
                    'cash_account_id' => $cash_account_id,
                    'owner_drawings_account_id' => $owner_drawings_account_id,
                    'amount' => $drawing_payment->amount
                ]);
            }
            //---------------------------------------------------
            // 12D. Process Cash Deposits - Create Account Transactions
            //      NOTE: Cash deposits are already processed in section 10B above
            //      which creates both credit (bank) and debit (cash) entries correctly.
            //      This section is removed to prevent duplicate/incorrect entries.
            //---------------------------------------------------

            //---------------------------------------------------
            // 12E. Process Shortage Payments - Create Transactions
            //      Issue 5 & 6 Fix
            //---------------------------------------------------
            foreach ($settlement->shortage_payments as $shortage_payment) {
                // Create transaction for shortage
                $shortage_transaction = $this->createTransaction(
                    $settlement,
                    $shortage_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    'settlement',
                    'shortage',
                    $settlement_no,
                    $shortage_payment->id
                );

                // Create Transaction Payment
                $shortage_transaction_payment = $this->createTransactionPayment($shortage_transaction, 'cash');

                // Create account transaction with ledger_show sub_type for pump operator ledger
                $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                if (!empty($accounts_receivable_id)) {
                    $this->createAccountTransaction(
                        $shortage_transaction,
                        'debit', // Shortage = operator owes money = debit
                        $accounts_receivable_id,
                        $shortage_transaction_payment->id,
                        'ledger_show', // This makes it appear in pump operator ledger
                        null,
                        0,
                        false,
                        'Settlement No: ' . $settlement_no . ' | Shortage'
                    );
                }

                SettlementSwLog::info('Successfully created transaction for shortage payment', [
                    'shortage_payment_id' => $shortage_payment->id,
                    'amount' => $shortage_payment->amount
                ]);
            }

            //---------------------------------------------------
            // 12F. Process Excess Payments - Create Transactions
            //      Issue 5 & 6 Fix
            //---------------------------------------------------
            foreach ($settlement->excess_payments as $excess_payment) {
                // Create transaction for excess
                $excess_transaction = $this->createTransaction(
                    $settlement,
                    $excess_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    'settlement',
                    'excess',
                    $settlement_no,
                    $excess_payment->id
                );

                // Create Transaction Payment
                $excess_transaction_payment = $this->createTransactionPayment($excess_transaction, 'cash');

                // Create account transaction with ledger_show sub_type for pump operator ledger
                $accounts_payable_id = $this->transactionUtil->account_exist_return_id('Accounts Payable');
                if (!empty($accounts_payable_id)) {
                    $this->createAccountTransaction(
                        $excess_transaction,
                        'credit', // Excess = money owed to operator = credit
                        $accounts_payable_id,
                        $excess_transaction_payment->id,
                        'ledger_show', // This makes it appear in pump operator ledger
                        null,
                        0,
                        false,
                        'Settlement No: ' . $settlement_no . ' | Excess'
                    );
                }

                SettlementSwLog::info('Successfully created transaction for excess payment', [
                    'excess_payment_id' => $excess_payment->id,
                    'amount' => $excess_payment->amount
                ]);
            }

            //---------------------------------------------------
            // 13. Mark settlement finished & commit
            //---------------------------------------------------
            $settlement->update([
                'status'      => 0,
                'is_edit'     => 0,
                'finish_date' => now(),
            ]);

            DB::commit();

            //---------------------------------------------------
            // 12. Reload for print view (include daily_* relations)
            //---------------------------------------------------

            $settlement->unsetRelation('credit_sale_payments');
            $settlement->refresh()->load([
                'meter_sales',
                'other_sales',
                'other_incomes',
                'cash_payments',
                'card_payments',
                'cheque_payments',
                'credit_sale_payments.product',
                'expense_payments',
                'excess_payments',
                'shortage_payments',
                'loan_payments',
                'drawings_payments',
                'customer_loans',
                'daily_collections',
                'daily_cards',
                'daily_vouchers',
                'cash_deposits',
            ]);

            // Ensure cash deposits are loaded correctly
            // The relationship matches by id, but Settlement SW stores settlement_no as string (e.g., "SET-SW1")
            // So we need to reload using both formats to ensure all cash deposits are found
            $cash_deposits = SettlementCashDeposit::where(function($q) use ($settlement, $settlement_no) {
                $q->where('settlement_no', $settlement_no) // String format (e.g., "SET-SW1")
                  ->orWhere('settlement_no', $settlement->id); // ID format (integer)
            })
            ->where('business_id', $business_id)
            ->get();
            
            // CRITICAL: Also load cash deposits from accounting module that were linked to THIS settlement
            // These are deposits added via Accounting → List Accounts → Cash Deposit
            // We updated their notes earlier to include the settlement number
            $account_deposits = AccountTransaction::where('business_id', $business_id)
                ->where('sub_type', 'deposit')
                ->where('type', 'credit') // Credit entries for bank deposits
                ->where(function($q) use ($settlement, $settlement_no) {
                    $q->where('note', 'like', '%Settlement No: ' . $settlement_no . '%')
                      ->orWhere('note', 'like', '%Settlement No: ' . $settlement->id . '%');
                })
                ->get();
            
            // Convert account_transactions to SettlementCashDeposit-like objects for display
            $fake_deposits = $account_deposits->map(function($txn) {
                $bank_account = Account::find($txn->account_id);
                $deposit = new \stdClass();
                $deposit->id = $txn->id;
                $deposit->settlement_no = null; // Not in settlement_cash_deposits table
                $deposit->amount = $txn->amount;
                $deposit->bank_id = $txn->account_id;
                $deposit->bank_name = $bank_account ? $bank_account->name : 'Unknown Bank';
                $deposit->account_no = $txn->cheque_number ?? '';
                $deposit->time_deposited = $txn->operation_date;
                return $deposit;
            });
            
            // Merge both collections
            $all_cash_deposits = $cash_deposits->merge($fake_deposits);
            
            // Always set the relation to ensure we have the correct cash deposits
            $settlement->setRelation('cash_deposits', $all_cash_deposits);
            
                        
                        // ============================================================
            // ISSUES #5 & #6 FIX - Task IS-761
            // Issue #5: Shortage/Excess not updating pump_operators cumulative amounts
            // Issue #6: Shortage/Excess not creating contact_ledgers entries
            // Solution: Update pump_operators table and create ledger entries
         
            // ============================================================
            
            try {
                // Get the pump operator for this settlement
                $pump_operator = \Modules\SettlementSW\Entities\PumpOperator::find($settlement->pump_operator_id);
                
                if ($pump_operator) {
                    // ========== ISSUE #5 FIX: Update Pump Operator Cumulative Amounts ==========
                    
                    // Get shortage payments for this settlement
                    $shortage_payments = \Modules\SettlementSW\Entities\SettlementShortagePayment::where('settlement_no', $settlement->id)
                        ->where('business_id', $business_id)
                        ->get();
                    
                    // Get excess payments for this settlement
                    $excess_payments = \Modules\SettlementSW\Entities\SettlementExcessPayment::where('settlement_no', $settlement->id)
                        ->where('business_id', $business_id)
                        ->get();
                    
                    // Calculate total shortage for this settlement
                    $total_shortage = $shortage_payments->sum('amount');
                    
                    // Calculate total excess for this settlement (absolute value)
                    $total_excess = abs($excess_payments->sum('amount'));
                    
                    // Update pump operator cumulative amounts
                    if ($total_shortage > 0 || $total_excess > 0) {
                        // Update shortage amount (cumulative)
                        if ($total_shortage > 0) {
                            $pump_operator->short_amount = $pump_operator->short_amount + $total_shortage;
                        }
                        
                        // Update excess amount (cumulative)
                        if ($total_excess > 0) {
                            $pump_operator->excess_amount = $pump_operator->excess_amount + $total_excess;
                        }
                        
                        // Save pump operator
                        $pump_operator->save();
                        
                        SettlementSwLog::info('Issue #5 Fix: Updated pump operator cumulative amounts', [
                            'settlement_no' => $settlement->settlement_no,
                            'pump_operator_id' => $pump_operator->id,
                            'pump_operator_name' => $pump_operator->name,
                            'shortage_added' => $total_shortage,
                            'new_short_amount' => $pump_operator->short_amount,
                            'excess_added' => $total_excess,
                            'new_excess_amount' => $pump_operator->excess_amount
                        ]);
                    }
                    
                    // ========== ISSUE #6 FIX: Create Contact Ledger Entries ==========
                    
                    if ($total_shortage > 0 || $total_excess > 0) {
                        // Find or create contact for this pump operator
                        // NOTE: No direct foreign key exists between pump_operators and contacts
                        // We link by matching name and business_id, type = 'supplier'
                        
                        $operator_contact = \App\Contact::where('business_id', $business_id)
                            ->where('type', 'supplier')
                            ->where(function($query) use ($pump_operator) {
                                $query->where('name', $pump_operator->name)
                                    ->orWhere('supplier_business_name', $pump_operator->name);
                            })
                            ->first();
                        
                        // If contact not found, create new contact for this pump operator
                        if (!$operator_contact) {
                            $operator_contact = new \App\Contact();
                            $operator_contact->business_id = $business_id;
                            $operator_contact->type = 'supplier';
                            $operator_contact->name = $pump_operator->name;
                            $operator_contact->supplier_business_name = $pump_operator->name;
                            $operator_contact->mobile = $pump_operator->mobile ?? '';
                            $operator_contact->created_by = Auth::user()->id;
                            $operator_contact->save();
                            
                            SettlementSwLog::info('Issue #6 Fix: Created new contact for pump operator', [
                                'pump_operator_id' => $pump_operator->id,
                                'pump_operator_name' => $pump_operator->name,
                                'contact_id' => $operator_contact->id
                            ]);
                        }
                        
                        // Create ledger entry for shortage
                        if ($total_shortage > 0) {
                            $shortage_ledger = new \App\ContactLedger();
                            $shortage_ledger->contact_id = $operator_contact->id;
                            $shortage_ledger->type = 'debit'; // Shortage = Operator owes money = DEBIT
                            $shortage_ledger->amount = $total_shortage;
                            $shortage_ledger->reff_no = $settlement->settlement_no;
                            $shortage_ledger->operation_date = now();
                            $shortage_ledger->created_by = Auth::user()->id;
                            $shortage_ledger->note = 'Shortage - Settlement: ' . $settlement->settlement_no . 
                                                   ' | Operator: ' . $pump_operator->name .
                                                   ' | Cumulative Shortage: ₹' . number_format($pump_operator->short_amount, 2);
                            $shortage_ledger->save();
                            
                            SettlementSwLog::info('Issue #6 Fix: Created shortage ledger entry', [
                                'settlement_no' => $settlement->settlement_no,
                                'contact_id' => $operator_contact->id,
                                'amount' => $total_shortage,
                                'type' => 'debit',
                                'ledger_id' => $shortage_ledger->id
                            ]);
                        }
                        
                        // Create ledger entry for excess
                        if ($total_excess > 0) {
                            $excess_ledger = new \App\ContactLedger();
                            $excess_ledger->contact_id = $operator_contact->id;
                            $excess_ledger->type = 'credit'; // Excess = Money owed to operator = CREDIT
                            $excess_ledger->amount = $total_excess;
                            $excess_ledger->reff_no = $settlement->settlement_no;
                            $excess_ledger->operation_date = now();
                            $excess_ledger->created_by = Auth::user()->id;
                            $excess_ledger->note = 'Excess - Settlement: ' . $settlement->settlement_no . 
                                                 ' | Operator: ' . $pump_operator->name .
                                                 ' | Cumulative Excess: ₹' . number_format($pump_operator->excess_amount, 2);
                            $excess_ledger->save();
                            
                            SettlementSwLog::info('Issue #6 Fix: Created excess ledger entry', [
                                'settlement_no' => $settlement->settlement_no,
                                'contact_id' => $operator_contact->id,
                                'amount' => $total_excess,
                                'type' => 'credit',
                                'ledger_id' => $excess_ledger->id
                            ]);
                        }
                    }
                } else {
                    \Log::warning('Issue #5 & #6 Fix: Pump operator not found for settlement', [
                        'settlement_no' => $settlement->settlement_no,
                        'pump_operator_id' => $settlement->pump_operator_id
                    ]);
                }
                
            } catch (\Exception $e) {
                \Log::error('Issue #5 & #6 Fix: Error processing shortage/excess', [
                    'settlement_no' => $settlement->settlement_no ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw - let settlement continue even if this fails
            }
            
            // ============================================================
            // END ISSUES #5 & #6 FIX - Task IS-761
            // ============================================================


            // prepare print data
            $business      = Business::find($business_id);
            $pump_operator = PumpOperator::find($pump_operator_id);

            $customer_payments_tab = SettlementCashPayment::leftJoin(SettlementSwTables::contacts(), 'settlement_cash_payments.customer_id', 'contacts.id')
                ->where('settlement_cash_payments.settlement_no', $settlement_no)
                ->select('settlement_cash_payments.*', 'contacts.name as customer_name')
                ->get();

            // total_daily_collection compute from settlement's attached daily_collections
            $total_daily_collection = $settlement->daily_collections->sum('current_amount');

            // ---------------------------------------------
            // DEBUG PRODUCT NAME FOR EACH CREDIT SALE ENTRY
            // ---------------------------------------------
            // Ensure cash deposits are loaded correctly
            // The relationship matches by id, but Settlement SW stores settlement_no as string (e.g., "SET-SW1")
            // So we need to reload using both formats to ensure all cash deposits are found
            $cash_deposits = SettlementCashDeposit::where(function($q) use ($settlement, $settlement_no) {
                $q->where('settlement_no', $settlement_no) // String format (e.g., "SET-SW1")
                  ->orWhere('settlement_no', $settlement->id); // ID format (integer)
            })
                    ->where('business_id', $business_id)
            ->get();
            
            // Also include cash deposits from accounting module (AccountTransactions with sub_type='deposit')
            // These are deposits added via Accounting → List Accounts → Cash Deposit
            $account_deposits = AccountTransaction::where('business_id', $business_id)
                ->where('sub_type', 'deposit')
                ->where('type', 'credit') // Credit entries for bank deposits
                ->where(function($q) use ($settlement, $settlement_no) {
                    $q->where('note', 'like', '%Settlement No: ' . $settlement_no . '%')
                      ->orWhere('note', 'like', '%Settlement No: ' . $settlement->id . '%');
                })
                ->get();
            
            // Convert account_transactions to SettlementCashDeposit-like objects for display
            $fake_deposits = $account_deposits->map(function($txn) {
                $bank_account = Account::find($txn->account_id);
                $deposit = new \stdClass();
                $deposit->id = $txn->id;
                $deposit->settlement_no = null; // Not in settlement_cash_deposits table
                $deposit->amount = $txn->amount;
                $deposit->bank_id = $txn->account_id;
                $deposit->bank_name = $bank_account ? $bank_account->name : 'Unknown Bank';
                $deposit->account_no = $txn->cheque_number ?? '';
                $deposit->time_deposited = $txn->operation_date;
                return $deposit;
            });
            
            // Merge both collections
            $all_cash_deposits = $cash_deposits->merge($fake_deposits);
            
            // Always set the relation to ensure we have the correct cash deposits
            $settlement->setRelation('cash_deposits', $all_cash_deposits);

            // DEBUG PRODUCT NAME FOR EACH CREDIT SALE ENTRY
            foreach ($settlement->credit_sale_payments as $csp) {
                SettlementSwLog::info('CREDIT SALE DEBUG', [
                    'id'                   => $csp->id,
                    'product_id'           => $csp->product_id,
                    'has_product_relation' => $csp->relationLoaded('product'),
                    'product_object'       => $csp->product,
                    'product_name'         => $csp->product->name ?? null,
                ]);
            }

            return view('settlementsw::swsettlement.print')->with(compact(
                'settlement',
                'business',
                'pump_operator',
                'customer_payments_tab',
                'total_daily_collection',
                'shift_ids'
            ));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return ["success" => 0, "msg" => __('messages.something_went_wrong') . ': ' . $e->getMessage()];
        }
    }

    /**
     * Helper method to create transaction
     */
    public function createTransaction(
        $settlement,
        $amount,
        $customer_id,
        $pump_operator_id,
        $type,
        $sub_type,
        $settlement_no,
        $ref_no = null,
        $is_credit_sale = 0,
        $total_sales_discount_amount = 0.0,
        $transaction_note = null
    ) {
        $business_id = $this->settlementSwBusinessId();

        $business_location = BusinessLocation::where('business_id', $business_id)->first();

        if (empty($business_location)) {
            throw new \Exception('Business location not found for this business.');
        }

        $total_sales_discount_amount = ! empty($total_sales_discount_amount) ? $total_sales_discount_amount : 0;

        $final_amount = $amount;

        $ob_data = [
            'business_id'      => $business_id,
            'location_id'      => $business_location->id,
            'type'             => $type,
            'sub_type'         => $sub_type,
            'status'           => 'final',
            'payment_status'   => 'paid',
            'contact_id'       => $customer_id,
            'pump_operator_id' => $pump_operator_id,
            'transaction_date' => Carbon::parse($settlement->transaction_date)->format('Y-m-d'),
            'total_before_tax' => $final_amount,
            'final_total'      => $final_amount,
            'discount_amount'  => $total_sales_discount_amount,
            'created_by'       => request()->session()->get('user.id'),
            'is_settlement'    => 1,
            'transaction_note' => $transaction_note,
            app(SettlementSwLegacyMap::class)->settlementReferenceColumn() => $settlement->id,
        ];

        if ($sub_type == 'excess' || $sub_type == 'shortage' || $sub_type == 'customer_loan') {
            $ob_data['payment_status'] = 'due';
        }

        $ob_data['invoice_no'] = $settlement_no;
        $ob_data['ref_no']     = ! empty($ref_no) ? $ref_no : null;

        if ($is_credit_sale == 1) {
            $ob_data['type']     = 'sell';
            $ob_data['sub_type'] = 'credit_sale';
        }

        // Check if transaction already exists for this settlement
        $existing_transaction = Transaction::where('business_id', $ob_data['business_id'])
            ->where('invoice_no', $settlement_no)
            ->where('type', $ob_data['type'])
            ->where('sub_type', $ob_data['sub_type'])
            ->where('is_settlement', 1)
            ->first();

        if ($existing_transaction) {
            return $existing_transaction;
        }

        // Create new transaction
        $transaction = Transaction::create($ob_data);

        return $transaction;
    }

    /**
     * Helper method to create sell transactions
     */
    public function createSellTransactions(
        $transaction,
        $sale,
        $business_id,
        $default_location,
        $fuel_tank_id = null,
        $is_other_sale = null
    ) {
        $uf_quantity = $this->productUtil->num_uf($sale->qty);

        // Build from Product so settlement sell lines are created reliably for
        // products with or without variation edge-cases. Missing sell lines here
        // means Sales Income / COGS / Finished Goods account books never get rows.
        $product = Product::leftjoin('product_variations', 'products.id', 'product_variations.product_id')
            ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->where('products.id', $sale->product_id)
            ->select(
                'variations.id as variation_id',
                'variation_location_details.location_id',
                'products.id as product_id',
                'categories.name as category_name',
                'products.enable_stock'
            )
            ->first();

        if ($product) {
            $this->transactionUtil->createOrUpdateSellLinesSettlement(
                $transaction,
                $product->product_id,
                $product->variation_id,
                $product->location_id ?? $default_location,
                $sale
            );

            $location_product = ! empty($product->location_id) ? $product->location_id : $default_location;

            // if enable stock
            if ($product->enable_stock && ! empty($is_other_sale)) {
                /*
                 * IS1994: a meter sale is not an OtherSale.
                 *
                 * Looking it up by id would fetch an unrelated OtherSale row
                 * that happened to share the id and pull the wrong store, so
                 * the decrement falls back to the business default store below.
                 */
                $otherSale = ($is_other_sale === 'meter_sale')
                    ? null
                    : OtherSale::where('id', $sale->id)->first();

                $this->productUtil->decreaseProductQuantity(
                    $sale->product_id,
                    $product->variation_id,
                    $location_product,
                    $uf_quantity,
                    0,
                    'decrease',
                    isset($otherSale->store_id) ? $otherSale->store_id : 0
                );

                // IS1994: guarded. This now runs for every fuel meter sale, and
                // ->first()->id was a fatal on a business with no Store row.
                $store = Store::where('business_id', $business_id)->first();
                $store_id = ! empty($store) ? $store->id : 0;

                $this->productUtil->decreaseProductQuantityStore(
                    $sale->product_id,
                    $product->variation_id,
                    $location_product,
                    $uf_quantity,
                    isset($otherSale->store_id) ? $otherSale->store_id : $store_id,
                    'decrease',
                    0
                );
            }
        }

        // update qty to fuel tank current stock
        if (! empty($fuel_tank_id)) {
            FuelTank::where('id', $fuel_tank_id)->decrement('current_balance', $sale->qty);

            \Modules\SettlementSW\Entities\TankSellLine::create([
                'business_id'    => $business_id,
                'transaction_id' => $transaction->id,
                'tank_id'        => $fuel_tank_id,
                'product_id'     => $sale->product_id,
                'quantity'       => $sale->qty,
            ]);
        }

        return true;
    }

    /**
     * Helper method to create credit sell transactions
     */
    public function createCreditSellTransactions(
        $settlement,
        $sale,
        $default_location
    ) {
        $final_total = $sale->amount - $sale->total_discount;

        $ob_data = [
            'business_id'      => $sale->business_id,
            'location_id'      => $settlement->location_id,
            'type'             => 'sell',
            'status'           => 'final',
            'payment_status'   => 'due',
            'contact_id'       => $sale->customer_id,
            'pump_operator_id' => $settlement->pump_operator_id,
            'transaction_date' => \Carbon::parse($settlement->transaction_date)->format('Y-m-d'),
            'total_before_tax' => $final_total,
            'final_total'      => $final_total,
            'discount_type'    => 'fixed',
            'discount_amount'  => $sale->total_discount,
            'credit_sale_id'   => $sale->id,
            'is_credit_sale'   => 1,
            'is_settlement'    => 1,
            'created_by'       => request()->session()->get('user.id'),
            'invoice_no'       => $settlement->settlement_no,
            'ref_no'           => $sale->customer_reference,
            'customer_ref'     => $sale->customer_reference,
            'order_date'       => $sale->order_date,
            'order_no'         => $sale->order_number,
            'sub_type'         => 'credit_sale',
            app(SettlementSwLegacyMap::class)->settlementReferenceColumn() => $settlement->id,
        ];

        // Check if credit sale transaction already exists
        $existing_transaction = Transaction::where('business_id', $sale->business_id)
            ->where('invoice_no', $settlement->settlement_no)
            ->where('credit_sale_id', $sale->id)
            ->where('is_settlement', 1)
            ->where('is_credit_sale', 1)
            ->first();

        if ($existing_transaction) {
            return $existing_transaction;
        }

        // Create transaction
        $transaction = Transaction::create($ob_data);

        // Write a customer ledger entry so settlement credit sale shows in customer ledger
        try {
            $ledger_entry = [
                'contact_id'     => $transaction->contact_id,
                'amount'         => $transaction->final_total,
                'type'           => 'debit',
                'operation_date' => $transaction->transaction_date,
                'created_by'     => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'note'           => 'Settlement No ' . $transaction->invoice_no,
            ];
            ContactLedger::createContactLedger($ledger_entry, 'Customer Settlement');
        } catch (\Throwable $e) {
            // swallow errors to avoid breaking settlement creation
        }

        return $transaction;
    }

    /**
     * Helper method to create account transaction
     */
    public function createAccountTransaction(
        $transaction,
        $type,
        $account_id,
        $transaction_payment_id = null,
        $sub_type = null,
        $contact_id = null,
        $amount = 0,
        $is_credit_sale = false,
        $note = null,
        $slip_no = null
    ) {
        $account_transaction_data = [
            'amount'                 => abs($transaction->final_total),
            'account_id'             => $account_id,
            'contact_id'             => $transaction->contact_id,
            'type'                   => $type,
            'sub_type'               => $sub_type,
            'operation_date'         => $transaction->transaction_date,
            'created_by'             => $transaction->created_by,
            'transaction_id'         => $transaction->id,
            'transaction_payment_id' => $transaction_payment_id,
            'note'                   => $note,
            'slip_no'                => $slip_no,
        ];

        if (! empty($contact_id)) {
            $account_transaction_data['contact_id'] = $contact_id;
        }

        if (! empty($amount)) {
            $account_transaction_data['amount'] = $amount;
        }

        AccountTransaction::createAccountTransaction($account_transaction_data);

        // create ledger transactions
        if ($sub_type == 'ledger_show') {
            ContactLedger::createContactLedger($account_transaction_data);

            if (! $is_credit_sale) {
                if ($type == 'debit') {
                    $ledger_type = 'credit';
                }

                if ($type == 'credit') {
                    $ledger_type = 'debit';
                }

                $account_transaction_data['type'] = $ledger_type;
                ContactLedger::createContactLedger($account_transaction_data);
            }
        }
    }

    /**
     * Helper method to create stock account transactions (Finished Goods, Sales Income, COGS)
     */
    public function createStockAccountTransactions($transaction)
    {
        $account_transaction_data = [
            'amount'         => abs($transaction->final_total),
            'operation_date' => $transaction->transaction_date,
            'created_by'     => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'note'           => null,
        ];

        $this->transactionUtil->manageStockAccount(
            $transaction,
            $account_transaction_data,
            'credit',
            $transaction->final_total
        );

        $this->transactionUtil->createCostofGoodsSoldTransaction(
            $transaction,
            'ledger_show',
            'debit'
        );

        $this->transactionUtil->createSaleIncomeTransaction(
            $transaction,
            'ledger_show',
            'credit'
        );
    }

    /**
     * Helper method to map sell purchase lines
     */
    public function mapSellPurchaseLines(
        $business_id,
        $transaction,
        $settlement
    ) {
        // Allocate the quantity from purchase and add mapping of
        // purchase & sell lines in transaction_sell_lines_purchase_lines table
        $business_details = $this->businessUtil->getDetails($business_id);

        $pos_settings = empty($business_details->pos_settings)
            ? $this->businessUtil->defaultPosSettings()
            : json_decode($business_details->pos_settings, true);

        $business = [
            'id'                => $business_id,
            'accounting_method' => request()->session()->get('business.accounting_method'),
            'location_id'       => $settlement->location_id,
            'pos_settings'      => $pos_settings,
        ];

        $this->transactionUtil->mapPurchaseSell(
            $business,
            $transaction->sell_lines,
            'purchase'
        );
    }

    /**
     * Helper method to create transaction payment
     */
    public function createTransactionPayment(
        $transaction,
        $method,
        $amount = 0,
        $card_number = null,
        $card_type = null,
        $cheque_number = null,
        $bank_name = null,
        $cheque_date = null,
        $post_dated_cheque = 0
    ) {
        $business_id = $this->settlementSwBusinessId();

        $transaction_payment_data = [
            'transaction_id'    => $transaction->id,
            'business_id'       => $business_id,
            'amount'            => abs($transaction->final_total),
            'method'            => $method,
            'paid_on'           => $transaction->transaction_date,
            'created_by'        => $transaction->created_by,
            'card_number'       => $card_number,
            'card_type'         => $card_type,
            'cheque_number'     => $cheque_number,
            'bank_name'         => $bank_name,
            'cheque_date'       => ! empty($cheque_date) ? \Carbon\Carbon::parse($cheque_date)->format('Y-m-d') : null,
            'post_dated_cheque' => $post_dated_cheque,
        ];

        if (! empty($amount)) {
            $transaction_payment_data['amount'] = $amount;
        }

        $transaction_payment_data['paid_in_type'] = 'settlement';

        $transaction_payment = TransactionPayment::create($transaction_payment_data);

        return $transaction_payment;
    }

    /**
     * Print settlement view with all relationships loaded
     * 
     * @param int $id Settlement ID
     * @return \Illuminate\View\View
     */
    public function print($id)
    {
        $business_id = $this->settlementSwBusinessId();

        $settlement = Settlement::with([
            'meter_sales',
            'other_sales',
            'other_incomes',
            'customer_payments',
            'cash_payments',
            'cash_deposits',
            'card_payments',
            'cheque_payments',
            'credit_sale_payments.product',
            'expense_payments',
            'excess_payments',
            'shortage_payments',
            'loan_payments',
            'drawings_payments',
            'customer_loans',
            'daily_collections',  // ← FIX: Load daily collections
            'daily_cards',        // ← FIX: Load daily cards
            'daily_vouchers',     // ← FIX: Load daily vouchers
        ])
        ->where('settlements.id', $id)
        ->where('settlements.business_id', $business_id)
        ->first();

        if (! $settlement) {
            abort(404, 'Settlement not found');
        }

        $business = Business::find($business_id);
        $pump_operator = PumpOperator::find($settlement->pump_operator_id);

        // Ensure cash deposits are loaded correctly
        // The relationship matches by id, but Settlement SW stores settlement_no as string (e.g., "SET-SW1")
        // So we need to reload using both formats to ensure all cash deposits are found
        $cash_deposits = SettlementCashDeposit::where(function($q) use ($settlement) {
            $q->where('settlement_no', $settlement->settlement_no) // String format (e.g., "SET-SW1")
              ->orWhere('settlement_no', $settlement->id); // ID format (integer)
        })
                ->where('business_id', $business_id)
        ->get();
        
        // Also include cash deposits from accounting module (AccountTransactions with sub_type='deposit')
        // These are deposits added via Accounting → List Accounts → Cash Deposit
        $account_deposits = AccountTransaction::where('business_id', $business_id)
            ->where('sub_type', 'deposit')
            ->where('type', 'credit') // Credit entries for bank deposits
            ->where(function($q) use ($settlement) {
                $q->where('note', 'like', '%Settlement No: ' . $settlement->settlement_no . '%')
                  ->orWhere('note', 'like', '%Settlement No: ' . $settlement->id . '%');
            })
            ->get();
        
        // Convert account_transactions to SettlementCashDeposit-like objects for display
        $depositAccounts = Account::query()
            ->where('business_id', $business_id)
            ->whereIn('id', $account_deposits->pluck('account_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $fake_deposits = $account_deposits->map(function($txn) use ($depositAccounts) {
            $bank_account = $depositAccounts->get($txn->account_id);
            $deposit = new \stdClass();
            $deposit->id = $txn->id;
            $deposit->settlement_no = null; // Not in settlement_cash_deposits table
            $deposit->amount = $txn->amount;
            $deposit->bank_id = $txn->account_id;
            $deposit->bank_name = $bank_account ? $bank_account->name : 'Unknown Bank';
            $deposit->account_no = $txn->cheque_number ?? '';
            $deposit->time_deposited = $txn->operation_date;
            return $deposit;
        });
        
        // Merge both collections
        $all_cash_deposits = $cash_deposits->merge($fake_deposits);
        
        // Always set the relation to ensure we have the correct cash deposits
        $settlement->setRelation('cash_deposits', $all_cash_deposits);

        // Collect shift IDs before the shift-number fallback uses them.
        $shift_ids = $settlement->daily_collections
            ->pluck('shift_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($shift_ids)) {
            $shift_ids = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->where('business_id', $business_id)
                ->whereNotNull('shift_id')
                ->pluck('shift_id')
                ->unique()
                ->toArray();
        }

        // Get shift number for this settlement
        // Priority 1: Get from daily_collections (where the data actually is)
        $shift_numbers_from_daily = DailyCollection::where('settlement_id', $settlement->id)
            ->where('business_id', $business_id)
            ->whereNotNull('shift_number')
            ->where('shift_number', '!=', '')
            ->where('shift_number', '!=', '0')
            ->distinct()
            ->pluck('shift_number')
            ->filter(function($value) {
                return !empty($value) && $value !== '0';
            })
            ->unique()
            ->values();
        
        $shift_number = null;
        
        // If we found shift numbers in daily_collections, use them
        if ($shift_numbers_from_daily->isNotEmpty()) {
            $shift_number = $shift_numbers_from_daily->implode(',');
        } else {
            // Priority 2: If not found in daily_collections, try pump_operator_assignments
            $shift_number_obj = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereNotNull('shift_number')
                ->where('shift_number', '!=', 0)
                ->select('shift_number')
                ->orderBy('id', 'desc')
                ->first();
            
            // Priority 3: If still not found, try without operator filter
            if (empty($shift_number_obj)) {
                $shift_number_obj = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->whereNotNull('shift_number')
                    ->where('shift_number', '!=', 0)
                    ->select('shift_number')
                    ->orderBy('id', 'desc')
                    ->first();
            }
            
            if (!empty($shift_number_obj)) {
                $shift_number = $shift_number_obj->shift_number;
            }
        }
        
        // If still empty, attempt to derive from shift IDs, otherwise set to N/A
        if (empty($shift_number) || $shift_number === 'N/A') {
            if (! empty($shift_ids)) {
                $shiftNos = DB::table(SettlementSwTables::dailyShifts())
                    ->whereIn('id', $shift_ids)
                    ->pluck('shift_no')
                    ->filter()
                    ->unique()
                    ->values();

                if ($shiftNos->isNotEmpty()) {
                    $shift_number = $shiftNos->implode(',');
                }
            }

            if (empty($shift_number)) {
                $shift_number = 'N/A';
            }
        }

        $customer_payments_tab = SettlementCashPayment::leftJoin(
            'contacts',
            'settlement_cash_payments.customer_id',
            'contacts.id'
        )
        ->where('settlement_cash_payments.settlement_no', $settlement->settlement_no)
        ->where('settlement_cash_payments.business_id', $business_id)
        ->select('settlement_cash_payments.*', 'contacts.name as customer_name')
        ->get();

        $total_daily_collection = $settlement->daily_collections->sum('current_amount');
        $settlementLookups = $this->buildSettlementViewLookups($settlement, $business_id, $customer_payments_tab);

        return view('settlementsw::swsettlement.print')->with(compact(
            'settlement',
            'business',
            'pump_operator',
            'customer_payments_tab',
            'total_daily_collection',
            'shift_number',
            'shift_ids',
            'settlementLookups'
        ));
    }


    /**
     * SW_AUDIT_006: module-local replacement for old Petro check_prev_settlement endpoint.
     * Returns status=false when the selected shift is already attached to an unfinished
     * Settlement SW record, so the create page can block duplicate/open settlements.
     */
    public function checkPrevSettlement(Request $request)
    {
        $business_id = $this->settlementSwBusinessId();
        $shift_id = (int) $request->get('shift_id');

        if (empty($business_id) || empty($shift_id)) {
            return response()->json(['status' => true]);
        }

        $existing = Settlement::where('business_id', $business_id)
            ->where(function ($query) use ($shift_id) {
                $query->whereJsonContains('work_shift', $shift_id)
                    ->orWhere('work_shift', 'like', '%"' . $shift_id . '"%')
                    ->orWhere('work_shift', 'like', '%[' . $shift_id . ']%')
                    ->orWhere('work_shift', (string) $shift_id);
            })
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['completed', 'complete', 'closed', 1]);
            })
            ->latest('id')
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'msg' => __('settlementsw::lang.previous_unfinished_settlement_exists'),
                'settlement_id' => $existing->id,
            ]);
        }

        return response()->json(['status' => true]);
    }

}
