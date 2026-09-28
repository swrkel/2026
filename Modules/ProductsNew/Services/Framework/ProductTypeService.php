<?php
namespace Modules\ProductsNew\Services\Framework;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class ProductTypeService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function list(){ $q=DB::table('products_new_product_types')->orderBy('name'); $this->guard->applyBusiness($q); return $q->paginate(25); }
    public function find(int $id){ $q=DB::table('products_new_product_types')->where('id',$id); $this->guard->applyBusiness($q); return $q->first(); }
    public function fields(int $typeId){ return DB::table('products_new_custom_fields')->where('product_type_id',$typeId)->orderBy('sort_order')->get(); }
    public function store(array $data): int
    {
        $data['business_id']=$this->guard->businessId(); $data['created_by']=auth()->id(); $data['created_at']=now(); $data['updated_at']=now();
        return (int) DB::table('products_new_product_types')->insertGetId($data);
    }
}
