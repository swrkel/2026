<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Collection;use Modules\EggManagement\Models\Flock;use Modules\EggManagement\Services\ProductionService;use Modules\EggManagement\Integrations\LocationStoreGateway;
class ProductionController extends BaseController
{
    public function index(){return view('egg::production.index',['rows'=>$this->scope(Collection::query())->latest('collection_date')->latest('id')->paginate(50)]);}
    public function create(LocationStoreGateway $scope){return view('egg::production.create',['flocks'=>$this->scope(Flock::query())->where('active',1)->orderBy('name')->get(),'locations'=>$scope->locations(),'stores'=>$scope->stores()]);}
    public function store(Request $r,ProductionService $svc,LocationStoreGateway $scope){$d=$r->validate(['collection_date'=>'required|date','flock_id'=>'nullable|integer','location_id'=>'nullable|integer','store_id'=>'nullable|integer','shift'=>'nullable|max:30','total_pieces'=>'required|integer|min:1','broken_pieces'=>'nullable|integer|min:0','dirty_pieces'=>'nullable|integer|min:0','rejected_pieces'=>'nullable|integer|min:0','note'=>'nullable|max:1000']);$scope->assertScope($d['location_id']??null,$d['store_id']??null);$svc->create($d);return redirect()->route('egg.production.index')->with('success','Egg collection saved.');}
}
