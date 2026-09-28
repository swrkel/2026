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
use Illuminate\Support\Facades\Schema;
use Modules\Distribution\Entities\Helper;
use Modules\Distribution\Entities\Distribution_provinces;
use Modules\Distribution\Entities\Distribution_districts;
use Modules\Distribution\Entities\Distribution_areas;
use Yajra\DataTables\Facades\DataTables;

class DistributionAreasController extends Controller
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

            $helpers = Distribution_areas::leftjoin('distribution_districts','distribution_areas.district_id','distribution_districts.id')
                ->leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')
                ->leftjoin('users', 'distribution_areas.added_by', 'users.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->select([
                    'distribution_areas.*',
                    'users.username as added_by_user',
                    'distribution_districts.name as district_name',
                    'distribution_provinces.name as province_name'
                ]);

            if (!empty(request()->province_id)) {
                $helpers->where('distribution_provinces.id', request()->province_id);
            }
            if (!empty(request()->district_id)) {
                $helpers->where('distribution_districts.id', request()->district_id);
            }
            
            return DataTables::of($helpers)
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
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionAreasController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionAreasController@destroy', [$row->id]) . '" class="delete_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        
                        return $html;
                    }
                )
                ->editColumn('date', '{{@format_date($created_at)}}')
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

        $provinces = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');
        $districts = Distribution_districts::leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')->where('distribution_provinces.business_id', $business_id)->pluck('distribution_districts.name', 'distribution_districts.id', 'distribution_districts.province_id');

        return view('distribution::settings.areas.create')->with(compact(
            'provinces','districts'
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
            $district = Distribution_districts::leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->where('distribution_districts.id', $request->input('district_id'))
                ->select('distribution_districts.*')
                ->first();
            if (!$district) {
                $output = ['success' => false, 'tab' => 'areas', 'msg' => 'Invalid district selected.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'areas'])->withInput();
            }

            $name = trim((string) $request->input('name'));
            if ($name === '') {
                $output = ['success' => false, 'tab' => 'areas', 'msg' => 'Area name is required.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'areas'])->withInput();
            }

            $duplicate = Distribution_areas::where('district_id', $district->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if ($duplicate) {
                $output = ['success' => false, 'tab' => 'areas', 'msg' => 'This area already exists for the selected district. Duplicate entries are not allowed.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'areas'])->withInput();
            }

            $data = [
                'name' => $name,
                'district_id' => $district->id,
                'added_by' => Auth::id(),
            ];
            if (Schema::hasColumn('distribution_areas', 'business_id')) {
                $data['business_id'] = $business_id;
            }
            Distribution_areas::create($data);

            $output = [
                'success' => true,
                'tab' => 'helpers',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'helpers',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status'=> $output,"page" => "areas"]);
    }

   

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $route = Distribution_areas::leftjoin('distribution_districts','distribution_areas.district_id','distribution_districts.id')
            ->leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')
            ->where('distribution_provinces.business_id', $business_id)
            ->where('distribution_areas.id', $id)
            ->select('distribution_areas.*')
            ->firstOrFail();


        $provinces = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');
        $districts = Distribution_districts::leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')->where('distribution_provinces.business_id', $business_id)->pluck('distribution_districts.name', 'distribution_districts.id', 'distribution_districts.province_id');


        return view('distribution::settings.areas.edit')->with(compact(
            'route','provinces','districts'
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
            $business_id = request()->session()->get('user.business_id');
            $district = Distribution_districts::leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->where('distribution_districts.id', $request->input('district_id'))
                ->select('distribution_districts.*')
                ->first();
            if (!$district) {
                $output = ['success' => false, 'tab' => 'areas', 'msg' => 'Invalid district selected.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'areas'])->withInput();
            }

            $name = trim((string) $request->input('name'));
            if ($name === '') {
                $output = ['success' => false, 'tab' => 'areas', 'msg' => 'Area name is required.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'areas'])->withInput();
            }

            $duplicate = Distribution_areas::where('district_id', $district->id)
                ->where('id', '!=', $id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if ($duplicate) {
                $output = ['success' => false, 'tab' => 'areas', 'msg' => 'This area already exists for the selected district. Duplicate entries are not allowed.'];
                return redirect()->back()->with(['status' => $output, 'page' => 'areas'])->withInput();
            }

            $data = [
                'name' => $name,
                'district_id' => $district->id,
            ];
            if (Schema::hasColumn('distribution_areas', 'business_id')) {
                $data['business_id'] = $business_id;
            }
            Distribution_areas::where('id', $id)->update($data);

            $output = [
                'success' => true,
                'tab' => 'helpers',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'helpers',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status'=> $output,"page" => "areas"]);
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
            $area = Distribution_areas::leftjoin('distribution_districts','distribution_areas.district_id','distribution_districts.id')
                ->leftjoin('distribution_provinces','distribution_districts.province_id','distribution_provinces.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->where('distribution_areas.id', $id)
                ->select('distribution_areas.id')
                ->firstOrFail();
            Distribution_areas::where('id', $area->id)->delete();

            
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
