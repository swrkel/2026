<?php
/* LICENSE: This source file belongs to The Web Fosters. The customer
 * is provided a licence to use it.
 * Permission is hereby granted, to any person obtaining the licence of this
 * software and associated documentation files (the "Software"), to use the
 * Software for personal or business purpose ONLY. The Software cannot be
 * copied, published, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. THE AUTHOR CAN FIX
 * ISSUES ON INTIMATION. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     The Web Fosters <thewebfosters@gmail.com>
 * @owner      The Web Fosters <thewebfosters@gmail.com>
 * @copyright  2018 The Web Fosters
 * @license    As attached in zip file.
 */

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use App\Account;
use App\AccountTransaction;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactGroup;
use App\ContactLedger;
use App\Media;
use App\Store;
use App\Product;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TypesOfService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use Modules\Repair\Entities\JobSheet;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Entities\DayEnd;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\SellController;
use App\NewVehicle;
use App\NotificationTemplate;

class SellPosController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $dummyPaymentLine;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */

    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->dummyPaymentLine = [
            'method' => 'cash',
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
            'transaction_no' => '',
        ];
    }

    private function userCan(string $ability): bool
    {
        $user = Auth::user();

        return !empty($user) && Gate::forUser($user)->allows($ability);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function getCustomerDueDetails(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $contact_id = request()->contact_id;
            
            // Input validation
            if (empty($business_id)) {
                Log::warning('getCustomerDueDetails: Missing business_id in session');
                return response()->json([
                    "name" => "",
                    "due" => "0.00",
                    "success" => false,
                    "error" => "Business session not found"
                ], 400);
            }
            
            if (empty($contact_id)) {
                Log::warning('getCustomerDueDetails: Missing contact_id parameter');
                return response()->json([
                    "name" => "",
                    "due" => "0.00",
                    "success" => false,
                    "error" => "Customer ID required"
                ], 400);
            }

            // Validate contact_id is numeric
            if (!is_numeric($contact_id)) {
                Log::warning('getCustomerDueDetails: Invalid contact_id format', ['contact_id' => $contact_id]);
                return response()->json([
                    "name" => "",
                    "due" => "0.00",
                    "success" => false,
                    "error" => "Invalid customer ID format"
                ], 400);
            }

            $name = '';
            $due = 0;

            // Get customer details with error handling
            $query = Contact::where('contacts.business_id', $business_id)
                ->where('contacts.id', $contact_id)
                ->select(['contacts.name as full_name'])
                ->first();
                
            $name = $query ? $query->full_name : '';
            
            // If customer not found, return zero due amount
            if (!$query) {
                Log::info('getCustomerDueDetails: Customer not found', [
                    'contact_id' => $contact_id,
                    'business_id' => $business_id
                ]);
                return response()->json([
                    "name" => "Customer Not Found",
                    "due" => "0.00",
                    "success" => true
                ]);
            }

            // Get customer balance with improved error handling
            $due = $this->contactUtil->getCustomerBalance($contact_id, $business_id, true);
            
            // Ensure due amount is never negative (for display purposes)
            $due = max(0, $due);
            
            // Format the due amount
            $formatted_due = $this->transactionUtil->num_f($due);

            return response()->json([
                "name" => $name,
                "due" => $formatted_due,
                "success" => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getCustomerDueDetails', [
                'contact_id' => $request->contact_id ?? 'not_set',
                'business_id' => request()->session()->get('user.business_id') ?? 'not_set',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return safe default values on error
            return response()->json([
                "name" => "",
                "due" => "0.00",
                "success" => false,
                "error" => "Unable to retrieve customer details"
            ], 500);
        }
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (!$this->userCan('sell.view') && !$this->userCan('sell.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        //Service staff filter
        $service_staffs = null;
        if ($is_service_staff_enabled) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }
        return view('sale_pos.index')->with(compact('business_locations', 'customers', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create(Request $request)
    {
        $creation_type = $request->get('type');

        $business_id = request()->session()->get('user.business_id');
        $customers = Contact::customersDropdown($business_id, false);
        if (!$this->userCan('sell.create')) {
            abort(403, 'Unauthorized action.');
        }
        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
        }
        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return Redirect::action('CashRegisterController@create');
        }
        $register_details = $this->cashRegisterUtil->getCurrentCashRegister(auth()->user()->id);
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        Log::info('Walk-in customer data:', $walk_in_customer ?: 'No walk-in customer found');
        $enable_petro_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module');
        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_lines[] = $this->dummyPaymentLine;
        $default_location = BusinessLocation::findOrFail($register_details->location_id);
        $payment_types = $this->productUtil->payment_types($default_location, false, false, false, true, "is_sale_enabled");
        unset($payment_types['credit_expense']);
        unset($payment_types['location_id']);
        //Shortcuts
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        if ($request->type == "quotation") {
            $pos_settings['allow_overselling'] = 1;
        }
        $search_product_settings = empty($business_details->search_product_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->search_product_settings, true);
        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }
        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id, $enable_petro_module) : false;

        Log::info('SellPosController@create - Checking brands for business_id: ' . $business_id);
        $raw_brands = Brands::where('business_id', $business_id)->get();
        Log::info('SellPosController@create - Raw brands count: ' . $raw_brands->count());
        
        $brands = (request()->session()->get('business.enable_brand') == 1) ? $raw_brands
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;
        $types = Contact::getContactTypes();
        $customer_groups = ContactGroup::forDropdown($business_id);
        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);
        if ($this->moduleUtil->isModuleEnabled('kitchen')) {
            $waiters = $this->transactionUtil->serviceStaffDropdown($business_id);
        } else {
            $waiters = '';
        }
        $default_price_group_id = !empty($default_location->selling_price_group_id) ? $default_location->selling_price_group_id : null;
        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }
        $waiter_enable = $this->moduleUtil->isModuleEnabled('kitchen');
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $default_datetime = $this->businessUtil->format_date('now', true);
        $contact_id = $this->businessUtil->check_customer_code($business_id, 1);
        $type = 'customer';
        $temp_data = DB::table('temp_data')->where('business_id', $business_id)->select('add_pos_data')->first();
        if (!empty($temp_data)) {
            $temp_data = json_decode($temp_data->add_pos_data);
        }
        if (!request()->session()->get('business.popup_load_save_data')) {
            $temp_data = [];
        }
        $patients = [];
        if (request()->session()->get('business.is_pharmacy')) {
            $patients = Business::where('is_patient', 1)->pluck('name', 'id');
        }
        $user = User::where('id', Auth::user()->id)->select('toggle_popup')->first();
        if (!empty($user)) {
            $toggle_popup = $user->toggle_popup;
        } else {
            $toggle_popup = 1;
        }

        $customer_id = $request->customer_id;
        if (!empty($request->job_sheet_id)) {
            $query = JobSheet::with(
                'customer',
                'customer.business',
                'technician',
                'status',
                'Brand',
                'Device',
                'deviceModel',
                'businessLocation',
                'invoices',
                'media'
            )
                ->where('business_id', $business_id);

            $job_sheet = $query->findOrFail($request->job_sheet_id);

            // Debug logging for job sheet customer
            Log::info('Job Sheet loaded:', [
                'job_sheet_id' => $request->job_sheet_id,
                'customer_id' => $job_sheet->contact_id,
                'customer_loaded' => isset($job_sheet->customer),
                'customer_data' => $job_sheet->customer ?? 'No customer data'
            ]);

            $parts = $job_sheet->getPartsUsed();
        } else {
            $job_sheet = (object) [
                'id' => '',
                'job_sheet_no' => '',
                'vehicle_id' => null,
                'reportStatus' => '', // Add this line
                'customer' => (object) [
                    'id' => '',
                    'name' => ''
                ]
            ];


            $parts = [];
        }



        $vehicle = [];

        // if(isset($job_sheet->customer)){
        // $vehicle=new_vehicle::where('customer_id',$job_sheet->customer->id)->first();
        //}

        if ($job_sheet) {
            $vehicle = NewVehicle::where('id', $job_sheet->vehicle_id)->first();
        } else {
            $vehicle = [];
        }

        $bank_group_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_groups.name', 'Bank Account')
            ->pluck('accounts.name', 'accounts.id');

        $total_outstanding = 0.00;

        $is_sales_page = null;
        if (empty($request->job_sheet_id)) {
            $is_sales_page = '1';
        }

        if (isset($job_sheet->customer->id)) {
            $customer_id = $job_sheet->customer->id;
            $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->leftjoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
                ->where('contacts.business_id', $business_id)
                ->where('contacts.id', $customer_id)
                ->onlyCustomers()
                ->select([
                    'contacts.contact_id',
                    'contacts.name',
                    'contacts.created_at',
                    'total_rp',
                    'cg.name as customer_group',
                    'sol_with_approval',
                    'state',
                    'country',
                    'landmark',
                    'mobile',
                    'contacts.id',
                    'is_default',
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'email',
                    'tax_number',
                    'contacts.pay_term_number',
                    'contacts.pay_term_type',
                    'contacts.credit_limit',
                    'contacts.custom_field1',
                    'contacts.custom_field2',
                    'contacts.custom_field3',
                    'contacts.custom_field4',
                    'contacts.type',
                ])
                ->groupBy('contacts.id')->first();
            if (!empty($query)) {
                $due = (float)($query->total_invoice ?? 0) - (float)($query->invoice_received ?? 0) + (float)($query->advance_payment ?? 0);
                $return_due = (float)($query->total_sell_return ?? 0) - (float)($query->sell_return_paid ?? 0);
                $opening_balance = (float)($query->opening_balance ?? 0);
                $total_outstanding = $due - $return_due + $opening_balance;
            } else {
                $due = 0.0;
                $return_due = 0.0;
                $opening_balance = 0.0;
                $total_outstanding = 0.0;
            }
            
            // Ensure total_outstanding is never negative for display purposes
            $total_outstanding = max(0, $total_outstanding);
            
            $total_outstanding = $this->transactionUtil->num_f($total_outstanding, false);
        }

        $disabled = $request->has('job_sheet_id') ? 'disabled' : '';

        // Debug logging for view data
        Log::info('View data being passed:', [
            'job_sheet_customer_id' => $job_sheet->customer->id ?? 'No customer ID',
            'job_sheet_customer_name' => $job_sheet->customer->name ?? 'No customer name',
            'customer_id' => $customer_id,
            'has_job_sheet' => !empty($request->job_sheet_id)
        ]);

        return view('sale_pos.create')
            ->with(compact(
                'total_outstanding',
                'bank_group_accounts',
                'toggle_popup',
                'patients',
                'business_details',
                'taxes',
                'type',
                'payment_types',
                'walk_in_customer',
                'payment_lines',
                'default_location',
                'shortcuts',
                'commission_agent',
                'categories',
                'brands',
                'pos_settings',
                'change_return',
                'types',
                'customer_groups',
                'accounts',
                'price_groups',
                'types_of_service',
                'default_price_group_id',
                'shipping_statuses',
                'default_datetime',
                'waiters',
                'waiter_enable',
                'search_product_settings',
                'temp_data',
                'contact_id',
                'job_sheet',
                'parts',
                'vehicle',
                'disabled',
                'creation_type',
                'customer_id',
                'customers',
                'is_sales_page'
            ));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        if (!$this->userCan('sell.create') && !$this->userCan('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        // logger(json_encode($request->all()));die;

        $business_id = request()->session()->get('user.business_id');
        DB::table('temp_data')->where('business_id', $business_id)->update(['add_pos_data' => '']);
        $is_direct_sale = true;
        /* if (!empty($request->input('is_direct_sale'))) {
        $is_direct_sale = true;
        } */
        if ($request->wantsJson()) {
            $is_direct_sale = false;
        }
        //Check if there is a open register, if no then redirect to Create Register screen.
        if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
            return Redirect::action('CashRegisterController@create');
        }
        try {
            // Get input data first
            $input = $request->except('_token');


            if (empty($input['contact_id'])) {
                $output = [
                    'success' => 0,
                    'msg' => 'Customer is required. Contact ID: ' . ($input['contact_id'] ?? 'not_set'),
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return Redirect::action('SellController@index')
                        ->with('status', $output);
                }
            }

            if (empty($input['products']) || !is_array($input['products'])) {
                $output = [
                    'success' => 0,
                    'msg' => 'At least one product is required',
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return Redirect::action('SellController@index')
                        ->with('status', $output);
                }
            }

            if ((isset($input['is_pos']) && !$input['is_pos']) && (empty($input['payment']) || !is_array($input['payment']))) {
                $output = [
                    'success' => 0,
                    'msg' => 'Payment method is required',
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return Redirect::action('SellController@index')
                        ->with('status', $output);
                }
            }

            $reviewed = $this->productUtil->get_review(date('Y-m-d'), date('Y-m-d'));

            if (!empty($reviewed)) {
                Log::warning('POS Store: Reviewed date block', ['date' => date('Y-m-d')]);
                $output = [
                    'success' => 0,
                    'msg' => "You can't make a sale for an already reviewed date",
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return Redirect::action('SellController@index')
                        ->with('status', $output);
                }
            }

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);
            if ($is_credit_limit_exeeded !== false) {
                Log::warning('POS Store: Credit limit exceeded', ['contact_id' => $input['contact_id']]);
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return Redirect::action('SellController@index')
                        ->with('status', $output);
                }
            }

            $is_over_limit_credit_sale = $this->transactionUtil->isOverLimitCreditSale($input);
            $input['is_quotation'] = 0;
            $input['store_id'] = app(\App\Services\StoreStockIntegrityService::class)
                ->resolveStoreIdForLocation(
                    (int) ($input['location_id'] ?? 0),
                    !empty($input['store_id']) ? (int) $input['store_id'] : null,
                    (int) ($request->session()->get('user.business_id') ?: 0)
                );
            // This ensures if a sale is done while /pos/create?type=quotation the sale does not use the displayed quotation number
            if (!empty($input['invoice_no'])) {
                $sellController = app()->make(SellController::class);
                $getInvoiveNo = $sellController->getInvoiveNo($request);
                if ($input['is_duplicate'] == 1) {
                    $input['invoice_no'] = $getInvoiveNo['duplicate_invoice_no'];
                } else {
                    $input['invoice_no'] = $getInvoiveNo['orignal_invoice_no'];
                }
            }
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }
            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');
                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                } elseif (!$this->moduleUtil->isQuotaAvailable('monthly_total_sales_limit', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('monthly_total_sales_limit', $business_id, action('SellPosController@index'));
                }
                $user_id = $request->session()->get('user.id');
                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);
                $subscription = Subscription::active_subscription($business_id);
                if (empty($subscription) || empty($subscription->package)) {
                    $output = [
                        'success' => 0,
                        'msg' => __('superadmin::lang.subscription_expired_notification'),
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return Redirect::action('SellController@index')
                            ->with('status', $output);
                    }
                }
                $monthly_max_sale_limit = $subscription->package->monthly_max_sale_limit;
                $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
                $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
                $current_monthly_sale = DB::table('transactions')
                    ->select(DB::raw('sum(final_total) as total'))
                    ->where('business_id', $business_id)
                    ->whereIn('type', ['sell', 'property_sell'])
                    ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                    ->groupBy('business_id')
                    ->first();
                $current_monthly_sale = is_null($current_monthly_sale) ? 0 : (float) $current_monthly_sale->total;
                $current_monthly_sale += $invoice_total['final_total'];
                if ($current_monthly_sale > $monthly_max_sale_limit) {
                    Log::warning('POS Store: Monthly max sale limit exceeded', ['business_id' => $business_id]);
                    $output = [
                        'success' => 0,
                        'msg' => __('lang_v1.monthly_max_sale_limit_exceeded', ['monthly_max_sale_limit' => $monthly_max_sale_limit]),
                    ];

                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return Redirect::action('SellController@index')
                            ->with('status', $output);
                    }
                }


                DB::beginTransaction();
                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = Carbon::now();
                } else {
                    $dateInput = $request->input('transaction_date');
                    $formats = ['m/d/Y H:i', 'Y-m-d H:i:s', 'd/m/Y H:i', 'd-m-Y H:i', 'Y-m-d\TH:i'];
                    $parsedDate = null;

                    foreach ($formats as $format) {
                        try {
                            $parsedDate = Carbon::createFromFormat($format, $dateInput);
                            break; // Exit the loop if a format matches
                        } catch (\Exception $e) {
                            // Continue trying other formats
                        }
                    }

                    if ($parsedDate) {
                        $input['transaction_date'] = $parsedDate->format('Y-m-d H:i:s');
                    } else {
                        // Handle the error case where no format matched
                        $input['transaction_date'] = Carbon::now();
                    }
                }

                $latest_day_end = DayEnd::where('business_id', $business_id)->max('day_end_date');
                if (!empty($latest_day_end)) {
                    $sale_date_for_lock = Carbon::parse($input['transaction_date'])->format('Y-m-d');
                    if (strtotime($sale_date_for_lock) <= strtotime($latest_day_end)) {
                        Log::warning('POS Store: Day end exists', ['date' => $input['transaction_date'], 'latest_day_end' => $latest_day_end]);
                        DB::rollBack();
                        $output = [
                            'success' => 0,
                            'msg' => __('petro::lang.date_greater_than_day_end'),
                        ];
                        if (!$is_direct_sale) {
                            return $output;
                        } else {
                            return Redirect::action('SellController@index')
                                ->with('status', $output);
                        }
                    }
                }

                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }
                //Set commission agent
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }
                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }
                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $customer_group_id = null;
                if (is_object($cg) && !empty($cg->id)) {
                    $customer_group_id = $cg->id;
                } elseif (is_array($cg) && !empty($cg['id'])) {
                    $customer_group_id = $cg['id'];
                }
                $input['customer_group_id'] = $customer_group_id;
                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;
                //If default price group for the location exists
                $price_group_id = $price_group_id == 0 && $request->has('default_price_group') ? $request->input('default_price_group') : $price_group_id;
                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }
                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }
                if ($is_direct_sale) {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }
                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                        $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                        $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                        $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                        $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                        $request->input('service_custom_field_4') : null;
                }
                $input['selling_price_group_id'] = $price_group_id;
                $input['order_no'] = !empty($request->order_no) ? $request->order_no : null;
                $input['order_date'] = !empty($request->order_date) ? Carbon::parse($request->order_date)->format('Y-m-d') : null;
                $input['customer_ref'] = !empty($request->customer_ref) ? $request->customer_ref : null;
                $input['is_credit_sale'] = 0;
                $is_credit_sale = !empty($request->is_credit_sale) ? $request->is_credit_sale : 0;
                $is_credit_sale_request = isset($request->payment) && is_array($request->payment)
                    && in_array('credit_sale', array_column($request->payment, 'method'));
                // if ($input['payment'][0]['amount'] < $input['final_total']) {
                //     $is_credit_sale = 1;
                // }
                if ($is_credit_sale || $is_credit_sale_request) {
                    $input['is_credit_sale'] = 1;
                }
                $customer = Contact::findOrFail($contact_id);
                if ($is_over_limit_credit_sale && $input['is_credit_sale']) {
                    $input['approved_user'] = $customer->temp_approved_user;
                    $input['requested_by'] = $customer->temp_requested_by;
                    $input['is_over_limit_credit_sale'] = 1;
                    $over_limit_amount = 0;
                    $over_limit_amount = abs($this->transactionUtil->getOverLimitAmount($input));
                    $input['over_limit_amount'] = $over_limit_amount;
                }
                $input['customer_limit'] = $customer->credit_limit;
                if ($request->input('autoservice')) {
                    $input['sub_type'] = 'repair';
                }

                $input['type'] = 'sell';
                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);


                if (!$is_direct_sale) {
                    //Add change return
                    if ($request->is_pos != 1) {
                        $change_return = $this->dummyPaymentLine;
                        if (isset($input['change_return'])) {
                            $change_return['amount'] = $input['change_return'];
                        }
                        $change_return['is_return'] = 1;
                        $input['payment'][] = $change_return;
                    }
                }
                /* ------------Account Transaction--------------- */
                $is_credit_sale_request = isset($request->payment) && is_array($request->payment)
                    && in_array('credit_sale', array_column($request->payment, 'method'));
                if ($is_credit_sale_request || $is_credit_sale == '1') {
                    $acc_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                    $this->createAccountTransaction($transaction, 'debit', $acc_id);
                    $this->createContactLedger($transaction, 'debit');
                }
                $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->where('is_closed', 0)->first();
                $exccess_data = [
                    'account_id' => $account_payable->id,
                    'sub_type' => '',
                    'operation_date' => $transaction->transaction_date,
                    'created_by' => $transaction->created_by,
                    'transaction_id' => $transaction->id,
                    'transaction_payment_id' => null,
                ];
                if (!empty($request->in_customer_wallet) && $request->in_customer_wallet > 0) {
                    $exccess_data['amount'] = $request->in_customer_wallet;
                    $exccess_data['type'] = 'credit';
                    AccountTransaction::createAccountTransaction($exccess_data);
                }
                if (!empty($request->was_customer_wallet) && $request->was_customer_wallet > 0) {
                    if ($request->was_customer_wallet >= $transaction->final_total) {
                        $old_excess_amount = $transaction->final_total;
                        $input['payment'][0]['amount'] = '0.00';
                    }
                    if ($request->was_customer_wallet < $transaction->final_total) {
                        $old_excess_amount = $request->was_customer_wallet;
                    }
                    $is_credit_sale = 0;
                    $exccess_data['amount'] = $old_excess_amount;
                    $exccess_data['type'] = 'debit';
                    AccountTransaction::createAccountTransaction($exccess_data);
                    $transaction->amount_paid_from_advance = $old_excess_amount;
                    $transaction->save();
                }

                // Add account transaction for sales discount (Bill wise)
                if ($this->transactionUtil->num_uf($input['discount_amount']) > 0) {
                    $discount_account_id = $this->transactionUtil->account_exist_return_id('Sales Discount');
                    if (!empty($discount_account_id)) {
                        if ($input['discount_type'] == 'fixed') {
                            $discount_amount = $this->transactionUtil->num_uf($input['discount_amount']);
                        } else {
                            $total_amount = 0;
                            foreach ($input['products'] as $product) {
                                $total_amount += $this->transactionUtil->num_uf($product['unit_price_inc_tax']) * $this->transactionUtil->num_uf($product['quantity']);
                            }
                            // $discount_amount = ($this->transactionUtil->num_uf($input['final_total']) / (1 - ($this->transactionUtil->num_uf($input['discount_amount']) / 100))) - $this->transactionUtil->num_uf($input['final_total']);
                            $discount_amount = $total_amount * ($this->transactionUtil->num_uf($input['discount_amount']) / 100);
                        }
                        if ($discount_amount > 0) {
                            $account_transaction_data = [
                                'amount' => abs($discount_amount),
                                'account_id' => $discount_account_id,
                                'type' => "debit",
                                'sub_type' => null,
                                'operation_date' => $transaction->transaction_date,
                                'created_by' => $transaction->created_by,
                                'transaction_id' => $transaction->id,
                                'note' => null
                            ];
                            AccountTransaction::createAccountTransaction($account_transaction_data);
                        }
                    }
                }

                if (isset($input['is_cash']) && $input['is_cash'] == 1) {
                    $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
                    if (!empty($cash_account_id)) {
                        $input['payment'] = [
                            [
                                // Always use persisted final_total (after all discounts/taxes),
                                // not raw request input, for account-book/payment consistency.
                                'amount' => $transaction->final_total ?? 0,
                                'method' => 'cash',
                                'account_id' => $cash_account_id,
                                'card_transaction_number' => null,
                                'cheque_number' => null,
                                'cheque_date' => null,
                            ]
                        ];
                    }
                }
                if (!$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                    $normalized_payments = $this->appendPosPaymentNote($input['payment'], $is_direct_sale);
                    // Filter and normalize payment array to ensure all valid payments are processed
                    $valid_payments = [];
                    foreach ($normalized_payments as $payment) {
                        // Skip if not an array
                        if (!is_array($payment)) {
                            continue;
                        }
                        
                        // Skip if amount is empty or zero (unless it's credit_sale)
                        $payment_amount = $this->transactionUtil->num_uf($payment['amount'] ?? 0);
                        $payment_method = $payment['method'] ?? '';
                        
                        if (empty($payment_method)) {
                            continue;
                        }
                        
                        // Include payment if amount > 0 or if it's credit_sale
                        if ($payment_amount > 0 || $payment_method == 'credit_sale') {
                            $valid_payments[] = $payment;
                        }
                    }
                    
                    // Only process if we have valid payments
                    if (!empty($valid_payments)) {
                        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $valid_payments);
                    }
                }

                $update_transaction = false;
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $update_transaction = true;
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $update_transaction = true;
                }
                if ($update_transaction) {
                    $transaction->save();
                }
                //Check for final and do some processing.
                if ($input['status'] == 'final' || ($input['status'] == 'order' && $input['need_to_reserve'] == 'yes')) {
                    //update product stock
                    foreach ($input['products'] as $product) {
                        $decrease_qty = $this->productUtil->num_uf($product['quantity']);
                        if (!empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }
                        if ($product['enable_stock']) {
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty,
                                0,
                                'decrease',
                                $input['store_id']
                            );

                            $this->productUtil->decreaseProductQuantityStore(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty,
                                $input['store_id'],
                                "decrease",
                                0
                            );
                        }
                        if ($product['product_type'] == 'combo') {
                            //Decrease quantity of combo as well.
                            $this->productUtil
                                ->decreaseProductQuantityCombo(
                                    $product['combo'],
                                    $input['location_id']
                                );
                        }
                        // if(!empty($product['weight_loss']) || !empty($product['weight_excess'])){
                        //     $this->productUtil->createWeightExcessLossAdjustment($product, $input['location_id']);
                        // }
                    }
                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                        $normalized_payments = $this->appendPosPaymentNote($input['payment'], $is_direct_sale);
                        // Filter and normalize payment array to ensure all valid payments are processed
                        $valid_payments = [];
                        foreach ($normalized_payments as $payment) {
                            // Skip if not an array
                            if (!is_array($payment)) {
                                continue;
                            }
                            
                            // Skip if amount is empty or zero (unless it's credit_sale)
                            $payment_amount = $this->transactionUtil->num_uf($payment['amount'] ?? 0);
                            $payment_method = $payment['method'] ?? '';
                            
                            if (empty($payment_method)) {
                                continue;
                            }
                            
                            // Include payment if amount > 0 or if it's credit_sale
                            if ($payment_amount > 0 || $payment_method == 'credit_sale') {
                                $valid_payments[] = $payment;
                            }
                        }
                        
                        // Only process if we have valid payments
                        if (!empty($valid_payments)) {
                            $pos_return_transactions = Transaction::where('business_id', $business_id)->where('pos_invoice_return', $transaction->invoice_no)->first();
                            $this->cashRegisterUtil->addSellPayments($transaction, $valid_payments, $pos_return_transactions);
                        }
                    }
                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }
                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
                    $business = [
                        'id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings,
                    ];
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');
                }

                //Auto send notification
                $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }
                Media::uploadMedia($business_id, $transaction, $request, 'documents');
                // Contact::where('id', $contact_id)->update(['temp_approved_user' => null, 'temp_requested_by' => null]);


                // add VAT entries
                $this->transactionUtil->calculateAndUpdateVAT($transaction);

                DB::commit();

                $msg = '';
                $receipt = '';
                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_added");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, false);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    if (empty($input['sub_type'])) {
                        $msg = trans("sale.pos_sale_added");
                        if (!$transaction->is_suspend) {

                            $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, false);
                        } else {
                            $receipt = '';
                        }
                    } else {
                        $msg = trans("sale.pos_sale_added");
                        $receipt = '';
                    }
                }
                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong"),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } elseif (config('app.debug')) {
                $msg = $e->getMessage();
            }
            $output = [
                'success' => 0,
                'msg' => $msg,
                'error_details' => $e->getMessage()
            ];
        }
        
        if (!$is_direct_sale || (!empty($request->submit_type) && $request->submit_type == 'save_and_print')) {
            // This was not initialized at this block, it was throwing an error
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return Redirect::action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return Redirect::action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return Redirect::to($redirect_url)
                        ->with('status', $output);
                }
                return Redirect::action('SellController@index')
                    ->with('status', $output);
            }
        }
    }

    public function createAccountTransaction($transaction, $type, $account_id)
    {
        $account_transaction_data = [
            'amount' => $transaction->final_total,
            'account_id' => $account_id,
            'type' => $type,
            'sub_type' => '',
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
        ];
        AccountTransaction::createAccountTransaction($account_transaction_data);
        $account_transaction_data['ledger_show'] = null;
        $transaction->refresh();
        $this->transactionUtil->manageStockAccount($transaction, $account_transaction_data, 'credit', $transaction->final_total);
        $this->transactionUtil->createCostofGoodsSoldTransaction($transaction, null, 'debit');
        $this->transactionUtil->createSaleIncomeTransaction($transaction, null, 'credit');
    }

    public function createContactLedger($transaction, $type)
    {
        $account_transaction_data = [
            'contact_id' => !empty($transaction) ? $transaction->contact_id : null,
            'amount' => $transaction->final_total,
            'type' => $type,
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
        ];
        ContactLedger::createContactLedger($account_transaction_data);
    }

    private function appendPosPaymentNote(array $payments, $is_direct_sale)
    {
        if ($is_direct_sale) {
            return $payments;
        }

        foreach ($payments as $key => $payment) {
            if (!is_array($payment)) {
                continue;
            }

            $method = $payment['method'] ?? '';
            if (empty($method)) {
                continue;
            }

            $note = trim((string) ($payment['note'] ?? ''));
            if (stripos($note, 'POS Payment') === 0) {
                $payments[$key]['note'] = $note;
                continue;
            }

            $payments[$key]['note'] = $note === '' ? 'POS Payment' : 'POS Payment - ' . $note;
        }

        return $payments;
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */

    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true
    ) {
        $output = [
            'is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => [],
        ];
        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);
        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.
        $output['is_enabled'] = true;
        $transaction = Transaction::find($transaction_id);
        if ($transaction->is_quotation) {
            $location_details->invoice_layout_id = null;
        }
        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);
        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;
        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
        // Guard: ensure we have an object with expected properties
        if (is_array($receipt_details)) {
            $receipt_details = (object) $receipt_details;
        }



        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;
        if ($is_package_slip) {

            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
            return $output;
        }
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['printer_config']['design'] = $receipt_details->design;
            $output['data'] = $receipt_details;
        } else {
            $layout = (!empty($receipt_details->design) && !$transaction->is_quotation) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';
            $output['html_content'] = view($layout, compact('receipt_details'))->render();
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
        if (!$this->userCan('sell.update')) {
            abort(403, 'Unauthorized action.');
        }
        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days]),
                ]);
        }
        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return Redirect::action('CashRegisterController@create');
        }
        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.return_exist'),
            ]);
        }
        $price_later = request()->price_later;
        $is_sales_page = request()->is_sales_page;
        $business_id = request()->session()->get('user.business_id');
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->with(['price_group', 'types_of_service'])
            ->findorfail($id);
        $location_id = $transaction->location_id;
        $business_location = BusinessLocation::find($location_id);
        $payment_types = $this->productUtil->payment_types($business_location, false, false, false, true, "is_sale_enabled");
        unset($payment_types['credit_expense']);
        unset($payment_types['location_id']);
        $location_printer_type = $business_location->receipt_printer_type;
        $sell_details = TransactionSellLine::join(
            'products AS p',
            'transaction_sell_lines.product_id',
            '=',
            'p.id'
        )
            ->join(
                'variations AS variations',
                'transaction_sell_lines.variation_id',
                '=',
                'variations.id'
            )
            ->join(
                'product_variations AS pv',
                'variations.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
            ->where('transaction_sell_lines.transaction_id', $id)
            ->with(['warranties'])
            ->select(
                DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                'p.id as product_id',
                'p.enable_stock',
                'p.name as product_actual_name',
                'p.type as product_type',
                'pv.name as product_variation_name',
                'pv.is_dummy as is_dummy',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.barcode_type',
                'p.enable_sr_no',
                'variations.id as variation_id',
                'units.short_name as unit',
                'units.allow_decimal as unit_allow_decimal',
                'transaction_sell_lines.tax_id as tax_id',
                'transaction_sell_lines.item_tax as item_tax',
                'transaction_sell_lines.unit_price as default_sell_price',
                'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                'transaction_sell_lines.id as transaction_sell_lines_id',
                'transaction_sell_lines.id',
                'transaction_sell_lines.quantity as quantity_ordered',
                'transaction_sell_lines.sell_line_note as sell_line_note',
                'transaction_sell_lines.parent_sell_line_id',
                'transaction_sell_lines.lot_no_line_id',
                'transaction_sell_lines.line_discount_type',
                'transaction_sell_lines.line_discount_amount',
                'transaction_sell_lines.res_service_staff_id',
                'transaction_sell_lines.weight_excess',
                'transaction_sell_lines.weight_loss',
                'transaction_sell_lines.last_purchased_price',
                'units.id as unit_id',
                'transaction_sell_lines.sub_unit_id',
                DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
            )
            ->get();
        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                //If modifier or combo sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }
                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);
                    //Add available lot numbers for dropdown to sell lines
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        if (!is_iterable($lot_number_obj)) {
                            $lot_number_obj = [];
                        }
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }
                            $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;
                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }
                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);
                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'modifier')
                            ->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;
                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }
                    //Get details of combo items
                    if ($sell_details[$key]->product_type == 'combo') {
                        $sell_line_combos = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'combo')
                            ->get()
                            ->toArray();
                        if (!empty($sell_line_combos)) {
                            $sell_details[$key]->combo_products = $sell_line_combos;
                        }
                        //calculate quantity available if combo product
                        $combo_variations = [];
                        foreach ($sell_line_combos as $combo_line) {
                            $combo_variations[] = [
                                'variation_id' => $combo_line['variation_id'],
                                'quantity' => $combo_line['quantity'] / $sell_details[$key]->quantity_ordered,
                                'unit_id' => null,
                            ];
                        }
                        $sell_details[$key]->qty_available =
                            $this->productUtil->calculateComboQuantity($location_id, $combo_variations);
                        if ($transaction->status == 'final') {
                            $sell_details[$key]->qty_available = $sell_details[$key]->qty_available + $sell_details[$key]->quantity_ordered;
                        }
                        $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($sell_details[$key]->qty_available, false, null, true);
                    }
                }
            }
        }
        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        if (!is_array($payment_lines)) {
            $payment_lines = [];
        }
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }
        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_brands'), 'all') : false;
        $change_return = $this->dummyPaymentLine;
        $types = [];
        if ($this->userCan('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if ($this->userCan('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if ($this->userCan('supplier.create') && $this->userCan('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = ContactGroup::forDropdown($business_id);
        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $waiters = [];
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }
        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);
            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }
        $contact_id = $this->businessUtil->check_customer_code($business_id, 1);
        $type = 'customer'; //contact type /used in quick add contact
        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);
        $edit_discount = $this->userCan('edit_product_discount_from_pos_screen');
        $edit_price = $this->userCan('edit_product_price_from_pos_screen');
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $warranties = $this->__getwarranties();
        $temp_data = json_decode('[]');
        $user = User::where('id', Auth::user()->id)->select('toggle_popup')->first();
        if (!empty($user)) {
            $toggle_popup = $user->toggle_popup;
        } else {
            $toggle_popup = 1;
        }
        $bank_group_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_groups.name', 'Bank Account')
            ->pluck('accounts.name', 'accounts.id');
        $transaction->tax_id = $transaction->order_tax_id;
        return view('sale_pos.edit')
            ->with(compact('bank_group_accounts', 'price_later', 'is_sales_page', 'toggle_popup', 'temp_data', 'contact_id', 'type', 'business_details', 'taxes', 'payment_types', 'walk_in_customer', 'sell_details', 'transaction', 'payment_lines', 'location_printer_type', 'shortcuts', 'commission_agent', 'categories', 'pos_settings', 'change_return', 'types', 'customer_groups', 'brands', 'accounts', 'price_groups', 'waiters', 'redeem_details', 'edit_price', 'edit_discount', 'shipping_statuses', 'warranties'));
    }
    /**
     * Update the specified resource in storage.
     * TODO: Add edit log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        if (!$this->userCan('sell.update')) {
            abort(403, 'Unauthorized action.');
        }

        $txnPerm = Transaction::where('business_id', request()->session()->get('user.business_id'))
            ->where('id', $id)
            ->select('is_credit_sale')
            ->first();
        if ($txnPerm && (int) $txnPerm->is_credit_sale === 1 && ! $this->userCan('cr.edit_lioc_statement')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');
            //status is send as quotation from edit sales screen.
            $input['notify'] = !empty($request->notify) ? 1 : 0;
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }
            $is_direct_sale = false;
            if (!empty($input['products'])) {
                $transaction_before = Transaction::find($id);
                $status_before = $transaction_before->status;
                $rp_earned_before = $transaction_before->rp_earned;
                $rp_redeemed_before = $transaction_before->rp_redeemed;
                $old_payments = TransactionPayment::where('transaction_id', $id)->get()->toArray();
                $old_final_total = $transaction_before->final_total;
                
                if ($transaction_before->is_direct_sale == 1 && $transaction_before->is_customer_order == 0) {
                    $is_direct_sale = true;
                }


                $has_reviewed = $this->transactionUtil->hasReviewed($transaction_before->transaction_date);

                if (!empty($has_reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg' => __('lang_v1.review_first'),
                    ];

                    return Redirect::back()->with(['status' => $output]);
                }


                $reviewed = $this->productUtil->get_review($transaction_before->transaction_date, $transaction_before->transaction_date);

                if (!empty($reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg' => "You can't edit a sale for an already reviewed date",
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return Redirect::action('SellController@index')
                            ->with('status', $output);
                    }
                }


                //Check Customer credit limit
                $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id);
                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = [
                        'success' => 0,
                        'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return Redirect::action('SellController@index')
                            ->with('status', $output);
                    }
                }
                //Check if there is a open register, if no then redirect to Create Register screen.
                if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return Redirect::action('CashRegisterController@create');
                }
                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);
                $subscription = Subscription::active_subscription($business_id);
                if (empty($subscription) || empty($subscription->package)) {
                    $output = [
                        'success' => 0,
                        'msg' => __('superadmin::lang.subscription_expired_notification'),
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return Redirect::action('SellController@index')
                            ->with('status', $output);
                    }
                }
                $monthly_max_sale_limit = $subscription->package->monthly_max_sale_limit;
                $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
                $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
                $current_monthly_sale = DB::table('transactions')
                    ->select(DB::raw('sum(final_total) as total'))
                    ->where('business_id', $business_id)
                    ->where('id', '<>', $id)
                    ->whereIn('type', ['sell', 'property_sell'])
                    ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                    ->groupBy('business_id')
                    ->first();
                $current_monthly_sale = is_null($current_monthly_sale) ? 0 : (float) $current_monthly_sale->total;
                $current_monthly_sale += $invoice_total['final_total'];
                if ($current_monthly_sale > $monthly_max_sale_limit) {
                    $output = [
                        'success' => 0,
                        'msg' => __('lang_v1.monthly_max_sale_limit_exceeded', ['monthly_max_sale_limit' => $monthly_max_sale_limit]),
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return Redirect::action('SellController@index')
                            ->with('status', $output);
                    }
                }
                if (!empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                $latest_day_end = DayEnd::where('business_id', $business_id)->max('day_end_date');
                if (!empty($latest_day_end)) {
                    $transaction_date_for_lock = !empty($input['transaction_date']) ? $input['transaction_date'] : $transaction_before->transaction_date;
                    $transaction_date_for_lock = Carbon::parse($transaction_date_for_lock)->format('Y-m-d');
                    if (strtotime($transaction_date_for_lock) <= strtotime($latest_day_end)) {
                        $output = [
                            'success' => 0,
                            'msg' => __('petro::lang.date_greater_than_day_end'),
                        ];
                        if (!$is_direct_sale) {
                            return $output;
                        } else {
                            return Redirect::action('SellController@index')
                                ->with('status', $output);
                        }
                    }
                }
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }
                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }
                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $customer_group_id = null;
                if (is_object($cg) && !empty($cg->id)) {
                    $customer_group_id = $cg->id;
                } elseif (is_array($cg) && !empty($cg['id'])) {
                    $customer_group_id = $cg['id'];
                }
                $input['customer_group_id'] = $customer_group_id;
                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;
                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }
                if ($is_direct_sale && $status_before == 'draft') {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }
                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                        $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                        $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                        $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                        $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                        $request->input('service_custom_field_4') : null;
                }
                if ($input['status'] == 'order') {
                    $input['is_customer_order'] = 1;
                    $input['is_direct_sale'] = 1;
                }
                $input['selling_price_group_id'] = $price_group_id;
                //Begin transaction
                DB::beginTransaction();
                $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);
                //Update Sell lines
                $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before);
                //Update update lines
                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;
                if (!$is_direct_sale && !$transaction->is_suspend && !$is_credit_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    if (isset($input['change_return'])) {
                        $change_return['amount'] = $input['change_return'];
                    }
                    $change_return['is_return'] = 1;
                    if (!empty($input['change_return_id'])) {
                        $change_return['id'] = $input['change_return_id'];
                    }
                    $input['payment'][] = $change_return;
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                    //Update cash register
                    $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                }
                if ($input['is_quotation'] == 0) {
                    if ($transaction->is_customer_order == 1) {
                        $transaction->order_status = 'invoiced';
                        $transaction->save();
                    }
                }
                if ($request->session()->get('business.enable_rp') == 1) {
                    $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, $rp_earned_before, $transaction->rp_redeemed, $rp_redeemed_before);
                }
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                
                if (abs($old_final_total - $transaction->final_total) > 0.01) {
                    $this->transactionUtil->updatePaymentAccountTransactions($transaction, $old_payments);
                } else {
                    $this->transactionUtil->reconcileSellPaymentAccountTransactions($transaction, true);
                }
                
                if ($transaction->is_credit_sale == 1 || $is_credit_sale) {
                    $ar_account_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
                    if (!empty($ar_account_id)) {
                        AccountTransaction::where('transaction_id', $transaction->id)
                            ->where('account_id', $ar_account_id)
                            ->where('type', 'debit')
                            ->whereNull('transaction_payment_id')
                            ->update(['amount' => $transaction->final_total]);
                        
                        ContactLedger::where('transaction_id', $transaction->id)
                            ->where('type', 'debit')
                            ->whereNull('transaction_payment_id')
                            ->update(['amount' => $transaction->final_total]);
                    }
                }
                
                //Update Stock account data
                $this->transactionUtil->updateManageStockAccount($transaction);
                $this->transactionUtil->updateCostofGoodsSoldTransaction($transaction);
                $this->transactionUtil->updateSaleIncomeTransaction($transaction);
                //Update product stock
                $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);
                //Allocate the quantity from purchase and add mapping of
                //purchase & sell lines in
                //transaction_sell_lines_purchase_lines table
                $business_details = $this->businessUtil->getDetails($business_id);
                $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $input['location_id'],
                    'pos_settings' => $pos_settings,
                ];
                $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $transaction->save();
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $transaction->save();
                }
                $log_properties = [];
                if (isset($input['repair_completed_on'])) {
                    $completed_on = !empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                    if ($transaction->repair_completed_on != $completed_on) {
                        $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                        $log_properties['completed_on_to'] = $completed_on;
                    }
                }
                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }
                if (!empty($input['update_note'])) {
                    $log_properties['update_note'] = $input['update_note'];
                }
                
                if (abs($old_final_total - $transaction->final_total) > 0.01) {
                    $log_properties['amount_changed'] = true;
                    $log_properties['old_total'] = $old_final_total;
                    $log_properties['new_total'] = $transaction->final_total;
                    $log_properties['payment_accounts_updated'] = true;
                }
                
                Media::uploadMedia($business_id, $transaction, $request, 'documents');
                activity()
                    ->performedOn($transaction)
                    ->withProperties($log_properties)
                    ->log('edited');
                if ($input['notify']) {
                    $transaction->order_status = 'waiting_for_confirmation';
                    $transaction->save();
                    $this->notificationUtil->autoSendNotification($business_id, 'customer_notify', $transaction, $transaction->contact);
                    $output = [
                        'success' => 1,
                        'msg' => __("lang_v1.notification_sent_to_customer"),
                    ];
                }

                $this->transactionUtil->calculateAndUpdateVAT($transaction);

                // Soft-check accounting equation: log problems but do not block POS edits
                if (!$this->transactionUtil->validateAccountingEquation($transaction->id)) {
                    Log::warning('Accounting validation failed after POS edit, but changes committed', [
                        'transaction_id' => $transaction->id,
                    ]);
                }

                DB::commit();
                if ($input['notify']) {
                    return $output;
                }
                $msg = '';
                $receipt = '';
                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_updated");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_sale_updated");
                    if (!$is_direct_sale && !$transaction->is_suspend) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                }
                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong"),
                ];
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return Redirect::action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return Redirect::action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return Redirect::action('\Modules\Repair\Http\Controllers\RepairController@index')
                        ->with('status', $output);
                }
                return Redirect::action('SellController@index')
                    ->with('status', $output);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        if (!$this->userCan('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            try {
                $tansactionMain = Transaction::find($id);
                if ($tansactionMain):
                    $transactionPayment = TransactionPayment::where('transaction_id', $id)->where('business_id', $business_id)->delete();
                    $transactionSellLine = TransactionSellLine::where('transaction_id', $id)->delete();
                    $accountTxn = AccountTransaction::where('transaction_id', $id)->where('business_id', $business_id)->delete();
                    $tansactionMain->delete();
                endif;


                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.sale_delete_success'),
                ];
            } catch (\Exception $e) {
                logger($e);
                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }
            return $output;
        }
    }
    /**
     * Returns the HTML row for a product in POS
     *
     * @param  int  $variation_id
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */

    public function getProductRow($variation_id, $location_id)
    {
        $output = [];
        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            $price_later = request()->price_later;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }
            $business_id = request()->session()->get('user.business_id');
            if (empty($business_id)) {
                //using for ecom customer to get product of business without business session
                $location = BusinessLocation::where('id', $location_id)->first();
                $business_id = $location->business_id;
            }
            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
            if (request()->is_quotation == "yes") {
                $pos_settings['allow_overselling'] = 1;
            }
            $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, $check_qty);

            if ($product) {
                $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);
            } else {
                $product = (object) [
                    'formatted_qty_available' => '0'
                ];
                Log::warning("No product found for variation ID {$variation_id}, business ID {$business_id}");
            }

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);
            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $cg_amount = 0;
            $cg_id = null;
            if (is_object($cg)) {
                $cg_amount = !empty($cg->amount) ? $cg->amount : 0;
                $cg_id = !empty($cg->id) ? $cg->id : null;
            } elseif (is_array($cg)) {
                $cg_amount = !empty($cg['amount']) ? $cg['amount'] : 0;
                $cg_id = !empty($cg['id']) ? $cg['id'] : null;
            }
            $percent = $cg_amount;
            $product->default_sell_price = $product->default_sell_price /*+ ($percent * $product->default_sell_price / 100)*/ ;
            $product->sell_price_inc_tax = $product->sell_price_inc_tax  /*+($percent * $product->sell_price_inc_tax / 100)*/ ;
            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
            $enabled_modules = $this->transactionUtil->allModulesEnabled();
            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                if (!is_iterable($lot_number_obj)) {
                    $lot_number_obj = [];
                }
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;
            $purchase_line_id = request()->get('purchase_line_id');
            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);
                if (is_array($variation_group_prices) && !empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            // Debug log: capture price fields after price group adjustment (if any)
            /*try {
                Log::info('POS getProductRow post-price-group price debug', [
                    'variation_id' => $variation_id,
                    'location_id' => $location_id,
                    'business_id' => $business_id,
                    'applied_price_group' => $price_group,
                    'product' => [
                        'default_sell_price' => $product->default_sell_price ?? null,
                        'sell_price_inc_tax' => $product->sell_price_inc_tax ?? null,
                        'default_purchase_price' => $product->default_purchase_price ?? null,
                        'dpp_inc_tax' => $product->dpp_inc_tax ?? null,
                        'pp_inc_tax' => $product->pp_inc_tax ?? null,
                    ],
                ]);
            } catch (\Throwable $e) {
            }*/
            $warranties = $this->__getwarranties();
            $output['success'] = true;
            $waiters = [];
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }
            // just for avoid none set value pass with 0 when checking temp qty in product_row view
            $temp_qty = 0;
            if (request()->get('type') == 'sell-return') {
                $output['html_content'] = view('sell_return.partials.product_row')
                    ->with(compact('temp_qty', 'product', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units'))
                    ->render();
            } else {
                $is_cg = !empty($cg_id);
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);
                if ($is_direct_sell) {
                    $edit_discount = $this->userCan('edit_product_discount_from_sale_screen');
                    $edit_price = $this->userCan('edit_product_price_from_sale_screen');
                } else {
                    $edit_discount = $this->userCan('edit_product_discount_from_pos_screen');
                    $edit_price = $this->userCan('edit_product_price_from_pos_screen');
                }
                //get defualt unit selling price
                $default_multiple_unit_price = (array) json_decode(Variation::where('id', $variation_id)->select('default_multiple_unit_price')->first()->default_multiple_unit_price);
                $user = User::where('id', Auth::user()->id)->select('toggle_popup')->first();
                if (!empty($user)) {
                    $toggle_popup = $user->toggle_popup;
                } else {
                    $toggle_popup = 1;
                }
                $discount_type = ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')];
                $is_sales_page = request()->is_sales_page;

                if (request()->session()->has('is_sales_page')) {
                    $is_sales_page = request()->session()->get('is_sales_page');
                    request()->session()->put('is_sales_pos', $is_sales_page);
                }

                $output['html_content'] = view('sale_pos.product_row')
                    ->with(compact('is_sales_page', 'discount_type', 'price_later', 'toggle_popup', 'default_multiple_unit_price', 'temp_qty', 'product', 'row_count', 'tax_dropdown', 'enabled_modules', 'pos_settings', 'sub_units', 'discount', 'waiters', 'edit_discount', 'edit_price', 'purchase_line_id', 'warranties'))
                    ->render();
            }
            $output['enable_sr_no'] = $product->enable_sr_no;
            if ($this->transactionUtil->isModuleEnabled('modifiers') && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                    ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] = view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }
        } catch (\Exception $e) {
            Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            $output['success'] = false;
            $output['msg'] = __('lang_v1.item_out_of_stock');
        }
        return $output;
    }

    public function getProductRowTemp($variation_id, $location_id, $temp_qty)
    {
        Log::info("getProductRowTemp called with variation_id: {$variation_id}, location_id: {$location_id}, temp_qty: {$temp_qty}");
        $output = [];
        try {
            $row_count = request()->get('product_row');
            // $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }
            $business_id = request()->session()->get('user.business_id');
            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
            if (isset($_GET['type']) && $_GET['type'] == "quotation") {
                $pos_settings['allow_overselling'] = 1;
            }
            $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, $check_qty);
            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);
            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);
            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $cg_amount = 0;
            $cg_id = null;
            if (is_object($cg)) {
                $cg_amount = !empty($cg->amount) ? $cg->amount : 0;
                $cg_id = !empty($cg->id) ? $cg->id : null;
            } elseif (is_array($cg)) {
                $cg_amount = !empty($cg['amount']) ? $cg['amount'] : 0;
                $cg_id = !empty($cg['id']) ? $cg['id'] : null;
            }
            $percent = $cg_amount;
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);
            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
            $enabled_modules = $this->transactionUtil->allModulesEnabled();
            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                if (!is_iterable($lot_number_obj)) {
                    $lot_number_obj = [];
                }
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $purchase_line_id = request()->get('purchase_line_id');
            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);
                if (is_array($variation_group_prices) && !empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }
            $warranties = $this->__getwarranties();
            $output['success'] = true;
            $waiters = [];
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }
            $user = User::where('id', Auth::user()->id)->select('toggle_popup')->first();
            if (!empty($user)) {
                $toggle_popup = $user->toggle_popup;
            } else {
                $toggle_popup = 1;
            }
            $default_multiple_unit_price = (array) json_decode(Variation::where('id', $variation_id)->select('default_multiple_unit_price')->first()->default_multiple_unit_price);
            if (request()->get('type') == 'sell-return') {
                $output['html_content'] = view('sell_return.partials.product_row')
                    ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units', 'toggle_popup', 'default_multiple_unit_price'))
                    ->render();
            } else {
                $is_cg = !empty($cg_id);
                $is_pg = !empty($price_group) ? true : false;
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);
                if ($is_direct_sell) {
                    $edit_discount = $this->userCan('edit_product_discount_from_sale_screen');
                    $edit_price = $this->userCan('edit_product_price_from_sale_screen');
                } else {
                    $edit_discount = $this->userCan('edit_product_discount_from_pos_screen');
                    $edit_price = $this->userCan('edit_product_price_from_pos_screen');
                }
                $output['html_content'] = view('sale_pos.product_row')
                    ->with(compact('temp_qty', 'product', 'row_count', 'tax_dropdown', 'enabled_modules', 'pos_settings', 'sub_units', 'discount', 'waiters', 'edit_discount', 'edit_price', 'purchase_line_id', 'warranties', 'toggle_popup', 'default_multiple_unit_price'))
                    ->render();
            }
            $output['enable_sr_no'] = $product->enable_sr_no;
            if ($this->transactionUtil->isModuleEnabled('modifiers') && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                    ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] = view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }
        } catch (\Exception $e) {
            Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            $output['success'] = false;
            $output['msg'] = __('lang_v1.item_out_of_stock');
        }
        return $output;
    }
    /**
     * Returns the HTML row for a payment in POS
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function getPaymentRow(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $row_index = $request->input('row_index');
            
            // Validate inputs
            if (!$business_id) {
                Log::error('Payment row generation failed: No business ID in session');
                return response()->json(['error' => 'Session expired. Please refresh the page.'], 401);
            }
            
            if ($row_index === null || $row_index === '' || !is_numeric($row_index) || $row_index < 0) {
                Log::error('Payment row generation failed: Invalid row index', ['row_index' => $row_index]);
                return response()->json(['error' => 'Invalid row index provided'], 400);
            }
            
            // Convert to integer to ensure proper type
            $row_index = (int) $row_index;
            
            // Validate payment configuration
            $validation_result = $this->validatePaymentConfiguration($business_id);
            if (!$validation_result['valid']) {
                return response()->json(['error' => $validation_result['message']], 500);
            }
            
            $removable = true;
            $payment_types = $this->productUtil->payment_types('null', false, false, false, true, "is_sale_enabled");
            unset($payment_types['location_id']);
            
            if (empty($payment_types)) {
                Log::error('No payment types available for business', ['business_id' => $business_id]);
                return response()->json(['error' => 'No payment methods configured'], 500);
            }
            
            $payment_line = $this->dummyPaymentLine;
            
            // Get accounts with error handling
            $accounts = [];
            if ($this->moduleUtil->isModuleEnabled('account')) {
                try {
                    $accounts = Account::forDropdown($business_id, true, false);
                } catch (\Exception $e) {
                    Log::error('Failed to load accounts for payment row', [
                        'business_id' => $business_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without accounts rather than failing completely
                }
            }
            
            // Get bank accounts with error handling
            $bank_group_accounts = [];
            try {
                $bank_group_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
                    ->where('accounts.business_id', $business_id)
                    ->where('account_groups.name', 'Bank Account')
                    ->pluck('accounts.name', 'accounts.id');
            } catch (\Exception $e) {
                Log::error('Failed to load bank accounts for payment row', [
                    'business_id' => $business_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            return view('sale_pos.partials.payment_row')
                ->with(compact('payment_types', 'row_index', 'removable', 'payment_line', 'accounts', 'bank_group_accounts'));
                
        } catch (\Exception $e) {
            Log::error('Payment row generation failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'business_id' => request()->session()->get('user.business_id'),
                'row_index' => $request->input('row_index')
            ]);
            
            return response()->json([
                'error' => 'Failed to generate payment row. Please try again or contact administrator.'
            ], 500);
        }
    }

    /**
     * Validate payment configuration for the business
     *
     * @param int $business_id
     * @return array
     */
    private function validatePaymentConfiguration($business_id)
    {
        try {
            // Check if cash account exists
            if (!Account::validateCashAccountExists($business_id)) {
                Log::warning('Cash account validation failed for business', ['business_id' => $business_id]);
                
                // Try to ensure cash account exists
                try {
                    Account::ensureCashAccountExists($business_id);
                } catch (\Exception $e) {
                    return [
                        'valid' => false,
                        'message' => 'Cash account is required but not configured. Please contact administrator.'
                    ];
                }
            }
            
            // Validate that payment types are available
            $payment_types = $this->productUtil->payment_types('null', false, false, false, true, "is_sale_enabled");
            unset($payment_types['credit_expense']);
            if (empty($payment_types)) {
                return [
                    'valid' => false,
                    'message' => 'No payment methods are configured for this location.'
                ];
            }
            
            // Check if cash payment method is available
            if (!isset($payment_types['cash'])) {
                Log::warning('Cash payment method not available', ['business_id' => $business_id]);
            }
            
            return ['valid' => true];
            
        } catch (\Exception $e) {
            Log::error('Payment configuration validation failed', [
                'business_id' => $business_id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'message' => 'Payment configuration validation failed. Please try again.'
            ];
        }
    }

    public function getPaymentMethods(Request $request)
    {
        $location_id = $request->input('location_id');
        $payment_types = $this->productUtil->payment_types('null', false, false, false, true, "is_sale_enabled");
        unset($payment_types['credit_expense']);
        unset($payment_types['location_id']);
        $accounts_by_payment_type = [];
        foreach ($payment_types as $key => $label) {
            if ($key == 'credit_sale') {
                $acc_id = $this->moduleUtil->account_exist_return_id('Accounts Receivable');
                $accounts = Account::where('id', $acc_id)->select('name', 'id')->pluck('name', 'id');
            } elseif ($key == 'credit_purchase' || $key == 'credit_expense') {
                $acc_id = $this->moduleUtil->account_exist_return_id('Accounts Payable');
                $accounts = Account::where('id', $acc_id)->select('name', 'id')->pluck('name', 'id');
            } else {
                $group_id = $this->moduleUtil->one_payment_type($key, $location_id);
                if (!empty($group_id)) {
                    $accounts = Account::getAccountByAccountGroupId($group_id);
                } else {
                    $accounts = [];
                }
            }
            $accounts_by_payment_type[$key] = $accounts;
        }

        return response()->json([
            'payment_types' => $payment_types,
            'accounts_by_payment_type' => $accounts_by_payment_type
        ]);
    }

    /**
     * Returns recent transactions
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function getRecentTransactions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = $request->get('status');
        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);
        $query = Transaction::where('business_id', $business_id)
            ->where('transactions.created_by', $user_id)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0)
            ->where(function ($query) {
                $query->where('transactions.is_settlement', '!=', 1)
                    ->orWhereNull('transactions.is_settlement');
            });
        if ($transaction_status == 'final') {
            if (!empty($register->id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                    ->where('crt.cash_register_id', $register->id);
            }
        }
        if ($transaction_status == 'quotation') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 1);
        } elseif ($transaction_status == 'draft') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 0);
        } else {
            $query->where('transactions.status', $transaction_status);
        }
        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact'])
            ->limit(10)
            ->get();
        return view('sale_pos.partials.recent_transactions')
            ->with(compact('transactions'));
    }

    /**
     * Get Recent Transection Popup
     *
     * Undocumented function long description
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
     **/
    public function getRecentTransactionPopup(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = 'final';
        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);
        $query = Transaction::where('business_id', $business_id)
            ->where('transactions.created_by', $user_id)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0)
            ->where(function ($query) {
                $query->where('transactions.is_settlement', '!=', 1)
                    ->orWhereNull('transactions.is_settlement');
            });
        if ($transaction_status == 'final') {
            if (!empty($register->id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                    ->where('crt.cash_register_id', $register->id);
            }
        }
        $query->where('transactions.status', $transaction_status);

        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact'])
            ->limit(10)
            ->get();
        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        return view('sale_pos.partials.recent_transactions_popup')
            ->with(compact('transactions', 'pos_settings'));
    }
    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong"),
                ];
                $business_id = $request->session()->get('user.business_id');
                $transaction = Transaction::where('business_id', $business_id)
                    ->where('id', $transaction_id)
                    ->with(['location'])
                    ->first();
                if (empty($transaction)) {
                    return $output;
                }

                // $printer_type = 'browser';
                // if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                $printer_type = $transaction->location->receipt_printer_type;
                // }

                $is_package_slip = !empty($request->input('package_slip')) ? true : false;
                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false);

                if ($printer_type != 'browser') {
                    $receipt_tmp = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, 'browser', $is_package_slip, false);
                    $receipt['html_content'] = $receipt_tmp['html_content'];
                }

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong"),
                    'error' => $e->getMessage()
                ];
            }
            return $output;
        }
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function printOustandingInvoice(Request $request, $ref_number)
    {
        if (request()->ajax()) {
            try {
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong"),
                ];
                $business_id = $request->session()->get('user.business_id');
                $transaction = Transaction::select('transactions.*')
                    ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                    ->where('transactions.business_id', $business_id)
                    ->where('tp.payment_ref_no', $ref_number)
                    ->where('tp.paid_on', request()->paid_on)
                    ->with('location')
                    ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $printer_type = 'browser';
                if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                    $printer_type = $transaction->location->receipt_printer_type;
                }
                $is_package_slip = !empty($request->input('package_slip')) ? true : false;
                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction->id, $printer_type, $is_package_slip, false);
                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
                dd("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.something_went_wrong"),
                ];
            }
            return $output;
        }
    }

    /**
     * Gives suggetion for product based on category
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function getProductSuggestion(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }
        
        $business_id = $request->session()->get('business.id');
        $enable_petro_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module');
        $fuel_cat = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();
        $fuel_cat_id = !empty($fuel_cat) ? $fuel_cat->id : 0;
        
        $category_id = $request->get('category_id');
        $brand_id = $request->get('brand_id');
        $location_id = $request->get('location_id');
        $term = $request->get('term');
        $check_qty = false;
        $module = $request->module ?? null;
        $search_fields = $request->get('search_fields', ['name', 'sku']);
        if (in_array('sku', $search_fields)) {
            $search_fields[] = 'sub_sku';
        }

        if (!Auth::user()->is_company_customer) {
            $business_id_obj = BusinessLocation::where('id', $location_id)->first();
            if (!empty($business_id_obj)) {
                $business_id = $business_id_obj->business_id;
            } else {
                $business_id = request()->session()->get('user.business_id');
            }
        } else {
            $business_id = $request->session()->get('user.business_id');
        }

        $store_id = app(\App\Services\StoreStockIntegrityService::class)
            ->resolveStoreIdForLocation((int) $location_id, null, (int) $business_id);
        
        // If term is provided, return JSON for autocomplete
        if (!empty($term)) {
            $price_group_id = $request->get('price_group', null);
            $not_for_selling = $request->get('not_for_selling', 0);
            $product_types = [];
            
            $result = $this->productUtil->filterProductPos(
                $business_id, 
                $term, 
                $location_id, 
                $not_for_selling, 
                $price_group_id, 
                $product_types, 
                $search_fields, 
                $check_qty, 
                'like', 
                $store_id, 
                $brand_id, 
                $module
            );
            
            // Format for autocomplete - ensure qty_available is numeric
            $formatted_result = $result->map(function($item) {
                return [
                    'variation_id' => $item->variation_id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'variation' => $item->variation ?? '',
                    'sub_sku' => $item->sub_sku ?? '',
                    'qty_available' => (float)($item->qty_available ?? 0),
                    'enable_stock' => $item->enable_stock,
                    'selling_price' => $item->selling_price ?? 0,
                    'unit' => $item->unit ?? '',
                    'text' => $item->name . (!empty($item->variation) ? ' - ' . $item->variation : '') . (!empty($item->sub_sku) ? ' (' . $item->sub_sku . ')' : ''),
                    'label' => $item->name . (!empty($item->variation) ? ' - ' . $item->variation : '') . (!empty($item->sub_sku) ? ' (' . $item->sub_sku . ')' : ''),
                ];
            })->toArray();
            
            return response()->json($formatted_result);
        }
        
        // Otherwise return HTML for product grid
        $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');
                        if (!empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                $query->orWhereNull('VLD.location_id');
                            });
                        }
                    }
                )
                ->leftjoin(
                    'variation_store_details AS VSD',
                    function ($join) use ($store_id) {
                        $join->on('variations.id', '=', 'VSD.variation_id');
                        if (!empty($store_id)) {
                            $join->where(function ($query) use ($store_id) {
                                $query->where('VSD.store_id', '=', $store_id);
                                $query->orWhereNull('VSD.store_id');
                            });
                        }
                    }
                )
                ->where('p.business_id', $business_id)
                ->where('p.type', '!=', 'modifier')
                ->where('p.is_inactive', 0)
                ->where('p.not_for_selling', 0)
                ->where(function ($q) use ($location_id) {
                    $q->where('pl.location_id', $location_id);
                });

        if (!empty($module)) {
            $products->where(function ($q) use ($module) {
                $q->whereNull('p.disabled_in')->orwhereRaw("NOT FIND_IN_SET(?, p.disabled_in)", [$module]);
            });
        }

        if (empty($enable_petro_module)) {
            $products->where('category_id', '!=', $fuel_cat_id);
        }
        
        if ($check_qty) {
            $products->where(DB::raw('IFNULL(VSD.qty_available, 0)'), '>', 0);
        }
        
        if ($category_id != 'all') {
            $products->where(function ($query) use ($category_id) {
                $query->where('p.category_id', $category_id);
                $query->orWhere('p.sub_category_id', $category_id);
            });
        }
        
        if ($brand_id != 'all') {
            $products->where('p.brand_id', $brand_id);
        }
        
        $products = $products->select(
                'p.id as product_id',
                'p.name',
                'p.type',
                'p.enable_stock',
                'p.image as product_image',
                'variations.id',
                'variations.name as variation',
                'VSD.qty_available',
                'variations.default_sell_price as selling_price',
                'variations.sub_sku'
            )
                ->with(['media'])
                ->orderBy('p.id', 'desc')
                ->paginate(20);

        return view('sale_pos.partials.product_list')
            ->with(compact('products'));
    }
    /**
     * Shows invoice url.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function showInvoiceUrl($id)
    {
        if (!$this->userCan('sell.update')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->findorfail($id);
            $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);
            return view('sale_pos.partials.invoice_url_modal')
                ->with(compact('transaction', 'url'));
        }
    }
    /**
     * Shows invoice to guest user.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */

    public function showInvoice($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->with(['business'])->first();
        if (!empty($transaction)) {
            $is_distribution = \Modules\Distribution\Entities\DistributionInvoice::where('business_id', $transaction->business_id)
                ->where('invoice_no', $transaction->invoice_no)
                ->exists();

            if ($is_distribution) {
                return redirect()->route('show_distribution_invoice', ['token' => $token]);
            }
            $receipt = $this->receiptContent($transaction->business_id, $transaction->location_id, $transaction->id, 'browser');
            $title = $transaction->business->name . ' | ' . $transaction->invoice_no;
            return view('sale_pos.partials.show_invoice')
                ->with(compact('receipt', 'title'));
        } else {
            die(__("messages.something_went_wrong"));
        }
    }

    /**
     * Shows distribution invoice guest view.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function showDistributionInvoice($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->first();
        if (empty($transaction)) {
            abort(404);
        }

        $business_id = $transaction->business_id;
        $business = Business::find($business_id);
        
        $invoice = \Modules\Distribution\Entities\DistributionInvoice::where('business_id', $business_id)
            ->where('invoice_no', $transaction->invoice_no)
            ->with(['customer', 'lines.product', 'lines.product.unit', 'salesRep', 'route', 'vehicle', 'category', 'cheques'])
            ->first();

        if (empty($invoice)) {
            abort(404);
        }

        $location = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->first();

        if (!$location) {
            $location = BusinessLocation::where('business_id', $business_id)->first();
        }

        $title = $business->name . ' | ' . $invoice->invoice_no;

        return view('distribution::invoices.show_guest_invoice', compact('invoice', 'business', 'location', 'title'));
    }

    /**
     * Display a listing of the recurring invoices.
     *
     * @return \Illuminate\Http\Response
     */

    public function listSubscriptions()
    {
        if (!$this->userCan('sell.view') && !$this->userCan('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_recurring', 1)
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.subscription_no',
                    'bl.name as business_location',
                    'transactions.recur_parent_id',
                    'transactions.recur_stopped_on',
                    'transactions.is_recurring',
                    'transactions.recur_interval',
                    'transactions.recur_interval_type',
                    'transactions.recur_repetitions'
                )->with(['subscription_invoices']);
            /** @var \App\User|null $user */
            $user = auth()->user();
            $permitted_locations = ($user instanceof User) ? $user->permitted_locations() : 'all';
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        if ($row->is_recurring == 1 && $this->userCan("sell.update")) {
                            $link_text = !empty($row->recur_stopped_on) ? __('lang_v1.start_subscription') : __('lang_v1.stop_subscription');
                            $link_class = !empty($row->recur_stopped_on) ? 'btn-success' : 'btn-danger';
                            $html .= '<a href="' . action('SellPosController@toggleRecurringInvoices', [$row->id]) . '" class="toggle_recurring_invoice btn btn-xs ' . $link_class . '"><i class="fa fa-power-off"></i> ' . $link_text . '</a>';
                            if ($row->is_direct_sale == 0) {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            } else {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            }
                        }
                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('recur_interval', function ($row) {
                    $type = $row->recur_interval == 1 ? Str::singular(__('lang_v1.' . $row->recur_interval_type)) : __('lang_v1.' . $row->recur_interval_type);
                    return $row->recur_interval . $type;
                })
                ->addColumn('subscription_invoices', function ($row) {
                    $invoices = [];
                    if (!empty($row->subscription_invoices)) {
                        $invoices = $row->subscription_invoices->pluck('invoice_no')->toArray();
                    }
                    $html = '';
                    $count = 0;
                    if (!empty($invoices)) {
                        $imploded_invoices = '<span class="label bg-info">' . implode('</span>, <span class="label bg-info">', $invoices) . '</span>';
                        $count = count($invoices);
                        $html .= '<small>' . $imploded_invoices . '</small>';
                    }
                    if ($count > 0) {
                        $html .= '<br><small class="text-muted">' .
                            __('sale.total') . ': ' . $count . '</small>';
                    }
                    return $html;
                })
                ->addColumn('last_generated', function ($row) {
                    if (!empty($row->subscription_invoices)) {
                        $last_generated_date = $row->subscription_invoices->max('created_at');
                    }
                    return !empty($last_generated_date) ? $last_generated_date->diffForHumans() : '';
                })
                ->addColumn('upcoming_invoice', function ($row) {
                    if (empty($row->recur_stopped_on)) {
                        $last_generated = !empty($row->subscription_invoices) ? Carbon::parse($row->subscription_invoices->max('transaction_date')) : Carbon::parse($row->transaction_date);
                        if ($row->recur_interval_type == 'days') {
                            $upcoming_invoice = $last_generated->addDays($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'months') {
                            $upcoming_invoice = $last_generated->addMonths($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'years') {
                            $upcoming_invoice = $last_generated->addYears($row->recur_interval);
                        }
                    }
                    return !empty($upcoming_invoice) ? $this->transactionUtil->format_date($upcoming_invoice) : '';
                })
                ->rawColumns(['action', 'subscription_invoices'])
                ->make(true);
            return $datatable;
        }
        return view('sale_pos.subscriptions');
    }
    /**
     * Starts or stops a recurring invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function toggleRecurringInvoices($id)
    {
        if (!$this->userCan('sell.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('is_recurring', 1)
                ->findorfail($id);
            if (empty($transaction->recur_stopped_on)) {
                $transaction->recur_stopped_on = Carbon::now();
            } else {
                $transaction->recur_stopped_on = null;
            }
            $transaction->save();
            $output = [
                'success' => 1,
                'msg' => trans("lang_v1.updated_success"),
            ];
        } catch (\Exception $e) {
            Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => trans("messages.something_went_wrong"),
            ];
        }
        return $output;
    }

    public function getRewardDetails(Request $request)
    {
        if ($request->session()->get('business.enable_rp') != 1) {
            return '';
        }
        $business_id = request()->session()->get('user.business_id');
        $customer_id = $request->input('customer_id');
        $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $customer_id);
        return json_encode($redeem_details);
    }

    public function placeOrdersApi(Request $request)
    {
        try {
            $api_token = $request->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);
            $business_id = $api_settings->business_id;
            $location_id = $api_settings->location_id;
            $input = $request->only(['products', 'customer_id', 'addresses']);
            //check if all stocks are available
            $variation_ids = [];
            foreach ($input['products'] as $product_data) {
                $variation_ids[] = $product_data['variation_id'];
            }
            $variations_details = $this->getVariationsDetails($business_id, $location_id, $variation_ids);

            // Get business settings to check allow_overselling
            $business = Business::find($business_id);
            $pos_settings = !empty($business->pos_settings) ? json_decode($business->pos_settings, true) : [];
            $allow_overselling = (!empty($pos_settings['allow_overselling']) || (request()->has('offline_mode') && (int)request()->input('offline_mode') === 1)) ? true : false;

            $is_valid = true;
            $error_messages = [];
            $sell_lines = [];
            $final_total = 0;
            foreach ($variations_details as $variation_details) {
                if ($variation_details->product->enable_stock == 1 && !$allow_overselling) {
                    if (empty($variation_details->variation_location_details[0]) || $variation_details->variation_location_details[0]->qty_available < $input['products'][$variation_details->id]['quantity']) {
                        $is_valid = false;
                        $error_messages[] = 'Only ' . $variation_details->variation_location_details[0]->qty_available . ' ' . $variation_details->product->unit->short_name . ' of ' . $input['products'][$variation_details->id]['product_name'] . ' available';
                    }
                }
                //Create product line array
                $sell_lines[] = [
                    'product_id' => $variation_details->product->id,
                    'unit_price_before_discount' => $variation_details->unit_price_inc_tax,
                    'unit_price' => $variation_details->unit_price_inc_tax,
                    'unit_price_inc_tax' => $variation_details->unit_price_inc_tax,
                    'variation_id' => $variation_details->id,
                    'quantity' => $input['products'][$variation_details->id]['quantity'],
                    'item_tax' => 0,
                    'enable_stock' => $variation_details->product->enable_stock,
                    'tax_id' => null,
                ];
                $final_total += ($input['products'][$variation_details->id]['quantity'] * $variation_details->unit_price_inc_tax);
            }
            if (!$is_valid) {
                return $this->respond([
                    'success' => false,
                    'error_messages' => $error_messages,
                ]);
            }
            // $business already fetched above for allow_overselling check
            $user_id = $business->owner_id;
            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $location_id,
            ];
            $customer = Contact::where('business_id', $business_id)
                ->whereIn('type', ['customer', 'both'])
                ->find($input['customer_id']);
            $order_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $input['customer_id'],
                'final_total' => $final_total,
                'created_by' => $user_id,
                'status' => 'final',
                'payment_status' => 'due',
                'additional_notes' => '',
                'transaction_date' => Carbon::now(),
                'customer_group_id' => $customer->customer_group_id,
                'tax_rate_id' => null,
                'sale_note' => null,
                'commission_agent' => null,
                'order_addresses' => json_encode($input['addresses']),
                'products' => $sell_lines,
                'is_created_from_api' => 1,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
            ];
            $invoice_total = [
                'total_before_tax' => $final_total,
                'tax' => 0,
            ];
            DB::beginTransaction();
            $transaction = $this->transactionUtil->createSellTransaction($business_id, $order_data, $invoice_total, $user_id, false);
            //Create sell lines
            $this->transactionUtil->createOrUpdateSellLines($transaction, $order_data['products'], $order_data['location_id'], false, null, [], false);
            //update product stock
            $store_id = Store::where('business_id', $business_id)->first()->id;

            foreach ($order_data['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $order_data['location_id'],
                        $product['quantity'],
                        0,
                        'decrease'
                    );


                    $this->productUtil->decreaseProductQuantityStore(
                        $product['product_id'],
                        $product['variation_id'],
                        $order_data['location_id'],
                        $product['quantity'],
                        $store_id,
                        "decrease",
                        0
                    );
                }
            }
            $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            //Auto send notification
            $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
            DB::commit();
            $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction->id);
            $output = [
                'success' => 1,
                'transaction' => $transaction,
                'receipt' => $receipt,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("POS_PAYMENT_ERROR: File:" . $e->getFile() . " Line:" . $e->getLine() . " Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            $output = [
                'success' => 0,
                'error_messages' => [$msg],
            ];
        }
        return $this->respond($output);
    }

    private function getVariationsDetails($business_id, $location_id, $variation_ids)
    {
        $variation_details = Variation::whereIn('id', $variation_ids)
            ->with([
                'product' => function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                },
                'product.unit',
                'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                },
            ])->get();
        return $variation_details;
    }

    public function getTypesOfServiceDetails(Request $request)
    {
        $location_id = $request->input('location_id');
        $types_of_service_id = $request->input('types_of_service_id');
        $business_id = $request->session()->get('user.business_id');
        $types_of_service = TypesOfService::where('business_id', $business_id)
            ->where('id', $types_of_service_id)
            ->first();
        $price_group_id = !empty($types_of_service->location_price_group[$location_id])
            ? $types_of_service->location_price_group[$location_id] : '';
        $price_group_name = '';
        if (!empty($price_group_id)) {
            $price_group = SellingPriceGroup::find($price_group_id);
            $price_group_name = $price_group->name;
        }
        $modal_html = view('types_of_service.pos_form_modal')
            ->with(compact('types_of_service'))->render();
        return $this->respond([
            'price_group_id' => $price_group_id,
            'packing_charge' => $types_of_service->packing_charge,
            'packing_charge_type' => $types_of_service->packing_charge_type,
            'modal_html' => $modal_html,
            'price_group_name' => $price_group_name,
        ]);
    }

    private function __getwarranties()
    {
        $business_id = session()->get('user.business_id');
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];
        return $warranties;
    }

    public function toggle_popup()
    {
        $toggle_popup = User::where('id', Auth::user()->id)->select('toggle_popup')->first()->toggle_popup;
        User::where('id', Auth::user()->id)->update(['toggle_popup' => !$toggle_popup]);
        return json_encode(['result' => $toggle_popup]);
    }

    public function getCustomerDetails(Request $request, $contact_id = null)
    {
        if (!empty($contact_id)) {
            $customer_id = $contact_id;
        } else {
            $customer_id = $request->customer_id;
        }
        $business_id = request()->session()->get('business.id');
        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->leftjoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
            ->where('contacts.business_id', $business_id)
            ->where('contacts.id', $customer_id)
            ->onlyCustomers()
            ->select([
                'contacts.contact_id',
                'contacts.name',
                'contacts.created_at',
                'total_rp',
                'cg.name as customer_group',
                'sol_with_approval',
                'state',
                'country',
                'landmark',
                'mobile',
                'contacts.id',
                'is_default',
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                'email',
                'tax_number',
                'contacts.pay_term_number',
                'contacts.pay_term_type',
                'contacts.credit_limit',
                'contacts.custom_field1',
                'contacts.custom_field2',
                'contacts.custom_field3',
                'contacts.custom_field4',
                'contacts.type',
            ])
            ->groupBy('contacts.id')->first();
        $due = (float)($query->total_invoice ?? 0) - (float)($query->invoice_received ?? 0) + (float)($query->advance_payment ?? 0);
        $return_due = (float)($query->total_sell_return ?? 0) - (float)($query->sell_return_paid ?? 0);
        $opening_balance = (float)($query->opening_balance ?? 0);
        $total_outstanding = $due - $return_due + $opening_balance;
        
        // Ensure total_outstanding is never negative for display purposes
        $total_outstanding = max(0, $total_outstanding);
        
        $total_outstanding = $this->transactionUtil->num_f($total_outstanding, false);
        return ['due_amount' => $total_outstanding, 'customer_name' => $query->name, 'sol_with_approval' => $query->sol_with_approval];
    }

    public function getCustomerInfos()
    {
        $business_id = request()->session()->get('user.business_id');
        $all_contacts = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1)
            ->select(DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' (', contact_id, ')')) AS customer"),'id')
            ->get()
            ->keyBy('id');

        foreach ($all_contacts as $contact) {
            $contact->due = $this->contactUtil->getCustomerBalance($contact->id, $business_id, true);
        }

        return response()->json([
            'all_contacts' => $all_contacts
        ]);
    }
}

