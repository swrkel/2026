<?php

namespace Modules\MPCS\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;
use App\Contact;
use App\Transaction;
class Form9CCashController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
   
    
    
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        } 
        
        $business_id = request()->session()->get('business.id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        
        // Get date range from request or set defaults
        $dateRange = $request->input('form_16a_date_range');
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            $start_date = $dates[0];
            $end_date = $dates[1];
        } else {
            // Set default date range if the request parameter is empty
            $start_date = Carbon::now()->subDays(7)->format('Y-m-d');
            $end_date = Carbon::now()->format('Y-m-d');
        }

        if (!empty($settings)) {
            $F16a_from_no = $settings->F16A_form_sn;
        } else {
            $F16a_from_no = 1;
        }

        $suppliers = Contact::suppliersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
         
        $setting = MpcsFormSetting::where('business_id', $business_id)->first();
        
        // Debug: Check what data exists
        dd([
            'business_id' => $business_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_transactions' => Transaction::where('business_id', $business_id)->count(),
            'sell_transactions' => Transaction::where('business_id', $business_id)->where('type', 'sell')->count(),
            'credit_sales' => Transaction::where('business_id', $business_id)->where('is_credit_sale', 1)->count(),
            'settlement_credit_payments' => DB::table('settlement_credit_sale_payments')->count(),
            'transaction_types' => Transaction::where('business_id', $business_id)->select('type')->distinct()->pluck('type')->toArray(),
            'sample_transactions' => Transaction::where('business_id', $business_id)->limit(5)->get(['id', 'type', 'transaction_date', 'final_total', 'is_credit_sale', 'credit_sale_id'])->toArray()
        ]);

        // Get F9C Credit data (similar to F14 but for cash transactions)
        $f9c_credit_data = Transaction::leftjoin('settlement_credit_sale_payments', 'transactions.credit_sale_id', '=', 'settlement_credit_sale_payments.id')
            ->leftjoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
            ->leftjoin('variations', 'products.id', '=', 'variations.product_id')
            ->leftjoin('contacts', 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
            ->leftjoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.is_credit_sale', 1)  // Credit sales like F14
            ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
            ->select(
                'transactions.id',
                'transactions.transaction_date as settlement_date',
                'transactions.final_total',
                'products.name as description',
                'settlement_credit_sale_payments.qty as balance_qty',
                'settlement_credit_sale_payments.order_date as order_date',
                'settlement_credit_sale_payments.price as unit_price',
                'variations.sell_price_inc_tax as sell_price_inc_tax',
                'transactions.ref_no as our_ref',
                'transactions.invoice_no',
                'contacts.name as customer',
                'settlement_credit_sale_payments.order_number as order_no',
                'transactions.location_id',
                'business_locations.name as location',
                'business_locations.mobile as tel'
            )
            ->orderBy('transactions.transaction_date', 'desc')
            ->get();

        // Format the data similar to F14
        $quantity_precision = 2;
        $currency_precision = 2;
        
        $f9c_credit_data->transform(function ($item) use ($quantity_precision, $currency_precision) {
            $is_fuel_product = $item->description == 'Fuel';
            $item->balance_qty = $this->productUtil->num_f($item->balance_qty, $is_fuel_product ? 3 : $quantity_precision, false, true);
            $item->final_total = $this->productUtil->num_f($item->final_total, $currency_precision, false, false);
            return $item;
        });

        return view('mpcs::forms.f9c_cash')->with(compact(
            'business_locations',
            'F16a_from_no',
            'sub_categories',
            'setting',
            'suppliers',
            'f9c_credit_data',
            'start_date',
            'end_date'
        ));
    }

 public function store(Request $request){
     
      $request->validate([
        'form_starting_number' => 'required|string',
        'previous_note_amount' => 'required|numeric',
    ]);

    Form9CSetting::create([
        'form_starting_number' => $request->input('starting_number'),
        'previous_note_amount' => $request->input('previous_note_amount'),
        'user_id' => auth()->id(),
        'created_at' => now(),
    ]);

    return redirect()->back()->with('success', 'Settings saved successfully!');
 }
  

 
}
