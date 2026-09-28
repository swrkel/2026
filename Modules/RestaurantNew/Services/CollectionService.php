<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\CollectionToken;
use Modules\RestaurantNew\Entities\Order;
class CollectionService
{
 public function __construct(private TenantScopeService $scope,private NumberService $numbers,private AuditService $audit){}
 public function issue(Order $order): CollectionToken
 {
  if($order->order_type!=='takeaway') throw ValidationException::withMessages(['order'=>'Collection tokens are only available for takeaway orders.']);
  if($order->collectionToken) return $order->collectionToken;
  $token=$this->numbers->next((int)$order->business_id,'collection_token','T-',4);
  return CollectionToken::withoutGlobalScopes()->create(['business_id'=>$order->business_id,'location_id'=>$order->location_id,'order_id'=>$order->id,'token_no'=>$token,'status'=>'queued']);
 }
 public function markReady(Order $order): void
 {
  if($order->order_type!=='takeaway') return;
  $token=$order->collectionToken?:$this->issue($order);
  $token->update(['status'=>'ready','ready_at'=>now()]);
 }
 public function call(CollectionToken $token): CollectionToken
 {
  $this->scope->assertBusinessRecord($token,$this->scope->businessId());
  if(!in_array($token->status,['ready','called'],true)) throw ValidationException::withMessages(['token'=>'Only ready orders can be called.']);
  $token->update(['status'=>'called','called_at'=>now(),'call_count'=>(int)$token->call_count+1]);
  $this->audit->record('collection.called','collection_token',$token->id,[],['token'=>$token->token_no]);
  return $token->fresh();
 }
 public function collect(CollectionToken $token): CollectionToken
 {
  $this->scope->assertBusinessRecord($token,$this->scope->businessId());
  $order=$token->order;
  if(!$order || $order->payment_status!=='paid') throw ValidationException::withMessages(['token'=>'Payment must be completed before collection.']);
  if(!in_array($token->status,['ready','called'],true)) throw ValidationException::withMessages(['token'=>'This order is not ready for collection.']);
  $token->update(['status'=>'collected','collected_at'=>now(),'collected_by'=>auth()->id()]);
  $order->update(['status'=>'completed','completed_at'=>now()]);
  $this->audit->record('collection.completed','collection_token',$token->id,[],['token'=>$token->token_no]);
  return $token->fresh();
 }
}
