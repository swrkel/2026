<?php

namespace Modules\PetroGeneral\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\NotificationTemplate;
use App\Product;
use App\Transaction;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Milon\Barcode\DNS2D;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DailyVoucherItem;
use Modules\PetroGeneral\Entities\PetroDailyShift;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Yajra\DataTables\Facades\DataTables;

class DailyVoucherController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $contactUtil;

    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, ContactUtil $contactUtil)
    {
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->contactUtil = $contactUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('business.id');
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if (request()->ajax()) {
            $daily_vouchers = DailyVoucher::leftJoin('pumps', 'daily_vouchers.pump_id', 'pumps.id')
                ->leftJoin('pump_operators', 'daily_vouchers.operator_id', 'pump_operators.id')
                // ->leftJoin('settlements', 'settlements.settlement_no', 'daily_vouchers.settlement_no')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('CONVERT(settlements.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci'), '=',
                        DB::raw('CONVERT(daily_vouchers.settlement_no USING utf8mb4) COLLATE utf8mb4_unicode_ci'));
                })
                ->leftJoin('contacts', 'daily_vouchers.customer_id', 'contacts.id')
                ->leftJoin('business_locations', 'daily_vouchers.location_id', 'business_locations.id')
                ->leftJoin('customer_references', 'daily_vouchers.vehicle_no', 'customer_references.id')
                ->leftJoin('users', 'daily_vouchers.created_by', 'users.id')
                ->leftJoin('pump_operator_assignments', function ($join) {
                    $join->on('pump_operator_assignments.pump_operator_id', '=', 'daily_vouchers.operator_id')
                        ->whereRaw('pump_operator_assignments.date_and_time <= daily_vouchers.created_at')
                        ->whereRaw('(pump_operator_assignments.close_date_and_time >= daily_vouchers.created_at OR pump_operator_assignments.close_date_and_time IS NULL)');
                })
                ->where('daily_vouchers.business_id', $business_id)
                ->select(
                    'daily_vouchers.*',
                    'pumps.pump_name',
                    'business_locations.name as location_name',
                    'pump_operators.name as operator_name',
                    'customer_references.reference',
                    'contacts.name as customer_name',
                    'contacts.credit_limit',
                    'users.username as username',
                    'settlements.settlement_no as settlement_nos',
                    'pump_operator_assignments.shift_number as shift_number',
                    'settlements.status as settlement_status'
                )
                ->orderBy('daily_vouchers.created_at', 'desc')
                ->groupBy('daily_vouchers.id');

            // --- Filters ---
            if (! empty(request()->location_id)) {
                $daily_vouchers->where('daily_vouchers.location_id', request()->location_id);
            }

            if (! empty(request()->settlement_id)) {
                $daily_vouchers->where('settlements.id', request()->settlement_id);
            }

            if (! empty(request()->dv_pump_operator)) {
                $daily_vouchers->where('daily_vouchers.operator_id', request()->dv_pump_operator);
            }

            if (! empty(request()->status)) {
                if (request()->status === 'completed') {
                    $daily_vouchers->whereNotNull('settlements.settlement_no')
                        ->where('settlements.status', 0);
                } elseif (request()->status === 'pending') {
                    $daily_vouchers->where(function ($q) {
                        $q->whereNull('settlements.settlement_no')
                            ->orWhere('settlements.status', 1);
                    });
                }
            }

            if (! empty(request()->customer_id)) {
                $daily_vouchers->where('daily_vouchers.customer_id', request()->customer_id);
            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $daily_vouchers->whereDate('daily_vouchers.transaction_date', '>=', request()->start_date)
                    ->whereDate('daily_vouchers.transaction_date', '<=', request()->end_date);
            }

            return DataTables::of($daily_vouchers)
                ->addColumn('action', function ($row) use ($is_pumper_dashboard_enabled) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                    .__('messages.actions').'<span class="caret"></span></button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    if (auth()->user()->can('daily_voucher.view')) {
                        $html .= '<li><a href="#" data-href="'.action('\Modules\PetroGeneral\Http\Controllers\DailyVoucherController@print', $row->id).'" class="print_bill"><i class="fa fa-print"></i>'.__('messages.print').'</a></li>';
                    }
                    // Add edit button if no settlement and pumper dashboard is not enabled
                    if (!$is_pumper_dashboard_enabled && empty($row->settlement_nos) && auth()->user()->can('daily_voucher.edit') && empty(auth()->user()->pump_operator_id)) {
                        $html .= '<li><a href="#" data-href="'.action('\Modules\PetroGeneral\Http\Controllers\DailyVoucherController@edit', $row->id).'" data-container=".pump_modal" class="btn-modal"><i class="fa fa-pencil"></i>'.__('messages.edit').'</a></li>';
                    }
                    $html .= '</ul></div>';

                    return $html;
                })
                ->editColumn('credit_limit', function ($row) {
                    return empty($row->credit_limit) ? 'No Limit' : $this->productUtil->num_f($row->credit_limit);
                })
                ->addColumn('balance_available', function ($row) {
                    $bal = empty($row->credit_limit)
                        ? $this->productUtil->num_f($row->current_outstanding - $row->total_amount)
                        : $this->productUtil->num_f($row->credit_limit - $row->current_outstanding - $row->total_amount);

                    return $bal < 0 ? "<span style='color:red;'>$bal</span>" : "<span>$bal</span>";
                })
                ->addColumn('status', function ($row) {
                    // Show Completed if settlement_no exists (settlement SW has been saved)
                    // regardless of settlement status, as per requirement
                    if (! empty($row->settlement_nos)) {
                        return 'Completed';
                    }

                    return 'Pending';
                })
                ->addColumn('total_collection', function ($row) {
                    // If the card/voucher has been settled, sum all records under that settlement
                    if (! empty($row->settlement_nos)) {
                        $total = DB::table('daily_vouchers')
                            ->where('settlement_no', $row->settlement_nos)
                            ->sum('total_amount') ?? 0;
                    } else {
                        // Otherwise, sum all unsettled records for that operator up to this point
                        $total = DB::table('daily_vouchers')
                            ->where('operator_id', $row->operator_id)
                            ->where('id', '<=', $row->id)
                            ->whereNull('settlement_no')
                            ->sum('total_amount') ?? 0;
                    }

                    return $this->productUtil->num_f($total);
                })
                ->editColumn('shift_number', '{{ $shift_number }}')
                ->filterColumn('shift_number', function ($query, $keyword) {
                    $query->orWhere('pump_operator_assignments.shift_number', 'like', "%{$keyword}%");
                })
                ->editColumn('voucher_order_date', '{{ @format_date($voucher_order_date) }}')
                ->editColumn('current_outstanding', function ($row) {
                    return $this->productUtil->num_f((float) ($row->current_outstanding ?? 0));
                })
                ->editColumn('total_amount', function ($row) {
                    return $this->productUtil->num_f((float) ($row->total_amount ?? 0));
                })
                ->rawColumns(['action', 'balance_available'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        Log::info('DailyVoucherController@create request', $request->all());

        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');

        Log::info('Business ID for DailyVoucherController@create', ['business_id' => $business_id]);

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');
        $open_shifts = PetroShift::where('business_id', $business_id)->where('status', '0')->pluck('pump_operator_id')->toArray();
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $daily_vouchers_no = (DailyVoucher::where('business_id', $business_id)->count()) + 1;
        $busness_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $default_location = current(array_keys($busness_locations->toArray()));
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        if ($request->type == 'daily_collection_sw') {
            // Get shifts: First try active (status = 1), then pending (status = 0)
            // This matches the flow: Open shift (status 1) -> Save shift (status 0)
            $dailyShift = PetroDailyShift::where('business_id', $business_id)
                ->where('type', 'daily_collection_sw')
                ->whereIn('status', [1, 0])  // Show both active AND pending shifts
                ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')  // Prioritize status 1
                ->orderBy('updated_at', 'desc')
                ->first();
            
            Log::info('Daily Shift found in DailyVoucherController@create', [
                'shift_found' => !empty($dailyShift),
                'shift_id' => $dailyShift->id ?? null,
                'shift_no' => $dailyShift->shift_no ?? null,
                'status' => $dailyShift->status ?? null,
                'business_id' => $business_id,
                'all_shifts_count' => PetroDailyShift::where('business_id', $business_id)
                    ->where('type', 'daily_collection_sw')
                    ->count()
            ]);
            
            // Format as array with id => shift_no (for select dropdown)
            $daily_shift_no = [];
            if ($dailyShift) {
                $daily_shift_no = [$dailyShift->id => $dailyShift->shift_no];
                Log::info('Daily shift array created', ['daily_shift_no' => $daily_shift_no]);
            } else {
                Log::warning('No daily shift found for daily_collection_sw', [
                    'business_id' => $business_id,
                    'active_shifts' => PetroDailyShift::where('business_id', $business_id)
                ->where('status', 1)
                        ->where('type', 'daily_collection_sw')
                        ->get(['id', 'shift_no', 'status'])->toArray()
                ]);
            }

            return view('dailycollectionsw::partials.create_daily_voucher')->with(compact(
                'customers',
                'pumps',
                'pump_operators',
                'daily_vouchers_no',
                'busness_locations',
                'products',
                'default_location',
                'daily_shift_no'
            ));
        }

        return view('petrogeneral::daily_collection.partials.create_daily_voucher')->with(compact(
            'customers',
            'pumps',
            'pump_operators',
            'daily_vouchers_no',
            'busness_locations',
            'products',
            'default_location'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        Log::info('Daily Voucher Store Request', $request->all());

        try {

            $data = $request->credit_data;
            $pump_operator_id = $request->pump_operator_id;
            $pump_operator = PumpOperator::findOrFail($pump_operator_id);
            $business_id = $request->session()->get('business.id');

            foreach ($data as $one) {
                $price = $this->productUtil->num_uf($one['price']);
                $unit_discount = $this->productUtil->num_uf($one['unit_discount']);
                $qty = $this->productUtil->num_uf($one['qty']);
                $amount = $this->productUtil->num_uf($one['amount']);
                $sub_total = $this->productUtil->num_uf($one['sub_total']);
                $total_discount = $this->productUtil->num_uf($one['total_discount']);

                if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'same_order_no_daily_collection')) {
                    $order_no = $one['order_number'];
                    $existingRecord = SettlementCreditSalePayment::where('order_number', $order_no)->exists();

                    if (! empty($order_no) && $existingRecord) {

                        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'allow_duplicate_order_numbers')) {

                            $lastRecord = SettlementCreditSalePayment::where('order_number', 'LIKE', 'No Order%')
                                ->orderBy('id', 'desc')
                                ->first();

                            if ($lastRecord) {
                                preg_match('/No Order (\d+)/', $lastRecord->order_number, $matches);
                                $nextNumber = isset($matches[1]) ? ((int) $matches[1] + 1) : 1;
                            } else {
                                $nextNumber = 1;
                            }

                            $order_no = 'No Order '.$nextNumber;
                            $one['order_number'] = $order_no;

                        } else {
                            $output = [
                                'success' => false,
                                'msg' => __('messages.duplicate_order'),
                            ];

                            return $output;
                        }
                    }
                }
                $dt = [
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'customer_id' => $one['customer_id'],
                    'product_id' => $one['product_id'],
                    'order_number' => $one['order_number'],
                    'order_date' => Carbon::parse($one['order_date'])->format('Y-m-d'),
                    'price' => $price,
                    'discount' => $unit_discount,
                    'qty' => $qty,
                    'amount' => $amount,
                    'sub_total' => $sub_total,
                    'total_discount' => $total_discount,
                    'outstanding' => $this->productUtil->num_uf($one['outstanding']),
                    'credit_limit' => $one['credit_limit'],
                    'customer_reference' => $one['customer_reference'],
                    'note' => $one['note'],
                    'is_from_pumper' => 1,
                    // DAY1-ORPHAN: no source pump_operator_payments row is in scope in this DailyVoucher write path.
                ];
                // DAY1-ORPHAN: no pump_payment_id in scope — routes through Reconciler's orphan path (always inserts).
                $credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, null, 'settlement_credit_sale_payments', $dt);

                // store the customer reference
                if (! empty($credit_sale_payment->customer_reference)) {
                    $customer = Contact::findOrFail($credit_sale_payment->customer_id);
                    $name = $customer->name;
                    $barcode_string = $name.'.'.$credit_sale_payment->customer_reference;
                    $qr = new DNS2D;
                    $qr = $qr->getBarcodePNG($barcode_string, 'QRCODE');
                    $src = 'data:image/png;base64,'.$qr;

                    $ref_data = [
                        'business_id' => $credit_sale_payment->business_id,
                        'date' => date('Y-m-d', strtotime($credit_sale_payment->order_date)),
                        'contact_id' => $credit_sale_payment->customer_id,
                        'reference' => $credit_sale_payment->customer_reference,
                        'barcode_src' => $src,
                    ];
                    CustomerReference::updateOrCreate(['business_id' => $credit_sale_payment->business_id, 'contact_id' => $credit_sale_payment->customer_id, 'reference' => $credit_sale_payment->customer_reference], $ref_data);

                }

                $customer_reference = CustomerReference::where('reference', $credit_sale_payment->customer_reference)->first()->id ?? 0;

                $daily_vouchers_no = (DailyVoucher::where('business_id', $business_id)->count()) + 1;

                $daily_shift_id = PetroDailyShift::where('business_id', $business_id)
                    ->where('id', $request->daily_shift_id)
                    ->value('id');

                $data = [
                    'business_id' => $business_id,
                    'transaction_date' => date('Y-m-d', strtotime($credit_sale_payment->order_date)),
                    'daily_vouchers_no' => $daily_vouchers_no,
                    'location_id' => $pump_operator->location_id,

                    'pump_id' => null,

                    'operator_id' => $pump_operator->id,
                    'shift_id' => $daily_shift_id,
                    'customer_id' => $credit_sale_payment->customer_id,
                    'current_outstanding' => $this->productUtil->num_uf($one['outstanding']),
                    'outstanding_pending' => $this->productUtil->num_uf($one['outstanding']),

                    'voucher_order_number' => $one['order_number'],
                    'voucher_order_date' => Carbon::parse($credit_sale_payment->order_date)->format('Y-m-d'),
                    'status' => 1,
                    'created_by' => Auth::user()->id,

                    'vehicle_no' => $customer_reference,
                    'total_amount' => $sub_total,
                ];

                $daily_voucher = DailyVoucher::create($data);
                $credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                    ->editCreditSale($business_id, $credit_sale_payment->id, [
                        'daily_voucher_id' => $daily_voucher->id,
                    ]);

                $details = [
                    'business_id' => $business_id,
                    'daily_voucher_id' => $daily_voucher->id,
                    'product_id' => $this->productUtil->num_uf($one['product_id']),
                    'unit_price' => $this->productUtil->num_uf($price),
                    'qty' => $qty,
                    'sub_total' => $this->productUtil->num_uf($sub_total),

                ];
                DailyVoucherItem::create($details);

                // fetch customer's uncreditted credit sales
                $uncreditted = SettlementCreditSalePayment::where('customer_id', $one['customer_id'])->whereNull('is_committed')->where('is_from_pumper', 1)->sum('sub_total') ?? 0;

                $total_paid = 0;

                $business_id = request()->session()->get('user.business_id');
                $business = Business::where('id', $business_id)->first();
                $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

                $contact = Contact::where('id', $credit_sale_payment->customer_id)->first();

                $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'credit_sale')->first();

                $final_total = $credit_sale_payment->amount - $credit_sale_payment->total_discount;

                if (! empty($msg_template) && $contact->credit_notification == 'pumper_dashboard') {

                    $msg = $msg_template->sms_body;
                    $msg = str_replace('{business_name}', $business->name, $msg);
                    $msg = str_replace('{total_amount}', $this->productUtil->num_f($final_total), $msg);
                    $msg = str_replace('{contact_name}', $contact->name, $msg);
                    $msg = str_replace('{invoice_number}', '', $msg);
                    $msg = str_replace('{transaction_date}', date('Y-m-d', strtotime($credit_sale_payment->order_date)), $msg);
                    $msg = str_replace('{paid_amount}', $this->productUtil->num_f($total_paid), $msg);
                    $msg = str_replace('{due_amount}', $this->productUtil->num_f($final_total - $total_paid), $msg);
                    $msg = str_replace('{cumulative_due_amount}', $this->productUtil->num_f($this->contactUtil->getCustomerBalance($credit_sale_payment->customer_id, $business_id, true) + $uncreditted), $msg);

                    $phones = [$contact->mobile, $contact->alternate_number];

                    if (! empty($phones)) {
                        $data = [
                            'sms_settings' => $sms_settings,
                            'mobile_number' => implode(',', $phones),
                            'sms_body' => $msg,
                        ];

                        $response = $this->businessUtil->sendSms($data, 'credit_sale', $contact);
                    }
                }

            }

            $output = [
                'success' => true,
                'msg' => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: '.$e->getFile().'Line: '.$e->getLine().'Message: '.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
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
        $business_id = request()->session()->get('business.id');
        $payment_id = request('payment_id');
        $credit_sale_id = request('credit_sale_id');

        // Load daily voucher with relationships
        $daily_voucher = DailyVoucher::leftjoin('pump_operators', 'daily_vouchers.operator_id', 'pump_operators.id')
            ->leftjoin('contacts', 'daily_vouchers.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'daily_vouchers.vehicle_no', 'customer_references.id')
            ->where('daily_vouchers.id', $id)
            ->where('daily_vouchers.business_id', $business_id)
            ->select(
                'daily_vouchers.*',
                'pump_operators.name as operator_name',
                'contacts.name as customer_name',
                'customer_references.reference as vehicle_reference'
            )
            ->first();

        if (empty($daily_voucher)) {
            abort(404, 'Daily Voucher not found');
        }

        // Check if already settled
        if (! empty($daily_voucher->settlement_no)) {
            return response()->json([
                'success' => false,
                'msg' => __('petrogeneral::lang.cannot_edit_settled_voucher'),
            ]);
        }

        // Load daily voucher items
        $daily_voucher_items = DailyVoucherItem::leftjoin('products', 'daily_voucher_items.product_id', 'products.id')
            ->where('daily_voucher_items.daily_voucher_id', $id)
            ->select('daily_voucher_items.*', 'products.name as product_name')
            ->get();

        // Load related settlement credit sale payment
        $credit_sale_payment_query = SettlementCreditSalePayment::where('daily_voucher_id', $id);
        if (! empty($credit_sale_id)) {
            $credit_sale_payment_query->where('id', $credit_sale_id);
        }
        $credit_sale_payment = $credit_sale_payment_query->first();

        // Get dropdown data
        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $busness_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        return view('petrogeneral::daily_collection.partials.edit_daily_voucher')->with(compact(
            'daily_voucher',
            'daily_voucher_items',
            'credit_sale_payment',
            'customers',
            'pumps',
            'pump_operators',
            'busness_locations',
            'products',
            'payment_id',
            'credit_sale_id'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('business.id');

            // Load daily voucher
            $daily_voucher = DailyVoucher::where('id', $id)
                ->where('business_id', $business_id)
                ->first();

            if (empty($daily_voucher)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('petrogeneral::lang.daily_voucher_not_found'),
                ]);
            }

            // Check if already settled
            if (! empty($daily_voucher->settlement_no)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('petrogeneral::lang.cannot_edit_settled_voucher'),
                ]);
            }

            $data = $request->credit_data;
            if (empty($data) || ! is_array($data) || count($data) == 0) {
                return response()->json([
                    'success' => false,
                    'msg' => __('petrogeneral::lang.no_data_to_update'),
                ]);
            }

            // Get the first item data (since daily voucher typically has one item)
            $one = $data[0];

            $price = $this->productUtil->num_uf($one['price']);
            $unit_discount = $this->productUtil->num_uf($one['unit_discount']);
            $qty = $this->productUtil->num_uf($one['qty']);
            $amount = $this->productUtil->num_uf($one['amount']);
            $sub_total = $this->productUtil->num_uf($one['sub_total']);
            $total_discount = $this->productUtil->num_uf($one['total_discount']);

            // Update daily voucher
            $daily_voucher->update([
                'customer_id' => $one['customer_id'],
                'product_id' => $one['product_id'] ?? null,
                'voucher_order_number' => $one['order_number'],
                'voucher_order_date' => Carbon::parse($one['order_date'])->format('Y-m-d'),
                'transaction_date' => Carbon::parse($one['order_date'])->format('Y-m-d'),
                'current_outstanding' => $this->productUtil->num_uf($one['outstanding']),
                'outstanding_pending' => $this->productUtil->num_uf($one['outstanding']),
                'total_amount' => $sub_total,
                'vehicle_no' => $one['customer_reference'] ?? null,
            ]);

            // Update or create daily voucher item
            $daily_voucher_item = DailyVoucherItem::where('daily_voucher_id', $id)->first();
            if ($daily_voucher_item) {
                $daily_voucher_item->update([
                    'product_id' => $one['product_id'],
                    'unit_price' => $price,
                    'qty' => $qty,
                    'sub_total' => $sub_total,
                ]);
            } else {
                DailyVoucherItem::create([
                    'business_id' => $business_id,
                    'daily_voucher_id' => $id,
                    'product_id' => $one['product_id'],
                    'unit_price' => $price,
                    'qty' => $qty,
                    'sub_total' => $sub_total,
                ]);
            }

            // Update settlement credit sale payment
            $credit_sale_payment_query = SettlementCreditSalePayment::where('daily_voucher_id', $id);
            if (! empty($request->input('credit_sale_id'))) {
                $credit_sale_payment_query->where('id', $request->input('credit_sale_id'));
            }
            $credit_sale_payment = $credit_sale_payment_query->first();
            if ($credit_sale_payment) {
                $old_credit_sale_amount = $credit_sale_payment->amount;

                $credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                    ->editCreditSale($business_id, $credit_sale_payment->id, [
                        'customer_id' => $one['customer_id'],
                        'product_id' => $one['product_id'],
                        'order_number' => $one['order_number'],
                        'order_date' => Carbon::parse($one['order_date'])->format('Y-m-d'),
                        'price' => $price,
                        'discount' => $unit_discount,
                        'qty' => $qty,
                        'amount' => $amount,
                        'sub_total' => $sub_total,
                        'total_discount' => $total_discount,
                        'outstanding' => $this->productUtil->num_uf($one['outstanding']),
                        'credit_limit' => $one['credit_limit'] ?? null,
                        'customer_reference' => $one['customer_reference'] ?? null,
                        'note' => $request->note ?? null,
                    ]);

                if (! empty($credit_sale_payment->collection_form_no)) {
                    $pump_operator_payment_query = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $credit_sale_payment->pump_operator_id)
                        ->where('payment_type', 'credit');

                    if (! empty($request->input('payment_id'))) {
                        $pump_operator_payment_query->where('id', $request->input('payment_id'));
                    } else {
                        $pump_operator_payment_query
                            ->where('collection_form_no', $credit_sale_payment->collection_form_no)
                            ->where('payment_amount', $old_credit_sale_amount);
                    }

                    $pump_operator_payment = $pump_operator_payment_query->orderByDesc('id')->first();
                    if ($pump_operator_payment) {
                        $pump_operator_payment->update([
                            'payment_amount' => $amount,
                            'note' => $request->note ?? null,
                        ]);
                    }
                }
            }

            // Update related transaction if exists
            $transaction = null;
            if (! empty($credit_sale_payment) && ! empty($credit_sale_payment->transaction_id)) {
                $transaction = Transaction::find($credit_sale_payment->transaction_id);
            }

            if ($transaction) {
                $transaction->update([
                    'contact_id' => $one['customer_id'],
                    'final_total' => $sub_total,
                    'transaction_date' => Carbon::parse($one['order_date'])->format('Y-m-d'),
                    'ref_no' => $one['order_number'] ?? null,
                    'additional_notes' => $request->note ?? 'Daily credit sale updated from Daily Collection',
                ]);

                // Update contact ledger
                $contact_ledger = ContactLedger::where('transaction_id', $transaction->id)
                    ->where('sub_type', 'daily_credit_sale')
                    ->first();
                if ($contact_ledger) {
                    $contact_ledger->update([
                        'contact_id' => $one['customer_id'],
                        'amount' => $sub_total,
                        'operation_date' => Carbon::parse($one['order_date'])->format('Y-m-d'),
                        'note' => $request->note ?? 'Daily credit sale updated from Daily Collection',
                    ]);
                }
            }

            $output = [
                'success' => true,
                'msg' => __('petrogeneral::lang.daily_voucher_updated_successfully'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: '.$e->getFile().'Line: '.$e->getLine().'Message: '.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getCustomerReference($id)
    {
        $refs = CustomerReference::where('contact_id', $id)->select('reference', 'id')->get();

        $html = '<option>Please Select</option>';

        foreach ($refs as $ref) {
            $html .= '<option value="'.$ref->id.'">'.$ref->reference.'</option>';
        }

        return $html;
    }

    public function getProductPrice($id)
    {
        $price = Product::leftjoin('variations', 'products.id', 'variations.product_id')
            ->where('variations.product_id', $id)
            ->select('default_sell_price')
            ->first();

        if (! empty($price)) {
            return $this->productUtil->num_f($price->default_sell_price);
        } else {
            return '0';
        }
    }

    public function getProductRow()
    {
        $index = request()->index;
        $business_id = request()->session()->get('business.id');
        $products = Product::where('business_id', $business_id)->pluck('name', 'id');

        return view('petrogeneral::daily_collection.partials.product_row')->with(compact('products', 'index'));
    }

    public function print($id)
    {
        $daily_voucher = DailyVoucher::leftjoin('pumps', 'daily_vouchers.pump_id', 'pumps.id')
            ->leftjoin('pump_operators', 'daily_vouchers.operator_id', 'pump_operators.id')
            ->leftjoin('contacts', 'daily_vouchers.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'daily_vouchers.vehicle_no', 'customer_references.id')
            ->leftjoin('users', 'daily_vouchers.created_by', 'users.id')
            ->where('daily_vouchers.id', $id)
            ->select(
                'daily_vouchers.*',
                'pumps.pump_name',
                'pump_operators.name as operator_name',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        $daily_voucher_items = DailyVoucherItem::leftjoin('products', 'daily_voucher_items.product_id', 'products.id')
            ->where('daily_voucher_items.daily_voucher_id', $id)
            ->select('daily_voucher_items.*', 'products.name as product_name')
            ->get();

        // Check print size parameter - default to 80mm
        $print_size = request()->get('print_size', '80mm');
        
        if ($print_size === 'a5') {
            return view('petrogeneral::daily_collection.partials.print_daily_voucher_a5')->with(compact('daily_voucher', 'daily_voucher_items'));
        }
        
        // Default: 80mm print
        return view('petrogeneral::daily_collection.partials.print_daily_voucher')->with(compact('daily_voucher', 'daily_voucher_items'));
    }
}
