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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

use Modules\Vat\Entities\VatInvoice2;
use Modules\Vat\Entities\VatInvoiceDetail2;

use Modules\Vat\Entities\VatUserInvoicePrefix;
use Modules\Vat\Entities\VatInvoicePayment2;
use Modules\Vat\Entities\VatInvoice2Prefix;

use Modules\Vat\Entities\FuelTank;
use Modules\Vat\Entities\TankSellLine;

use Yajra\DataTables\Facades\DataTables;
use App\Business;
use App\NotificationTemplate;
use App\System;

use App\Transaction;
use App\TransactionPayment;
use Modules\Vat\Http\Controllers\VatInvoiceToTransactionController;
use App\AccountTransaction;
use App\ContactLedger;
use App\Variation;
use App\Store;
use Modules\Vat\Entities\VatCreditBill;

use Modules\Vat\Entities\VatSupplyFrom;
use Modules\Vat\Entities\VatBankDetail;
use Modules\Vat\Entities\VatConcern;
// Separation step 4 (document 5-18): the shared `customer_references` table
// is reached through a VAT-owned model, so this file no longer depends on the
// core App\CustomerReference class when the Contact module is retired.
use Modules\Vat\Entities\SharedCustomerReference as CustomerReference;
use Milon\Barcode\DNS2D;

use Modules\Vat\Entities\VatInvoice2Setting;

use App\Utils\ModuleUtil;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Utils\ContactUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Modules\Vat\Entities\RouteOperation;
use Modules\Superadmin\Entities\Subscription;

class VatInvoice2Controller extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;

    protected $commonUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;
    protected $productUtil;
    protected $contactUtil;
    //protected $balance_duen;
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
        //balance_duen $GLOBALS
    ) {

        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->contactUtil = $contactUtil;
        //$this->balance_duen =& $GLOBALS;
    }

    /**
     * Resolve the active business id safely for multi-tenant requests.
     * Some AJAX/form-submit requests contain user.business_id but not business.id.
     */
    private function getBusinessId()
    {
        return request()->session()->get('business.id')
            ?? request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id;
    }

    /**
     * Build VAT Invoice-2 product/category dropdown data without adding any
     * persistence requirement to the invoice. Product Category is only an
     * optional UI filter; products remain the saved line-level value.
     */
    private function getVatInvoice2ProductDropdownData($business_id, $respect_module_scope = false)
    {
        $product_query = Product::where('business_id', $business_id);

        if ($respect_module_scope) {
            $product_query->forModule('vat_vatinvoice2');
        }

        $product_rows = $product_query
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'sub_category_id']);

        $products = $product_rows->pluck('name', 'id');

        // Product Category is optional and should show the business's full
        // category list, not only categories currently used by one product.
        $category_query = DB::table('categories')->where('business_id', $business_id);
        if (DB::getSchemaBuilder()->hasColumn('categories', 'category_type')) {
            $category_query->where(function ($query) {
                $query->whereNull('category_type')
                    ->orWhere('category_type', 'product');
            });
        }

        $product_categories = $category_query
            ->orderBy('name')
            ->pluck('name', 'id');

        $product_category_links = [];
        foreach ($product_rows as $product) {
            $links = [];
            if (!empty($product->category_id)) {
                $links[] = (int) $product->category_id;
            }
            if (!empty($product->sub_category_id)) {
                $links[] = (int) $product->sub_category_id;
            }
            $product_category_links[(string) $product->id] = array_values(array_unique($links));
        }

        return compact('products', 'product_categories', 'product_category_links');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business_id = $this->getBusinessId();

        // AJAX request: DataTables server-side
        if (request()->ajax()) {
            $issue_customer_bills = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
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
                    'contacts.name as customer_name',
                    'subc.name as sub_customer',
                    'users.username as username'
                );

            // Filters
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $issue_customer_bills->whereDate('vat_invoices_2.date', '>=', request()->start_date)
                                    ->whereDate('vat_invoices_2.date', '<=', request()->end_date);
            }

            if (!empty(request()->contact_id)) {
                $issue_customer_bills->where('vat_invoices_2.customer_id', request()->contact_id);
            }

            if (!empty(request()->sub_contact_id)) {
                $issue_customer_bills->where('vat_invoices_2.sub_customer', request()->sub_contact_id);
            }

            if (!empty(request()->customer_bill_no)) {
                $issue_customer_bills->where('vat_invoices_2.customer_bill_no', request()->customer_bill_no);
            }

            $issue_customer_bills->orderby('vat_invoices_2.id', 'desc');

            // Return for DataTables
            return DataTables::of($issue_customer_bills)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">';

                    $html .= '<li>
                        <a href="#"
                            data-print-old="' . action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print', $row->id) . '"
                            data-print-2026="' . action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_design_2026', $row->id) . '"
                            data-print-163="' . action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_163', $row->id) . '"
                            class="print_bill">
                            <i class="fa fa-print"></i> ' . __("messages.print") . '
                        </a>
                    </li>';

                    $html .= '<li><a href="' . action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@edit', $row->id) . '">
                        <i class="fa fa-edit"></i> ' . __("messages.edit") . '</a></li>';

                    $html .= '<li><a href="#" data-href="' . action("\Modules\Vat\Http\Controllers\VatInvoice2Controller@destroy", [$row->id]) . '" class="delete-issue_bill_customer">
                        <i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('outstanding_amount', '{{@num_format($outstanding_amount)}}')
                ->editColumn('date', '{{@format_date($date)}}')
                ->rawColumns(['action'])
                ->make(true);
        }

        // For page load dropdowns
        $contact_dropdown = Contact::contactDropdown($business_id, false, true, true, 'customer');

        $bill_no_dropdown = VatInvoice2::where('business_id', $business_id)
            ->pluck('customer_bill_no', 'customer_bill_no')
            ->prepend(__('lang_v1.none'), '');
        
        // Check VAT Print 2026 permission
        $vat_print_2026_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_print_2026');

        return view('vat::vat_invoice2.index')
            ->with(compact('contact_dropdown', 'bill_no_dropdown', 'vat_print_2026_enabled'));
    }
    public function customerQuickAdd(){
        $business_id = request()->session()->get('user.business_id');
        $contact_id = $this->businessUtil->check_customer_code($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        
        $types = [];
        if (Gate::allows('customer.create')) {
            $types['customer'] = __('report.customer');
        }

        return view('vat::contact.quick-create')
            ->with(compact('customers',  'contact_id','types'));
    }
    
    public function storeQuickCustomer(Request $request)
    {
        if (!Gate::allows('supplier.create') && !Gate::allows('customer.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {


            // $input['property_id']=$request->property_id;
            $business_id = $request->session()->get('user.business_id');
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }
            DB::beginTransaction();
            if ($request->type == 'customer') {

                if (!$this->moduleUtil->isQuotaAvailable('customers', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('customers', $business_id, action('ContactController@index'));
                }
                
                 //point 4b done updated by dushyant
                $customer_data = array(
                    'business_id' => $business_id,
                    'first_name' => $request->name,
                    'last_name' => '',
                    'email' => '',
                    'username' => (is_null($request->contact_id))?$request->name:$request->contact_id,
                    'password' => '',
                    'mobile' => $request->mobile ?? ' ',
                    'contact_number' => '',
                    'landline' => '',
                    'geo_location' => $request->country ?? ' ',
                    'address' => $request->address ?? ' ',
                    'town' => $request->state ?? ' ',
                    'district' => $request->city ?? ' ',
                    'is_company_customer' => 1
                );
                $userData=Customer::create($customer_data);


            }
             //point 4b done updated by dushyant
            $input = $request->only(['sub_customer','sub_customers','vat_number','credit_notification','transaction_date',
                'should_notify','type', 
                'name', 'contact_id'
            ]);
            
            $input['sub_customers'] = json_encode($request->sub_customers ?? []);
            
            $input['contact_transaction_date'] = $this->vatFormatter()->uf_date($input['transaction_date']);
            unset($input['transaction_date']);
            
            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');
            $input['credit_limit'] =  null;
            
            //Check Contact id
            $count = 0;
            if (!empty($input['contact_id'])) {
                $count = Contact::where('business_id', $input['business_id'])
                    ->where('contact_id', $input['contact_id'])
                    ->count();
            }
            if ($count == 0) {
                //Update reference count
                $ref_count = $this->commonUtil->setAndGetReferenceCount('contacts');
                if (empty($input['contact_id'])) {
                    //Generate reference number
                    $input['contact_id'] = $this->commonUtil->generateReferenceNumber('contacts', $ref_count);
                }

                $contact = Contact::create($input);
              
                //Add opening balance
                if (!empty($request->input('opening_balance'))) {
                    $this->transactionUtil->createOpeningBalanceTransaction($business_id, $contact->id, $request->input('opening_balance'), $request->transaction_date);
                }
                $output = [
                    'success' => true,
                    'data' => $contact,
                    'msg' => __("contact.added_success")
                ];
            } else {
                throw new \Exception("Error Processing Request", 1);
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
        return $output;
    }
    
    public function referenceQuickAdd(){
        $business_id = request()->session()->get('user.business_id');
        $customers = Contact::customersDropdown($business_id, false);
    
        return view('vat::contact.quick-reference')
            ->with(compact('customers'));
    }
    
    public function storeQuickReference(Request $request)
    {
        
        try {
            
            $business_id = $request->session()->get('user.business_id');
            
            DB::beginTransaction();
            
            $customer = Contact::findOrFail($request->customer_id);
            $name = $customer->name;
            $barcode_string = $name . '.' . $request->customer_reference;
            $qr_generator = new DNS2D();
            $qr_png_base64 = (string) $qr_generator->getBarcodePNG($barcode_string, 'QRCODE');
            $src = 'data:image/png;base64,' . $qr_png_base64;

            
            $ref_data = array(
                'business_id' => $business_id,
                'date' => date('Y-m-d', strtotime($request->reference_date)),
                'contact_id' => $request->customer_id,
                'reference' =>$request->customer_reference,
                'barcode_src' => $src
            );
            $ref = CustomerReference::updateOrCreate(['business_id' => $business_id,'contact_id' => $request->customer_id,'reference' =>$request->customer_reference],$ref_data);
            
            $output = [
                'success' => true,
                'data' => $ref,
                'msg' => __("contact.added_success")
            ];
            DB::commit();
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
        return $output;
    }
    
    public function invoicesSetting()
    {
        $business_id = $this->getBusinessId();
        $invoice2_settings = optional(VatInvoice2Setting::where('business_id',$business_id)->first())->settings ?? json_encode(array());
        $invoice2_settings = (object) json_decode($invoice2_settings);
        
        return view('vat::vat_invoice2.invoices_setting',compact('invoice2_settings'));
    }

    public function updateSetting(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id') ?: $request->session()->get('business.id') ?: $this->getBusinessId();
            $data  = $request->except('_token');
            $data = array_map(function ($value) { return is_numeric($value) ? (int) $value : $value; }, $data);
            DB::beginTransaction();
            
            $tdata = array('business_id' => $business_id,'settings' => json_encode($data));
            VatInvoice2Setting::updateOrCreate(['business_id' => $business_id],$tdata);
               

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
    
     public function index127()
    {
       
        $business_id = $this->getBusinessId();

        if (request()->ajax()) {
            $issue_customer_bills = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
                ->leftjoin('contacts as subc', 'vat_invoices_2.sub_customer', 'subc.id')
                ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
                ->where('vat_invoices_2.business_id', $business_id)
                ->select(
                    'vat_invoices_2.*',
                    'contacts.name as customer_name',
                    'subc.name as sub_customer',
                    'users.username as username'
                )->get();

            return DataTables::of($issue_customer_bills)

                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print127', $row->id) . '" class="print_bill" ><i class="fa fa-print" aria-hidden="true"></i>' . __("messages.print") . '</a></li>';
                        if (Gate::allows('vat_edit_invoice127')) {
                            $html .= '<li><a href="' . action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@edit127', $row->id) . '" class="" ><i class="fa fa-edit" aria-hidden="true"></i>' . __("messages.edit") . '</a></li>';
                       
                        } 

                    $html .=  '</ul></div>';
                    return $html;
                })
                ->editColumn('outstanding_amount','{{@num_format($outstanding_amount)}}')
                ->editColumn('date','{{@format_date($date)}}')
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('vat::vat_invoice2.index127');
    }
    
    public function productsSold()
    {
        
        $business_id = $this->getBusinessId();

        if (request()->ajax()) {
            $issue_customer_bills = VatInvoiceDetail2::join('vat_invoices_2','vat_invoices_2.id','vat_invoice_details_2.issue_bill_id')->leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
                ->leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
                ->where('vat_invoices_2.business_id', $business_id)
                ->select(
                    'vat_invoices_2.date',
                    'vat_invoices_2.customer_bill_no',
                    'vat_invoice_details_2.*',
                    'contacts.name as customer_name',
                    'products.name as product_name'
                );
                
            if(!empty(request()->start_date) && !empty(request()->end_date)){
                $issue_customer_bills->whereDate('vat_invoices_2.date','>=',request()->start_date)->where('vat_invoices_2.date','<=',request()->end_date);
            }
            
            if(!empty(request()->customer_id)){
                $issue_customer_bills->where('contacts.id',request()->customer_id);
            }
            
            if(!empty(request()->product_id)){
               $issue_customer_bills->where('products.id',request()->product_id);
            }

            return DataTables::of($issue_customer_bills->get())
                ->editColumn('sub_total','{{@num_format($sub_total)}}')
                ->editColumn('unit_price_before_tax','{{@num_format($unit_price_before_tax)}}')
                ->editColumn('total_discount','{{@num_format($discount*$qty)}}')
                ->editColumn('total_vat','{{@num_format($unit_vat_rate * $qty)}}')
                ->editColumn('qty','{{@num_format($qty)}}')
                ->editColumn('date','{{@format_date($date)}}')
                ->rawColumns(['action'])
                ->make(true);
        }
        
        $business_id = $this->getBusinessId();

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        
        $products = Product::where('business_id', $business_id)->forModule('vat_vatinvoice2')->pluck('name', 'id');
        
        return view('vat::vat_invoice2.products_sold',compact('customers','products'));
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $business_id = $this->getBusinessId();
        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        
        /*
         * Add VAT Invoice-2 must show the complete product list for the active
         * tenant/business. Do not depend on a module-mapping scope here because
         * older tenants may not have those mappings even though their products
         * are valid for VAT invoicing.
         */
        $product_dropdown_data = $this->getVatInvoice2ProductDropdownData($business_id, false);
        $products = $product_dropdown_data['products'];
        $product_categories = $product_dropdown_data['product_categories'];
        $product_category_links = $product_dropdown_data['product_category_links'];
        
        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes','vat_invoice2_prefixes.id','vat_user_invoice_prefixes.prefix_id2')->where('vat_user_invoice_prefixes.business_id',$business_id)->where('vat_user_invoice_prefixes.user_id',auth()->user()->id)->pluck('vat_invoice2_prefixes.prefix','vat_invoice2_prefixes.id');
        
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        
        $payment_types = $this->productUtil->payment_types(null, false, false, false, false, true);
        
        $fleet_active = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'fleet_module');
        $ro_customerIDs = RouteOperation::where('business_id',$business_id)->where('is_vat',1)->whereNull('vat_applied')->pluck('contact_id')->toArray();
        
        $fleet_customers = Contact::whereIn('id',$ro_customerIDs)->pluck('name','id');
        $vat_print_2026_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_print_2026');
        return view('vat::vat_invoice2.create')->with(compact(
            'customers',
            'products',
            'product_categories',
            'product_category_links',
            'prefixes',
            'business_locations',
            'payment_types',
            'fleet_active',
            'fleet_customers',
            'vat_print_2026_enabled'
        ));
    }
    
    /**
     * Return all Add VAT Invoice-2 customer information from the VAT module.
     *
     * This endpoint intentionally replaces the old Petro endpoints so the VAT
     * form continues to work even when Petro is disabled in Manage Side Bar.
     */
    public function getCustomerAjaxDetails($customer_id)
    {
        $business_id = $this->getBusinessId();

        $customer = Contact::where('business_id', $business_id)
            ->where('id', $customer_id)
            ->whereIn('type', ['customer', 'both'])
            ->first();

        if (empty($customer)) {
            return response()->json([
                'success' => false,
                'vat_number' => '',
                'credit_limit' => 0,
                'total_outstanding' => 0,
                'sub_customers' => [],
                'references' => [],
            ], 404);
        }

        $sub_customer_ids = [];
        if (!empty($customer->sub_customers)) {
            if (is_array($customer->sub_customers)) {
                $sub_customer_ids = $customer->sub_customers;
            } else {
                $decoded = json_decode((string) $customer->sub_customers, true);
                $sub_customer_ids = is_array($decoded) ? $decoded : [];
            }
        }

        $sub_customer_ids = array_values(array_unique(array_filter(array_map('intval', $sub_customer_ids))));

        $sub_customers = empty($sub_customer_ids)
            ? collect()
            : Contact::where('business_id', $business_id)
                ->whereIn('id', $sub_customer_ids)
                ->orderBy('name')
                ->pluck('name', 'id');

        $references = CustomerReference::where('contact_id', $customer->id)
            ->orderBy('reference')
            ->pluck('reference', 'id');

        $outstanding = $this->contactUtil->getCustomerBalance($customer->id, $business_id, true);
        if (!is_numeric($outstanding)) {
            $outstanding = $this->vatFormatter()->num_uf($outstanding ?? 0);
        }

        return response()->json([
            'success' => true,
            'vat_number' => (string) ($customer->vat_number ?? ''),
            'credit_limit' => is_numeric($customer->credit_limit) ? (float) $customer->credit_limit : 0,
            'total_outstanding' => is_numeric($outstanding) ? (float) $outstanding : 0,
            'sub_customers' => $sub_customers,
            'references' => $references,
        ]);
    }

    /**
     * Return the selected VAT Invoice-2 product prices from VAT-owned code.
     */
    public function getProductAjaxDetails($product_id)
    {
        $business_id = $this->getBusinessId();

        $product = Product::where('business_id', $business_id)
            ->where('id', $product_id)
            ->first();

        if (empty($product)) {
            return response()->json([
                'success' => false,
                'unit_price' => 0,
                'unit_price_excl' => 0,
            ], 404);
        }

        $variation = Variation::where('product_id', $product->id)
            ->orderBy('id')
            ->first(['sell_price_inc_tax', 'default_sell_price']);

        return response()->json([
            'success' => true,
            'unit_price' => (float) optional($variation)->sell_price_inc_tax,
            'unit_price_excl' => (float) optional($variation)->default_sell_price,
        ]);
    }

    public function getRouteOperations($contact_id){
        return RouteOperation::where('contact_id',$contact_id)->where('is_vat',1)->whereNull('vat_applied')->pluck('invoice_no','id');
    }
    
    public function routeOperationDetails($id){
        return RouteOperation::findOrFail($id);
    }
    
    public function create127()
    {
        
        $business_id = $this->getBusinessId();

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        
        $products = Product::where('business_id', $business_id)->forModule('vat_vatinvoice127')->pluck('name', 'id');
        
        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes','vat_invoice2_prefixes.id','vat_user_invoice_prefixes.prefix_id2')->where('vat_user_invoice_prefixes.business_id',$business_id)->where('vat_user_invoice_prefixes.user_id',auth()->user()->id)->pluck('vat_invoice2_prefixes.prefix','vat_invoice2_prefixes.id');
        
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        
        $payment_types = $this->productUtil->payment_types(null, false, false, false, false, true);
        
        return view('vat::vat_invoice2.create127')->with(compact(
            'customers',
            'products',
            'prefixes',
            'business_locations',
            'payment_types'
        ));
    }
    
    /**
     * Return the selected prefix and its VAT Invoice2 precision rules.
     * Prefixes are always scoped to the active business/tenant.
     */
    public function getPrefixes($id)
    {
        $business_id = $this->getBusinessId();

        /** @var VatInvoice2Prefix $prefixes */
        $prefixes = VatInvoice2Prefix::where('business_id', $business_id)->findOrFail($id);

        $existing = VatInvoice2::where('business_id', $business_id)
            ->where('prefix', $id)
            ->orderByDesc('id')
            ->first();

        $starting_no_string = (string) $prefixes->starting_no;
        $starting_no_numeric = (int) $starting_no_string;
        $pad_length = max(strlen($starting_no_string), 1);

        if (!empty($existing)) {
            preg_match('/(\d+)$/', (string) $existing->customer_bill_no, $number_match);
            $current_no = isset($number_match[1]) ? (int) $number_match[1] : 0;
            $next_no = $current_no >= $starting_no_numeric ? ($current_no + 1) : $starting_no_numeric;
        } else {
            $next_no = $starting_no_numeric;
        }

        $next_no_padded = str_pad((string) $next_no, $pad_length, '0', STR_PAD_LEFT);
        $config = $this->getVatInvoice2DecimalConfig($id, $business_id);

        return array_merge([
            // The configured prefix is complete. Never insert a separator here.
            'bill_no' => (string) $prefixes->prefix . $next_no_padded,
        ], $config);
    }

    /**
     * Get Qty/Unit VAT and Sub Total precision rules for a prefix.
     */
    private function getVatInvoice2DecimalConfig($prefix_id, $business_id): array
    {
        $prefix = VatInvoice2Prefix::where('business_id', $business_id)->find($prefix_id);

        return [
            'unit_vat_no_of_decimals' => $prefix && !is_null($prefix->unit_vat_no_of_decimals)
                ? min(max((int) $prefix->unit_vat_no_of_decimals, 0), 10)
                : 2,
            'unit_vat_rounding_off_required' => $prefix
                ? (bool) $prefix->unit_vat_rounding_off_required
                : false,
            'sub_total_no_of_decimals' => $prefix && !is_null($prefix->sub_total_no_of_decimals)
                ? min(max((int) $prefix->sub_total_no_of_decimals, 0), 10)
                : 2,
            'sub_total_rounding_off_required' => $prefix
                ? (bool) $prefix->sub_total_rounding_off_required
                : false,
        ];
    }

    /**
     * Apply the selected rounding/truncation rule without changing the field's
     * configured number of visible decimal places.
     */
    private function applyVatInvoice2DecimalRule($value, int $decimals, bool $rounding_required): float
    {
        $numeric = $this->vatFormatter()->num_uf($value ?? 0);
        $numeric = is_numeric($numeric) ? (float) $numeric : 0.0;
        $decimals = min(max($decimals, 0), 10);

        if ($rounding_required) {
            return round($numeric, $decimals, PHP_ROUND_HALF_UP);
        }

        $factor = pow(10, $decimals);
        return $numeric < 0
            ? ceil($numeric * $factor) / $factor
            : floor($numeric * $factor) / $factor;
    }

    private function vatInvoice2RequestNumber(float $value, int $decimals): string
    {
        return number_format($value, min(max($decimals, 0), 10), '.', '');
    }

    /**
     * Normalise all line values on the server as well as in the browser. This
     * prevents formatted/tampered request values from bypassing prefix setup.
     */
    private function applyVatInvoice2DecimalRulesToRequest(Request $request, $business_id): void
    {
        $config = $this->getVatInvoice2DecimalConfig($request->prefix_id, $business_id);
        $lines = $request->input('issue_customer_bill', []);

        if (empty($lines) || empty($lines['product_id']) || !is_array($lines['product_id'])) {
            return;
        }

        foreach (array_keys($lines['product_id']) as $key) {
            foreach (['qty', 'unit_vat_rate'] as $field) {
                if (isset($lines[$field]) && is_array($lines[$field]) && array_key_exists($key, $lines[$field])) {
                    $normalised = $this->applyVatInvoice2DecimalRule(
                        $lines[$field][$key],
                        $config['unit_vat_no_of_decimals'],
                        $config['unit_vat_rounding_off_required']
                    );
                    $lines[$field][$key] = $this->vatInvoice2RequestNumber(
                        $normalised,
                        $config['unit_vat_no_of_decimals']
                    );
                }
            }

            foreach (['tax', 'sub_total', 'tax_unformatted', 'sub_total_unformatted'] as $field) {
                if (isset($lines[$field]) && is_array($lines[$field]) && array_key_exists($key, $lines[$field])) {
                    $normalised = $this->applyVatInvoice2DecimalRule(
                        $lines[$field][$key],
                        $config['sub_total_no_of_decimals'],
                        $config['sub_total_rounding_off_required']
                    );
                    $lines[$field][$key] = $this->vatInvoice2RequestNumber(
                        $normalised,
                        $config['sub_total_no_of_decimals']
                    );
                }
            }
        }

        $request->merge(['issue_customer_bill' => $lines]);
    }

    private function generateInvoiceNumber($prefix_id, $business_id) {
        try {
            /** @var VatInvoice2Prefix|null $prefixes */
            $prefixes = VatInvoice2Prefix::where('business_id', $business_id)->find($prefix_id);
            if (!$prefixes instanceof VatInvoice2Prefix) {
                throw new \RuntimeException('VAT prefix not found');
            }

            $existing = VatInvoice2::where('business_id', $business_id)->where('prefix', $prefix_id)->get()->last();
            $starting_no_string = (string) $prefixes->starting_no;
            $starting_no_numeric = (int) $starting_no_string;
            $pad_length = max(strlen($starting_no_string), 1);
            
            if(!empty($existing)){
                $current_bill = $existing->customer_bill_no;
                preg_match('/(\d+)$/', (string) $current_bill, $number_match);
                $current_no = isset($number_match[1]) ? (int) $number_match[1] : 0;
                $next_no = $current_no >= $starting_no_numeric ? ($current_no + 1) : $starting_no_numeric;
            } else {
                $next_no = $starting_no_numeric;
            }
            
            // Use the length of the starting_no string to determine padding
            // If starting_no is "001", pad_length will be 3
            // If starting_no is "1", pad_length will be 1
            $next_no_padded = str_pad((string) $next_no, $pad_length, '0', STR_PAD_LEFT);
            return (string) $prefixes->prefix . $next_no_padded;
        } catch (\Exception $e) {
            Log::error('Error in generateInvoiceNumber: ' . $e->getMessage());
            // Fallback to simple numbering if there's an error
            return 'VAT-' . time();
        }
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
            $business_id = $this->getBusinessId();
            $this->applyVatInvoice2DecimalRulesToRequest($request, $business_id);
            $is_print  = request()->is_print;
            $invoice_date = Carbon::parse($request->voucher_order_date)->format('Y-m-d');

            if ($this->hasExceededVatInvoice2MonthlyLimit($business_id, $invoice_date)) {
                return back()->withInput()->with('status', $this->vatInvoice2MonthlyLimitExceededOutput());
            }
            
            $vat_bill = VatCreditBill::where('customer_id',$request->customer_id)->first();
            $save_txns = false;
            if(!empty($vat_bill)){
                if($vat_bill->linked_accounts == "yes"){
                    $save_txns = true;
                }
            }
            
            $data = array(
                'business_id' => $business_id,
                'date' => $invoice_date,
                'customer_bill_no' => $this->generateInvoiceNumber($request->prefix_id, $business_id),
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
                'sale_type' => $request->sale_type,
                'route_operation_id' => $request->route_operation_id
            );
            DB::beginTransaction();
            
            $issue_customer_bill = VatInvoice2::create($data);
            
            if(!empty($request->route_operation_id)){
                RouteOperation::where('id',$request->route_operation_id)->update(array('vat_applied' => 1));
            }
            
            if(!empty($save_txns)){
                $transaction = $this->createCreditSellTransactions($issue_customer_bill);
                $pa_transaction = $this->transactionUtil->createOrUpdatePriceAdjustment($issue_customer_bill,$issue_customer_bill->customer_bill_no);
            }
            

            $total_amount = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $unit_vat_rate = 0;
            
            foreach ($request->issue_customer_bill['product_id'] as $key => $product_id) {
                $total_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]);
                $tax_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]);
                $discount_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]);
                $unit_vat_rate += $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]);
                
                $details = array(
                    'business_id' => $business_id,
                    'issue_bill_id' => $issue_customer_bill->id,
                    'product_id' => $product_id,
                    'unit_price' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price'][$key]),
                    'unit_price_before_tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price_excl'][$key]),
                    'qty' => $this->vatFormatter()->num_uf($request->issue_customer_bill['qty'][$key]),
                    'discount' => $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]),
                    'tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]),
                    'sub_total' => $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]),
                    'unit_vat_rate' =>  $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]),

                );
                $bill_detail = VatInvoiceDetail2::create($details);
                $business_locations = BusinessLocation::forDropdown($business_id);
                $business_locations_array = [];
                foreach ($business_locations as $location_key => $location_name) {
                    $business_locations_array[$location_key] = $location_name;
                }
                $location_keys = array_keys($business_locations_array);
                $default_location = !empty($location_keys) ? $location_keys[0] : null;
                
                if(!empty($save_txns)){
                    $this->createSellTransactions($transaction, $bill_detail, $business_id, $default_location);
                }
            }
            
            foreach($request->payment as $payment){

                $payment_data = [
                    'invoice_id' => $issue_customer_bill->id,
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
            
            $issue_customer_bill->total_amount = $total_amount;
            $issue_customer_bill->total_amount_words = $request->final_grand_total_words;
            $issue_customer_bill->tax_amount = $tax_amount;
            $issue_customer_bill->discount_amount = $discount_amount;
            $issue_customer_bill->unit_vat_rate_total = $unit_vat_rate;
            $issue_customer_bill->save();
            if(!empty($save_txns)){
                $transaction->total_before_tax = $total_amount - $tax_amount;
                $transaction->final_total = $total_amount;
                $transaction->tax_amount = $tax_amount;
                $transaction->discount_amount = $discount_amount;
                $transaction->save();
                
                $payments = $request->payment ?? [];
                
                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments, null, null,  true,'due');
                }
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            }

            VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($issue_customer_bill);
                
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            
            $contact = Contact::where('id',$issue_customer_bill->customer_id)->first();
            
            $msg_template = NotificationTemplate::where('business_id',$business_id)->where('template_for','credit_sale')->first();

            if(!empty($msg_template) && $contact->credit_notification == 'customer_bill'){
                
                $msg = $msg_template->sms_body;
                $msg = str_replace('{business_name}',$business->name,$msg);
                $msg = str_replace('{total_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                $msg = str_replace('{contact_name}',$contact->name,$msg);
                $msg = str_replace('{invoice_number}',$issue_customer_bill->customer_bill_no,$msg);
                $msg = str_replace('{paid_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                
                $msg = str_replace('{transaction_date}',date('Y-m-d', strtotime($issue_customer_bill->date)),$msg);
                
                $msg = str_replace('{due_amount}',$this->vatFormatter()->num_f(0),$msg);
                $msg = str_replace('{cumulative_due_amount}', $this->vatFormatter()->num_f(($issue_customer_bill->outstanding_amount+$issue_customer_bill->total_amount)),$msg);
                
                
                $phones = [];
                if(!empty($business->sms_settings)){
                    $phones = explode(',',str_replace(' ','',$business->sms_settings['msg_phone_nos']));
                }
                
                $phones[] = $contact->mobile;
                $phones[] = $contact->alternate_number;
            
                if(!empty($phones)){
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',',$phones),
                        'sms_body' => $msg
                    ];
                    
                    $this->businessUtil->sendSms($data,'credit_sale',$contact); 
                }
            }
            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
            if($is_print || $is_print == true || $is_print == 'true'){
                if($request->print_format == 'vat_print_2026'){
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_design_2026', $issue_customer_bill->id);
                } elseif ($request->print_format == 'vat_print_163') {
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_163', $issue_customer_bill->id);
                } else {
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print', $issue_customer_bill->id);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::to('vat-module/vat-invoice2')->with('status', $output);
    }
    
    public function store127(Request $request)
    {
        try {
            
            $business_id = $this->getBusinessId();
            $this->applyVatInvoice2DecimalRulesToRequest($request, $business_id);
            $is_print  = request()->is_print;
            $invoice_date = Carbon::parse($request->voucher_order_date)->format('Y-m-d');

            if ($this->hasExceededVatInvoice2MonthlyLimit($business_id, $invoice_date)) {
                return back()->withInput()->with('status', $this->vatInvoice2MonthlyLimitExceededOutput());
            }
            
            $vat_bill = VatCreditBill::where('customer_id',$request->customer_id)->first();
            $save_txns = false;
            if(!empty($vat_bill)){
                if($vat_bill->linked_accounts == "yes"){
                    $save_txns = true;
                }
            }
            
            $data = array(
                'business_id' => $business_id,
                'date' => $invoice_date,
                'customer_bill_no' => $this->generateInvoiceNumber($request->prefix_id, $business_id),
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
                'address'=>$request->delivery_to['address'],
                'name'=>$request->delivery_to['name'],
            );
            DB::beginTransaction();
            
            $issue_customer_bill = VatInvoice2::create($data);
            
            if(!empty($save_txns)){
                $transaction = $this->createCreditSellTransactions($issue_customer_bill);
                $pa_transaction = $this->transactionUtil->createOrUpdatePriceAdjustment($issue_customer_bill,$issue_customer_bill->customer_bill_no);
            }
            

            $total_amount = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $unit_vat_rate = 0;
            
            foreach ($request->issue_customer_bill['product_id'] as $key => $product_id) {
                $total_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]);
                $tax_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]);
                $discount_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]);
                $unit_vat_rate += $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]);
                
                $details = array(
                    'business_id' => $business_id,
                    'issue_bill_id' => $issue_customer_bill->id,
                    'product_id' => $product_id,
                    'unit_price' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price'][$key]),
                    'unit_price_before_tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price_excl'][$key]),
                    'qty' => $this->vatFormatter()->num_uf($request->issue_customer_bill['qty'][$key]),
                    'discount' => $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]),
                    'tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]),
                    'sub_total' => $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]),
                    'unit_vat_rate' =>  $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]),

                );
                // dd($details);
                $bill_detail = VatInvoiceDetail2::create($details);
                
                $business_locations = BusinessLocation::forDropdown($business_id);
                $business_locations_array = [];
                foreach ($business_locations as $location_key => $location_name) {
                    $business_locations_array[$location_key] = $location_name;
                }
                $location_keys = array_keys($business_locations_array);
                $default_location = !empty($location_keys) ? $location_keys[0] : null;
                
                if(!empty($save_txns)){
                    $this->createSellTransactions($transaction, $bill_detail, $business_id, $default_location);
                }  
            }
            
            foreach($request->payment as $payment){

                $payment_data = [
                    'invoice_id' => $issue_customer_bill->id,
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
            
            
            $issue_customer_bill->total_amount = $total_amount;
            $issue_customer_bill->tax_amount = $tax_amount;
            $issue_customer_bill->discount_amount = $discount_amount;
            $issue_customer_bill->unit_vat_rate_total = $unit_vat_rate;
            $issue_customer_bill->save();
            
            
            if(!empty($save_txns)){
                $transaction->total_before_tax = $total_amount - $tax_amount;
                $transaction->final_total = $total_amount;
                $transaction->tax_amount = $tax_amount;
                $transaction->discount_amount = $discount_amount;
                $transaction->save();
                
                $payments = $request->payment ?? [];
                
                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments, null, null,  true,'due');
                }
                
                
                $status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            }
            
            VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($issue_customer_bill);
                
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            
            $contact = Contact::where('id',$issue_customer_bill->customer_id)->first();
            
            $msg_template = NotificationTemplate::where('business_id',$business_id)->where('template_for','credit_sale')->first();

            if(!empty($msg_template) && $contact->credit_notification == 'customer_bill'){
                
                $msg = $msg_template->sms_body;
                $msg = str_replace('{business_name}',$business->name,$msg);
                $msg = str_replace('{total_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                $msg = str_replace('{contact_name}',$contact->name,$msg);
                $msg = str_replace('{invoice_number}',$issue_customer_bill->customer_bill_no,$msg);
                $msg = str_replace('{paid_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                
                $msg = str_replace('{transaction_date}',date('Y-m-d', strtotime($issue_customer_bill->date)),$msg);
                
                $msg = str_replace('{due_amount}',$this->vatFormatter()->num_f(0),$msg);
                $msg = str_replace('{cumulative_due_amount}', $this->vatFormatter()->num_f(($issue_customer_bill->outstanding_amount+$issue_customer_bill->total_amount)),$msg);
                
                $phones = [];
                if(!empty($business->sms_settings)){
                    $phones = explode(',',str_replace(' ','',$business->sms_settings['msg_phone_nos']));
                }
                
                $phones[] = $contact->mobile;
                $phones[] = $contact->alternate_number;
            
                if(!empty($phones)){
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',',$phones),
                        'sms_body' => $msg
                    ];
                    
                    $response = $this->businessUtil->sendSms($data,'credit_sale',$contact); 
                }
                        
            }

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
            if($is_print || $is_print == true || $is_print == 'true'){
                if($request->print_format == 'vat_print_2026'){
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_design_2026_127', $issue_customer_bill->id);
                }else{
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print127', $issue_customer_bill->id);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }


         return Redirect::to('vat-module/invoices-127')->with('status', $output);
    }
    
    public function createAccountTransaction($transaction, $type, $account_id, $sub_type = null, $is_credit_sale)
    {
        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'account_id' => $account_id,
            'contact_id' => $transaction->contact_id,
            'type' => $type,
            'sub_type' => $sub_type,
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id
        ];
        
    
        AccountTransaction::createAccountTransaction($account_transaction_data);
        // create ledger transactions
        if ($sub_type == 'ledger_show') {
            ContactLedger::createContactLedger($account_transaction_data, 'VAT Statement');
            if (!$is_credit_sale) {
                if ($type == 'debit') {
                    $ledger_type = 'credit';
                }
                if ($type == 'credit') {
                    $ledger_type = 'debit';
                }
                $account_transaction_data['type'] = $ledger_type;
                ContactLedger::createContactLedger($account_transaction_data, 'VAT Statement');
            }
        }
    }
    
    public function createCreditSellTransactions($sale,$id = null)
    {
        
        $final_total = $sale->total_amount - $sale->discount_amount;
        $total_before_tax = $sale->total_amount - $sale->tax_amount;
        $ob_data = [
            'business_id' => $sale->business_id,
            'location_id' => $sale->location_id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'contact_id' => $sale->customer_id,
            'pump_operator_id' => $sale->operator_id,
            'transaction_date' => Carbon::parse($sale->date)->format('Y-m-d'),
            'total_before_tax' => $total_before_tax,
            'final_total' => $final_total,
            'tax_amount' => $sale->tax_amount,
            'discount_type' => 'fixed',
            'discount_amount' => $sale->discount_amount,
            'credit_sale_id' => $sale->id,
            'is_credit_sale' => 1,
            'is_settlement' => 0,
            'created_by' => request()->session()->get('user.id'),
            'invoice_no' => $sale->customer_bill_no,
            'sub_type' => 'credit_sale',
            
        ];
        
        if(empty($id)){
            //Create transaction
            $transaction = Transaction::create($ob_data);
        }else{
            $transaction = Transaction::where('invoice_no', $id)->where('type','sell')->first();
            Transaction::where('invoice_no', $id)->where('type','sell')->update($ob_data);
        }
        
        return $transaction;
    }
    
    public function createSellTransactions($transaction, $sale, $business_id, $default_location)
    {
        $uf_quantity = $this->vatFormatter()->num_uf($sale->qty);
        
        $product = Variation::leftjoin('products', 'variations.product_id', 'products.id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->leftjoin('categories', 'products.category_id', 'categories.id')
            ->where('products.id', $sale->product_id)
            ->select('variations.id as variation_id', 'variation_location_details.location_id', 'products.id as product_id', 'categories.name as category_name', 'products.enable_stock')->first();

        $this->transactionUtil->createOrUpdateSellLinesVatBill($transaction, $product->product_id, $product->variation_id, $product->location_id, $sale);
        $location_product = !empty($product->location_id) ? $product->location_id : $default_location;
        
        // if enable stock
        if ($product->enable_stock && !empty($is_other_sale)) {
            
            $this->productUtil->decreaseProductQuantity(
                $sale->product_id,
                $product->variation_id,
                $location_product,
                $uf_quantity,
                0,
                'decrease',
                0
            );
            
            $store_id = Store::where('business_id', $business_id)->first()->id;
			$this->productUtil->decreaseProductQuantityStore(
                $sale->product_id,
                $product->variation_id,
                $location_product,
                $uf_quantity,
                $store_id,
                "decrease",
                0
            );

        }
        
        $fuel_tank = FuelTank::where('product_id',$sale->product_id)->first();
        
        if (!empty($fuel_tank)) {
            $fuel_tank_id = $fuel_tank->id;
            FuelTank::where('id', $fuel_tank_id)->decrement('current_balance', $sale->qty);
            TankSellLine::create([
                'business_id' => $sale->business_id,
                'transaction_id' => $transaction->id,
                'tank_id' => $fuel_tank_id,
                'product_id' => $sale->product_id,
                'quantity' => $sale->qty
            ]);
        }

        
        return true;
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
        
        $business_id = $this->getBusinessId();

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        
        $product_dropdown_data = $this->getVatInvoice2ProductDropdownData($business_id, true);
        $products = $product_dropdown_data['products'];
        $product_categories = $product_dropdown_data['product_categories'];
        $product_category_links = $product_dropdown_data['product_category_links'];
        
        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes','vat_invoice2_prefixes.id','vat_user_invoice_prefixes.prefix_id2')->where('vat_user_invoice_prefixes.business_id',$business_id)->where('vat_user_invoice_prefixes.user_id',auth()->user()->id)->pluck('vat_invoice2_prefixes.prefix','vat_invoice2_prefixes.id');
        
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        
        $payment_types = $this->productUtil->payment_types(null, false, false, false, false, true);
        
        $payment = VatInvoicePayment2::where('invoice_id',$id)->get();
        $invoice = VatInvoice2::findOrFail($id);
        $invoice_details = VatInvoiceDetail2::where('issue_bill_id',$id)->get();
        $customer_ref = CustomerReference::where('business_id',$business_id)->where('contact_id',$invoice->customer_id)->pluck('reference','id');
        $vat_print_2026_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_print_2026');
    
        return view('vat::vat_invoice2.edit')->with(compact(
            'customers',
            'products',
            'product_categories',
            'product_category_links',
            'prefixes',
            'business_locations',
            'payment_types',
            'payment',
            'invoice',
            'customer_ref',
            'invoice_details','vat_print_2026_enabled'
        ));
    }
    
    public function edit127($id)
    {
        
        $business_id = $this->getBusinessId();

        $customers = Contact::where('business_id', $business_id)->where('type', 'customer')->pluck('name', 'id');
        
        $products = Product::where('business_id', $business_id)->forModule('vat_vatinvoice127')->pluck('name', 'id');
        
        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes','vat_invoice2_prefixes.id','vat_user_invoice_prefixes.prefix_id2')->where('vat_user_invoice_prefixes.business_id',$business_id)->where('vat_user_invoice_prefixes.user_id',auth()->user()->id)->pluck('vat_invoice2_prefixes.prefix','vat_invoice2_prefixes.id');
        
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
        
        $payment_types = $this->productUtil->payment_types(null, false, false, false, false, true);
        
        $payment = VatInvoicePayment2::where('invoice_id',$id)->get();
        $invoice = VatInvoice2::findOrFail($id);
        $invoice_details = VatInvoiceDetail2::where('issue_bill_id',$id)->get();
        $customer_ref = CustomerReference::where('business_id',$business_id)->where('contact_id',$invoice->customer_id)->pluck('reference','id');
        
        return view('vat::vat_invoice2.edit127')->with(compact(
            'customers',
            'products',
            'prefixes',
            'business_locations',
            'payment_types',
            'payment',
            'invoice',
            'customer_ref',
            'invoice_details'
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
        try{
            
            $business_id = $this->getBusinessId();
            $this->applyVatInvoice2DecimalRulesToRequest($request, $business_id);
            $is_print  = request()->is_print;
            
            $vat_bill = VatCreditBill::where('customer_id',$request->customer_id)->first();
            $save_txns = false;
            if(!empty($vat_bill)){
                if($vat_bill->linked_accounts == "yes"){
                    $save_txns = true;
                }
            }
            
            $data = array(
                'business_id' => $business_id,
                'date' => $this->resolveVatInvoiceDate($request->voucher_order_date), // S664 item 11
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
            
            VatInvoice2::where('id',$id)->update($data);
            $issue_customer_bill = VatInvoice2::findOrFail($id);
            $this->deletePreviouseTransactions($issue_customer_bill->id);
            
            if(!empty($save_txns)){
                $transaction = $this->createCreditSellTransactions($issue_customer_bill,$issue_customer_bill->customer_bill_no);
                $pa_transaction = $this->transactionUtil->createOrUpdatePriceAdjustment($issue_customer_bill,$issue_customer_bill->customer_bill_no);
            }

            $total_amount = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $unit_vat_rate = 0;
            
            foreach ($request->issue_customer_bill['product_id'] as $key => $product_id) {
                $total_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]);
                $tax_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]);
                $discount_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]);
                $unit_vat_rate += $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]);
                
                $details = array(
                    'business_id' => $business_id,
                    'issue_bill_id' => $issue_customer_bill->id,
                    'product_id' => $product_id,
                    'unit_price' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price'][$key]),
                    'unit_price_before_tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price_excl'][$key]),
                    'qty' => $this->vatFormatter()->num_uf($request->issue_customer_bill['qty'][$key]),
                    'discount' => $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]),
                    'tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]),
                    'sub_total' => $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]),
                    'unit_vat_rate' =>  $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]),

                );
                // dd($details);
                $bill_detail = VatInvoiceDetail2::create($details);
                $business_locations = BusinessLocation::forDropdown($business_id);
                $business_locations_array = [];
                foreach ($business_locations as $location_key => $location_name) {
                    $business_locations_array[$location_key] = $location_name;
                }
                $location_keys = array_keys($business_locations_array);
                $default_location = !empty($location_keys) ? $location_keys[0] : null;
                
                if(!empty($save_txns)){
                    $this->createSellTransactions($transaction, $bill_detail, $business_id, $default_location);
                }
                
            }
            
            $payments =  [];
            
            foreach($request->payment as $payment){

                $payment_data = [
                    'invoice_id' => $issue_customer_bill->id,
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
                
                unset($payment['payment_id']);
                
                $payments[] = $payment;
                
                VatInvoicePayment2::create($payment_data);
            }
            
            $issue_customer_bill->total_amount_words = $request->edit_final_grand_total_words;
            $issue_customer_bill->total_amount = $total_amount;
            $issue_customer_bill->tax_amount = $tax_amount;
            $issue_customer_bill->discount_amount = $discount_amount;
            $issue_customer_bill->unit_vat_rate_total = $unit_vat_rate;
            $issue_customer_bill->save();
            
            
            if(!empty($save_txns)){
                $transaction->total_before_tax = $total_amount - $tax_amount;
                $transaction->final_total = $total_amount;
                $transaction->tax_amount = $tax_amount;
                $transaction->discount_amount = $discount_amount;
                $transaction->save();
                
                
                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments, null, null,  true,'due');
                }
                
                
                $status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            }
            \Modules\Vat\Http\Controllers\VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($issue_customer_bill);
            
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            
            $contact = Contact::where('id',$issue_customer_bill->customer_id)->first();
            
            $msg_template = NotificationTemplate::where('business_id',$business_id)->where('template_for','credit_sale')->first();

            if(!empty($msg_template) && $contact->credit_notification == 'customer_bill'){
                
                $msg = $msg_template->sms_body;
                $msg = str_replace('{business_name}',$business->name,$msg);
                $msg = str_replace('{total_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                $msg = str_replace('{contact_name}',$contact->name,$msg);
                $msg = str_replace('{invoice_number}',$issue_customer_bill->customer_bill_no,$msg);
                $msg = str_replace('{paid_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                
                $msg = str_replace('{transaction_date}',date('Y-m-d', strtotime($issue_customer_bill->date)),$msg);
                
                $msg = str_replace('{due_amount}',$this->vatFormatter()->num_f(0),$msg);
                $msg = str_replace('{cumulative_due_amount}', $this->vatFormatter()->num_f(($issue_customer_bill->outstanding_amount+$issue_customer_bill->total_amount)),$msg);
                
                $phones = [];
                if(!empty($business->sms_settings)){
                    $phones = explode(',',str_replace(' ','',$business->sms_settings['msg_phone_nos']));
                }
                
                $phones[] = $contact->mobile;
                $phones[] = $contact->alternate_number;
            
                if(!empty($phones)){
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',',$phones),
                        'sms_body' => $msg
                    ];
                    
                    $response = $this->businessUtil->sendSms($data,'credit_sale',$contact); 
                }
                        
            }
                

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
            
            if($is_print || $is_print == true || $is_print == 'true'){
                if($request->print_format == 'vat_print_2026'){
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_design_2026', $issue_customer_bill->id);
                } elseif ($request->print_format == 'vat_print_163') {
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_163', $issue_customer_bill->id);
                } else {
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print', $issue_customer_bill->id);
                }
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::to('vat-module/vat-invoice2')->with('status', $output);
    }
    
    public function update127(Request $request, $id)
    {
        try{
            
            $business_id = $this->getBusinessId();
            $this->applyVatInvoice2DecimalRulesToRequest($request, $business_id);
            $is_print  = request()->is_print;
            
            $vat_bill = VatCreditBill::where('customer_id',$request->customer_id)->first();
            $save_txns = false;
            if(!empty($vat_bill)){
                if($vat_bill->linked_accounts == "yes"){
                    $save_txns = true;
                }
            }
            
            $data = array(
                'business_id' => $business_id,
                'date' => $this->resolveVatInvoiceDate($request->voucher_order_date), // S664 item 11
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
                'place_of_supply'=> $request->place_of_supply,
                'additional_information' => $request->additional_information,
                'supplied_on' => $request->supplied_on,
                'price_adjustment' => $request->price_adjustment
            );
            
            
            DB::beginTransaction();
            
            VatInvoice2::where('id',$id)->update($data);
            $issue_customer_bill = VatInvoice2::findOrFail($id);
            $this->deletePreviouseTransactions($issue_customer_bill->id);
            
            if(!empty($save_txns)){
                $transaction = $this->createCreditSellTransactions($issue_customer_bill,$issue_customer_bill->customer_bill_no);
                $pa_transaction = $this->transactionUtil->createOrUpdatePriceAdjustment($issue_customer_bill,$issue_customer_bill->customer_bill_no);
            }

            $total_amount = 0;
            $tax_amount = 0;
            $discount_amount = 0;
            $unit_vat_rate = 0;
            
            foreach ($request->issue_customer_bill['product_id'] as $key => $product_id) {
                $total_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]);
                $tax_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]);
                $discount_amount += $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]);
                $unit_vat_rate += $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]);
                
                $details = array(
                    'business_id' => $business_id,
                    'issue_bill_id' => $issue_customer_bill->id,
                    'product_id' => $product_id,
                    'unit_price' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price'][$key]),
                    'unit_price_before_tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_price_excl'][$key]),
                    'qty' => $this->vatFormatter()->num_uf($request->issue_customer_bill['qty'][$key]),
                    'discount' => $this->vatFormatter()->num_uf($request->issue_customer_bill['discount'][$key]),
                    'tax' => $this->vatFormatter()->num_uf($request->issue_customer_bill['tax'][$key]),
                    'sub_total' => $this->vatFormatter()->num_uf($request->issue_customer_bill['sub_total'][$key]),
                    'unit_vat_rate' =>  $this->vatFormatter()->num_uf($request->issue_customer_bill['unit_vat_rate'][$key]),

                );
                // dd($details);
                $bill_detail = VatInvoiceDetail2::create($details);
                $business_locations = BusinessLocation::forDropdown($business_id);
                $business_locations_array = [];
                foreach ($business_locations as $location_key => $location_name) {
                    $business_locations_array[$location_key] = $location_name;
                }
                $location_keys = array_keys($business_locations_array);
                $default_location = !empty($location_keys) ? $location_keys[0] : null;
                
                if(!empty($save_txns)){
                    $this->createSellTransactions($transaction, $bill_detail, $business_id, $default_location);
                }
                
            }
            
            $payments =  [];
            
            foreach($request->payment as $payment){

                $payment_data = [
                    'invoice_id' => $issue_customer_bill->id,
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
                
                unset($payment['payment_id']);
                
                $payments[] = $payment;
                
                VatInvoicePayment2::create($payment_data);
            }
            
            $issue_customer_bill->total_amount_words = $request->final_grand_total_words;
            $issue_customer_bill->total_amount = $total_amount;
            $issue_customer_bill->tax_amount = $tax_amount;
            $issue_customer_bill->discount_amount = $discount_amount;
            $issue_customer_bill->unit_vat_rate_total = $unit_vat_rate;
            $issue_customer_bill->save();
            
            
            if(!empty($save_txns)){
                $transaction->total_before_tax = $total_amount - $tax_amount;
                $transaction->final_total = $total_amount;
                $transaction->tax_amount = $tax_amount;
                $transaction->discount_amount = $discount_amount;
                $transaction->save();
                
                
                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments, null, null,  true,'due');
                }
                
                
                $status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            }
            \Modules\Vat\Http\Controllers\VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($issue_customer_bill);
            
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            
            $contact = Contact::where('id',$issue_customer_bill->customer_id)->first();
            
            $msg_template = NotificationTemplate::where('business_id',$business_id)->where('template_for','credit_sale')->first();

            if(!empty($msg_template) && $contact->credit_notification == 'customer_bill'){
                
                $msg = $msg_template->sms_body;
                $msg = str_replace('{business_name}',$business->name,$msg);
                $msg = str_replace('{total_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                $msg = str_replace('{contact_name}',$contact->name,$msg);
                $msg = str_replace('{invoice_number}',$issue_customer_bill->customer_bill_no,$msg);
                $msg = str_replace('{paid_amount}',$this->vatFormatter()->num_f($issue_customer_bill->total_amount),$msg);
                
                $msg = str_replace('{transaction_date}',date('Y-m-d', strtotime($issue_customer_bill->date)),$msg);
                
                $msg = str_replace('{due_amount}',$this->vatFormatter()->num_f(0),$msg);
                $msg = str_replace('{cumulative_due_amount}', $this->vatFormatter()->num_f(($issue_customer_bill->outstanding_amount+$issue_customer_bill->total_amount)),$msg);
                
                
                $phones = [];
                if(!empty($business->sms_settings)){
                    $phones = explode(',',str_replace(' ','',$business->sms_settings['msg_phone_nos']));
                }
                
                $phones[] = $contact->mobile;
                $phones[] = $contact->alternate_number;
            
                if(!empty($phones)){
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',',$phones),
                        'sms_body' => $msg
                    ];
                    
                    $response = $this->businessUtil->sendSms($data,'credit_sale',$contact); 
                }
                        
            }
                

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];

            if($is_print || $is_print == true || $is_print == 'true'){
                if($request->print_format == 'vat_print_2026'){
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print_design_2026_127', $issue_customer_bill->id);
                }else{
                    $output['print_url'] = action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@print127', $issue_customer_bill->id);
                }
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }


        return Redirect::to('vat-module/invoices-127')->with('status', $output);
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
            $settlement = VatInvoice2::findOrFail($id);
            $this->deletePreviouseTransactions($settlement->id, true);
            $settlement->delete();
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
    
    public function deletePreviouseTransactions($id, $is_destory = false)
    {
        $business_id = $this->getBusinessId();
        $settlement = VatInvoice2::find($id);
        if ($settlement) {
            \Modules\Vat\Http\Controllers\VatInvoiceToTransactionController::deleteVatInvoiceTransactions($business_id, $settlement->customer_bill_no);
        }
        
        VatInvoiceDetail2::where('issue_bill_id',$id)->delete();
        VatInvoicePayment2::where('invoice_id',$id)->delete();
        
        $all_trasactions = Transaction::where('invoice_no', $settlement->customer_bill_no)->where('business_id', $business_id)->with(['sell_lines'])->withTrashed()->get();

        foreach ($all_trasactions as $transaction) {
            if (!empty($transaction)) {
                $deleted_sell_lines = $transaction->sell_lines;
                $deleted_sell_lines_ids = $deleted_sell_lines->pluck('id')->toArray();
                if ($transaction->sub_type == 'credit_sale') {
                    $this->transactionUtil->deleteSellLinesSettlement(
                        $deleted_sell_lines_ids,
                        $transaction->location_id,
                        false
                    );
                } else {
                    $this->transactionUtil->deleteSellLinesSettlement(
                        $deleted_sell_lines_ids,
                        $transaction->location_id
                    );
                }


                //Delete Cash register transactions
                $transaction->cash_register_payments()->delete();
            }

            $tank_sell_lines =  TankSellLine::where('transaction_id', $transaction->id)->get();
            foreach ($tank_sell_lines as $tank_sell_line) {
                FuelTank::where('id', $tank_sell_line->tank_id)->increment('current_balance', $tank_sell_line->quantity);
            }
            TankSellLine::where('transaction_id', $transaction->id)->forceDelete();
            AccountTransaction::where('transaction_id', $transaction->id)->forceDelete();
            ContactLedger::where('transaction_id', $transaction->id)->forceDelete();
            TransactionPayment::where('transaction_id', $transaction->id)->forceDelete();
        }

       
        if ($is_destory) {
            VatInvoiceDetail2::where('issue_bill_id',$id)->delete();
            VatInvoicePayment2::where('invoice_id',$id)->delete();
            Transaction::where('invoice_no', $settlement->customer_bill_no)->forceDelete();
            $settlement->delete();
        }
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
        $business_id = $this->getBusinessId();
        $products = Product::where('business_id', $business_id)->forModule('vat_vatinvoice2')->pluck('name', 'id');
        return view('vat::vat_invoice2.partials.product_row')->with(compact('products', 'index'));
    }

    public function print($id)
    {
        $business_id = $this->getBusinessId();

        $issue_customer_bill = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.business_id', $business_id)
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        if (empty($issue_customer_bill)) {
            abort(404);
        }
        $vat_decimal_config = $this->getVatInvoice2DecimalConfig(
            $issue_customer_bill->prefix,
            $business_id
        );

        $bill_details = VatInvoiceDetail2::leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select('vat_invoice_details_2.*', 'products.name as product_name')
            ->get();

        // // Calculate the total tax amount (total_vat)
        // $taxSum = VatInvoiceDetail2::where('issue_bill_id', $id)->sum('tax') ?? 0;

        // // Calculate the total of sub_total as total_with_vat
        // $totalWithVat = VatInvoiceDetail2::where('issue_bill_id', $id)->sum('sub_total') ?? 0;
        
        $business_details = $this->businessUtil->getDetails($issue_customer_bill->business_id);

        $receipt_details = $this->__getReceiptDetails($issue_customer_bill);
        // $receipt_details->total_vat = $this->vatFormatter()->num_f($taxSum,true,$business_details); // Overwrite total_vat with taxSum
        // $receipt_details->total_with_vat =  $this->vatFormatter()->num_f($totalWithVat + $taxSum,true,$business_details); // Overwrite total_with_vat with totalWithVat
        $receipt_details->price_adjustment =  $this->vatFormatter()->num_f($issue_customer_bill->price_adjustment,true,$business_details);
        $receipt_details->final_total =  $this->vatFormatter()->num_f($issue_customer_bill->total_amount + $issue_customer_bill->price_adjustment,true,$business_details);
        
        $payment_details = VatInvoicePayment2::where('invoice_id',$id)->get();

        return view('vat::vat_invoice2.print')->with(compact('issue_customer_bill', 'bill_details', 'receipt_details', 'payment_details', 'vat_decimal_config'));
    }

    public function print_design_2026($id)
    {
        $business_id = $this->getBusinessId();

        $invoice_record = VatInvoice2::where('business_id', $business_id)
            ->findOrFail($id);

        // Increment only when this print preview is opened. This keeps the
        // duplicate indicator consistent for Add -> Save & Print and List -> Print.
        $invoice_record->vat_invoice_2_2026 = ($invoice_record->vat_invoice_2_2026 ?? 0) + 1;
        $invoice_record->save();
        $vat_invoice_2_2026_print = (int) $invoice_record->vat_invoice_2_2026;

        $issue_customer_bill = VatInvoice2::leftJoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftJoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftJoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.business_id', $business_id)
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )
            ->firstOrFail();

        $vat_decimal_config = $this->getVatInvoice2DecimalConfig(
            $issue_customer_bill->prefix,
            $business_id
        );

        $bill_details = VatInvoiceDetail2::leftJoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select(
                'vat_invoice_details_2.*',
                'products.name as product_name',
                'products.barcode_type as product_code',
                'products.product_description as product_description'
            )
            ->get();

        $business_details = $this->businessUtil->getDetails($business_id);
        $receipt_details = $this->__getReceiptDetails($issue_customer_bill);
        $receipt_details->price_adjustment = $this->vatFormatter()->num_f(
            $issue_customer_bill->price_adjustment ?? 0,
            true,
            $business_details
        );
        $receipt_details->final_total = $this->vatFormatter()->num_f(
            $issue_customer_bill->total_amount + ($issue_customer_bill->price_adjustment ?? 0),
            true,
            $business_details
        );

        $payment = VatInvoicePayment2::where('invoice_id', $id)->first();
        $payment_details = VatInvoicePayment2::where('invoice_id', $id)->get();

        // S729: print_2026.blade.php now uses the approved VAT77-style A4 layout
        // and suppresses the global Report & Bill footer only for these VAT Invoice2 paths.
        $is_standard_vat_invoice2_print = true;

        return view('vat::vat_invoice2.print_2026')->with(compact(
            'issue_customer_bill',
            'bill_details',
            'receipt_details',
            'payment',
            'payment_details',
            'vat_invoice_2_2026_print',
            'vat_decimal_config',
            'is_standard_vat_invoice2_print'
        ));
    }

    /**
     * VAT Print - 163
     *
     * Additional A4 VAT Invoice 2 print design based on the supplied 163
     * reference. This is intentionally separate from the existing Old VAT
     * Print and VAT Print 2026 so users can choose the most suitable layout.
     */
    public function print_163($id)
    {
        $business_id = $this->getBusinessId();

        $issue_customer_bill = VatInvoice2::leftJoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftJoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftJoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.business_id', $business_id)
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )
            ->firstOrFail();

        $vat_decimal_config = $this->getVatInvoice2DecimalConfig(
            $issue_customer_bill->prefix,
            $business_id
        );

        $bill_details = VatInvoiceDetail2::leftJoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select(
                'vat_invoice_details_2.*',
                'products.name as product_name',
                'products.barcode_type as product_code',
                'products.product_description as product_description'
            )
            ->get();

        $business_details = $this->businessUtil->getDetails($business_id);
        $receipt_details = $this->__getReceiptDetails($issue_customer_bill);
        $receipt_details->price_adjustment = $this->vatFormatter()->num_f(
            $issue_customer_bill->price_adjustment ?? 0,
            true,
            $business_details
        );
        $receipt_details->final_total = $this->vatFormatter()->num_f(
            $issue_customer_bill->total_amount + ($issue_customer_bill->price_adjustment ?? 0),
            true,
            $business_details
        );

        $payment_details = VatInvoicePayment2::where('invoice_id', $id)->get();

        return view('vat::vat_invoice2.print_163')->with(compact(
            'issue_customer_bill',
            'bill_details',
            'receipt_details',
            'payment_details',
            'vat_decimal_config'
        ));
    }

    public function print127($id)
    {
        $issue_customer_bill = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        if (empty($issue_customer_bill)) {
            abort(404);
        }
        $vat_decimal_config = $this->getVatInvoice2DecimalConfig(
            $issue_customer_bill->prefix,
            $issue_customer_bill->business_id
        );

        $bill_details = VatInvoiceDetail2::leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select('vat_invoice_details_2.*', 'products.name as product_name')
            ->get();
            
        $receipt_details = $this->__getReceiptDetails($issue_customer_bill);

        return view('vat::vat_invoice2.print127')->with(compact('issue_customer_bill', 'bill_details', 'receipt_details', 'vat_decimal_config'));
    }

    public function print_design_2026_127($id)
    {
        $issue_customer_bill = VatInvoice2::leftjoin('contacts', 'vat_invoices_2.customer_id', 'contacts.id')
            ->leftjoin('customer_references', 'vat_invoices_2.reference_id', 'customer_references.id')
            ->leftjoin('users', 'vat_invoices_2.created_by', 'users.id')
            ->where('vat_invoices_2.id', $id)
            ->select(
                'vat_invoices_2.*',
                'customer_references.reference',
                'contacts.name as customer_name',
                'users.username as username'
            )->first();

        if (empty($issue_customer_bill)) {
            abort(404);
        }
        $vat_decimal_config = $this->getVatInvoice2DecimalConfig(
            $issue_customer_bill->prefix,
            $issue_customer_bill->business_id
        );

         // Increment print count for duplicate detection
        $invoiceRecord = VatInvoice2::find($id);
        $invoiceRecord->vat_invoice_2_2026 = ($invoiceRecord->vat_invoice_2_2026 ?? 0) + 1;
        $invoiceRecord->save();
        
        // Check if this is a duplicate print (print_count > 1)
        $vat_invoice_2_2026_print = $invoiceRecord->vat_invoice_2_2026;

        $bill_details = VatInvoiceDetail2::leftjoin('products', 'vat_invoice_details_2.product_id', 'products.id')
            ->where('vat_invoice_details_2.issue_bill_id', $id)
            ->select('vat_invoice_details_2.*', 'products.name as product_name','products.barcode_type as product_code','products.product_description as product_description')
            ->get();
        
        $payment = VatInvoicePayment2::where('invoice_id', $id)->first();            
        $receipt_details = $this->__getReceiptDetails($issue_customer_bill);
        $business_details = $this->businessUtil->getDetails($issue_customer_bill->business_id);
        $receipt_details->price_adjustment = $this->vatFormatter()->num_f($issue_customer_bill->price_adjustment ?? 0, true, $business_details);
        $receipt_details->final_total = $this->vatFormatter()->num_f($issue_customer_bill->total_amount + ($issue_customer_bill->price_adjustment ?? 0), true, $business_details);
        return view('vat::vat_invoice2.print_2026_127')->with(compact('issue_customer_bill', 'bill_details', 'receipt_details', 'payment', 'vat_invoice_2_2026_print', 'vat_decimal_config'));
    }

    protected function hasExceededVatInvoice2MonthlyLimit($business_id, $invoiceDate): bool
    {
        $limit = $this->getVatInvoice2MonthlyLimit($business_id);

        if ($limit === 0) {
            return false;
        }

        $invoiceDate = Carbon::parse($invoiceDate);

        $monthlyInvoiceCount = VatInvoice2::where('business_id', $business_id)
            ->whereBetween('date', [
                $invoiceDate->copy()->startOfMonth()->format('Y-m-d'),
                $invoiceDate->copy()->endOfMonth()->format('Y-m-d'),
            ])
            ->count();

        return $monthlyInvoiceCount >= $limit;
    }

    protected function getVatInvoice2MonthlyLimit($business_id): int
    {
        $centralConnection = array_key_exists('system', config('database.connections', []))
            ? 'system'
            : config('tenancy.database.central_connection', 'mysql');

        $subscription = Subscription::on($centralConnection)
            ->where('business_id', $business_id)
            ->whereDate('start_date', '<=', Carbon::today()->toDateString())
            ->whereDate('end_date', '>=', Carbon::today()->toDateString())
            ->where('status', 'approved')
            ->orderBy('id', 'desc')
            ->first();

        if (empty($subscription)) {
            $subscription = Subscription::on($centralConnection)
                ->where('business_id', $business_id)
                ->orderBy('id', 'desc')
                ->first();
        }

        if (empty($subscription)) {
            return 0;
        }

        return max(0, (int) data_get($subscription->package_details, 'vat_main_invoice2_monthly_limit', 0));
    }

    protected function vatInvoice2MonthlyLimitExceededOutput(): array
    {
        return [
            'success' => 0,
            'msg' => 'Your VAT Invoice limit has exceeded.',
        ];
    }

    
    public function __getReceiptDetails($transaction, $receipt_printer_type = 'browser'){
        $business_details = $this->businessUtil->getDetails($transaction->business_id);
        $tax_rate = TaxRate::where('business_id',$transaction->business_id)->first();
        
        
        $supply_from = VatSupplyFrom::where('business_id',$transaction->business_id)->where('status',1)->first();
        $bank_detail = VatBankDetail::where('business_id',$transaction->business_id)->where('status',1)->first();
        $concern = VatConcern::where('business_id',$transaction->business_id)->where('status',1)->first();
        
        $location_details = BusinessLocation::find($transaction->location_id);
        $invoice_layout = $this->businessUtil->invoiceLayout($transaction->business_id, $transaction->location_id, $location_details->invoice_layout_id);
        $il = $invoice_layout;
        
        $footer_top_margin = System::getProperty('footer_top_margin');
        $admin_invoice_footer = System::getProperty('admin_invoice_footer');
        
        $ref_no = CustomerReference::find($transaction->reference_id)->reference ?? '';
       

        $output = [
            'vat_logo' =>  $business_details->vat_logo,
            'vat_logo_width'  => $business_details->vat_logo_width,
            'vat_logo_height' => $business_details->vat_logo_height,
            'reference_no' => $ref_no,
            'header_text' => isset($il->header_text) ? $il->header_text : '',
            'delivery_date' => $transaction->supplied_on ? \Carbon\Carbon::parse($transaction->supplied_on)->format('m/d/Y') : '-',
            'suppliers_tin' => $business_details->tax_number_1 ?? '-',
            'business_name' => $business_details->name ??  '-',
            'location_name' => ($il->show_location_name == 1) ? $location_details->name : '-',
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
            'concern'  => $concern ,
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
        
        
        //Tax Info
        // if (!empty($business_details->tax_number_1)) {
        //     $output['tax_label1'] = !empty($business_details->tax_label_1) ? $business_details->tax_label_1 . ': ' : '';
        //  
        
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
                // $output['customer_info'] .= '<br>' . implode(',', array_filter([$customer->city, $customer->state, $customer->country]));
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
        // Format invoice number to preserve leading zeros for display
        /*
         * MA-002: PRINT THE NUMBER THAT WAS ISSUED, NOT A REBUILT ONE.
         *
         * This used to take the stored bill number, strip out its trailing
         * digits, and re-attach the prefix and padding from the CURRENT
         * VatInvoice2Prefix record:
         *
         *     $prefix_details = VatInvoice2Prefix::find($transaction->prefix);
         *     ... str_pad($number_part, strlen($prefix_details->starting_no)) ...
         *     $invoice_no = $prefix_details->prefix . $padded_number;
         *
         * So the moment anyone edited that prefix - changed its text, or
         * changed starting_no from "1" to "0001" - EVERY REPRINT OF EVERY
         * OLDER INVOICE CHANGED with it. A document reprinted a month later
         * carried a different number from the one the customer holds, and
         * nothing on screen said so.
         *
         * The rebuild was never needed. generateInvoiceNumber() already stores
         * the COMPLETE number, prefix and padding included:
         *
         *     return (string) $prefixes->prefix . $next_no_padded;
         *
         * so customer_bill_no is the issued number exactly as printed the
         * first time. It is now used as it stands.
         *
         * On a tax document this matters beyond tidiness - the number is the
         * document's identity, and a reprint that disagrees with the original
         * is a real problem in an audit.
         */
        $invoice_no = $transaction->customer_bill_no;
        $output['invoice_no'] = $invoice_no;
        
        //Heading & invoice label, when quotation use the quotation heading.
        $output['invoice_no_prefix'] = $il->invoice_no_prefix;
        $output['invoice_heading'] = $il->invoice_heading;
            
        $output['date_label'] = $il->date_label;
        // Combine date from transaction->date with time from created_at
        // Store as datetime string (Y-m-d H:i:s) so template can parse and format it correctly
        $invoice_datetime = \Carbon\Carbon::parse($transaction->date)->setTimeFromTimeString(\Carbon\Carbon::parse($transaction->created_at)->format('H:i:s'));
        $output['invoice_date'] = $invoice_datetime->format('Y-m-d H:i:s');
        
        
        $show_currency = true;
        $output['show_cat_code'] = $il->show_cat_code;
        $output['cat_code_label'] = $il->cat_code_label;
        //Subtotal
        $output['subtotal_label'] = $il->sub_total_label . ':';
        $output['subtotal'] = ($transaction->total_amount != 0) ? $this->vatFormatter()->num_f($transaction->total_amount, $show_currency, $business_details) : 0;
        $output['subtotal_unformatted'] = ($transaction->total_amount != 0) ? $transaction->total_amount: 0;
        //Discount
        $output['line_discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] = $invoice_layout->discount_label;
        $discount = $transaction->discount_amount;
        
        $output['discount'] = ($discount != 0) ? $this->vatFormatter()->num_f($discount, $show_currency, $business_details) : 0;
        
        
        //Order Tax
        $tax = $transaction->tax_amount;
        $output['tax_label'] = $invoice_layout->tax_label;
        $output['tax_label'] .= ':';
        $output['tax'] = ($transaction->tax_amount != 0) ? $this->vatFormatter()->num_f($transaction->tax_amount, $show_currency, $business_details) : 0;
        
        $output['total_label'] = $invoice_layout->total_label . ':';
        $output['total'] = $this->vatFormatter()->num_f($transaction->total_amount-$transaction->tax_amount, $show_currency, $business_details);
        $output['total_vat'] = $this->vatFormatter()->num_f($transaction->tax_amount, $show_currency, $business_details);  
        $output['total_with_vat'] = $this->vatFormatter()->num_f($transaction->total_amount, $show_currency, $business_details);  
        $total = $transaction->total_amount - $transaction->tax_amount; 
        $vatRate = $total * 0.18;
        $output['vat_rate'] = $this->vatFormatter()->num_f($vatRate, $show_currency, $business_details);  
        $output['footer_text'] = $invoice_layout->footer_text;
        $output['design'] = $il->design;
        return (object) $output;
    }

    public function logoUpload(){
         $business_id = $this->getBusinessId();

        $business = Business::findOrFail($business_id);

        return view('vat::vat_invoice2.logo_upload', [
            'vat_logo'        => $business->vat_logo,
            'vat_logo_width'  => $business->vat_logo_width,
            'vat_logo_height' => $business->vat_logo_height,
        ]);
    }
    
   public function logoUploadSave(Request $request)
    {
        $request->validate([
            'vat_logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'vat_logo_width'  => 'nullable|integer|min:1',
            'vat_logo_height' => 'nullable|integer|min:1',
        ]);

        $business_id = $this->getBusinessId();
        $business = Business::findOrFail($business_id);

        // Save width & height (even if logo not uploaded)
        $business->vat_logo_width  = $request->vat_logo_width;
        $business->vat_logo_height = $request->vat_logo_height;

        if ($request->hasFile('vat_logo')) {

            $businessName = strtolower(trim($business->name));
            $businessName = preg_replace('/[^a-z0-9]+/', '_', $businessName);
            $uploadPath = public_path('uploads/business/' . $businessName);

            // Create folder if not exists
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            // Delete old logo
            if (!empty($business->vat_logo)) {
                $oldPath = public_path($business->vat_logo);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $file = $request->file('vat_logo');
            $fileName = 'vat_logo_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);

            $business->vat_logo = 'uploads/business/' . $businessName . '/' . $fileName;
        }

        $business->save();

        return Redirect::back()->with('status', [
            'success' => true,
            'msg' => __('lang_v1.success'),
        ]);
    }
    

    /**
     * S664 (item 11): turn the submitted invoice date into Y-m-d.
     *
     * Carbon::parse() on an empty or unreadable voucher_order_date produced an
     * unusable value, and the saved row ended up with no date - which is the
     * blank Date column on List VAT Invoice 2.
     *
     * The business format is tried first because it is the only thing that can
     * tell 08/11/2026 apart as 8 November or 11 August. Common formats follow,
     * and today is the last resort so an invoice is never rejected over its
     * date alone.
     */
    private function resolveVatInvoiceDate($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return Carbon::now()->format('Y-m-d');
        }

        $businessFormat = session('business.date_format');

        $candidates = [];

        if (! empty($businessFormat)) {
            // Convert the datepicker-style token set to PHP's.
            $candidates[] = str_replace(
                ['d', 'm', 'Y', 'yyyy', 'mm', 'dd'],
                ['d', 'm', 'Y', 'Y', 'm', 'd'],
                $businessFormat
            );
        }

        $candidates = array_merge($candidates, ['Y-m-d', 'm/d/Y', 'd/m/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d']);

        foreach ($candidates as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);

                // Round-trip check: without it Carbon quietly accepts 31/02 and
                // rolls it into March.
                if ($parsed && $parsed->format($format) === $value) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return Carbon::now()->format('Y-m-d');
        }
    }
}
