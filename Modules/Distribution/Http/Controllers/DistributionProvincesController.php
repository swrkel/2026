<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Transaction;
use Modules\Distribution\Entities\Core\TransactionPayment;
use Modules\Distribution\Utils\ModuleUtil;
use Modules\Distribution\Utils\ProductUtil;
use Modules\Distribution\Utils\TransactionUtil;
use Modules\Distribution\Utils\Util;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Distribution\Entities\Distribution_provinces;
use Yajra\DataTables\Facades\DataTables;

class DistributionProvincesController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
        $this->transactionUtil =  $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $provinces = Distribution_provinces::leftjoin('users', 'distribution_provinces.added_by', 'users.id')
                ->where('distribution_provinces.business_id',$business_id)
                ->select([
                    'distribution_provinces.*',
                    'users.username as added_by_user',
                ]);

            
            return DataTables::of($provinces)
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
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionProvincesController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionProvincesController@destroy', [$row->id]) . '" class="delete_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        
                        return $html;
                    }
                )
                ->editColumn('created_at', '{{@format_date($created_at)}}')
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
        $business_id = request()->session()->get('user.business_id');
        
        return view('distribution::settings.provinces.create')->with(compact(
            'business_id'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        try {
            $name = trim((string) $request->input('name'));
            if ($name === '') {
                $output = ['success' => false, 'tab' => 'provinces', 'msg' => 'Province name is required.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'provinces'])->withInput();
            }

            $exists = Distribution_provinces::where('business_id', $business_id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if ($exists) {
                $output = ['success' => false, 'tab' => 'provinces', 'msg' => 'This province already exists. Duplicate entries are not allowed.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'provinces'])->withInput();
            }

            Distribution_provinces::create([
                'name' => $name,
                'business_id' => $business_id,
                'added_by' => Auth::id(),
            ]);

            $output = ['success' => true, 'tab' => 'provinces', 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = ['success' => false, 'tab' => 'provinces', 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->back()->with(['status'=> $output,"page" => "provinces"]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $driver_dropdown = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');
        $view_type = request()->tab;
        $business_id = request()->session()->get('user.business_id');
        $driver = Distribution_provinces::where('business_id', $business_id)->findOrFail($id);

        return view('distribution::settings.provinces.show')->with(compact(
            'driver_dropdown',
            'view_type',
            'driver'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $driver = Distribution_provinces::where('business_id', $business_id)->findOrFail($id);

        return view('distribution::settings.provinces.edit')->with(compact(
            'driver'
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
        $business_id = request()->session()->get('user.business_id');
        try {
            $name = trim((string) $request->input('name'));
            if ($name === '') {
                $output = ['success' => false, 'tab' => 'provinces', 'msg' => 'Province name is required.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'provinces'])->withInput();
            }

            $duplicate = Distribution_provinces::where('business_id', $business_id)
                ->where('id', '!=', $id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if ($duplicate) {
                $output = ['success' => false, 'tab' => 'provinces', 'msg' => 'This province already exists. Duplicate entries are not allowed.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'provinces'])->withInput();
            }

            Distribution_provinces::where('business_id', $business_id)->where('id', $id)->update(['name' => $name]);

            $output = ['success' => true, 'tab' => 'provinces', 'msg' => __('lang_v1.success')];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = ['success' => false, 'tab' => 'provinces', 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->back()->with(['status'=> $output,"page" => "provinces"]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            Distribution_provinces::where('business_id', $business_id)->where('id', $id)->delete();

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
}
