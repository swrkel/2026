<?php
namespace Modules\ProductsNew\Services\InventoryIntelligence;
use Modules\ProductsNew\Entities\ProductCostSnapshot;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
class ProductCostEngineService
{
    public function dashboard(array $filters=[]): array { $businessId=ProductsNewTenantGuard::businessId($filters['business_id']??null); $base=ProductCostSnapshot::where('business_id',$businessId); return ['snapshot_count'=>(clone $base)->count(),'avg_margin'=>round((float)(clone $base)->avg('margin_percentage'),2),'avg_landed_cost'=>round((float)(clone $base)->avg('landed_cost'),4),'highest_replacement_cost'=>round((float)(clone $base)->max('replacement_cost'),4)]; }
    public function snapshots(array $filters=[]) { $businessId=ProductsNewTenantGuard::businessId($filters['business_id']??null); return ProductCostSnapshot::where('business_id',$businessId)->latest('snapshot_date')->paginate($filters['per_page']??25); }
    public function captureSnapshot(array $data): ProductCostSnapshot { $businessId=ProductsNewTenantGuard::businessId($data['business_id']??null); $last=(float)($data['last_cost']??0); $sell=(float)($data['selling_price']??0); $landed=(float)($data['landed_cost']??$last); $margin=$sell>0?(($sell-$landed)/$sell)*100:0; return ProductCostSnapshot::create(['business_id'=>$businessId,'location_id'=>$data['location_id']??null,'product_id'=>$data['product_id'],'variation_id'=>$data['variation_id']??null,'snapshot_date'=>$data['snapshot_date']??now()->toDateString(),'last_cost'=>$last,'average_cost'=>$data['average_cost']??$last,'moving_average_cost'=>$data['moving_average_cost']??$last,'standard_cost'=>$data['standard_cost']??$last,'replacement_cost'=>$data['replacement_cost']??$last,'landed_cost'=>$landed,'margin_percentage'=>round($margin,4),'payload'=>$data['payload']??null,'created_by'=>auth()->id()]); }
}
