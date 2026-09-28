<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\ProductMapping;use Modules\EggManagement\Models\Grade;use Modules\EggManagement\Integrations\ProductGateway;
class ProductMappingController extends BaseController
{
    public function index(ProductGateway $products){return view('egg::settings.product_mappings',['rows'=>$this->scope(ProductMapping::query())->latest('id')->get(),'grades'=>$this->scope(Grade::query())->where('active',1)->orderBy('sort_order')->get(),'products'=>$products->options(null,500)]);}
    public function store(Request $r){$d=$r->validate(['grade_id'=>'required|integer','product_id'=>'required|integer','pieces_per_unit'=>'required|integer|min:1']);abort_unless($this->scope(Grade::query())->where('id',$d['grade_id'])->exists(),422,'Invalid grade.');ProductMapping::updateOrCreate(['business_id'=>$this->context->businessId(),'grade_id'=>$d['grade_id'],'product_id'=>$d['product_id']],['pieces_per_unit'=>$d['pieces_per_unit']]);return back()->with('success','Products New mapping saved.');}
    public function destroy(ProductMapping $mapping){abort_unless($mapping->business_id==$this->context->businessId(),404);$mapping->delete();return back()->with('success','Product mapping removed.');}
}
