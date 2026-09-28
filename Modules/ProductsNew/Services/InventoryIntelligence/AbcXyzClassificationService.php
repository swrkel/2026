<?php
namespace Modules\ProductsNew\Services\InventoryIntelligence;
use Modules\ProductsNew\Entities\ProductClassification;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class AbcXyzClassificationService
{
    public function summary(array $filters=[]): array { $businessId=ProductsNewTenantGuard::businessId($filters['business_id']??null); $base=ProductClassification::where('business_id',$businessId); return ['a_items'=>(clone $base)->where('abc_class','A')->count(),'b_items'=>(clone $base)->where('abc_class','B')->count(),'c_items'=>(clone $base)->where('abc_class','C')->count(),'x_items'=>(clone $base)->where('xyz_class','X')->count(),'y_items'=>(clone $base)->where('xyz_class','Y')->count(),'z_items'=>(clone $base)->where('xyz_class','Z')->count()]; }
    public function classifications(array $filters=[]) { $businessId=ProductsNewTenantGuard::businessId($filters['business_id']??null); return ProductClassification::where('business_id',$businessId)->latest('classification_date')->paginate($filters['per_page']??25); }
    public function classify(array $data): ProductClassification { $businessId=ProductsNewTenantGuard::businessId($data['business_id']??null); $annual=(float)($data['annual_consumption_value']??0); $var=(float)($data['demand_variability']??0); $abc=$annual>=1000000?'A':($annual>=250000?'B':'C'); $xyz=$var<=10?'X':($var<=30?'Y':'Z'); return ProductClassification::updateOrCreate(['business_id'=>$businessId,'location_id'=>$data['location_id']??null,'product_id'=>$data['product_id']],['abc_class'=>$abc,'xyz_class'=>$xyz,'combined_class'=>$abc.$xyz,'annual_consumption_value'=>$annual,'demand_variability'=>$var,'movement_rank'=>$data['movement_rank']??null,'classification_date'=>now()->toDateString(),'payload'=>$data['payload']??null,'created_by'=>auth()->id()]); }
}
