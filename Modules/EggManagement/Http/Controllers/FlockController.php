<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Flock;use Modules\EggManagement\Integrations\LocationStoreGateway;
class FlockController extends BaseController
{
    public function index(){return view('egg::flocks.index',['rows'=>$this->scope(Flock::query())->latest('id')->paginate(50)]);}
    public function create(LocationStoreGateway $scope){return view('egg::flocks.create',['locations'=>$scope->locations(),'stores'=>$scope->stores()]);}
    public function store(Request $r,LocationStoreGateway $scope){$d=$r->validate(['flock_code'=>'required|max:50','name'=>'required|max:100','breed'=>'nullable|max:100','started_on'=>'nullable|date','bird_count'=>'nullable|integer|min:0','location_id'=>'nullable|integer','store_id'=>'nullable|integer','note'=>'nullable|max:1000']);$scope->assertScope($d['location_id']??null,$d['store_id']??null);Flock::create(array_merge($this->context->scopePayload(['location_id'=>$d['location_id']??null,'store_id'=>$d['store_id']??null]),$d,['active'=>1,'created_by'=>$this->context->userId()]));return redirect()->route('egg.flocks.index')->with('success','Flock created.');}
}
