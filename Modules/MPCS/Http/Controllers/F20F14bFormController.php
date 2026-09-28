<?php
namespace Modules\MPCS\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Transaction;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;

class F20F14bFormController extends Controller
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
        $this->productUtil     = $productUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->util            = $util;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }
        $business_id = request()->session()->get('business.id');
        $setting     = MpcsFormSetting::where('business_id', $business_id)->first();
        if (! empty($setting)) {
            $F20_form_sn = ! empty($setting->F20_form_sn) ? $setting->F20_form_sn : 1;
        } else {
            $F20_form_sn = 1;
        }
        if (! empty($setting)) {
            $F14_from_no = ! empty($setting->F14_form_sn) ? $setting->F14_form_sn : 1;
        } else {
            $F14_from_no = 1;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('mpcs::forms.F20andF14b.index')->with(compact(
            'F20_form_sn',
            'setting',
            'business_locations'
        ));
    }

    /**
     * Show the form for getForm20
     * @return Response
     */
    /**
     * Show the form for getFrom20
     * @return Response
     */
    public function getFrom20(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            // Start building the query
            $query = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join('business_locations AS BS', 'transactions.location_id', '=', 'BS.id')
                ->leftJoin('transaction_payments AS TP', 'transactions.id', '=', 'TP.transaction_id')
                ->leftJoin('transactions AS PR', 'transactions.id', '=', 'PR.return_parent_id')
                ->leftJoin('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
                ->leftJoin('products', 'transaction_sell_lines.product_id', '=', 'products.id')
                ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final') // Only finalized sales
                ->select(
                    'products.sku as sku',
                    'products.name as product',
                    DB::raw('SUM(transaction_sell_lines.quantity) as sold_qty'),
                    DB::raw('AVG(transaction_sell_lines.unit_price) as unit_price'),
                    DB::raw('SUM(transaction_sell_lines.quantity * transaction_sell_lines.unit_price) as total_amount'),
                    'transactions.is_credit_sale',
                    'TP.method as payment_method'
                )
                ->groupBy('products.id', 'products.sku', 'products.name', 'transactions.is_credit_sale', 'TP.method');

            // Location filter
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            // Specific location filter
            if (! empty($request->location_id)) {
                $query->where('transactions.location_id', $request->location_id);
            }

            // Date range filter
            if (! empty($request->start_date) && ! empty($request->end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $request->start_date)
                    ->whereDate('transactions.transaction_date', '<=', $request->end_date);
            }

            $business_details = Business::find($business_id);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('DT_Row_Index', function ($row) {
                    static $count = 1;
                    return $count++;
                })
                ->addColumn('cash_sale_amount', function ($row) {
                    // Calculate cash sale amount
                    if ($row->payment_method == 'cash' || $row->payment_method == 'card' || $row->payment_method == 'cheque') {
                        return $row->total_amount;
                    }
                    return 0;
                })
                ->addColumn('credit_sale_amount', function ($row) {
                    // Calculate credit sale amount
                    if ($row->is_credit_sale == 1) {
                        return $row->total_amount;
                    }
                    return 0;
                })
                ->editColumn('sold_qty', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->sold_qty, false, $business_details, true);
                })
                ->editColumn('unit_price', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->unit_price, false, $business_details, false);
                })
                ->editColumn('total_amount', function ($row) use ($business_details) {
                    return $this->productUtil->num_f($row->total_amount, false, $business_details, false);
                })
                ->with([
                    'footer_cash_sale'   => $query->get()->sum(function ($item) {
                        return ($item->payment_method == 'cash' || $item->payment_method == 'card' || $item->payment_method == 'cheque') ? $item->total_amount : 0;
                    }),
                    'footer_credit_sale' => $query->get()->sum(function ($item) {
                        return $item->is_credit_sale == 1 ? $item->total_amount : 0;
                    }),
                ])
                ->rawColumns(['sold_qty', 'unit_price', 'total_amount'])
                ->make(true);
        }
    }

    /**
     * Get Form 14b data
     */
    public function getForm14b(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

                    // Your Form 14b logic here
        $data = []; // Replace with your actual Form 14b data

        return view('mpcs::forms.F20andF14b.partials.form14b_content')->with(compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('mpcs::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('mpcs::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('mpcs::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}
