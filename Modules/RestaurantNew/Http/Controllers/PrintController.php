<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;use Modules\RestaurantNew\Entities\KitchenTicket;use Modules\RestaurantNew\Entities\Order;use Modules\RestaurantNew\Entities\Shift;use Modules\RestaurantNew\Services\TenantScopeService;
class PrintController extends Controller
{
 public function kot(KitchenTicket $ticket,TenantScopeService $s){$s->assertBusinessRecord($ticket,$s->businessId());$ticket->update(['printed_at'=>now()]);return view('restaurantnew::prints.kot',['ticket'=>$ticket->load(['order.table','order.collectionToken','items'])]);}
 public function bill(Order $order,TenantScopeService $s){$s->assertBusinessRecord($order,$s->businessId());return view('restaurantnew::prints.bill',['order'=>$order->load(['items.modifiers','payments','table','collectionToken'])]);}
 public function takeaway(Order $order,TenantScopeService $s){$s->assertBusinessRecord($order,$s->businessId());return view('restaurantnew::prints.takeaway',['order'=>$order->load(['items.modifiers','collectionToken'])]);}
 public function token(Order $order,TenantScopeService $s){$s->assertBusinessRecord($order,$s->businessId());return view('restaurantnew::prints.token',['order'=>$order->load('collectionToken')]);}
 public function shift(Shift $shift,TenantScopeService $s){$s->assertBusinessRecord($shift,$s->businessId());return view('restaurantnew::prints.shift',['shift'=>$shift->load(['orders','payments'])]);}
}
