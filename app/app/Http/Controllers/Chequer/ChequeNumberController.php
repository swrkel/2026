<?php



namespace App\Http\Controllers\Chequer;



use App\Account;

use App\Contact;

use App\DefaultSettings;

use App\Utils\ModuleUtil;

use Illuminate\Http\Request;

use App\Chequer\ChequeNumber;

use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Log;

use App\Chequer\PrintedChequeDetail;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Chequer\ChequerDefaultSetting;



class ChequeNumberController extends Controller

{

    protected $moduleUtil;



    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */

    public function __construct(ModuleUtil $moduleUtil)

    {

        $this->moduleUtil = $moduleUtil;

    }



    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */
    public function index()
    {
        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');
        $defaultVal = [];

        if (request()->ajax()) {
            try {
                if (empty($business_id)) {
                    return response()->json([
                        'draw' => intval(request()->get('draw')),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ]);
                }

                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                }

                $cheque_number = ChequeNumber::leftJoin('accounts', 'cheque_numbers.account_no', '=', 'accounts.id')
                    ->leftJoin('users', 'cheque_numbers.user_id', '=', 'users.id')
                    ->where('cheque_numbers.business_id', $business_id)
                    ->select('cheque_numbers.*', 'users.username', 'accounts.name as account_name');

                if (!empty(request()->bank_account_no)) {
                    $cheque_number->where('cheque_numbers.account_no', request()->bank_account_no);
                }

                if (!empty(request()->cheque_no)) {
                    $cheque_number->where('cheque_numbers.id', request()->cheque_no);
                }

                if (!empty(request()->date_range) && request()->date_range !== 'All') {
                    $dates = explode(' - ', request()->date_range);
                    if (count($dates) === 2) {
                        $start_date = date('Y-m-d', strtotime($dates[0]));
                        $end_date = date('Y-m-d', strtotime($dates[1]));
                        $cheque_number->whereBetween('cheque_numbers.date_time', [$start_date, $end_date]);
                    }
                }

                return DataTables::of($cheque_number)
                    ->addColumn('action', function ($row) {
                        return '<div class="btn-group">'
                            . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                            . __('messages.actions') . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                            . '<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                            . '<li><a href="' . action('Chequer\ChequeNumberController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>'
                            . '</ul></div>';
                    })
                    ->editColumn('name', function ($row) {
                        return $row->account_name ?: '-';
                    })
                    ->editColumn('date_time', function ($row) {
                        return !empty($row->date_time) ? date('Y-m-d', strtotime($row->date_time)) : '-';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Exception $e) {
                Log::emergency('Cheque number list failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

                return response()->json([
                    'draw' => intval(request()->get('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Unable to load cheque numbers. Please check laravel.log for details.'
                ], 200);
            }
        }

        $accounts = $this->linkedChequeAccounts($business_id)->pluck('name', 'id');
        $chequeNumbers = ChequeNumber::where('business_id', $business_id)
            ->orderBy('reference_no')
            ->pluck('reference_no', 'id');

        return view('chequer.cheque_number.index')->with(compact('accounts', 'chequeNumbers', 'defaultVal'));
    }

    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function create()
    {
        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');
        $no_of_cheque_leaves = 0;
        $check_book_number = 1;
        $accounts = $this->linkedChequeAccounts($business_id)->pluck('name', 'id');

        $checkbook = ChequeNumber::where('business_id', $business_id)->orderBy('reference_no', 'desc')->first();
        if ($checkbook && is_numeric($checkbook->reference_no)) {
            $check_book_number = ((int) $checkbook->reference_no) + 1;
        } else {
            $settings = DefaultSettings::where('business_id', $business_id)->first();
            $check_book_number = ($settings && $settings->def_autostart_chbk_no) ? $settings->def_autostart_chbk_no : 1;
        }

        return view('chequer/cheque_number/create')->with(compact('accounts', 'check_book_number', 'no_of_cheque_leaves'));
    }

    private function linkedChequeAccounts($business_id)
    {
        $linked_account_ids = \App\Chequer\ChequerBankAccount::where('business_id', $business_id)
            ->where('is_visible', 1)
            ->pluck('account_id')
            ->filter()
            ->unique()
            ->values();

        $query = Account::where('business_id', $business_id)->notClosed();

        if ($linked_account_ids->isNotEmpty()) {
            $query->whereIn('id', $linked_account_ids);
        } else {
            $query->where('is_need_cheque', 'Y');
        }

        return $query->orderBy('name')->get();
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

            $business_id = $request->session()->get('business.id');

            $data = array(

                'date_time' => $request->date_time,

                'reference_no' => $request->reference_no,

                'business_id' => $business_id,

                'account_no' => $request->account_number,

                'first_cheque_no' => $request->first_cheque_no,

                'last_cheque_no' => $request->last_cheque_no,

                'no_of_cheque_leaves' => $request->no_of_cheque_leaves,

                'user_id' => Auth::user()->id,
                'status' => 'active'

            );

            

            ChequeNumber::create($data);

            $output = [

                'success' => 1,

                'msg' => __('cheque.cheque_number_add_succuss')

            ];

            

        } catch (\Exception $e) {

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => 0,

                'msg' => __('messages.something_went_wrong')

            ];

        }

        if ($request->ajax()) {
            return response()->json($output);
        }
        return redirect()->back()->with('status', $output);

    }



    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        return 'asldkfj';

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
    $cheque = ChequeNumber::where('business_id', $business_id)->where('id', $id)->firstOrFail();
    $accounts = Account::where('business_id', $business_id)->where('is_need_cheque', 'Y')->notClosed()->pluck('name', 'id');

    return view('chequer/cheque_number/edit', compact('cheque', 'accounts'));
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
        $business_id = $request->session()->get('business.id');
        $cheque = ChequeNumber::where('business_id', $business_id)->where('id', $id)->firstOrFail();

        $cheque->update([
            'date_time' => $request->date_time,
            'reference_no' => $request->reference_no,
            'account_no' => $request->account_number,
            'first_cheque_no' => $request->first_cheque_no,
            'last_cheque_no' => $request->last_cheque_no,
            'no_of_cheque_leaves' => $request->no_of_cheque_leaves,
            'user_id' => Auth::user()->id
        ]);

        return redirect()->route('cheque-numbers.index')->with('success', __('cheque.cheque_number_update_success'));
    } catch (\Exception $e) {
        Log::error("Update Error: " . $e->getMessage());

        return redirect()->back()->with('error', __('messages.something_went_wrong'));
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

        //

    }

    public function printedcheque(Request $request)

    {

        $defaultVal=null;

        if($request){

            $defaultVal=array();

            $defaultVal['bank_acount_no'] = $request->bank_acount_no;

            $defaultVal['cheque_no'] = $request->cheque_no;

            $defaultVal['payment_status'] = $request->payment_status;

            $defaultVal['payee_no'] = $request->payee_no;

            $defaultVal['startDate'] = date('m/01/Y');

            $defaultVal['endDate'] = date("m/t/Y");

            if($request->date_range){

                $dates = explode(' - ', $request->date_range);

                $defaultVal['startDate'] = $dates[0];

                $defaultVal['endDate'] = $dates[1];

            }

        } 

        $business_id = request()->session()->get('business.id');
        $getvoucher = PrintedChequeDetail::where('business_id', $business_id)->orderBy('id', 'desc')->get();
        $get_defultvalu = ChequerDefaultSetting::where('business_id', $business_id)->get();

        $bankAcounts = Account::where('accounts.business_id', $business_id)
        ->where('is_need_cheque', 'Y')
        ->leftJoin('account_groups', 'accounts.asset_type', '=', 'account_groups.id')
        ->select('accounts.name AS account_name', 'account_groups.name AS group_name', 'accounts.id')
        ->pluck('account_name', 'id'); 


        $payeeList = Contact::where('business_id', $business_id)->where('type', 'supplier')->pluck('name','id');

        $chequeNumbers = PrintedChequeDetail::groupBy('cheque_no')->pluck('cheque_no','cheque_no');

        $paymentStatus = array('Full Payment'=>'Full Payment','Partial Payment'=>'Partial Payment','Last Payment'=>'Last Payment');

        // \DB::connection()->enableQueryLog();

        $printedcheque = PrintedChequeDetail::where('printed_cheque_details.business_id', $business_id)

                                            //->where('printed_cheque_details.status','!=','Cancelled' )

                                             ->leftjoin('users', 'printed_cheque_details.user_id', 'users.id')

                                             ->leftjoin('chequer_bank_accounts', 'printed_cheque_details.bank_account_no', 'chequer_bank_accounts.id')

                                             ->leftjoin('contacts', 'printed_cheque_details.payee', 'contacts.id')

                                            ->leftjoin('transaction_payments', 'printed_cheque_details.cheque_no', 'transaction_payments.cheque_number')
                                             ->leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id');

        if($request->bank_acount_no && $request->bank_acount_no!="")

            $printedcheque = $printedcheque->where('printed_cheque_details.bank_account_no', $request->bank_acount_no);

        if($request->payment_status && $request->payment_status!="")

            $printedcheque = $printedcheque->where('printed_cheque_details.supplier_paid_amount',$request->payment_status);

        if($request->payee_no && $request->payee_no!="")

            $printedcheque = $printedcheque->where('printed_cheque_details.payee',$request->payee_no);

        if($request->cheque_no && $request->cheque_no!="")

            $printedcheque = $printedcheque->where('printed_cheque_details.cheque_no',$request->cheque_no);

        if($request->date_range){

            $printedcheque = $printedcheque->where('printed_cheque_details.cheque_date','>=',date('Y-m-d',strtotime($defaultVal['startDate'])));

            $printedcheque = $printedcheque->where('printed_cheque_details.cheque_date','<=',date('Y-m-d',strtotime($defaultVal['endDate'])));

        }


        $printedcheque = $printedcheque->select('printed_cheque_details.*', 'chequer_bank_accounts.bank', 'chequer_bank_accounts.account_number', 'chequer_bank_accounts.branch','users.username','transactions.type','transactions.ref_no','transactions.invoice_no', 'transactions.payment_status','contacts.name')

                                                ->orderBy('printed_cheque_details.id','DESC')

                                                ->get();

        // $queries = \DB::getQueryLog();

        // print_r($queries);

        return view('chequer/printedcheque/index')->with(compact('printedcheque','bankAcounts','payeeList','chequeNumbers','paymentStatus','defaultVal', 'getvoucher', 'get_defultvalu'));

    }

}

