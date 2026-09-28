<?php
namespace Modules\ProductsNew\Services\Framework;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class ProductTemplateService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}
    public function list(){ $q=DB::table('products_new_templates')->orderBy('name'); $this->guard->applyBusiness($q); return $q->paginate(25); }
    public function store(array $data): int { return (int) DB::table('products_new_templates')->insertGetId(['business_id'=>$this->guard->businessId(),'name'=>$data['name']??'Template','product_type_id'=>$data['product_type_id']??null,'template_json'=>json_encode($data['template']??$data),'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]); }
    public function apply(int $id): array { $row=DB::table('products_new_templates')->where('id',$id)->first(); return $row?json_decode($row->template_json,true):[]; }
}
