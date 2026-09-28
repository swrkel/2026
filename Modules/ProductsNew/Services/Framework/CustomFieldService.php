<?php
namespace Modules\ProductsNew\Services\Framework;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class CustomFieldService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function list(){ $q=DB::table('products_new_custom_fields as f')->leftJoin('products_new_product_types as t','f.product_type_id','=','t.id')->select('f.*','t.name as product_type_name')->orderBy('f.product_type_id')->orderBy('f.sort_order'); $this->guard->applyBusiness($q,'f.business_id'); return $q->paginate(50); }
    public function store(array $data): int { $data['business_id']=$this->guard->businessId(); $data['created_by']=auth()->id(); $data['created_at']=now(); $data['updated_at']=now(); return (int) DB::table('products_new_custom_fields')->insertGetId($data); }
    public function values(int $productId): array { return DB::table('products_new_custom_field_values')->where('product_id',$productId)->pluck('field_value','field_key')->toArray(); }
    public function saveValues(int $productId, array $fields): void { foreach($fields as $key=>$value){ DB::table('products_new_custom_field_values')->updateOrInsert(['product_id'=>$productId,'field_key'=>$key],['field_value'=>is_array($value)?json_encode($value):$value,'updated_by'=>auth()->id(),'updated_at'=>now(),'created_at'=>now()]); } }
}
