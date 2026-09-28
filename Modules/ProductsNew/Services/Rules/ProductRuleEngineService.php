<?php
namespace Modules\ProductsNew\Services\Rules;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class ProductRuleEngineService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function rules(){ $q=DB::table('products_new_rules')->orderBy('rule_group')->orderBy('name'); $this->guard->applyBusiness($q); return $q->paginate(50); }
    public function store(array $data): int { $insert=['business_id'=>$this->guard->businessId(),'name'=>$data['name']??'Rule','rule_group'=>$data['rule_group']??'general','condition_json'=>json_encode($data['conditions']??[]),'action_json'=>json_encode($data['actions']??[]),'severity'=>$data['severity']??'warning','is_active'=>!empty($data['is_active']),'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]; return (int) DB::table('products_new_rules')->insertGetId($insert); }
    public function validateProduct(int $productId): array
    {
        $product=DB::table('products')->where('id',$productId)->first(); if(!$product){ return ['ok'=>false,'messages'=>[['type'=>'error','message'=>'Product not found']]]; }
        $messages=[];
        if(empty($product->sku)){ $messages[]=['type'=>'warning','message'=>'SKU is missing.']; }
        if(empty($product->barcode)){ $messages[]=['type'=>'warning','message'=>'Barcode is missing.']; }
        if((float)($product->alert_quantity ?? 0)<=0 && !empty($product->enable_stock)){ $messages[]=['type'=>'info','message'=>'Reorder level is not configured.']; }
        return ['ok'=>count(array_filter($messages,fn($m)=>$m['type']==='error'))===0,'messages'=>$messages];
    }
}
