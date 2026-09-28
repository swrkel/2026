<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Category;
use App\User;
use App\Utils\ModuleUtil;
;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\LeadsNew\Entities\Lead;
use Illuminate\Support\Facades\DB;
use Modules\LeadsNew\Entities\Town;
use Illuminate\Database\Query\JoinClause;

class AjaxController extends Controller
{
    protected $moduleUtil;
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil =  $moduleUtil;
    }

    public function ajax_mobile(Request $request)
    {
        if ($request->ajax()) {
            if ($request->post('postData')) {

                $result = DB::table('leads_new')
                    ->join('leads_new_categories', 'leads_new_categories.id', '=', 'leads_new.category_id')
                    ->where('leads_new.mobile_no_1', $request->post('postData'))
                    ->orWhere('leads_new.mobile_no_2', $request->post('postData'))
                    ->get();

                if (count($result) > 0) {
                    return $result[0];
                } else {
                    return false;
                }
            }else{
                return false;
            }
        }
    }
    
    public function ajax_town(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json([]);
        }

        $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
        $district_value = $request->post('postData');

        $query = Town::query()
            ->select('id', 'name', 'district_id')
            ->where('business_id', $business_id)
            ->orderBy('name');

        if (!empty($district_value)) {
            $district_ids = [];

            if (is_numeric($district_value)) {
                $district_ids[] = (int) $district_value;
            }

            $matched_district_ids = DB::table('districts')
                ->where('business_id', $business_id)
                ->where(function ($q) use ($district_value) {
                    $q->where('id', $district_value)
                        ->orWhere('name', $district_value);
                })
                ->pluck('id')
                ->toArray();

            $district_ids = array_values(array_unique(array_filter(array_merge($district_ids, $matched_district_ids))));

            if (!empty($district_ids)) {
                $query->whereIn('district_id', $district_ids);
            }
        }

        return response()->json($query->get());
    }
    
     public function ajax_district(Request $request){
        if($request->ajax()){
            if($request->post('postData')){
                $business_id = $request->session()->get('business.id') ?? $request->session()->get('user.business_id');
                $districts = DB::table('districts')
                    ->select('name', 'id')
                    ->where('business_id', $business_id)
                    ->where('country_id', $request->post('postData'))
                    ->orderBy('name')
                    ->get();
                
                return $districts;
            }
        }
    }
}
