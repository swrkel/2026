<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Adjustment;use Modules\EggManagement\Models\Grade;use Modules\EggManagement\Services\AdjustmentService;use Modules\EggManagement\Integrations\LocationStoreGateway;
class AdjustmentController extends BaseController
{
    public function index(){return view('egg::adjustments.index',['rows'=>$this->scope(Adjustment::query())->latest('adjustment_date')->latest('id')->paginate(50)]);}
    public function create(LocationStoreGateway $scope){return view('egg::adjustments.create',['locations'=>$scope->locations(),'stores'=>$scope->stores(),'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get()]);}
    public function store(Request $r,AdjustmentService $svc,LocationStoreGateway $scope){$d=$r->validate(['adjustment_date'=>'required|date','location_id'=>'nullable|integer','store_id'=>'nullable|integer','reason'=>'required|max:150','note'=>'nullable|max:1000','lines'=>'required|array','lines.*.grade_id'=>'required|integer','lines.*.pieces'=>'required|integer','lines.*.unit_cost'=>'nullable|numeric|min:0']);$scope->assertScope($d['location_id']??null,$d['store_id']??null);$svc->create($d);return redirect()->route('egg.adjustments.index')->with('success','Stock adjustment approved.');}
}
