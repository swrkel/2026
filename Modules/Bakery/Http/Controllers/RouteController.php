<?php

namespace Modules\Bakery\Http\Controllers;

use App\Utils\Util;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Bakery\Entities\BakeryRoute;
use Yajra\DataTables\Facades\DataTables;

class RouteController extends Controller
{
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $routes = BakeryRoute::leftJoin('users', 'bakery_routes.added_by', '=', 'users.id')
                ->select([
                    'bakery_routes.*',
                    'users.username as added_by_name',
                ])
                ->orderByDesc('bakery_routes.id');

            return DataTables::of($routes)
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
                        if (auth()->user()->can('fleet.drivers.edit')) {
                            $html .= '<li><a href="#" data-href="' . action('\Modules\Bakery\Http\Controllers\RouteController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        }
                        // $html .= '<li class="divider"></li>';
                        // $html .= '<li><a href="' . action('\Modules\Bakery\Http\Controllers\DriverController@show', [$row->id]) . '?tab=ledger" class=""><i class="fa fa-anchor"></i> ' . __("lang_v1.ledger") . '</a></li>';

                        return $html;
                    }
                )
                ->editColumn('route', function ($row) {
                    return $row->route;
                })
                ->editColumn('added_by', function ($row) {
                    return $row->added_by_name ?? $row->added_by;
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('bakery::routes.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $data = $request->except('_token');
            $data['date'] = $this->commonUtil->uf_date($data['date']);
            $data['added_by']  = auth()->user()->id;
            $submitted_routes = [];

            foreach ((array) $request->route as $route_name) {
                $normalized_route = trim((string) $route_name);

                if ($normalized_route === '') {
                    continue;
                }

                $normalized_route_key = mb_strtolower($normalized_route);
                $duplicate_in_request = in_array($normalized_route_key, $submitted_routes, true);
                $duplicate_in_db = BakeryRoute::whereRaw('LOWER(route) = ?', [$normalized_route_key])->exists();

                if ($duplicate_in_request || $duplicate_in_db) {
                    $output = [
                        'success' => false,
                        'tab' => 'routes',
                        'msg' => __('bakery::lang.route_check')
                    ];

                    return redirect()->action('\Modules\Bakery\Http\Controllers\BakeryController@settings')->with('status', $output);
                }

                $submitted_routes[] = $normalized_route_key;
                $data['route'] = $normalized_route;
                $route = BakeryRoute::create($data);
            }
            $output = [
                'success' => true,
                'tab' => 'routes',
                'msg' => __('lang_v1.success'),
                'data' => $route
            ];
            
            if (request()->ajax()) {
                return $output;
            }
            
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'routes',
                'msg' => __('messages.something_went_wrong')
            ];
            
            if (request()->ajax()) {
                return $output;
            }
        }

        return redirect()->action('\Modules\Bakery\Http\Controllers\BakeryController@settings')->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show()
    {

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $route = BakeryRoute::find($id);

        return view('bakery::routes.edit')->with(compact(
            'route'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->except('_token', '_method');
            $normalized_route = trim((string) $request->route);
            $duplicate_route = BakeryRoute::whereRaw('LOWER(route) = ?', [mb_strtolower($normalized_route)])
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicate_route) {
                $output = [
                    'success' => false,
                    'tab' => 'routes',
                    'msg' => __('bakery::lang.route_check')
                ];

                return redirect()->action('\Modules\Bakery\Http\Controllers\BakeryController@settings')->with('status', $output);
            }

            $data['date'] = $this->commonUtil->uf_date($data['date']);
            $data['route'] = $normalized_route;
            BakeryRoute::where('id', $id)->update($data);

            $output = [
                'success' => true,
                'tab' => 'routes',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'routes',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('\Modules\Bakery\Http\Controllers\BakeryController@settings')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {

    }
}
