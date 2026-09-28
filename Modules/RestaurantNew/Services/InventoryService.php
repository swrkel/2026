<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\Ingredient;
use Modules\RestaurantNew\Entities\InventoryBalance;
use Modules\RestaurantNew\Entities\OrderItem;
use Modules\RestaurantNew\Entities\Recipe;
use Modules\RestaurantNew\Entities\StockMovement;
class InventoryService
{
 public function __construct(private AuditService $audit){}
 public function consumeForOrderItem(OrderItem $orderItem): void
 {
  if($orderItem->stock_posted_at) return;
  DB::transaction(function() use($orderItem){
   $locked=OrderItem::withoutGlobalScopes()->whereKey($orderItem->id)->lockForUpdate()->first();
   if(!$locked || $locked->stock_posted_at) return;
   $order=$locked->order()->withoutGlobalScopes()->first();
   $recipe=Recipe::withoutGlobalScopes()->where('business_id',$locked->business_id)->where('menu_item_id',$locked->menu_item_id)->where('is_active',true)->with('lines')->first();
   if($recipe){
    $yield=max(0.0001,(float)$recipe->yield_qty);
    foreach($recipe->lines as $line){
     $qty=((float)$line->quantity/$yield)*(float)$locked->quantity*(1+((float)$line->waste_percent/100));
     $balance=InventoryBalance::withoutGlobalScopes()->where('business_id',$locked->business_id)->where('location_id',$order?->location_id)->where('ingredient_id',$line->ingredient_id)->lockForUpdate()->first();
     if(!$balance){$unitCost=(float)Ingredient::withoutGlobalScopes()->where('business_id',$locked->business_id)->whereKey($line->ingredient_id)->value('unit_cost');$balance=InventoryBalance::withoutGlobalScopes()->create(['business_id'=>$locked->business_id,'location_id'=>$order?->location_id,'ingredient_id'=>$line->ingredient_id,'quantity'=>0,'average_cost'=>$unitCost]);}
     $before=(float)$balance->quantity;$after=$before-$qty;$cost=(float)$balance->average_cost;
     $balance->update(['quantity'=>$after]);
     StockMovement::withoutGlobalScopes()->firstOrCreate(['source_type'=>'restnew_order_item','source_id'=>$locked->id,'ingredient_id'=>$line->ingredient_id],['business_id'=>$locked->business_id,'location_id'=>$order?->location_id,'movement_type'=>'kitchen_consumption','quantity'=>-$qty,'unit_cost'=>$cost,'value'=>-$qty*$cost,'reference_no'=>$order?->order_no,'created_by'=>auth()->id()]);
    }
   }
   $locked->update(['stock_posted_at'=>now()]);
  },3);
 }
 public function adjust(int $businessId,?int $locationId,int $ingredientId,float $quantity,string $reason): InventoryBalance
 {
  return DB::transaction(function() use($businessId,$locationId,$ingredientId,$quantity,$reason){
   $balance=InventoryBalance::withoutGlobalScopes()->where('business_id',$businessId)->where('location_id',$locationId)->where('ingredient_id',$ingredientId)->lockForUpdate()->first();
   if(!$balance){$unitCost=(float)Ingredient::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($ingredientId)->value('unit_cost');$balance=InventoryBalance::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$locationId,'ingredient_id'=>$ingredientId,'quantity'=>0,'average_cost'=>$unitCost]);}
   $balance->quantity=(float)$balance->quantity+$quantity;$balance->save();
   StockMovement::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$locationId,'ingredient_id'=>$ingredientId,'movement_type'=>'manual_adjustment','quantity'=>$quantity,'unit_cost'=>$balance->average_cost,'value'=>$quantity*(float)$balance->average_cost,'source_type'=>'manual_adjustment','source_id'=>null,'reference_no'=>'ADJ-'.now()->format('YmdHis').'-'.auth()->id(),'notes'=>$reason,'created_by'=>auth()->id()]);
   $this->audit->record('stock.adjusted','ingredient',$ingredientId,[],['quantity'=>$quantity,'reason'=>$reason]);
   return $balance->fresh();
  },3);
 }
 public function reverseForOrderItem(OrderItem $orderItem,string $reason): void
 {
  DB::transaction(function()use($orderItem,$reason){
   $movements=StockMovement::withoutGlobalScopes()->where('business_id',$orderItem->business_id)->where('source_type','restnew_order_item')->where('source_id',$orderItem->id)->lockForUpdate()->get();
   foreach($movements as $movement){
    $exists=StockMovement::withoutGlobalScopes()->where('source_type','restnew_order_item_reversal')->where('source_id',$movement->id)->where('ingredient_id',$movement->ingredient_id)->exists();
    if($exists)continue;
    $qty=abs((float)$movement->quantity);$balance=InventoryBalance::withoutGlobalScopes()->where('business_id',$movement->business_id)->where('location_id',$movement->location_id)->where('ingredient_id',$movement->ingredient_id)->lockForUpdate()->first();
    if(!$balance)$balance=InventoryBalance::withoutGlobalScopes()->create(['business_id'=>$movement->business_id,'location_id'=>$movement->location_id,'ingredient_id'=>$movement->ingredient_id,'quantity'=>0,'average_cost'=>$movement->unit_cost]);
    $balance->increment('quantity',$qty);
    StockMovement::withoutGlobalScopes()->create(['business_id'=>$movement->business_id,'location_id'=>$movement->location_id,'ingredient_id'=>$movement->ingredient_id,'movement_type'=>'kitchen_consumption_reversal','quantity'=>$qty,'unit_cost'=>$movement->unit_cost,'value'=>$qty*(float)$movement->unit_cost,'source_type'=>'restnew_order_item_reversal','source_id'=>$movement->id,'reference_no'=>$movement->reference_no,'notes'=>$reason,'created_by'=>auth()->id()]);
   }
   OrderItem::withoutGlobalScopes()->whereKey($orderItem->id)->update(['stock_posted_at'=>null]);
  },3);
 }

}
