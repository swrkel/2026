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

class Form9CSettingController extends Controller
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
       
    $business_id = request()->session()->get('business.id');
    $settings = MpcsFormSetting::where('business_id', $business_id)->first();
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
    $invoiceno = FormF16Detail::pluck('invoice_no')->toArray();
    $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
     
    $setting = MpcsFormSetting::where('business_id', $business_id)->first();
    $max_form_no = FormF16Detail::max('form_no');
    $all_form_no = FormF16Detail::pluck('form_no')->toArray();
    $max_form_nos = $max_form_no + 1;
         
    if ($max_form_nos >= $F16a_from_no) {
        $F16a_from_no = $max_form_nos;
    }

    $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
        ->join(
            'business_locations AS BS',
            'transactions.location_id',
            '=',
            'BS.id'
        )
        ->leftJoin(
            'transaction_payments AS TP',
            'transactions.id',
            '=',
            'TP.transaction_id'
        )
        ->leftJoin(
            'transactions AS PR',
            'transactions.id',
            '=',
            'PR.return_parent_id'
        )
        ->leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
        ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
        ->leftjoin('variations', 'products.id', 'variations.product_id')
        ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
        ->where('transactions.business_id', $business_id)
        ->where('transactions.status', 'received')
        ->select(
            'transactions.id',
            'transactions.invoice_no as invoice',
            'transactions.ref_no as reference_no',
            'purchase_lines.quantity as received_qty',
            'purchase_lines.purchase_price as unit_purchase_price',
            'transactions.final_total as total_purchase_price',
            'BS.name as location',
            'contacts.name as name',
            'transactions.updated_at as date',
            'products.name as product',
            'products.id as product_id',
            'variations.default_sell_price',
            'transactions.pay_term_number',
            'transactions.pay_term_type',
            'PR.id as return_transaction_id',
            DB::raw('SUM(TP.amount) as amount_paid'),
            DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                    TP2.transaction_id=PR.id ) as return_paid'),
            DB::raw('COUNT(PR.id) as return_exists'),
            DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
            DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
        )
        ->groupBy('transactions.id')
            ->get()
            ->toArray();
           $lastRecord = end($purchases);
    
 $name = $lastRecord['name'];
  $invoice = $lastRecord['invoice']; 
 
  //$form_f16a = FormF16Detail::where('transaction_id', $lastRecord['id'])->first();
    return view('mpcs::forms.f9c_cash')->with(compact(
        'business_locations',
        'F16a_from_no',
        'sub_categories',
        'setting',
        'suppliers',
        'invoiceno',
        'all_form_no',
        'lastRecord',
        'name',
        'invoice'
    ));
}

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
  
    public function saveF16Form(Request $request)
    {
        

    $business_id = request()->session()->get('user.business_id');

try {
    DB::beginTransaction();

        $data_details = [
             'transaction_id' => $request->transaction_id,
            'form_no' => $request->form_number,
            'invoice_no' => $request->form_invoice,
            'supplier' => $request->formSupplier,
             'this_form_total' => ($request->thisformtotal),
            'last_form_total' => ($request->prevformtotal),
            'grand_total' => ($request->grandformtotal),
            'book_no' => $request->stockNo,
            'book_stock' => $request->stockBook,
            'this_book' => $request->thisbook,
            'prev_book' => $request->prevbook,
            'grand_book' => $request->grandbook
           
        ];

        $existingRecord = FormF16Detail::where('transaction_id', $request->transaction_id)
            ->first();
  
        if ($existingRecord) {
           
            // Update the existing record
            $existingRecord->update($data_details);
        } else {
            // Create a new record
            $data_details['business_id'] = $business_id;
            $details = FormF16Detail::create($data_details);
        }
    
        DB::commit();
    
        $output = [
            'success' => 1,
            'msg' => __('customer.')
        ];
    } catch (\Exception $e) {
        \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
        $output = [
            'success' => 0,
            'msg' => __('messages.something_went_wrong')
        ];
    
        return $output;
    }
    }
   
    public function getF16FormList(Request $request)
    {
        
   
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $header = FormF16Detail::select('form_f16_details.*');

            if (!empty(request()->form_no) && request()->form_no !== 'All') {
              
                $header->where('form_f16_details.form_no', $request->form_no);
            }
             if (!empty(request()->invoice_no) && request()->invoice_no !== 'All' ) {
              
                $header->where('form_f16_details.invoice_no', $request->invoice_no);
            }
          if (!empty(request()->supplier) && request()->supplier !== 'All' ) {
               $supplierStart = substr($request->supplier, 0, 5);
                //$header->where('form_f16_details.supplier', $request->supplier);
                 $header->where('form_f16_details.supplier', 'like', $supplierStart . '%');
            }
             
        //  if (!empty($request->input('start_date')) && !empty($request->input('end_date'))) {

        //       $header->whereDate('form_f16_details.created_at', '>=', request()->start_date);
        //       $header->whereDate('form_f16_details.created_at', '<=', request()->end_date);
        //     }
         
            return Datatables::of($header)
                ->addIndexColumn()

                ->removeColumn('id')
                ->editColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (auth()->user()->can("edit_f22_stock_Taking_form")) {
                        $html .= '<li><a href="' . action('\Modules\MPCS\Http\Controllers\F16AFormController@view', [$row->transaction_id]) . '"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                    }

                     $html .= '<li><a href="' . action('\Modules\MPCS\Http\Controllers\F16AFormController@edit', [$row->transaction_id]) . '"><i class="fa fa-edit" aria-hidden="true"></i>' . __("messages.edit") . '</a></li>';

                    $html .= '</ul>';
                    return $html;
                })

                ->rawColumns(['action'])
                ->make(true);
        }
    }
      public function printF16Form(Request $request)
    {
        
       
        return view('mpcs::forms.partials.list_f16');
    }
  public function view(Request $request)
    {
   
    $transaction_id=$request->id;
    
    $business_id = request()->session()->get('business.id');
    $settings = MpcsFormSetting::where('business_id', $business_id)->first();
    $transactionDetails =  $this->getTransactionDetails($transaction_id, $business_id);

    $setting = $transactionDetails['settings'];
    $suppliers = $transactionDetails['suppliers'];
    $lastRecord = $transactionDetails['lastRecord'];
    $name = $transactionDetails['name'];
    $invoice = $transactionDetails['invoice']; 
    $business_locations= $transactionDetails['business_locations']; 
    $F16a_from_no= $transactionDetails['F16a_from_no']; 
    $sub_categories= $transactionDetails['sub_categories']; 
    $invoiceno= $transactionDetails['invoiceno']; 
    $all_form_no= $transactionDetails['all_form_no']; 
    $form_f16a= $transactionDetails['form_f16a']; 
    return view('mpcs::forms.partials.16a_view')->with(compact(
        'business_locations',
        'F16a_from_no',
        'sub_categories',
        'setting',
        'suppliers',
        'invoiceno',
        'all_form_no',
        'lastRecord',
        'name',
        'invoice',
        'form_f16a'
    ));
    }
       
public function getTransactionDetails($transaction_id, $business_id)
{
    $settings = MpcsFormSetting::where('business_id', $business_id)->first();

    if (!empty($settings)) {
        $F16a_from_no = $settings->F16A_form_sn;
    } else {
        $F16a_from_no = 1;
    }

    $suppliers = Contact::suppliersDropdown($business_id, false);
    $business_locations = BusinessLocation::forDropdown($business_id);
    $invoiceno = FormF16Detail::pluck('invoice_no')->toArray();
    $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
    $form_f16a = FormF16Detail::where('transaction_id', $transaction_id)->first();
    $setting = MpcsFormSetting::where('business_id', $business_id)->first();
    $max_form_no = FormF16Detail::max('form_no');
    $all_form_no = FormF16Detail::pluck('form_no')->toArray();
    $max_form_nos = $max_form_no + 1;

    if ($max_form_nos >= $F16a_from_no) {
        $F16a_from_no = $max_form_nos;
    }



    $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
        ->join(
            'business_locations AS BS',
            'transactions.location_id',
            '=',
            'BS.id'
        )
        ->leftJoin(
            'transaction_payments AS TP',
            'transactions.id',
            '=',
            'TP.transaction_id'
        )
        ->leftJoin(
            'transactions AS PR',
            'transactions.id',
            '=',
            'PR.return_parent_id'
        )
        ->leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
        ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
        ->leftjoin('variations', 'products.id', 'variations.product_id')
        ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
        ->where('transactions.business_id', $business_id)
        ->where('transactions.id', $transaction_id)
        ->where('transactions.status', 'received')
        ->select(
            'transactions.id',
            'transactions.invoice_no as invoice',
            'transactions.ref_no as reference_no',
            'purchase_lines.quantity as received_qty',
            'purchase_lines.purchase_price as unit_purchase_price',
            'transactions.final_total as total_purchase_price',
            'BS.name as location',
            'contacts.name as name',
            'transactions.updated_at as date',
            'products.name as product',
            'products.id as product_id',
            'variations.default_sell_price',
            'transactions.pay_term_number',
            'transactions.pay_term_type',
            'PR.id as return_transaction_id',
            DB::raw('SUM(TP.amount) as amount_paid'),
            DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                    TP2.transaction_id=PR.id ) as return_paid'),
            DB::raw('COUNT(PR.id) as return_exists'),
            DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
            DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
        )
        ->groupBy('transactions.id')
        ->get()
        ->toArray();

    $lastRecord = end($purchases);
    $name=$lastRecord['name'];
    $invoice = $lastRecord['invoice'];  
    return [
        'settings' => $settings,
        'suppliers' => $suppliers,
        'business_locations' => $business_locations,
        'invoiceno' => $invoiceno,
        'sub_categories' => $sub_categories,
        'form_f16a' => $form_f16a,
        'setting' => $setting,
        'F16a_from_no' => $F16a_from_no,
        'purchases' => $purchases,
        'lastRecord' => $lastRecord,
        'name' =>$name,
        'invoice' => $invoice,
        'all_form_no' =>$all_form_no
    ];
}
   public function edit(Request $request)
    {
    $transaction_id=$request->id;
    
    $business_id = request()->session()->get('business.id');
    $settings = MpcsFormSetting::where('business_id', $business_id)->first();
    $transactionDetails =  $this->getTransactionDetails($transaction_id, $business_id);

    $setting = $transactionDetails['settings'];
    $suppliers = $transactionDetails['suppliers'];
    $lastRecord = $transactionDetails['lastRecord'];
    $name = $transactionDetails['name'];
    $invoice = $transactionDetails['invoice']; 
    $business_locations= $transactionDetails['business_locations']; 
    $F16a_from_no= $transactionDetails['F16a_from_no']; 
    $sub_categories= $transactionDetails['sub_categories']; 
    $invoiceno= $transactionDetails['invoiceno']; 
    $all_form_no= $transactionDetails['all_form_no']; 
    $form_f16a= $transactionDetails['form_f16a']; 
    $edit='edit';
    return view('mpcs::forms.partials.16a_edit')->with(compact(
        'business_locations',
        'F16a_from_no',
        'sub_categories',
        'setting',
        'suppliers',
        'invoiceno',
        'all_form_no',
        'lastRecord',
        'name',
        'invoice',
        'edit',
        'form_f16a'
    ));
      }
   public function print(Request $request)
   {
    
     
     $form_f16a = FormF16Detail::where('form_no', $request->formId)->first();
     $transaction_id=$form_f16a['transaction_id'];
    $business_id = request()->session()->get('business.id');
    $settings = MpcsFormSetting::where('business_id', $business_id)->first();
    $transactionDetails =  $this->getTransactionDetails($transaction_id, $business_id);

    $setting = $transactionDetails['settings'];
    $suppliers = $transactionDetails['suppliers'];
    $lastRecord = $transactionDetails['lastRecord'];
    $name = $transactionDetails['name'];
    $invoice = $transactionDetails['invoice']; 
    $business_locations= $transactionDetails['business_locations']; 
    $F16a_from_no= $transactionDetails['F16a_from_no']; 
    $sub_categories= $transactionDetails['sub_categories']; 
    $invoiceno= $transactionDetails['invoiceno']; 
    $all_form_no= $transactionDetails['all_form_no']; 
    return view('mpcs::forms.partials.print_f16_form')->with(compact(
        'business_locations',
        'F16a_from_no',
        'sub_categories',
        'setting',
        'suppliers',
        'invoiceno',
        'all_form_no',
        'lastRecord',
        'name',
        'invoice',
        'form_f16a'
    ));
    }
 
}
