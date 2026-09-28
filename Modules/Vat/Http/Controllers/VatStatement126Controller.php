<?php

namespace Modules\Vat\Http\Controllers;

use App\BusinessLocation;
// Separation step 3 (document 5-18): the shared `contacts` table is now
// reached through a VAT-owned model, so this file no longer depends on the
// core App\Contact class when the Contact module is retired for Customers.
// NOTE: SharedContact maps to `contacts`; the existing VatContact entity
// maps to `vat_contacts` and is a different data set.
use Modules\Vat\Entities\SharedContact as Contact;
use App\Customer;
use App\TaxRate;

use Illuminate\Routing\Controller;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use Modules\Vat\Entities\VatInvoice2;
use Modules\Vat\Entities\VatInvoiceDetail2;
use Modules\Vat\Entities\VatUserInvoicePrefix;
use Modules\Vat\Entities\VatInvoicePayment2;
use Modules\Vat\Entities\VatInvoice2Prefix;

use Yajra\DataTables\Facades\DataTables;
use App\Business;
use App\NotificationTemplate;
use App\System;

use App\Transaction;
use App\TransactionPayment;
use App\AccountTransaction;
use App\ContactLedger;
use App\Variation;
use Modules\Vat\Entities\VatCreditBill;

use Modules\Vat\Entities\VatSupplyFrom;
use Modules\Vat\Entities\VatBankDetail;
use Modules\Vat\Entities\VatConcern;
// Separation step 4 (document 5-18): the shared `customer_references` table
// is reached through a VAT-owned model, so this file no longer depends on the
// core App\CustomerReference class when the Contact module is retired.
use Modules\Vat\Entities\SharedCustomerReference as CustomerReference;

use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Utils\ContactUtil;
use Illuminate\Support\Carbon;


class VatStatement126Controller extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;

    protected $commonUtil;

    private function currentBusinessId()
    {
        return request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: (auth()->check() ? auth()->user()->business_id : null);
    }
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;
    protected $productUtil;
    protected $contactUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(
        Util $commonUtil,
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        BusinessUtil $businessUtil,
        ProductUtil $productUtil,
        ContactUtil $contactUtil
    ) {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->contactUtil = $contactUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = request()->session()->get('business.id');

        if (request()->ajax()) {
            $statements = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
                ->leftjoin('contacts as subc', 'vat_invoices_2.sub_customer', 'subc.id')
                ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
                ->where('vat_invoices_2.business_id', $business_id)
                ->select(
                    'vat_invoices_2.id',
                    'vat_invoices_2.date',
                    'vat_invoices_2.customer_bill_no',
                    'vat_invoices_2.total_amount',
                    'vat_invoices_2.credit_limit',
                    'vat_invoices_2.outstanding_amount',
                    'vat_invoices_2.customer_id',
                    'vat_invoices_2.sub_customer',
                    'contacts.name as customer_name',
                    'subc.name as sub_customer_name',
                    'users.username as username'
                );
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $statements = $statements->whereDate('vat_invoices_2.date', '>=', request()->start_date);
                $statements = $statements->whereDate('vat_invoices_2.date', '<=', request()->end_date);
            }
            if (!empty(request()->contact_id)) {
                $statements = $statements->where('vat_invoices_2.customer_id', request()->contact_id);
            }
            if (!empty(request()->sub_contact_id)) {
                $statements = $statements->where('vat_invoices_2.sub_customer', request()->sub_contact_id);
            }
            if (!empty(request()->customer_bill_no)) {
                $statements = $statements->where('vat_invoices_2.customer_bill_no', request()->customer_bill_no);
            }
            $statements = $statements->orderby('vat_invoices_2.id', 'desc')->get();

            return DataTables::of($statements)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    $html .= '<li>
                        <a href="#"
                            data-print-old="' . action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print', $row->id) . '"
                            data-print-2026="' . action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print_design_2026', $row->id) . '"
                            data-print-126="' . action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print_126', $row->id) . '"
                            class="print_bill">
                            <i class="fa fa-print"></i> ' . __("messages.print") . '
                        </a>
                    </li>';
                    $html .= '<li><a href="' . action('\Modules\Vat\Http\Controllers\VatStatement126Controller@edit', $row->id) . '" class="" ><i class="fa fa-edit" aria-hidden="true"></i>' . __("messages.edit") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . action("\Modules\Vat\Http\Controllers\VatStatement126Controller@destroy", [$row->id]) . '" class="delete-statement-126"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';

                    $html .=  '</ul></div>';
                    return $html;
                })
                ->editColumn('total_amount', function ($row) {
                    $business_id = session()->get('user.business_id');
                    $business_details = Business::find($business_id);
                    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
                    $value = floatval($row->total_amount ?? 0);
                    return number_format($value, $currency_precision, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',');
                })
                ->editColumn('credit_limit', function ($row) {
                    $business_id = session()->get('user.business_id');
                    $business_details = Business::find($business_id);
                    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
                    $value = floatval($row->credit_limit ?? 0);
                    return number_format($value, $currency_precision, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',');
                })
                ->editColumn('outstanding_amount', function ($row) {
                    $business_id = session()->get('user.business_id');
                    $business_details = Business::find($business_id);
                    $currency_precision = !empty($business_details->currency_precision) ? $business_details->currency_precision : 2;
                    $value = floatval($row->outstanding_amount ?? 0);
                    return number_format($value, $currency_precision, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',');
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('sub_customer_name', function ($row) {
                    return $row->sub_customer_name ?? '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $contact_dropdown = Contact::contactDropdown($business_id, false, true, true, 'customer');
        $bill_no_dropdown = VatInvoice2::where('business_id', $business_id)
            ->pluck('customer_bill_no', 'customer_bill_no');
        $bill_no_dropdown = $bill_no_dropdown->prepend(__('lang_v1.none'), '');
        $vat_print_2026_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_print_2026');
        return view('vat::statement126.index')
            ->with(compact('contact_dropdown', 'bill_no_dropdown','vat_print_2026_enabled'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = request()->session()->get('business.id');

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->forModule('vat_statement126')->pluck('name', 'id');

        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes', 'vat_invoice2_prefixes.id', 'vat_user_invoice_prefixes.prefix_id2')
            ->where('vat_user_invoice_prefixes.business_id', $business_id)
            ->where('vat_user_invoice_prefixes.user_id', auth()->user()->id)
            ->pluck('vat_invoice2_prefixes.prefix', 'vat_invoice2_prefixes.id');

        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        $payment_types = $this->productUtil->payment_types(null, false, false, false, false, true);
        $vat_print_2026_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_print_2026');

        return view('vat::statement126.create')->with(compact(
            'customers',
            'products',
            'prefixes',
            'business_locations',
            'payment_types','vat_print_2026_enabled'
        ));
    }

    public function getPrefixes($id)
    {
        /** @var VatInvoice2Prefix|null $prefixes */
        $prefixes = VatInvoice2Prefix::find($id);
        if (!$prefixes instanceof VatInvoice2Prefix) {
            abort(404);
        }
        $business_id = request()->session()->get('business.id');

        $existing = VatInvoice2::where('business_id', $business_id)->where('prefix', $id)->get()->last();
        $starting_no_string = (string) $prefixes->starting_no;
        $starting_no_numeric = (int) $starting_no_string;
        $pad_length = max(strlen($starting_no_string), 1);

        if (!empty($existing)) {
            $current_bill = $existing->customer_bill_no;
            preg_match('/(\d+)$/', (string) $current_bill, $number_match);
            $current_no = isset($number_match[1]) ? (int) $number_match[1] : 0;
            $next_no = $current_no >= $starting_no_numeric ? ($current_no + 1) : $starting_no_numeric;
        } else {
            $next_no = $starting_no_numeric;
        }

        $next_no_padded = str_pad((string) $next_no, $pad_length, '0', STR_PAD_LEFT);
        $new_prefix = (string) $prefixes->prefix . $next_no_padded;

        return array('bill_no' => $new_prefix);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $business_id = request()->session()->get('business.id');
            $is_print  = request()->is_print;

            $data = array(
                'business_id' => $business_id,
                'date' => Carbon::parse($request->voucher_order_date)->format('Y-m-d'),
                'customer_bill_no' => $request->customer_bill_no,
                'location_id' => $request->location_id,
                'customer_id' => $request->customer_id,
                'reference_id' => $request->reference_id,
                'prefix' => $request->prefix_id,
                'created_by' => Auth::user()->id,
                'total_amount' => $this->vatFormatter()->num_uf($request->voucher_order_amount),
                'outstanding_amount' => $this->vatFormatter()->num_uf($request->voucher_order_outstanding),
                'tax_amount' => $request->vat_total,
                'discount_amount' => 0,
                'credit_limit' => $request->voucher_order_creditlimit,
                'sub_customer' => $request->sub_customer,
                'invoice_to' => $request->invoice_to,
                'supplied_on' => $request->supplied_on,
                'place_of_supply' => $request->place_of_supply,
                'additional_information' => $request->additional_information,
                'price_adjustment' => $request->price_adjustment,
                'sale_type' => $request->sale_type
            );
            DB::beginTransaction();

            $statement = VatInvoice2::create($data);

            $total_amount = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $unit_vat_rate = 0;

            if (!empty($request->issue_customer_bill) && !empty($request->issue_customer_bill['product_id']) && is_array($request->issue_customer_bill['product_id'])) {
                foreach ($request->issue_customer_bill['product_id'] as $key => $product_id) {
                    $total_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]);
                    $tax_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]);
                    $discount_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]);
                    $unit_vat_rate += $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]);

                    $details = array(
                        'business_id' => $business_id,
                        'issue_bill_id' => $statement->id,
                        'product_id' => $product_id,
                        'unit_price' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price'][$key]),
                        'unit_price_before_tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price_excl'][$key]),
                        'qty' => $this->vatFormatter()->num_uf($request->issue_customer_bill['qty'][$key]),
                        'discount' => $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]),
                        'tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]),
                        'sub_total' => $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]),
                        'unit_vat_rate' =>  $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]),
                    );

                    VatInvoiceDetail2::create($details);
                }
            }

            if (!empty($request->payment) && is_array($request->payment)) {
                foreach ($request->payment as $payment) {
                    $payment_data = [
                        'invoice_id' => $statement->id,
                        'account_id' => $payment['account_id'],
                        'business_id' => $business_id,
                        'amount' => $this->vatFormatter()->num_uf($payment['amount']),
                        'method' => $payment['method'],
                        'card_transaction_number' => $payment['card_transaction_number'],
                        'cheque_number' => $payment['cheque_number'],
                        'cheque_date' => $payment['cheque_date'],
                        'bank_name' => $payment['bank_name'],
                        'paid_on' => Carbon::parse($request->voucher_order_date)->format('Y-m-d'),
                        'created_by' => auth()->user()->id,
                        'payment_for' => $request->customer_id,
                        'note' => $payment['note']
                    ];

                    VatInvoicePayment2::create($payment_data);
                }
            }

            $statement->total_amount = $total_amount;
            $statement->total_amount_words = $request->state_final_grand_total_words;
            $statement->tax_amount = $tax_amount;
            $statement->discount_amount = $discount_amount;
            $statement->unit_vat_rate_total = $unit_vat_rate;
            $statement->save();

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
             if($is_print || $is_print == true || $is_print == 'true'){
                if($request->print_format == 'vat_print_2026'){
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print_design_2026', $statement->id);
                } elseif ($request->print_format == '126_print') {
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print_126', $statement->id);
                } else{
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print', $statement->id);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => 'Store Error: ' . $e->getMessage()
            ];
        }

        return Redirect::to('vat-module/statement-126')->with('status', $output);
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

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)->forModule('vat_statement126')->pluck('name', 'id');

        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes', 'vat_invoice2_prefixes.id', 'vat_user_invoice_prefixes.prefix_id2')
            ->where('vat_user_invoice_prefixes.business_id', $business_id)
            ->where('vat_user_invoice_prefixes.user_id', auth()->user()->id)
            ->pluck('vat_invoice2_prefixes.prefix', 'vat_invoice2_prefixes.id');

        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        $payment_types = $this->productUtil->payment_types(null, false, false, false, false, true);

        $payment = VatInvoicePayment2::where('invoice_id', $id);
        $statement = VatInvoice2::findOrFail($id);
        $statement_details = VatInvoiceDetail2::where('issue_bill_id', $id)->get();
        $customer_ref = CustomerReference::where('business_id', $business_id)->where('contact_id', $statement->customer_id)->pluck('reference', 'id');

        $invoice = $statement;
        $invoice_details = $statement_details;
        $vat_print_2026_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_print_2026');

        return view('vat::statement126.edit')->with(compact(
            'customers',
            'products',
            'prefixes',
            'business_locations',
            'payment_types',
            'payment',
            'statement',
            'invoice',
            'invoice_details',
            'customer_ref',
            'statement_details',
            'vat_print_2026_enabled'
        ));
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
        try {
            $business_id = request()->session()->get('business.id');
            $is_print  = request()->is_print;

            $data = array(
                'business_id' => $business_id,
                'date' => Carbon::parse($request->voucher_order_date)->format('Y-m-d'),
                'customer_bill_no' => $request->customer_bill_no,
                'location_id' => $request->location_id,
                'customer_id' => $request->customer_id,
                'reference_id' => $request->reference_id,
                'prefix' => $request->prefix_id,
                'created_by' => Auth::user()->id,
                'total_amount' => $this->vatFormatter()->num_uf($request->voucher_order_amount),
                'tax_amount' => $request->vat_total,
                'discount_amount' => 0,
                'outstanding_amount' => $this->vatFormatter()->num_uf($request->voucher_order_outstanding),
                'credit_limit' => $request->voucher_order_creditlimit,
                'sub_customer' => $request->sub_customer,
                'invoice_to' => $request->invoice_to,
                'supplied_on' => $request->supplied_on,
                'place_of_supply'=> $request->place_of_supply,
                'additional_information' => $request->additional_information,
                'price_adjustment' => $request->price_adjustment,
                'sale_type' => $request->sale_type
            );
            DB::beginTransaction();

            VatInvoice2::where('id', $id)->update($data);
            $statement = VatInvoice2::findOrFail($id);

            // Delete old details and payments
            VatInvoiceDetail2::where('issue_bill_id', $id)->delete();
            VatInvoicePayment2::where('invoice_id', $id)->delete();

            $total_amount = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $unit_vat_rate = 0;

            if (!empty($request->issue_customer_bill) && !empty($request->issue_customer_bill['product_id']) && is_array($request->issue_customer_bill['product_id'])) {
                foreach ($request->issue_customer_bill['product_id'] as $key => $product_id) {
                    $total_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]);
                    $tax_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]);
                    $discount_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]);
                    $unit_vat_rate += $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]);

                    $details = array(
                        'business_id' => $business_id,
                        'issue_bill_id' => $statement->id,
                        'product_id' => $product_id,
                        'unit_price' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price'][$key]),
                        'unit_price_before_tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price_excl'][$key]),
                        'qty' => $this->vatFormatter()->num_uf($request->issue_customer_bill['qty'][$key]),
                        'discount' => $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]),
                        'tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]),
                        'sub_total' => $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]),
                        'unit_vat_rate' =>  $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]),
                    );

                    VatInvoiceDetail2::create($details);
                }
            }

            if (!empty($request->payment) && is_array($request->payment)) {
                foreach ($request->payment as $payment) {
                    $payment_data = [
                        'invoice_id' => $statement->id,
                        'account_id' => $payment['account_id'],
                        'business_id' => $business_id,
                        'amount' => $this->vatFormatter()->num_uf($payment['amount']),
                        'method' => $payment['method'],
                        'card_transaction_number' => $payment['card_transaction_number'],
                        'cheque_number' => $payment['cheque_number'],
                        'cheque_date' => $payment['cheque_date'],
                        'bank_name' => $payment['bank_name'],
                        'paid_on' => Carbon::parse($request->voucher_order_date)->format('Y-m-d'),
                        'created_by' => auth()->user()->id,
                        'payment_for' => $request->customer_id,
                        'note' => $payment['note']
                    ];

                    VatInvoicePayment2::create($payment_data);
                }
            }

            $statement->total_amount_words = $request->state_edit_final_grand_total_words;
            $statement->total_amount = $total_amount;
            $statement->tax_amount = $tax_amount;
            $statement->discount_amount = $discount_amount;
            $statement->unit_vat_rate_total = $unit_vat_rate;
            $statement->save();

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];

            if($is_print || $is_print == true || $is_print == 'true'){
                if($request->print_format == 'vat_print_2026'){
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print_design_2026', $statement->id);
                } elseif ($request->print_format == '126_print') {
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print_126', $statement->id);
                }else{
                     $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatStatement126Controller@print', $statement->id);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => 'Update Error: ' . $e->getMessage()
            ];
        }

        return Redirect::to('vat-module/statement-126')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $statement = VatInvoice2::findOrFail($id);

            VatInvoiceDetail2::where('issue_bill_id', $id)->delete();
            VatInvoicePayment2::where('invoice_id', $id)->delete();

            $statement->delete();
            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
    }

    public function getCustomerReference($id)
    {
        $refs = CustomerReference::where('contact_id', $id)->select('reference', 'id')->get();

        $html = '<option>Please Select</option>';

        foreach ($refs as $ref) {
            $html .= '<option value="' . $ref->id . '">' . $ref->reference . '</option>';
        }

        return $html;
    }

    public function getProductPrice($id)
    {
        $price = Product::leftjoin('variations', 'products.id', 'variations.product_id')
            ->where('variations.product_id', $id)
            ->select('sell_price_inc_tax')
            ->first();

        if (!empty($price)) {
            return $this->vatFormatter()->num_f($price->sell_price_inc_tax);
        } else {
            return '0';
        }
    }

    public function getProductRow()
    {
        $index = request()->index;
        $business_id = request()->session()->get('business.id');
        $products = Product::where('business_id', $business_id)->forModule('vat_statement126')->pluck('name', 'id');
        return view('vat::statement126.partials.product_row')->with(compact('products', 'index'));
    }

    public function print($id)
    {
        // Reuse the exact same layout as VAT Invoice 2 "old" print
        $statement = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        $bill_details = VatInvoiceDetail2::leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select('vat_invoice_details_2.*', 'products.name as product_name')
            ->get();

        $business_details = $this->businessUtil->getDetails($statement->business_id);

        $receipt_details = $this->__getReceiptDetails($statement);
        $receipt_details->price_adjustment =  $this->vatFormatter()->num_f($statement->price_adjustment, true, $business_details);
        $receipt_details->final_total =  $this->vatFormatter()->num_f($statement->total_amount + $statement->price_adjustment, true, $business_details);

        $payment_details = VatInvoicePayment2::where('invoice_id', $id)->get();

        // Use the existing VAT Invoice 2 print view so the layout is identical
        return view('vat::vat_invoice2.print')->with(compact('bill_details', 'receipt_details', 'payment_details'));
    }

    public function print_126($id)
    {
        // 126 Print should behave exactly like the old VAT print
        return $this->print($id);
    }


    public function print_design_2026($id)
    {
         $statement = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        // Increment print count for duplicate detection
       $invoiceRecord = VatInvoice2::findOrFail($id);

        $invoiceRecord->vat_statement_126_2026 =
            ($invoiceRecord->vat_statement_126_2026 ?? 0) + 1;

        $invoiceRecord->save();
        
        // Check if this is a duplicate print (print_count > 1)
        $vat_statement_126_2026_print = $invoiceRecord->vat_statement_126_2026;

        $statement_details = VatInvoiceDetail2::leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select('vat_invoice_details_2.*', 'products.name as product_name','products.barcode_type as product_code','products.product_description as product_description')
            ->get();

        $business_details = $this->businessUtil->getDetails($statement->business_id);
        $vat_logo = $business_details->vat_logo;
        $vat_logo_width  = $business_details->vat_logo_width;
        $vat_logo_height = $business_details->vat_logo_height;

        $receipt_details = $this->__getReceiptDetails($statement);
        $receipt_details->price_adjustment =  $this->vatFormatter()->num_f($statement->price_adjustment, true, $business_details);
        $receipt_details->final_total =  $this->vatFormatter()->num_f($statement->total_amount + $statement->price_adjustment, true, $business_details);
        $payment = VatInvoicePayment2::where('invoice_id', $id)->first();       

        return view('vat::statement126.print_2026')->with(compact('statement', 'statement_details', 'receipt_details', 'vat_statement_126_2026_print','payment','vat_logo','vat_logo_width','vat_logo_height'));
    }

    public function __getReceiptDetails($transaction, $receipt_printer_type = 'browser')
    {
        $business_details = $this->businessUtil->getDetails($transaction->business_id);
        $tax_rate = TaxRate::where('business_id', $transaction->business_id)->first();

        $supply_from = VatSupplyFrom::where('business_id', $transaction->business_id)->where('status', 1)->first();
        $bank_detail = VatBankDetail::where('business_id', $transaction->business_id)->where('status', 1)->first();
        $concern = VatConcern::where('business_id', $transaction->business_id)->where('status', 1)->first();

        $location_details = BusinessLocation::find($transaction->location_id);
        $invoice_layout = $this->businessUtil->invoiceLayout($transaction->business_id, $transaction->location_id, $location_details->invoice_layout_id);
        $il = $invoice_layout;

        $footer_top_margin = System::getProperty('footer_top_margin');
        $admin_invoice_footer = System::getProperty('admin_invoice_footer');

        $ref_no = CustomerReference::find($transaction->reference_id)->reference ?? '';

        $output = [
            'reference_no' => $ref_no,
            'header_text' => isset($il->header_text) ? $il->header_text : '',
            'delivery_date' => $transaction->supplied_on ? Carbon::parse($transaction->supplied_on)->format('m/d/Y') : '-',
            'suppliers_tin' => $business_details->tax_number_1 ?? '-',
            'business_name' => ($il->show_business_name == 1) ? $business_details->name : '',
            'location_name' => ($il->show_location_name == 1) ? $location_details->name : '',
            'sup_telephone_no' => $business_details->mobile ?? '-',
            'sub_heading_line1' => trim($il->sub_heading_line1),
            'sub_heading_line2' => trim($il->sub_heading_line2),
            'sub_heading_line3' => trim($il->sub_heading_line3),
            'sub_heading_line4' => trim($il->sub_heading_line4),
            'sub_heading_line5' => trim($il->sub_heading_line5),
            'table_product_label' => $il->table_product_label,
            'table_qty_label' => $il->table_qty_label,
            'table_unit_price_label' => $il->table_unit_price_label,
            'table_subtotal_label' => $il->table_subtotal_label,
            'font_size' => $il->font_size,
            'header_font_size' => $il->header_font_size,
            'footer_font_size' => $il->footer_font_size,
            'business_name_font_size' => $il->business_name_font_size,
            'invoice_heading_font_size' => $il->invoice_heading_font_size,
            'footer_top_margin' => $footer_top_margin,
            'admin_invoice_footer' => $admin_invoice_footer,
            'logo_height' => $il->logo_height,
            'logo_width' => $il->logo_width,
            'logo_margin_top' => $il->logo_margin_top,
            'logo_margin_bottom' => $il->logo_margin_bottom,
            'header_align' => $il->header_align,
            'tax_amount' => $transaction->tax_amount,
            'tax_rate' => $tax_rate,
            'business_location' => $location_details,
            'supply_from' => $supply_from,
            'bank_detail' => $bank_detail,
            'concern'  => $concern,
            'total_amount_words' => $transaction->total_amount_words ?? '-'
        ];

        $output['display_name'] = $output['business_name'];

        if (!empty($output['location_name'])) {
            if (!empty($output['display_name'])) {
                $output['display_name'] .= ', ';
            }
            $output['display_name'] .= $output['location_name'];
        }

        $contact_details = $this->transactionUtil->getCustomerDetails($transaction->invoice_to == 'customer' ? $transaction->customer_id : $transaction->sub_customer);
        $output['contact_details'] = $contact_details;

        //Logo
        $output['logo'] = $il->show_logo != 0 && !empty($il->logo) && file_exists(public_path('uploads/invoice_logos/' . $il->logo)) ? asset('uploads/invoice_logos/' . $il->logo) : false;

        //Address
        $output['address'] = '';
        $temp = [];
        if ($il->show_landmark == 1) {
            $output['address'] .= $location_details->landmark . "\n";
        }
        if ($il->show_city == 1 &&  !empty($location_details->city)) {
            $temp[] = $location_details->city;
        }
        if ($il->show_state == 1 &&  !empty($location_details->state)) {
            $temp[] = $location_details->state;
        }
        if ($il->show_zip_code == 1 &&  !empty($location_details->zip_code)) {
            $temp[] = $location_details->zip_code;
        }
        if ($il->show_country == 1 &&  !empty($location_details->country)) {
            $temp[] = $location_details->country;
        }
        if (!empty($temp)) {
            $output['address'] .= implode(',', $temp);
        }

        $output['website'] = $location_details->website;
        $output['location_custom_fields'] = '';
        $temp = [];

        $location_custom_field_settings = !empty($il->location_custom_fields) ? $il->location_custom_fields : [];
        if (!empty($location_details->custom_field1) && in_array('custom_field1', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field1;
        }
        if (!empty($location_details->custom_field2) && in_array('custom_field2', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field2;
        }
        if (!empty($location_details->custom_field3) && in_array('custom_field3', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field3;
        }
        if (!empty($location_details->custom_field4) && in_array('custom_field4', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field4;
        }
        if (!empty($temp)) {
            $output['location_custom_fields'] .= implode(', ', $temp);
        }

        $output['tax_label1'] = !empty($business_details->tax_label_1) ? $business_details->tax_label_1 . ': ' : '';
        $output['tax_info1'] = !empty($business_details->tax_number_1) ? $business_details->tax_number_1 : '';

        //Shop Contact Info
        $output['contact'] = '';
        if ($il->show_mobile_number == 1 && !empty($location_details->mobile)) {
            $output['contact'] .= __('contact.mobile') . ': ' . $location_details->mobile;
        }
        if ($il->show_alternate_number == 1 && !empty($location_details->alternate_number)) {
            if (empty($output['contact'])) {
                $output['contact'] .= __('contact.mobile') . ': ' . $location_details->alternate_number;
            } else {
                $output['contact'] .= ', ' . $location_details->alternate_number;
            }
        }
        if ($il->show_email == 1 && !empty($location_details->email)) {
            if (!empty($output['contact'])) {
                // $output['contact'] .= "\n";
            }
            $output['contact'] .= __('business.email') . ': ' . $location_details->email;
        }

        //Customer show_customer
        $customer = Contact::find($transaction->invoice_to == 'customer' ? $transaction->customer_id : $transaction->sub_customer);
        $output['customer'] = $customer;
        $output['customer_info'] = '';
        $output['customer_tax_number'] = '';
        $output['customer_tax_label'] = '';
        $output['customer_custom_fields'] = '';
        if ($il->show_customer == 1) {
            $output['customer_label'] = !empty($il->customer_label) ? $il->customer_label : '';
            $output['customer_name'] = !empty($customer->name) ? $customer->name : '';
            if (!empty($output['customer_name']) && $receipt_printer_type != 'printer') {
                $output['customer_info'] .= $customer->landmark;
                $output['customer_info'] .= '<br>' . $customer->mobile;
            }
            $output['customer_tax_number'] = !empty($customer->tax_number) ? $customer->tax_number : null;
            $output['customer_tax_label'] = !empty($il->client_tax_label) ? $il->client_tax_label : '';
            $temp = [];
            $customer_custom_fields_settings = !empty($il->contact_custom_fields) ? $il->contact_custom_fields : [];
            if (!empty($customer->custom_field1) && in_array('custom_field1', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field1;
            }
            if (!empty($customer->custom_field2) && in_array('custom_field2', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field2;
            }
            if (!empty($customer->custom_field3) && in_array('custom_field3', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field3;
            }
            if (!empty($customer->custom_field4) && in_array('custom_field4', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field4;
            }
            if (!empty($temp)) {
                $output['customer_custom_fields'] .= implode(',', $temp);
            }
        }

        $output['client_id'] = '';
        $output['client_id_label'] = '';
        if ($il->show_client_id == 1) {
            $output['client_id_label'] = !empty($il->client_id_label) ? $il->client_id_label : '';
            $output['client_id'] = !empty($customer->contact_id) ? $customer->contact_id : '';
        }

        //Invoice info
        $output['invoice_no'] = $transaction->customer_bill_no;

        //Heading & invoice label
        $output['invoice_no_prefix'] = $il->invoice_no_prefix;
        $output['invoice_heading'] = $il->invoice_heading;

        $output['date_label'] = $il->date_label;
        // Combine statement date with creation time so invoice date shows correct time
        $invoice_datetime = \Carbon\Carbon::parse($transaction->date)->setTimeFromTimeString(\Carbon\Carbon::parse($transaction->created_at)->format('H:i:s'));
        $output['invoice_date'] = $invoice_datetime->format('Y-m-d H:i:s');

        $show_currency = true;
        $output['show_cat_code'] = $il->show_cat_code;
        $output['cat_code_label'] = $il->cat_code_label;

        //Subtotal
        $output['subtotal_label'] = $il->sub_total_label . ':';
        $output['subtotal'] = ($transaction->total_amount != 0) ? $this->vatFormatter()->num_f($transaction->total_amount, $show_currency, $business_details) : 0;
        $output['subtotal_unformatted'] = ($transaction->total_amount != 0) ? $transaction->total_amount : 0;

        //Discount
        $output['line_discount_label'] = $il->discount_label;
        $output['discount_label'] = $il->discount_label;
        $discount = $transaction->discount_amount;

        $output['discount'] = ($discount != 0) ? $this->vatFormatter()->num_f($discount, $show_currency, $business_details) : 0;

        //Order Tax
        $tax = $transaction->tax_amount;
        $output['tax_label'] = $il->tax_label;
        $output['tax_label'] .= ':';
        $output['tax'] = ($transaction->tax_amount != 0) ? $this->vatFormatter()->num_f($transaction->tax_amount, $show_currency, $business_details) : 0;

        $output['total_label'] = $il->total_label . ':';
        $output['total'] = $this->vatFormatter()->num_f($transaction->total_amount - $transaction->tax_amount, $show_currency, $business_details);
        $output['total_vat'] = $this->vatFormatter()->num_f($transaction->tax_amount, $show_currency, $business_details);
        $output['total_with_vat'] = $this->vatFormatter()->num_f($transaction->total_amount, $show_currency, $business_details);
        $total = $transaction->total_amount - $transaction->tax_amount; 
        $vatRate = $total * 0.18;
        $output['vat_rate'] = $this->vatFormatter()->num_f($vatRate, $show_currency, $business_details);  

        $output['footer_text'] = $il->footer_text;
        $output['design'] = $il->design;
        return (object) $output;
    }

    /**
     * Display products sold report for statement 126
     *
     * @return \Illuminate\Http\Response
     */
    public function productsSold()
    {

        $business_id = request()->session()->get('business.id');

        if (request()->ajax()) {
            $issue_customer_bills = VatInvoiceDetail2::join('vat_invoices_2', 'vat_invoices_2.id', 'vat_invoice_details_2.issue_bill_id')->leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
                ->leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
                ->where('vat_invoices_2.business_id', $business_id)
                ->select(
                    'vat_invoices_2.date',
                    'vat_invoices_2.customer_bill_no',
                    'vat_invoice_details_2.*',
                    'contacts.name as customer_name',
                    'products.name as product_name'
                );

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $issue_customer_bills->whereDate('vat_invoices_2.date', '>=', request()->start_date)->where('vat_invoices_2.date', '<=', request()->end_date);
            }

            if (!empty(request()->customer_id)) {
                $issue_customer_bills->where('contacts.id', request()->customer_id);
            }

            if (!empty(request()->product_id)) {
                $issue_customer_bills->where('products.id', request()->product_id);
            }

            return DataTables::of($issue_customer_bills->get())
                ->editColumn('sub_total', '{{@num_format($sub_total)}}')
                ->editColumn('unit_price_before_tax', '{{@num_format($unit_price_before_tax)}}')
                ->editColumn('total_discount', '{{@num_format($discount*$qty)}}')
                ->editColumn('total_vat', '{{@num_format($unit_vat_rate * $qty)}}')
                ->editColumn('qty', '{{@num_format($qty)}}')
                ->editColumn('date', '{{@format_date($date)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        $business_id = request()->session()->get('business.id');

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)->forModule('vat_vatinvoice2')->pluck('name', 'id');

        return view('vat::statement126.products_sold', compact('customers', 'products'));
    }

    /**
     * Display invoice settings for statement 126
     *
     * @return \Illuminate\Http\Response
     */
    public function invoicesSetting()
    {
        $business_id = request()->session()->get('business.id');

        $invoice_setting = \Modules\Vat\Entities\VatInvoice2Setting::where('business_id', $business_id)->first();
        $invoice2_settings = !empty($invoice_setting) ? (object) json_decode($invoice_setting->settings) : (object) [];

        return view('vat::statement126.invoices_setting')->with(compact('invoice2_settings'));
    }

    public function updateSetting(Request $request)
    {
        try {

            $business_id = request()->session()->get('business.id');
            $data  = request()->except('_token');
            
            // Convert Arabic/Hindi numerals to English numerals
            $arabicNumerals = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            
            foreach ($data as $key => $value) {
                if (!empty($value)) {
                    $data[$key] = str_replace($arabicNumerals, $englishNumerals, $value);
                }
            }
            
            DB::beginTransaction();

            $tdata = array('business_id' => $business_id, 'settings' => json_encode($data));
            \Modules\Vat\Entities\VatInvoice2Setting::updateOrCreate(['business_id' => $business_id], $tdata);


            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::back()->with('status', $output);
    }
    
    /**
     * Show the form for assigning prefixes to users
     *
     * @return \Illuminate\Http\Response
     */
    public function assignPrefix()
    {
        $business_id = $this->currentBusinessId();

        $users = \App\User::where('business_id', $business_id)
            ->where('is_cmmsn_agnt', 0)
            ->where('is_customer', 0)
            ->select('id', DB::raw("TRIM(CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), ' (', COALESCE(username, ''), ')')) as full_name"))
            ->orderBy('username')
            ->pluck('full_name', 'id')
            ->map(function ($name, $id) {
                $clean = trim(str_replace('()', '', $name));
                return $clean !== '' ? $clean : ('User #' . $id);
            });
        
        $prefixes = VatInvoice2Prefix::where('business_id', $business_id)
            ->whereNotNull('prefix')
            ->orderBy('prefix')
            ->pluck('prefix', 'id');

        $assignments = VatUserInvoicePrefix::with(['user', 'prefix'])
            ->where('business_id', $business_id)
            ->orderBy('id', 'desc')
            ->get();

        return view('vat::statement126.assign_prefix')->with(compact('users', 'prefixes', 'assignments'));
    }

    /**
     * Store prefix assignment
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeAssignPrefix(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'prefix_id2' => 'required',
        ]);

        try {
            $business_id = $this->currentBusinessId();

            // Check if assignment already exists
            $existing = VatUserInvoicePrefix::where('business_id', $business_id)
                ->where('user_id', $request->user_id)
                ->first();

            $payload = [
                'business_id' => $business_id,
                'user_id' => $request->user_id,
                'prefix_id2' => $request->prefix_id2,
                'created_by' => auth()->id(),
            ];

            // Keep legacy columns safe when the same assignment table is shared by VAT Invoice Prefix settings.
            if (empty($existing)) {
                $payload['date_time'] = Carbon::now()->format('Y-m-d H:i:s');
            }

            VatUserInvoicePrefix::updateOrCreate(
                ['business_id' => $business_id, 'user_id' => $request->user_id],
                $payload
            );

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => 'Error: ' . $e->getMessage()
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**
     * Delete prefix assignment
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteAssignPrefix($id)
    {
        try {
            $assignment = VatUserInvoicePrefix::findOrFail($id);
            $assignment->delete();

            $output = [
                'success' => true,
                'msg' => __('messages.deleted_success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}
