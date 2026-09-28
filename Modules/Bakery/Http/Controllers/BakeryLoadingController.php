<?php
namespace Modules\Bakery\Http\Controllers;

use app\Business;
use App\BusinessLocation;
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
use Modules\Bakery\Entities\BakeryInvoiceNumber;
use Modules\Bakery\Entities\BakeryLoading;
use Modules\Bakery\Entities\BakeryLoadingProduct;
use Modules\Bakery\Entities\BakeryLoadingReturn;
use Modules\Bakery\Entities\BakeryLoadingReturnProduct;
use Modules\Bakery\Entities\BakeryProduct;
use Modules\Bakery\Entities\BakeryRoute;
use Modules\Bakery\Entities\BakeryUser;
use Yajra\DataTables\Facades\DataTables;

class BakeryLoadingController extends Controller
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
        Log::info("index in bakery loading controller");
        $business_id = $this->getBusinessId();
        Log::debug('business id in the index: ', ['' => $business_id]);

        if (request()->ajax()) {
            $business_id = $this->getBusinessId();

            $data = BakeryLoading::leftjoin('users', 'bakery_loadings.created_by', 'users.id')
                ->leftjoin('bakery_fleets', 'bakery_fleets.id', 'bakery_loadings.vehicle_id')
                ->leftjoin('bakery_drivers', 'bakery_drivers.id', 'bakery_loadings.driver_id')
                ->leftjoin('bakery_products', 'bakery_products.id', 'bakery_loadings.product_id')
                ->leftjoin('bakery_routes', 'bakery_routes.id', 'bakery_loadings.route_id')
                ->where('bakery_loadings.business_id', $business_id)
                ->select([
                    'bakery_loadings.*',
                    'users.username',
                    'bakery_fleets.vehicle_number',
                    'bakery_drivers.driver_name',
                    'bakery_products.name as product_name',
                    'bakery_routes.route as route_name',
                ])
                ->orderBy('bakery_loadings.id', 'desc');

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $data->whereDate('bakery_loadings.date', '>=', request()->start_date)->whereDate('bakery_loadings.date', '<=', request()->end_date);
            }

            if (! empty(request()->vehicle_id)) {
                $data->where('bakery_loadings.vehicle_id', request()->vehicle_id);
            }

            if (! empty(request()->driver_id)) {
                $data->where('bakery_loadings.driver_id', request()->driver_id);
            }

            if (! empty(request()->form_no)) {
                $data->where('bakery_loadings.form_no', request()->form_no);
            }

            if (! empty(request()->route_id)) {
                $data->where('bakery_loadings.route_id', request()->route_id);
            }

            if (! empty(request()->created_by)) {
                $data->where('bakery_loadings.created_by', request()->created_by);
            }

            return DataTables::of($data)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                            data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                        if (auth()->user()->can('bakery_edit_loading')) {
                            $html .= '<li><a href="#" data-href="' . action('\Modules\Bakery\Http\Controllers\BakeryLoadingController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Bakery\Http\Controllers\BakeryLoadingController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';

                        return $html;
                    }
                )
                ->editColumn('date', '{{@format_date($date)}}')
                ->addColumn('total_due_amount', function ($row) {
                    $amt          = BakeryLoadingProduct::where('loading_id', $row->id)->sum('total_amount');
                    $returned_amt = BakeryLoadingReturnProduct::where('loading_id', $row->id)->sum('amount_returned');

                    return $this->transactionUtil->num_f($amt - $returned_amt);
                })
                ->addColumn('total_sold_amount', function ($row) {
                    $amt = BakeryLoadingProduct::where('loading_id', $row->id)->sum('total_amount');

                    return $this->transactionUtil->num_f($amt);
                })

                ->addColumn('total_returned_amount', function ($row) {
                    $amt = BakeryLoadingReturnProduct::where('loading_id', $row->id)->sum('amount_returned');

                    return $this->transactionUtil->num_f($amt);
                })

                ->addColumn('total_short_amount', function ($row) {
                    $amt = BakeryLoadingProduct::where('loading_id', $row->id)->sum('total_amount');

                    $return_qty  = BakeryLoadingReturnProduct::where('loading_id', $row->id)->sum('amount_returned');
                    $settled_amt = BakeryLoadingReturnProduct::where('loading_id', $row->id)->sum('received_amount');
                    $total       = $amt - ($return_qty) - $settled_amt;

                    return $this->transactionUtil->num_f($total);
                })

                ->removeColumn('id')
                ->rawColumns(['action', 'unit_cost', 'qty', 'total_due'])
                ->make(true);
        }

        $fleet    = BakeryFleet::where('business_id', $business_id)->pluck('vehicle_number', 'id');
        $drivers  = BakeryDriver::where('business_id', $business_id)->pluck('driver_name', 'id');
        $product_details = BakeryProduct::where('business_id', $business_id)
            ->select('id', 'name', 'unit_cost')
            ->orderBy('name')
            ->get();
        $products = $product_details->pluck('name', 'id');
        $product_lookup = $product_details->mapWithKeys(function ($product) {
            return [
                $product->id => [
                    'name' => $product->name,
                    'unit_cost' => (float) $product->unit_cost,
                ],
            ];
        })->toArray();
        $routes   = BakeryRoute::pluck('route', 'id');
        $list_loading_form_nos = BakeryLoading::where('business_id', $business_id)
            ->orderByDesc('id')
            ->pluck('form_no', 'form_no');
        $list_loading_routes = BakeryRoute::orderBy('route')->pluck('route', 'id');
        $list_loading_users = \App\User::whereIn('id', BakeryLoading::where('business_id', $business_id)->select('created_by'))
            ->orderBy('username')
            ->pluck('username', 'id');

        $starting_no = BakeryInvoiceNumber::where('business_id', $business_id)->first();

        $no     = 1;
        $prefix = '';
        if (! empty($starting_no)) {
            $no     = $starting_no->starting_number;
            $prefix = $starting_no->prefix;
        }

        $existing = BakeryLoading::where('business_id', $business_id)->get()->last();
        if (! empty($existing)) {
            $inv_no = explode('-', $existing->form_no);
            $no     = $inv_no[(sizeof($inv_no) - 1)] + 1;
        }
        $form_no = $prefix . "-" . $no;

        $return_form_no = BakeryLoadingReturn::where('business_id', $business_id)->get()->last();
        $rform_no       = 1;
        if (! empty($return_form_no)) {
            $rform_no = $return_form_no->form_no + 1;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = BakeryUser::where('business_id', $business_id)->pluck('name', 'id');

        $default_location = current(array_keys($business_locations->toArray()));

        $loading_form_nos = BakeryLoading::where('business_id', $business_id)->get();
        $returned_loading_ids = BakeryLoadingReturn::where('business_id', $business_id)
            ->whereNotNull('loading_id')
            ->pluck('loading_id');

        $pending_return_loadings = BakeryLoading::where('business_id', $business_id)
            ->whereNotIn('id', $returned_loading_ids)
            ->orderByDesc('id')
            ->get();
        $pending_return_loading_lookup = $pending_return_loadings->mapWithKeys(function ($loading) {
            $loading_products = BakeryLoadingProduct::leftJoin('bakery_products', 'bakery_products.id', '=', 'bakery_loading_products.product_id')
                ->where('bakery_loading_products.loading_id', $loading->id)
                ->select([
                    'bakery_loading_products.product_id',
                    'bakery_products.name',
                ])
                ->get();

            return [
                $loading->id => [
                    'form_no' => $loading->form_no,
                    'vehicle_id' => $loading->vehicle_id,
                    'driver_id' => $loading->driver_id,
                    'date' => !empty($loading->date) ? \Carbon\Carbon::parse($loading->date)->format('m/d/Y') : '',
                    'products' => $loading_products
                        ->filter(function ($loading_product) {
                            return !empty($loading_product->name);
                        })
                        ->map(function ($loading_product) {
                            return [
                                'id' => $loading_product->product_id,
                                'name' => $loading_product->name,
                            ];
                        })
                        ->values()
                        ->all(),
                ],
            ];
        })->toArray();

        $business           = Business::find($business_id);
        $currency_precision = $business->currency_precision;
        $quantity_precision = $business->quantity_precision;

        Log::info('currency precision: ' . $currency_precision);
        Log::info('quantity precision: ' . $quantity_precision);

        return view('bakery::bakery_loading.index')->with(compact(
            'fleet', 'drivers', 'products', 'routes', 'form_no', 'rform_no', 'loading_form_nos',
            'business_locations', 'pump_operators', 'existing', 'product_details', 'product_lookup',
            'currency_precision', 'quantity_precision', 'list_loading_form_nos', 'list_loading_routes', 'list_loading_users',
            'pending_return_loadings', 'pending_return_loading_lookup'
        ));

    }

    // public function getProducts()
    // {
    //     Log::info('get products in bakery loading controller');
    //     if (request()->ajax()) {

    //         $business_id = request()->session()->get('user.business_id');

    //         $route_products = BakeryProduct::leftjoin('users', 'bakery_products.created_by', 'users.id')
    //             ->where('bakery_products.business_id', $business_id)
    //             ->select([
    //                 'bakery_products.*',
    //                 'users.username as created_by',
    //             ]);

    //         if (! empty(request()->product_id)) {
    //             $route_products->where('bakery_products.id', request()->product_id);
    //         }

    //         return DataTables::of($route_products)
    //             ->editColumn('unit_cost', '{{@num_format($unit_cost)}}')
    //             ->addColumn('qty', function ($row) {

    //                 $unit_cost = $row->unit_cost;
    //                 $qty       = '';

    //                 if (! empty(request()->loading_id)) {
    //                     $product = BakeryLoadingProduct::where('product_id', $row->id)->where('loading_id', request()->loading_id)->first();
    //                     if (! empty($product)) {
    //                         $qty       = $product->qty;
    //                         $unit_cost = $product->unit_cost;
    //                     }
    //                 }

    //                 $html = '<input type="hidden" class="table_product_id" name="table_product_id[]" value="' . $row->id . '">';
    //                 $html .= '<input type="hidden" class="table_unit_cost" name="table_unit_cost[]" value="' . $unit_cost . '">';
    //                 $html .= '<input type="text" name="table_qty[]" class="form-control table_entered_qty" value="' . $qty . '">';

    //                 return $html;
    //             })
    //             ->addColumn('total_due', function ($row) {
    //                 $total = 0;

    //                 if (! empty(request()->loading_id)) {
    //                     $product = BakeryLoadingProduct::where('product_id', $row->id)->where('loading_id', request()->loading_id)->first();
    //                     if (! empty($product)) {
    //                         $total = $product->total_amount;
    //                     }
    //                 }

    //                 $html = '<input type="hidden" class="table_total_due form-control" value="' . $total . '" name="table_total_due[]">';
    //                 $html .= '<span class="text-bold table_span_total">' . $this->transactionUtil->num_f($total) . '</span>';
    //                 return $html;
    //             })
    //             ->removeColumn('id')
    //             ->rawColumns(['action', 'unit_cost', 'qty', 'total_due'])
    //             ->make(true);
    //     }
    // }

    public function getProducts()
    {
        Log::info('get products in bakery loading controller');

        if (request()->ajax()) {
            $business_id = $this->getBusinessId();
            $loading_id  = request()->loading_id;

            $route_products = BakeryProduct::leftJoin('users', 'bakery_products.created_by', '=', 'users.id')
                ->leftJoin('bakery_loading_products as blp', function ($join) use ($loading_id) {
                    $join->on('bakery_products.id', '=', 'blp.product_id')
                        ->where('blp.loading_id', '=', $loading_id);
                })
                ->where('bakery_products.business_id', $business_id)
                ->select([
                    'bakery_products.id',
                    'bakery_products.name',
                    'bakery_products.unit_cost as default_unit_cost',
                    'users.username as created_by',
                    'blp.qty',
                    'blp.unit_cost',
                    'blp.total_amount',
                ]);

            // Debug log
            Log::info('route products: ' . json_encode($route_products->get()));

            return DataTables::of($route_products)
                ->addColumn('unit_cost', function ($row) {
                    $unit_cost = $row->unit_cost ?? $row->default_unit_cost ?? 0;
                    return '<input type="hidden" class="table_unit_cost" name="table_unit_cost[]" value="' . $unit_cost . '">
                        ' . number_format($unit_cost, 2);
                })
                ->addColumn('qty', function ($row) {
                    $qty = $row->qty ?? '';
                    return '<input type="hidden" class="table_product_id" name="table_product_id[]" value="' . $row->id . '">
                        <input type="text" name="table_qty[]" class="form-control table_entered_qty" value="' . $qty . '">';
                })
                ->addColumn('total_due', function ($row) {
                    $total = $row->total_amount ?? 0;
                    return '<input type="hidden" class="table_total_due form-control" name="table_total_due[]" value="' . $total . '">
                        <span class="text-bold table_span_total">' . number_format($total, 2) . '</span>';
                })
                ->rawColumns(['unit_cost', 'qty', 'total_due'])
                ->make(true);
        }
    }

    public function getProductsShow($id)
    {
        if (request()->ajax()) {

            $business_id = $this->getBusinessId();

            $route_products = BakeryLoadingProduct::leftjoin('bakery_products', 'bakery_products.id', 'bakery_loading_products.product_id')
                ->leftjoin('users', 'bakery_products.created_by', 'users.id')
                ->where('bakery_loading_products.loading_id', $id)
                ->select([
                    'bakery_products.name',
                    'bakery_loading_products.*',
                    'users.username as created_by',
                ]);

            return DataTables::of($route_products)
                ->editColumn('unit_cost', '{{@num_format($unit_cost)}}')
                ->editColumn('qty', '{{@num_format($qty)}}')
                ->editColumn('total_amount', '{{@num_format($total_amount)}}')

                ->addColumn('returned_qty', function ($row) {
                    $returned_amt = BakeryLoadingReturnProduct::where('loading_id', $row->loading_id)->where('product_id', $row->product_id)->sum('qty_returned');
                    return $this->transactionUtil->num_f($returned_amt);
                })
                ->addColumn('returned_qty_amt', function ($row) {
                    $returned_amt = BakeryLoadingReturnProduct::where('loading_id', $row->loading_id)->where('product_id', $row->product_id)->sum('amount_returned');
                    return $this->transactionUtil->num_f($returned_amt);
                })
                ->addColumn('settled_amt', function ($row) {
                    $returned_amt = BakeryLoadingReturnProduct::where('loading_id', $row->loading_id)->where('product_id', $row->product_id)->sum('received_amount');
                    return $this->transactionUtil->num_f($returned_amt);
                })
                ->addColumn('short_amt', function ($row) {
                    $return_qty  = BakeryLoadingReturnProduct::where('loading_id', $row->loading_id)->where('product_id', $row->product_id)->sum('amount_returned');
                    $settled_amt = BakeryLoadingReturnProduct::where('loading_id', $row->loading_id)->where('product_id', $row->product_id)->sum('received_amount');
                    $total       = $row->total_amount - ($return_qty) - $settled_amt;

                    return $this->transactionUtil->num_f($total);
                })

                ->removeColumn('id')
                ->rawColumns(['action', 'unit_cost', 'qty', 'total_due'])
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

        Log::info('request received in store: ', $request->all());

        $business_id = $this->getBusinessId();
        // dd($request->all());

        DB::beginTransaction();
        try {

            $loading_data = [
                'business_id' => $business_id,
                'date'        => $this->transactionUtil->uf_date($request->date),
                'form_no'     => $request->form_no,
                'vehicle_id'  => $request->vehicle_id,
                'driver_id'   => $request->driver_id,
                'product_id'  => !empty($request->table_product_id) ? $request->table_product_id[0] : null,
                'route_id'    => $request->route_id,
                'created_by'  => auth()->user()->id,
            ];

            $loading = BakeryLoading::create($loading_data);

            $added = 0;
            foreach ($request->table_product_id as $key => $product_id) {
                $qty = isset($request->table_qty[$key]) ? $request->table_qty[$key] : null;
                if (empty($qty) || floatval($qty) <= 0) {
                    continue;
                }
                $added++;

                // BakeryLoadingProduct::where('loading_id', $loading->id)
                //     ->where('product_id', $product_id)
                //     ->delete();
                BakeryLoadingProduct::create([
                    'loading_id'   => $loading->id,
                    'product_id'   => $product_id,
                    'unit_cost'    => $request->table_unit_cost[$key],
                    'qty'          => $qty,
                    'total_amount' => $request->table_total_due[$key],
                ]);
            }

            if ($added == 0) {
                DB::rollback();
                $output = [
                    'success' => false,
                    'tab'     => 'loading',
                    'msg'     => __('bakery::lang.you_must_load_atleast_one_product'),
                    'data'    => $loading,
                ];
                return redirect()->back()->with('status', $output);

            }

            $output = [
                'success' => true,
                'tab'     => 'loading',
                'msg'     => __('lang_v1.success'),
                'data'    => $loading,
            ];

            DB::commit();

            if (request()->ajax()) {
                return $output;
            }

            if ($request->input('save_and_print') === '1') {
                return redirect()->to(action('\Modules\Bakery\Http\Controllers\BakeryLoadingController@show', [$loading->id]) . '?print=1');
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab'     => 'loading',
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
    // public function show($id)
    // {
    //     $business_id = request()->session()->get('business.id');
    //     $data        = BakeryLoading::leftjoin('users', 'bakery_loadings.created_by', 'users.id')
    //         ->leftjoin('bakery_fleets', 'bakery_fleets.id', 'bakery_loadings.vehicle_id')
    //         ->leftjoin('bakery_drivers', 'bakery_drivers.id', 'bakery_loadings.driver_id')
    //         ->leftjoin('bakery_products', 'bakery_products.id', 'bakery_loadings.product_id')
    //         ->leftjoin('bakery_routes', 'bakery_routes.id', 'bakery_loadings.route_id')
    //         ->where('bakery_loadings.id', $id)
    //         ->select([
    //             'bakery_loadings.*',
    //             'users.username',
    //             'bakery_fleets.vehicle_number',
    //             'bakery_drivers.driver_name',
    //             'bakery_products.name as product_name',
    //             'bakery_routes.route as route_name',
    //         ])->first();

    //     return view('bakery::bakery_loading.show_loading')->with(compact('data'));
    // }

    public function show($id)
    {
        $business_id = $this->getBusinessId();
        $data        = BakeryLoading::leftjoin('users', 'bakery_loadings.created_by', 'users.id')
            ->leftjoin('bakery_fleets', 'bakery_fleets.id', 'bakery_loadings.vehicle_id')
            ->leftjoin('bakery_drivers', 'bakery_drivers.id', 'bakery_loadings.driver_id')
            ->leftjoin('bakery_routes', 'bakery_routes.id', 'bakery_loadings.route_id')
            ->where('bakery_loadings.id', $id)
            ->select([
                'bakery_loadings.*',
                'users.username',
                'bakery_fleets.vehicle_number',
                'bakery_drivers.driver_name',
                'bakery_routes.route as route_name',
            ])->first();

        // Get products only from bakery_loading_products for this loading
        $products = BakeryLoadingProduct::leftjoin('bakery_products', 'bakery_products.id', 'bakery_loading_products.product_id')
            ->where('bakery_loading_products.loading_id', $id)
            ->select([
                'bakery_products.name as product_name',
                'bakery_loading_products.*',
            ])
            ->get();

        if (request()->boolean('print')) {
            return view('bakery::bakery_loading.print_loading')->with(compact('data', 'products'));
        }

        return view('bakery::bakery_loading.show_loading')->with(compact('data', 'products'));
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

        $business           = Business::find($business_id);
        $currency_precision = $business->currency_precision;
        $quantity_precision = $business->quantity_precision;

        return view('bakery::bakery_loading.edit_loading')->with(compact('fleet', 'drivers', 'products', 'routes', 'loading', 'currency_precision', 'quantity_precision'));

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
                $qty = isset($request->table_qty[$key]) ? $request->table_qty[$key] : null;
                if ($qty === null || $qty === '' || floatval($qty) <= 0) {
                    continue;
                }
                $added++;
                $product = BakeryLoadingProduct::create([
                    'loading_id'   => $id,
                    'product_id'   => $product_id,
                    'unit_cost'    => $request->table_unit_cost[$key],
                    'qty'          => $qty,
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
