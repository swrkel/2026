<?php
namespace Modules\Bakery\Http\Controllers;

use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Bakery\Entities\BakeryDriver;
use Modules\Bakery\Entities\BakeryFleet;
use Modules\Bakery\Entities\BakeryLoading;
use Modules\Bakery\Entities\BakeryLoadingProduct;
use Modules\Bakery\Entities\BakeryLoadingReturn;
use Modules\Bakery\Entities\BakeryLoadingReturnProduct;
use Modules\Bakery\Entities\BakeryProduct;
use Modules\Bakery\Entities\BakeryRoute;
use Yajra\DataTables\Facades\DataTables;

class BakeryLoadingReturnController extends Controller
{

    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $businessUtil;

    private function getBusinessId()
    {
        return request()->session()->get('business.id') ?: request()->session()->get('user.business_id');
    }

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil      = $commonUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->productUtil     = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil    = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $business_id = $this->getBusinessId();

        Log::debug('business id in the index: ', [''=> $business_id]);

        if (request()->ajax()) {
            $query = BakeryLoading::with(['user', 'fleet', 'driver', 'product', 'route'])
                ->where('business_id', $business_id);

            Log::info('returns index query prepared for business id: ' . $business_id);

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $query->whereBetween('date', [request()->start_date, request()->end_date]);
            }

            if (! empty(request()->vehicle_id)) {
                $query->where('vehicle_id', request()->vehicle_id);
            }

            if (! empty(request()->driver_id)) {
                $query->where('driver_id', request()->driver_id);
            }

            return DataTables::of($query)
                ->addColumn('username', fn($row) => $row->user?->username)
                ->addColumn('vehicle_number', fn($row) => $row->fleet?->vehicle_number)
                ->addColumn('driver_name', fn($row) => $row->driver?->driver_name)
                ->addColumn('product_name', fn($row) => $row->product?->name)
                ->addColumn('route_name', fn($row) => $row->route?->route)
                ->addColumn('total_due_amount', function ($row) {
                    $amt = $row->loadingProducts->sum('total_amount');
                    return $this->transactionUtil->num_f($amt);
                })
                ->addColumn('total_sold_amount', function ($row) {
                    $amt = $row->loadingProducts->sum('total_amount');
                    return $this->transactionUtil->num_f($amt);
                })
                ->addColumn('total_returned_amount', fn($row) => $this->transactionUtil->num_f(0))
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                    . __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    if (auth()->user()->can('bakery.loading_edit')) {
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Bakery\Http\Controllers\BakeryLoadingReturnController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }
                    $html .= '<li><a href="#" data-href="' . action('\Modules\Bakery\Http\Controllers\BakeryLoadingReturnController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';
                    return $html;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        // Non-AJAX request (normal page load)
        return view('bakery::bakery_loading.index');
    }

    public function getProducts()
    {
        if (request()->ajax()) {

            $id = request()->id;

            Log::info('get products received id: ' . $id);

            $business_id = $this->getBusinessId();

            $route_products = BakeryLoadingProduct::leftjoin('bakery_products', 'bakery_products.id', 'bakery_loading_products.product_id')
                ->leftjoin('users', 'bakery_products.created_by', 'users.id')
                ->where('bakery_loading_products.loading_id', $id)
                ->select([
                    'bakery_products.name',
                    'bakery_loading_products.*',
                    'users.username as created_by',
                ]);

            Log::info('returns products query prepared for loading id: ' . $id);

            if (! empty(request()->product_id)) {
                $route_products->where('bakery_products.id', request()->product_id);
            }

            return DataTables::of($route_products)
                ->editColumn('unit_cost', '{{@num_format($unit_cost)}}')
                ->addColumn('qty_loaded', '{{@num_format($qty)}}')
                ->addColumn('total_loaded', '{{@num_format($total_amount)}}')

                ->addColumn('returned_qty', function ($row) {
                    $return_qty = BakeryLoadingReturnProduct::where('product_id', $row->product_id)->where('loading_id', $row->loading_id)->sum('qty_returned') ?? 0;

                    $html = '<input type="hidden" class="loading_table_product_id" name="loading_table_product_id[]" value="' . $row->product_id . '">';
                    $html .= '<input type="hidden" class="loading_table_loading_id" name="loading_table_loading_id[]" value="' . $row->loading_id . '">';
                    $html .= '<input type="hidden" class="loading_table_unit_cost" name="loading_table_unit_cost[]" value="' . $row->unit_cost . '">';
                    $html .= '<input type="hidden" class="loading_table_loaded_qty" name="loading_table_loaded_qty[]" value="' . $row->qty . '">';
                    $html .= '<input type="hidden" class="loading_table_loaded_amount" name="loading_table_loaded_amount[]" value="' . $row->total_amount . '">';

                    $html .= '<input type="text" name="loading_table_returned_qty[]" class="form-control loading_table_returned_qty" value="' . $return_qty . '" required>';

                    return $html;
                })
                ->addColumn('returned_qty_amt', function ($row) {

                    $total = BakeryLoadingReturnProduct::where('product_id', $row->product_id)->where('loading_id', $row->loading_id)->sum('amount_returned') ?? 0;

                    $html = '<input type="hidden" class="loading_table_total_returned form-control" value="' . $total . '" name="loading_table_total_returned[]">';
                    $html .= '<span class="text-bold loading_table_span_total_returned">' . $this->transactionUtil->num_f($total) . '</span>';
                    return $html;
                })
                ->addColumn('due_amount', function ($row) {
                    $return_qty = BakeryLoadingReturnProduct::where('product_id', $row->product_id)->where('loading_id', $row->loading_id)->sum('amount_returned') ?? 0;
                    $total      = $row->total_amount - ($return_qty);

                    $html = '<input type="hidden" class="loading_table_due_amount form-control" value="' . $total . '" name="loading_table_due_amount[]">';
                    $html .= '<span class="text-bold loading_table_span_due_amount">' . $this->transactionUtil->num_f($total) . '</span>';
                    return $html;
                })
                ->addColumn('settled_amt', function ($row) {
                    $settled_amt = BakeryLoadingReturnProduct::where('product_id', $row->product_id)->where('loading_id', $row->loading_id)->sum('received_amount') ?? 0;

                    $html = '<input type="text" name="loading_table_settled_amt[]" class="form-control loading_table_settled_amt" value="' . $settled_amt . '" required>';
                    return $html;
                })
                ->addColumn('short_amt', function ($row) {
                    $return_qty  = BakeryLoadingReturnProduct::where('product_id', $row->product_id)->where('loading_id', $row->loading_id)->sum('amount_returned') ?? 0;
                    $settled_amt = BakeryLoadingReturnProduct::where('product_id', $row->product_id)->where('loading_id', $row->loading_id)->sum('received_amount') ?? 0;
                    $total       = $row->total_amount - ($return_qty) - $settled_amt;

                    $html = '<input type="hidden" class="loading_table_short_amt form-control" value="' . $total . '" name="loading_table_short_amt[]">';
                    $html .= '<span class="text-bold loading_table_span_short_amt">' . $this->transactionUtil->num_f($total) . '</span>';
                    return $html;
                })

                ->removeColumn('id')
                ->rawColumns(['action', 'returned_qty', 'returned_qty_amt', 'due_amount', 'settled_amt', 'short_amt'])
                ->make(true);
        }
    }

    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {

        $business_id = $this->getBusinessId();
        // dd($request->all());

        DB::beginTransaction();
        try {

            $loading_data = [
                'business_id' => $business_id,
                'form_no'     => $request->return_form_no,
                'loading_id'  => $request->return_loading_form_no,
                'created_by'  => auth()->user()->id,
            ];

            $is_added = BakeryLoadingReturn::where('loading_id', $request->return_loading_form_no)->first();
            if (! empty($is_added)) {
                unset($loading_data['form_no']);
            }

            $product = BakeryLoadingReturn::updateOrCreate(['loading_id' => $request->return_loading_form_no], $loading_data);

            BakeryLoadingReturnProduct::where('return_id', $product->id)->delete();

            $added = 0;
            foreach ($request->loading_table_product_id as $key => $product_id) {
                $qty_returned = isset($request->loading_table_returned_qty[$key]) ? (float) $request->loading_table_returned_qty[$key] : 0;
                if ($qty_returned <= 0) {
                    continue;
                }
                $added++;

                $product = BakeryLoadingReturnProduct::create([
                    'return_id'       => $product->id,
                    'loading_id'      => $request->loading_table_loading_id[$key],
                    'product_id'      => $product_id,
                    'qty_returned'    => $qty_returned,
                    'amount_returned' => $request->loading_table_total_returned[$key],
                    'received_amount' => $request->loading_table_settled_amt[$key],
                ]);
            }

            if ($added === 0) {
                DB::rollback();
                $output = [
                    'success' => false,
                    'tab'     => 'returns',
                    'msg'     => __('bakery::lang.you_must_load_atleast_one_product'),
                ];

                return redirect()->back()->with('status', $output);
            }

            $output = [
                'success' => true,
                'tab'     => 'returns',
                'msg'     => __('lang_v1.success'),
                'data'    => $product,
            ];

            DB::commit();

            if (request()->ajax()) {
                return $output;
            }

            if ($request->input('save_and_print') === '1') {
                return redirect()->to(action('\Modules\Bakery\Http\Controllers\BakeryLoadingReturnController@show', [$product->id]) . '?print=1');
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab'     => 'returns',
                'msg'     => __('messages.something_went_wrong'),
            ];

            if (request()->ajax()) {
                return $output;
            }
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $business_id = $this->getBusinessId();
        $data = BakeryLoadingReturn::leftJoin('bakery_loadings', 'bakery_loading_returns.loading_id', '=', 'bakery_loadings.id')
            ->leftJoin('users', 'bakery_loading_returns.created_by', '=', 'users.id')
            ->leftJoin('bakery_fleets', 'bakery_fleets.id', '=', 'bakery_loadings.vehicle_id')
            ->leftJoin('bakery_drivers', 'bakery_drivers.id', '=', 'bakery_loadings.driver_id')
            ->leftJoin('bakery_routes', 'bakery_routes.id', '=', 'bakery_loadings.route_id')
            ->where('bakery_loading_returns.business_id', $business_id)
            ->where('bakery_loading_returns.id', $id)
            ->select([
                'bakery_loading_returns.*',
                'bakery_loadings.form_no as loading_form_no',
                'bakery_loadings.date as loading_date',
                'users.username',
                'bakery_fleets.vehicle_number',
                'bakery_drivers.driver_name',
                'bakery_routes.route as route_name',
            ])->firstOrFail();

        $products = BakeryLoadingReturnProduct::leftJoin('bakery_products', 'bakery_products.id', '=', 'bakery_loading_return_products.product_id')
            ->where('bakery_loading_return_products.return_id', $id)
            ->where('bakery_loading_return_products.qty_returned', '>', 0)
            ->select([
                'bakery_products.name as product_name',
                'bakery_loading_return_products.*',
            ])
            ->get();

        if (request()->boolean('print')) {
            return view('bakery::bakery_loading.print_return')->with(compact('data', 'products'));
        }

        return view('bakery::bakery_loading.show_return')->with(compact('data', 'products'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = $this->getBusinessId();

        $fleet    = BakeryFleet::where('business_id', $business_id)->pluck('vehicle_number', 'id');
        $drivers  = BakeryDriver::where('business_id', $business_id)->pluck('driver_name', 'id');
        $products = BakeryProduct::where('business_id', $business_id)->pluck('name', 'id');
        $routes   = BakeryRoute::pluck('route', 'id');
        $loading  = BakeryLoading::findOrFail($id);

        return view('bakery::bakery_loading.edit_loading')->with(compact('fleet', 'drivers', 'products', 'routes', 'loading'));

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $business_id = $this->getBusinessId();
        // dd($request->all());

        DB::beginTransaction();
        try {

            $loading_data = [
                'business_id' => $business_id,
                'date'        => $this->transactionUtil->uf_date($request->date),
                'vehicle_id'  => $request->vehicle_id,
                'driver_id'   => $request->driver_id,
                'product_id'  => $request->product_id,
                'route_id'    => $request->route_id,
            ];

            $product = BakeryLoading::where('id', $id)->update($loading_data);
            BakeryLoadingProduct::where('loading_id', $id)->delete();

            $added = 0;
            foreach ($request->table_product_id as $key => $product_id) {
                if (empty($request->table_qty[$key])) {
                    continue;
                }
                $added++;
                $product = BakeryLoadingProduct::create([
                    'loading_id'   => $id,
                    'product_id'   => $product_id,
                    'unit_cost'    => $request->table_unit_cost[$key],
                    'qty'          => $request->table_qty[$key],
                    'total_amount' => $request->table_total_due[$key],
                ]);
            }

            if ($added == 0) {
                DB::rollback();
                $output = [
                    'success' => false,
                    'tab'     => 'list_loading',
                    'msg'     => __('bakery::lang.you_must_load_atleast_one_product'),
                    'data'    => $product,
                ];
                return redirect()->back()->with('status', $output);

            }

            $output = [
                'success' => true,
                'tab'     => 'list_loading',
                'msg'     => __('lang_v1.success'),
                'data'    => $product,
            ];

            DB::commit();

            if (request()->ajax()) {
                return $output;
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab'     => 'list_loading',
                'msg'     => __('messages.something_went_wrong'),
            ];

            if (request()->ajax()) {
                return $output;
            }
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
