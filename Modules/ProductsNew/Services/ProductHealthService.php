<?php
namespace Modules\ProductsNew\Services;
class ProductHealthService
{
    public function score($product, array $stock=[]): array
    {
        $checks=['image'=>!empty($product->image),'barcode'=>!empty($product->sku)||!empty($product->barcode),'category'=>!empty($product->category_id),'brand'=>!empty($product->brand_id),'tax'=>!empty($product->tax),'reorder_level'=>isset($product->alert_quantity)&&(float)$product->alert_quantity>0,'warranty'=>!empty($product->warranty_id),'locations'=>!empty($stock),'active_price'=>isset($product->default_sell_price)||isset($product->sell_price_inc_tax)];
        $weights=config('productsnew.health_score',[]); $score=0; $max=0; foreach($checks as $key=>$passed){$w=(int)($weights[$key]??10); $max+=$w; if($passed){$score+=$w;}}
        return ['score'=>$max>0?(int)round(($score/$max)*100):0,'checks'=>$checks,'missing'=>array_keys(array_filter($checks,fn($passed)=>!$passed))];
    }
}
