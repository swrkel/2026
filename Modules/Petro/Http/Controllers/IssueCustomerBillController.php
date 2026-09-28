<?php
namespace Modules\Petro\Http\Controllers;

use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Product;
use App\System;
use App\Transaction;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DailyVoucherItem;
use Modules\Petro\Entities\IssueCustomerBill;
use Modules\Petro\Entities\IssueCustomerBillDetail;
use Modules\Petro\Entities\IssueCustomerBillSetting;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorMapping;
use Yajra\DataTables\Facades\DataTables;

class IssueCustomerBillController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $contactUtil;

    protected $commonUtil;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ContactUtil $contactUtil)
    {
        $this->productUtil = $productUtil;
        $this->contactUtil = $contactUtil;
        $this->commonUtil  = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('issue_customer_bill.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id              = request()->session()->get('business.id');
        $issueCustomerBillSetting = $this->getIssueCustomerBillSettings($business_id);

        $business_details   = Business::find($business_id);
        $currency_precision = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;

        if (request()->ajax()) {
            $issue_customer_bills = IssueCustomerBill::leftjoin('pumps', 'issue_customer_bills.pump_id', 'pumps.id')
                ->leftjoin('pump_operators', 'issue_customer_bills.operator_id', 'pump_operators.id')
                ->leftjoin('contacts', 'issue_customer_bills.customer_id', 'contacts.id')
                ->leftjoin('customer_references', 'issue_customer_bills.reference_id', 'customer_references.id')
                ->leftjoin('users', 'issue_customer_bills.created_by', 'users.id')
                ->where('issue_customer_bills.business_id', $business_id)
                ->select(
                    'issue_customer_bills.*',
                    'pumps.pump_name',
                    'pump_operators.name as operator_name',
                    'customer_references.reference',
                    'contacts.name as customer_name',
                    'users.username as username'
                );

            return DataTables::of($issue_customer_bills)
                ->editColumn('total_amount', function ($row) use ($currency_precision) {
                    return number_format($row->total_amount, $currency_precision, '.', ',');
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                    __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    if (auth()->user()->can('issue_customer_bill.view')) {
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Petro\Http\Controllers\IssueCustomerBillController@print', $row->id) . '" class="print_bill" ><i class="fa fa-print" aria-hidden="true"></i>' . __("messages.print") . '</a></li>';
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('petro::issue_bill_customer.index', compact('issueCustomerBillSetting'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('issue_customer_bill.add')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('business.id');

        $customers            = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        $pumps                = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');
        $pump_operators       = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $customer_bill_no     = (IssueCustomerBill::where('business_id', $business_id)->count()) + 1;
        $business_locations   = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        $products             = Product::where('business_id', $business_id)->forModule('billtocustomer_issuecustomerbills')->pluck('name', 'id');
        $business_details     = Business::find($business_id);
        $currency_precision   = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
        $walk_in_customer     = $this->contactUtil->getWalkInCustomer($business_id);
        $default_customer_id  = $walk_in_customer['id'] ?? null;
        $default_reference_id = null;

        if (! empty($default_customer_id)) {
            $default_reference = CustomerReference::where('contact_id', $default_customer_id)
                ->orderByRaw("CASE WHEN LOWER(reference) = ? THEN 0 ELSE 1 END", ['no vehicle no'])
                ->orderBy('id')
                ->first();
            $default_reference_id = $default_reference->id ?? null;
        }

        return view('petro::issue_bill_customer.create')->with(compact(
            'customers',
            'pumps',
            'pump_operators',
            'customer_bill_no',
            'business_locations',
            'products',
            'currency_precision',
            'default_customer_id',
            'default_reference_id'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Modules\Petro\Support\PetroDebug::info('Issue Customer Bill Store Request: ');
        \Modules\Petro\Support\PetroDebug::info('Issue Customer Bill request received');

        try {
            $business_id = request()->session()->get('business.id');

            DB::beginTransaction();

            $issue_customer_bill = IssueCustomerBill::create([
                'business_id'           => $business_id,
                'date'                  => Carbon::parse($request->date)->format('Y-m-d H:i:s'),
                'customer_bill_no'      => $request->customer_bill_no,
                'location_id'           => $request->location_id,
                'pump_id'               => $request->pump_id,
                'operator_id'           => $request->operator_id,
                'customer_id'           => $request->customer_id,
                'reference_id'          => $request->reference_id,
                'order_bill_no'         => $request->order_voucher_no,
                'show_in_daily_voucher' => $request->show_in_daily_voucher,
                'created_by'            => Auth::user()->id,
            ]);

            $total_amount = 0;
            foreach ($request->issue_customer_bill as $bill_detail) {
                $qty        = str_replace(',', '', $bill_detail['qty']);
                $unit_price = str_replace(',', '', $bill_detail['unit_price']);
                $sub_total  = str_replace(',', '', $bill_detail['sub_total']);
                $total_amount += $sub_total;

                IssueCustomerBillDetail::create([
                    'business_id'   => $business_id,
                    'issue_bill_id' => $issue_customer_bill->id,
                    'product_id'    => $bill_detail['product_id'],
                    'unit_price'    => $unit_price,
                    'qty'           => $qty,
                    'discount'      => $bill_detail['discount'],
                    'tax'           => 0,
                    'sub_total'     => $sub_total,
                ]);
            }

            $issue_customer_bill->total_amount = $total_amount;
            $issue_customer_bill->save();

            $transaction = Transaction::create([
                'type'                  => 'issue_customer_bill',
                'status'                => 'due',
                'business_id'           => $business_id,
                'transaction_date'      => Carbon::parse($request->date),
                'final_total'           => $total_amount,
                'contact_id'            => $request->customer_id,
                'location_id'           => $request->location_id,
                'created_by'            => Auth::user()->id,
                'transaction_reference' => $issue_customer_bill->customer_bill_no,
            ]);

            $transaction_id   = $transaction->id;
            $transaction_date = $transaction->transaction_date;
            $shift_number     = $request->shift_number ?? null;

            $ar_account_id = $this->commonUtil->account_exist_return_id('Accounts Receivable');

            if (! empty($ar_account_id)) {
                AccountTransaction::createAccountTransaction([
                    'amount'                 => $total_amount,
                    'account_id'             => $ar_account_id,
                    'type'                   => 'debit',
                    'operation_date'         => $transaction_date,
                    'note'                   => 'Issue Customer Bill #' . $issue_customer_bill->customer_bill_no,
                    'shift_number'           => $shift_number,
                    'created_by'             => Auth::id(),
                    'transaction_id'         => $transaction_id,
                    'transaction_payment_id' => null,
                    'sub_type'               => 'issue_customer_bill',
                    'contact_id'             => $request->customer_id,
                ]);
            }

            ContactLedger::createContactLedger([
                'contact_id'             => $request->customer_id,
                'amount'                 => $total_amount,
                'type'                   => 'debit',
                'operation_date'         => $transaction_date,
                'created_by'             => Auth::id(),
                'note'                   => 'Issue Customer Bill No:' . $issue_customer_bill->customer_bill_no,
                'transaction_id'         => $transaction_id,
                'transaction_payment_id' => null,
                'sub_type'               => 'issue_customer_bill',
            ]);

            if ($request->show_in_daily_voucher == 1) {
                $customer_details  = app('App\Http\Controllers\SellPosController')->getCustomerDetails($request);
                $daily_vouchers_no = (DailyVoucher::where('business_id', $business_id)->count()) + 1;

                $daily_voucher = DailyVoucher::create([
                    'business_id'            => $business_id,
                    'transaction_date'       => $transaction_date->format('Y-m-d'),
                    'daily_vouchers_no'      => $daily_vouchers_no,
                    'location_id'            => $request->location_id,
                    'pump_id'                => $request->pump_id,
                    'operator_id'            => $request->operator_id,
                    'customer_id'            => $request->customer_id,
                    'voucher_order_number'   => $request->order_voucher_no,
                    'voucher_order_date'     => Carbon::parse($request->order_voucher_date)->format('Y-m-d'),
                    'vehicle_no'             => $request->reference_id,
                    'current_outstanding'    => $customer_details['due_amount'],
                    'outstanding_pending'    => $customer_details['due_amount'],
                    'total_amount'           => $total_amount,
                    'is_issue_customer_bill' => 1,
                    'status'                 => 1,
                    'created_by'             => Auth::user()->id,
                ]);

                foreach ($request->issue_customer_bill as $bill_detail) {
                    DailyVoucherItem::create([
                        'business_id'      => $business_id,
                        'daily_voucher_id' => $daily_voucher->id,
                        'product_id'       => $bill_detail['product_id'],
                        'unit_price'       => $bill_detail['unit_price'],
                        'qty'              => $bill_detail['qty'],
                        'sub_total'        => $bill_detail['sub_total'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg'     => __('petro::lang.issue_customer_bill_create_success'),
                'id'      => $issue_customer_bill->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            return redirect()->back()->with('status', [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ]);
        }
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

        $html = '<option value="">No vehicle No</option>';

        foreach ($refs as $ref) {
            $html .= '<option value="' . $ref->id . '">' . $ref->reference . '</option>';
        }

        return $html;
    }

    public function getProductPrice($id)
    {
        $business_id        = request()->session()->get('business.id');
        $business_details   = Business::find($business_id);
        $currency_precision = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;

        $price = Product::leftjoin('variations', 'products.id', 'variations.product_id')
            ->where('variations.product_id', $id)
            ->select('sell_price_inc_tax', 'default_sell_price', 'dpp_inc_tax', 'category_id', 'sub_category_id')
            ->first();

        if (! empty($price)) {
            $multiplier = $price->default_sell_price;

            return ['unit_price' => number_format($price->sell_price_inc_tax, $currency_precision, '.', ','), 'unit_price_excl' => number_format($multiplier, $currency_precision, '.', ',')];
        } else {
            return ['unit_price' => '0', 'unit_price_excl' => 0];
        }
    }

    public function getProductRow()
    {
        $index              = request()->index;
        $business_id        = request()->session()->get('business.id');
        $business_details   = Business::find($business_id);
        $currency_precision = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
        $products           = Product::where('business_id', $business_id)->forModule('billtocustomer_issuecustomerbills')->pluck('name', 'id');
        return view('petro::issue_bill_customer.partials.product_row')->with(compact('products', 'index', 'currency_precision'));
    }

    public function print($id)
    {
        $issue_customer_bill = IssueCustomerBill::leftjoin('pumps', 'issue_customer_bills.pump_id', 'pumps.id')
            ->leftjoin('pump_operators', 'issue_customer_bills.operator_id', 'pump_operators.id')
            ->leftjoin('contacts', 'issue_customer_bills.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'issue_customer_bills.reference_id', 'customer_references.id')
            ->leftjoin('users', 'issue_customer_bills.created_by', 'users.id')
            ->where('issue_customer_bills.id', $id)
            ->select(
                'issue_customer_bills.*',
                'pumps.pump_name',
                'pump_operators.name as operator_name',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        $bill_details = IssueCustomerBillDetail::leftjoin('products', 'issue_customer_bill_details.product_id', 'products.id')
            ->where('issue_customer_bill_details.issue_bill_id', $id)
            ->select('issue_customer_bill_details.*', 'products.name as product_name')
            ->get();

        $business_id           = request()->session()->get('business.id');
        $business_location     = BusinessLocation::where('business_id', $business_id)->first();
        $business_details      = Business::find($business_id);
        $currency_precision    = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
        $total_before_discount = number_format($bill_details->sum(function ($item) {
            return $item->unit_price * $item->qty;
        }), $currency_precision, '.', ',');
        $total_discount              = number_format($bill_details->sum('discount'), $currency_precision, '.', ',');
        $total_after_discount        = number_format($bill_details->sum('sub_total'), $currency_precision, '.', ',');
        $issue_customer_bill_setting = $this->getIssueCustomerBillSettings($business_id);
        $admin_invoice_footer        = System::getProperty('admin_invoice_footer');

        return view('petro::issue_bill_customer.print')->with(compact('issue_customer_bill', 'bill_details', 'business_location', 'currency_precision', 'total_before_discount', 'total_discount', 'total_after_discount', 'issue_customer_bill_setting', 'admin_invoice_footer'));
    }

    public function getIssueCustomerBillSetting($pump_id)
    {
        $pump_operator_setting = PumpOperatorMapping::join('pumps', 'pumps.id', '=', 'pump_operator_mappings.pump_id')
            ->leftjoin('variations', 'pumps.product_id', 'variations.product_id')
            ->select(
                'pump_operator_mappings.operator_id',
                'pumps.product_id',
                'variations.sell_price_inc_tax as unit_price',
            )
            ->where('pump_operator_mappings.pump_id', $pump_id)
            ->first();

        if (! $pump_operator_setting) {
            return response()->json([
                'success' => false,
                'message' => 'No setting found for this pump.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'operator_id' => $pump_operator_setting->operator_id,
                'product_id'  => $pump_operator_setting->product_id,
            ],
        ]);
    }

    private function getIssueCustomerBillSettings($business_id)
    {
        $fallback = (object) [
            'default_customer_id' => null,
            'default_vehicle_id'  => null,
            'show_pump'           => 0,
            'show_fuel'           => 0,
            'show_amount'         => 0,
            'show_price'          => 0,
            'show_meter'          => 0,
            'show_liters'         => 0,
            'show_narration'      => 0,
            'print_size'          => null,
            'other_settings'      => null,
            'show_pump_operator'  => 0,
            'print_option'        => 1,
        ];

        if (! \Modules\Petro\Support\SchemaCapabilityCache::hasTable('issue_customer_bill_settings')) {
            return $fallback;
        }

        return IssueCustomerBillSetting::where('business_id', $business_id)->first() ?? $fallback;
    }
}
