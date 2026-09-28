<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\DiningTable;
use Modules\RestaurantNew\Entities\Order;
use Modules\RestaurantNew\Entities\Payment;
use Modules\RestaurantNew\Entities\PrintJob;
class PaymentService
{
 public function __construct(private TenantScopeService $scope,private ShiftService $shifts,private NumberService $numbers,private AuditService $audit){}
 public function settle(Order $order,array $lines): Order
 {
  $businessId=$this->scope->businessId();$this->scope->assertBusinessRecord($order,$businessId);
  if(in_array($order->status,['cancelled','completed'],true)&&$order->payment_status==='paid') throw ValidationException::withMessages(['payment'=>'This order is already settled.']);
  return DB::transaction(function() use($order,$lines,$businessId){
   $order=Order::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($order->id)->lockForUpdate()->firstOrFail();
   $balance=max(0,(float)$order->total_amount-(float)Payment::withoutGlobalScopes()->where('order_id',$order->id)->where('status','completed')->sum('amount'));
   if($balance<=0) throw ValidationException::withMessages(['payment'=>'No balance is outstanding.']);
   $total=0;$cashTendered=0;$cashAmount=0;
   $shift=$this->shifts->current($order->location_id);
   foreach($lines as $line){$amount=(float)$line['amount'];if($amount<=0)continue;$method=$line['payment_method'];$tendered=(float)($line['tendered_amount']??$amount);if($method==='cash'&&$tendered<$amount)throw ValidationException::withMessages(['payment'=>'Cash tendered is less than the cash payment amount.']);$change=$method==='cash'?max(0,$tendered-$amount):0;Payment::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$order->location_id,'order_id'=>$order->id,'shift_id'=>$shift?->id,'payment_no'=>$this->numbers->next($businessId,'payment','RP-'),'payment_method'=>$method,'amount'=>$amount,'tendered_amount'=>$tendered,'change_amount'=>$change,'reference_no'=>$line['reference_no']??null,'status'=>'completed','received_by'=>auth()->id(),'paid_at'=>now()]);$total+=$amount;if($method==='cash'){$cashTendered+=$tendered;$cashAmount+=$amount;}}
   if($total+0.0001<$balance) throw ValidationException::withMessages(['payment'=>'The payment total is less than the outstanding balance.']);
   if($total>$balance+0.0001) throw ValidationException::withMessages(['payment'=>'The payment amount exceeds the outstanding balance. Enter extra cash as tendered amount so change is calculated correctly.']);
   $paid=(float)Payment::withoutGlobalScopes()->where('order_id',$order->id)->where('status','completed')->sum('amount');
   $order->update(['bill_no'=>$order->bill_no?:$this->numbers->next($businessId,'bill','RB-'),'receipt_no'=>$order->receipt_no?:$this->numbers->next($businessId,'receipt','RR-'),'cashier_id'=>auth()->id(),'paid_amount'=>$paid,'balance_amount'=>max(0,(float)$order->total_amount-$paid),'payment_status'=>'paid','status'=>$order->kitchen_status==='ready'&&$order->order_type==='dine_in'?'completed':'paid','paid_at'=>now(),'completed_at'=>$order->kitchen_status==='ready'&&$order->order_type==='dine_in'?now():$order->completed_at]);
   if($order->status==='completed'&&$order->table_id)DiningTable::withoutGlobalScopes()->where('business_id',$businessId)->whereKey($order->table_id)->update(['status'=>'available']);
   PrintJob::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$order->location_id,'order_id'=>$order->id,'document_type'=>'bill','status'=>'pending','payload'=>['receipt_no'=>$order->receipt_no],'created_by'=>auth()->id()]);
   $this->audit->record('order.paid','order',$order->id,[],['paid_amount'=>$paid,'change_amount'=>max(0,$cashTendered-$cashAmount)]);
   return $order->fresh(['items.modifiers','payments','collectionToken']);
  },3);
 }
}
