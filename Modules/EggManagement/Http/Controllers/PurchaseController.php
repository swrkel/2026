<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Purchase;use Modules\EggManagement\Models\Grade;use Modules\EggManagement\Integrations\SupplierGateway;use Modules\EggManagement\Integrations\ProductGateway;use Modules\EggManagement\Integrations\LocationStoreGateway;use Modules\EggManagement\Services\PurchaseService;
class PurchaseController extends BaseController
{
    public function index(){return view('egg::purchases.index',['rows'=>$this->scope(Purchase::query())->latest('purchase_date')->latest('id')->paginate(50)]);}
    public function create(SupplierGateway $suppliers,ProductGateway $products,LocationStoreGateway $scope){return view('egg::purchases.create',['suppliers'=>$suppliers->options(),'products'=>$products->options(),'locations'=>$scope->locations(),'stores'=>$scope->stores(),'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get()]);}
    public function store(Request $r,PurchaseService $svc,LocationStoreGateway $scope){$d=$r->validate(['purchase_date'=>'required|date','supplier_id'=>'nullable|integer','supplier_reference'=>'nullable|max:100','location_id'=>'nullable|integer','store_id'=>'nullable|integer','payment_status'=>'required|in:paid,partial,due','discount'=>'nullable|numeric|min:0','note'=>'nullable|max:1000','lines'=>'required|array','lines.*.grade_id'=>'required|integer','lines.*.product_id'=>'nullable|integer','lines.*.pieces'=>'required|integer|min:0','lines.*.unit_cost'=>'required|numeric|min:0','lines.*.best_before'=>'nullable|date']);$scope->assertScope($d['location_id']??null,$d['store_id']??null);$svc->create($d);return redirect()->route('egg.purchases.index')->with('success','Egg purchase approved. Stock and Finance outbox updated.');}
}
