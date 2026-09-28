<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Sale;use Modules\EggManagement\Models\Grade;use Modules\EggManagement\Integrations\CustomerGateway;use Modules\EggManagement\Integrations\ProductGateway;use Modules\EggManagement\Integrations\LocationStoreGateway;use Modules\EggManagement\Services\SalesService;
class SaleController extends BaseController
{
    public function index(){return view('egg::sales.index',['rows'=>$this->scope(Sale::query())->latest('sale_date')->latest('id')->paginate(50)]);}
    public function create(CustomerGateway $customers,ProductGateway $products,LocationStoreGateway $scope){return view('egg::sales.create',['customers'=>$customers->options(),'products'=>$products->options(),'locations'=>$scope->locations(),'stores'=>$scope->stores(),'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get()]);}
    public function store(Request $r,SalesService $svc,LocationStoreGateway $scope){$d=$r->validate(['sale_date'=>'required|date','customer_id'=>'nullable|integer','location_id'=>'nullable|integer','store_id'=>'nullable|integer','payment_status'=>'required|in:paid,partial,due','discount'=>'nullable|numeric|min:0','note'=>'nullable|max:1000','lines'=>'required|array','lines.*.grade_id'=>'required|integer','lines.*.product_id'=>'nullable|integer','lines.*.pieces'=>'required|integer|min:0','lines.*.unit_price'=>'required|numeric|min:0']);$scope->assertScope($d['location_id']??null,$d['store_id']??null);$svc->create($d);return redirect()->route('egg.sales.index')->with('success','Egg sale approved. Stock and Finance outbox updated.');}
}
