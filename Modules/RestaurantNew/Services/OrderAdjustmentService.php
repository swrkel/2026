<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\{DiningTable,ManagerApproval,Order,OrderAdjustment,OrderItem};
class OrderAdjustmentService
{
    public function __construct(private TenantScopeService $scope,private InventoryService $inventory,private AuditService $audit){}
    public function voidItem(Order $order,OrderItem $item,string $reason): Order
    {
        $businessId=$this->scope->businessId();$this->scope->assertBusinessRecord($order,$businessId);abort_unless((int)$item->order_id===(int)$order->id&&(int)$item->business_id===$businessId,404);
        if($order->payment_status==='paid'||in_array($order->status,['cancelled','completed'],true))throw ValidationException::withMessages(['item'=>'Items cannot be voided on this order.']);
        if($item->status==='voided')return $order->fresh(['items.modifiers']);
        return DB::transaction(function()use($order,$item,$reason,$businessId){
            $locked=OrderItem::withoutGlobalScopes()->where('business_id',$businessId)->where('order_id',$order->id)->whereKey($item->id)->lockForUpdate()->firstOrFail();
            if($locked->status==='voided')return $order->fresh(['items.modifiers','tickets','payments']);
            if(in_array($locked->status,['cancelled'],true))throw ValidationException::withMessages(['item'=>'This item cannot be voided.']);
            $freshOrder=Order::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if($freshOrder->payment_status==='paid'||in_array($freshOrder->status,['cancelled','completed'],true))throw ValidationException::withMessages(['item'=>'Items cannot be voided on this order.']);
            $before=$locked->toArray();
            if($locked->stock_posted_at)$this->inventory->reverseForOrderItem($locked,'Item void: '.$reason);
            $locked->update(['status'=>'voided','voided_by'=>auth()->id(),'voided_at'=>now(),'void_reason'=>$reason]);
            OrderAdjustment::withoutGlobalScopes()->create(['business_id'=>$businessId,'order_id'=>$order->id,'order_item_id'=>$locked->id,'adjustment_type'=>'void_item','amount'=>-(float)$locked->line_total,'reason'=>$reason,'before_json'=>$before,'after_json'=>$locked->fresh()->toArray(),'requested_by'=>auth()->id(),'approved_by'=>auth()->id(),'approved_at'=>now()]);
            ManagerApproval::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$order->location_id,'approval_type'=>'void_item','entity_type'=>'order_item','entity_id'=>$locked->id,'status'=>'approved','reason'=>$reason,'payload_json'=>['order_id'=>$order->id,'amount'=>$locked->line_total],'requested_by'=>auth()->id(),'approved_by'=>auth()->id(),'approved_at'=>now()]);
            $this->recalculate($order);$this->audit->record('order_item.voided','order_item',$locked->id,$before,['reason'=>$reason]);return $order->fresh(['items.modifiers','tickets','payments']);
        },3);
    }
    public function transferTable(Order $order,int $tableId,string $reason): Order
    {
        $businessId=$this->scope->businessId();$this->scope->assertBusinessRecord($order,$businessId);if($order->order_type!=='dine_in'||in_array($order->status,['cancelled','completed'],true))throw ValidationException::withMessages(['table_id'=>'Only an open dine-in order can be transferred.']);
        return DB::transaction(function()use($order,$tableId,$reason,$businessId){
            $lockedOrder=Order::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();$newTable=DiningTable::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($tableId)->where('is_active',true)->lockForUpdate()->first();
            if(!$newTable||$newTable->status!=='available'||($newTable->location_id&&(int)$newTable->location_id!==(int)$lockedOrder->location_id))throw ValidationException::withMessages(['table_id'=>'The target table is unavailable.']);
            $oldId=$lockedOrder->table_id;if($oldId)DiningTable::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($oldId)->update(['status'=>'available']);$newTable->update(['status'=>'occupied']);$lockedOrder->update(['table_id'=>$newTable->id]);
            OrderAdjustment::withoutGlobalScopes()->create(['business_id'=>$businessId,'order_id'=>$lockedOrder->id,'adjustment_type'=>'table_transfer','reason'=>$reason,'before_json'=>['table_id'=>$oldId],'after_json'=>['table_id'=>$newTable->id],'requested_by'=>auth()->id(),'approved_by'=>auth()->id(),'approved_at'=>now()]);$this->audit->record('order.table_transferred','order',$lockedOrder->id,['table_id'=>$oldId],['table_id'=>$newTable->id,'reason'=>$reason]);return $lockedOrder->fresh();
        },3);
    }
    private function recalculate(Order $order): void
    {
        $items=$order->items()->whereNotIn('status',['voided','cancelled'])->get();$subtotal=(float)$items->sum(fn($i)=>(float)$i->unit_price*(float)$i->quantity+(float)$i->modifier_total);$lineDiscount=(float)$items->sum('discount_amount');$ruleDiscount=(float)$order->discountUsages()->sum('discount_amount');$discount=$lineDiscount+$ruleDiscount;$tax=(float)$items->sum('tax_amount');$service=(float)$items->sum('service_charge_amount');$total=max(0,$subtotal-$discount+$tax+$service+(float)($order->delivery_fee??0)+(float)$order->rounding_amount);$order->update(['subtotal'=>$subtotal,'discount_total'=>$discount,'tax_total'=>$tax,'service_charge_total'=>$service,'total_amount'=>$total,'balance_amount'=>max(0,$total-(float)$order->paid_amount)]);
    }
}
