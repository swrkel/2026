<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DiningTable;
use Modules\RestaurantNew\Entities\KitchenTicket;
use Modules\RestaurantNew\Entities\KitchenTicketItem;
use Modules\RestaurantNew\Entities\Order;
use Modules\RestaurantNew\Entities\OrderItem;
use Modules\RestaurantNew\Entities\OrderStatusLog;
use Modules\RestaurantNew\Entities\PrintJob;
class KitchenService
{
 public function __construct(private NumberService $numbers,private InventoryService $inventory,private CollectionService $collection,private AuditService $audit,private TenantScopeService $scope){}
 public function send(Order $order): Order
 {
  $businessId=$this->scope->businessId();
  $this->scope->assertBusinessRecord($order,$businessId);
  return DB::transaction(function() use($order,$businessId){
   $order=Order::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();
   if($order->tickets()->withoutGlobalScopes()->exists()) return $order;
   foreach($order->items->where('status','pending')->groupBy(fn($item)=>(int)($item->station_id??0)) as $stationId=>$items){
    $ticket=KitchenTicket::withoutGlobalScopes()->create(['business_id'=>$order->business_id,'location_id'=>$order->location_id,'order_id'=>$order->id,'station_id'=>$stationId?:null,'ticket_no'=>$this->numbers->next((int)$order->business_id,'kitchen_ticket','KOT-'),'ticket_type'=>$order->order_type,'status'=>'new']);
    foreach($items as $item){KitchenTicketItem::withoutGlobalScopes()->create(['business_id'=>$order->business_id,'ticket_id'=>$ticket->id,'order_item_id'=>$item->id,'item_name'=>$item->item_name,'quantity'=>$item->quantity,'notes'=>$item->notes,'status'=>'new']);$item->update(['status'=>'sent','sent_at'=>now()]);}
    PrintJob::withoutGlobalScopes()->create(['business_id'=>$order->business_id,'location_id'=>$order->location_id,'order_id'=>$order->id,'ticket_id'=>$ticket->id,'document_type'=>$order->order_type==='takeaway'?'takeaway_kitchen':'kot','status'=>'pending','payload'=>['ticket_no'=>$ticket->ticket_no],'created_by'=>auth()->id()]);
   }
   $from=$order->status;$order->update(['status'=>'in_kitchen','kitchen_status'=>'new','sent_at'=>now()]);
   OrderStatusLog::withoutGlobalScopes()->create(['business_id'=>$order->business_id,'order_id'=>$order->id,'from_status'=>$from,'to_status'=>'in_kitchen','created_by'=>auth()->id()]);
   $this->audit->record('order.sent_to_kitchen','order',$order->id,[],['order_no'=>$order->order_no]);
   return $order->fresh(['items','tickets.items']);
  },3);
 }
 public function updateStatus(KitchenTicket $ticket,string $status): KitchenTicket
 {
  $businessId=$this->scope->businessId();
  $this->scope->assertBusinessRecord($ticket,$businessId);
  $allowed=['new'=>['accepted','preparing','cancelled'],'accepted'=>['preparing','ready','cancelled'],'preparing'=>['ready','cancelled'],'ready'=>[],'cancelled'=>[]];
  if(!in_array($status,$allowed[$ticket->status]??[],true)) throw ValidationException::withMessages(['status'=>'Invalid kitchen status transition.']);
  return DB::transaction(function() use($ticket,$status,$businessId){
   $ticket=KitchenTicket::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($ticket->id)->lockForUpdate()->with('items')->firstOrFail();
   $update=['status'=>$status];
   if($status==='accepted'){$update['accepted_at']=now();$update['accepted_by']=auth()->id();}
   if($status==='preparing')$update['started_at']=now();
   if($status==='ready'){$update['ready_at']=now();$update['ready_by']=auth()->id();}
   $ticket->update($update);
   foreach($ticket->items as $ticketItem){$itemUpdate=['status'=>$status];if($status==='preparing')$itemUpdate['started_at']=now();if($status==='ready')$itemUpdate['ready_at']=now();$ticketItem->update($itemUpdate);$orderItem=OrderItem::withoutGlobalScopes()->find($ticketItem->order_item_id);if($orderItem){$orderItem->update($itemUpdate);if($status==='ready')$this->inventory->consumeForOrderItem($orderItem);}}
   $order=Order::withoutGlobalScopes()->find($ticket->order_id);
   if($order){
    $open=OrderItem::withoutGlobalScopes()->where('order_id',$order->id)->whereNotIn('status',['ready','voided','cancelled'])->exists();
    if(!$open){$completed=$order->payment_status==='paid'&&$order->order_type==='dine_in';$order->update(['kitchen_status'=>'ready','status'=>$completed?'completed':'ready','ready_at'=>now(),'completed_at'=>$completed?now():$order->completed_at]);if($completed&&$order->table_id)DiningTable::withoutGlobalScopes()->where('business_id',$order->business_id)->whereKey($order->table_id)->update(['status'=>'available']);$this->collection->markReady($order);}
    elseif($status==='preparing')$order->update(['kitchen_status'=>'preparing']);
   }
   $this->audit->record('kitchen.ticket_status','kitchen_ticket',$ticket->id,[],['status'=>$status]);
   return $ticket->fresh(['items','order']);
  },3);
 }
}
