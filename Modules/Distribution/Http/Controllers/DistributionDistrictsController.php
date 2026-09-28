<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Account;
use Modules\Distribution\Entities\Core\AccountType;
use Modules\Distribution\Entities\Core\BusinessLocation;
use Modules\Distribution\Entities\Core\ExpenseCategory;
use Modules\Distribution\Entities\Core\Transaction;
use Modules\Distribution\Entities\Core\OpeningBalance;
use Modules\Distribution\Utils\ModuleUtil;
use Modules\Distribution\Utils\ProductUtil;
use Modules\Distribution\Utils\TransactionUtil;
use Modules\Distribution\Utils\Util;
use Modules\Distribution\Entities\Integration\Subscription;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Modules\Distribution\Entities\Distribution_districts;
use Modules\Distribution\Entities\Distribution_provinces;
use Modules\Distribution\Entities\Core\Contact;
use Yajra\DataTables\Facades\DataTables;
use Modules\Distribution\Entities\DistributionVehicles;

class DistributionDistrictsController extends Controller
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
        $business_id = request()->session()->get('user.business_id');
        // if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'fleet_module')) {
        //     abort(403, 'Unauthorized action.');
        // }
        if (request()->ajax()) {
            $fleets = Distribution_districts::leftjoin('users', 'distribution_districts.added_by', 'users.id')
                ->leftjoin('distribution_provinces', 'distribution_districts.province_id', 'distribution_provinces.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->select([
                    'distribution_districts.*',
                    'distribution_provinces.name as province_name',
                    'users.username as added_by_user'
                ]);

           
            if (!empty(request()->province_id)) {
                $fleets->where('distribution_districts.province_id', request()->province_id);
            }
            
            return DataTables::of($fleets)
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
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionDistrictsController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';

                        $html .= '<li><a href="#" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionDistrictsController@destroy', [$row->id]) . '" class="delete_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        $html .= '<li class="divider"></li>';
                        
                        return $html;
                    }
                )
                ->editColumn('date', '{{@format_date($created_at)}}')
                
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }


        $provinces = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');
       
        return view('distribution::settings.districts.index')->with(compact(
            'provinces'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        $provinces = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');
        
        return view('distribution::settings.districts.create')->with(compact(
            'provinces'
        ));
    }

    

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $inputs = $request->except('_token');
            $inputs['added_by'] = Auth::user()->id;
            
            $province = Distribution_provinces::where('business_id', $business_id)->find($request->input('province_id'));
            if (!$province) {
                $output = ['success' => false, 'msg' => 'Invalid province selected.'];
                return $this->settingsResponse($request, $output, true);
            }

            $name = trim((string) $request->input('name'));
            if ($name === '') {
                $output = ['success' => false, 'msg' => 'District name is required.'];
                return $this->settingsResponse($request, $output, true);
            }

            $duplicate = Distribution_districts::where('province_id', $province->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if ($duplicate) {
                $output = ['success' => false, 'msg' => 'This district already exists for the selected province. Duplicate entries are not allowed.'];
                return $this->settingsResponse($request, $output, true);
            }

            $inputs = [
                'name' => $name,
                'province_id' => $province->id,
                'added_by' => Auth::id(),
            ];

            if ($this->districtTableHasBusinessIdColumn()) {
                $inputs['business_id'] = $business_id;
            }

            Distribution_districts::create($inputs);

            $output = [
                'success' => true,
                'msg' => 'District saved successfully.'
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $this->settingsResponse($request, $output);
    }

    
    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $district = Distribution_districts::leftjoin('distribution_provinces', 'distribution_districts.province_id', 'distribution_provinces.id')
            ->where('distribution_provinces.business_id', $business_id)
            ->where('distribution_districts.id', $id)
            ->select('distribution_districts.*')
            ->firstOrFail();
        $provinces = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');

        return view('distribution::settings.districts.edit')->with(compact(
            'provinces','district'
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
            $province = Distribution_provinces::where('business_id', $business_id)->find($request->input('province_id'));
            if (!$province) {
                $output = ['success' => false, 'msg' => 'Invalid province selected.'];
                return $this->settingsResponse($request, $output, true);
            }

            $name = trim((string) $request->input('name'));
            if ($name === '') {
                $output = ['success' => false, 'msg' => 'District name is required.'];
                return $this->settingsResponse($request, $output, true);
            }

            $duplicate = Distribution_districts::where('province_id', $province->id)
                ->where('id', '!=', $id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->exists();
            if ($duplicate) {
                $output = ['success' => false, 'msg' => 'This district already exists for the selected province. Duplicate entries are not allowed.'];
                return $this->settingsResponse($request, $output, true);
            }

            $update_data = [
                'name' => $name,
                'province_id' => $province->id,
            ];

            if ($this->districtTableHasBusinessIdColumn()) {
                $update_data['business_id'] = $business_id;
            }

            Distribution_districts::where('id', $id)->update($update_data);

            $output = [
                'success' => true,
                'msg' => 'District saved successfully.'
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $this->settingsResponse($request, $output);
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
            Distribution_districts::leftjoin('distribution_provinces', 'distribution_districts.province_id', 'distribution_provinces.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->where('distribution_districts.id', $id)
                ->delete();
            $output = [
                'success' => true,
                'msg' => 'District saved successfully.'
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

    /**
     * Build response for normal form submit or AJAX modal submit.
     */
    private function settingsResponse(Request $request, array $output, $withInput = false)
    {
        $output['tab'] = 'districts';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output, !empty($output['success']) ? 200 : 422);
        }

        $redirect = redirect()->back()->with(['status' => $output, 'page' => 'districts']);
        return $withInput ? $redirect->withInput() : $redirect;
    }

    /**
     * Some older tenant databases do not have business_id on distribution_districts.
     * District tenant ownership is safely resolved through the selected province.
     */
    private function districtTableHasBusinessIdColumn()
    {
        try {
            return DB::getSchemaBuilder()->hasColumn('distribution_districts', 'business_id');
        } catch (\Exception $e) {
            return false;
        }
    }

}
