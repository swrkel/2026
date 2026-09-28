<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\{Dispatch,RiceProduct};

class DispatchService
{
    public function __construct(
        private NumberSeriesService $numbers,
        private FinanceIntegrationService $finance,
        private SalesInvoiceCalculator $calculator,
        private FinanceAccountPostingService $financeAccounts
    ) {}

    public function create(int $businessId,int $userId,array $data,array $lines): Dispatch
    {
        return DB::transaction(function() use($businessId,$userId,$data,$lines){
            // Never trust client-submitted calculated amounts. Discount amount,
            // tax amount and net total are all recalculated on the server from
            // Discount Type / Discount Value / Tax Percentage.
            $pricing = $this->calculator->calculate(
                $lines,
                (string)($data['discount_type'] ?? 'fixed'),
                (float)($data['discount_value'] ?? 0),
                (float)($data['tax_percent'] ?? 0)
            );

            $data['discount_type'] = $pricing['discount_type'];
            $data['discount_value'] = $pricing['discount_value'];
            $data['unit_discount_amount'] = $pricing['unit_discount_amount'];
            $data['discount_amount'] = $pricing['discount_amount'];
            $data['tax_percent'] = $pricing['tax_percent'];
            $data['tax_amount'] = $pricing['tax_amount'];
            $data['subtotal'] = $pricing['subtotal'];
            $data['net_total'] = $pricing['net_total'];

            $d=Dispatch::create(array_merge($data,[
                'business_id'=>$businessId,
                'dispatch_no'=>$this->numbers->next($businessId,'dispatch','RCD-'),
                'status'=>'draft',
                'created_by'=>$userId,
            ]));

            $now=now();
            $insert=[];
            foreach($lines as $index=>$line){
                $qty=(float)$line['quantity'];
                $rate=(float)$line['unit_price'];
                $linePricing=$pricing['line_results'][$index] ?? [];
                $insert[]=[
                    'business_id'=>$businessId,
                    'dispatch_id'=>$d->id,
                    'product_id'=>(int)$line['product_id'],
                    'quantity'=>$qty,
                    'unit_price'=>$rate,
                    'unit_discount_type'=>$linePricing['unit_discount_type'] ?? 'fixed',
                    'unit_discount_value'=>$linePricing['unit_discount_value'] ?? 0,
                    'unit_discount_amount'=>$linePricing['unit_discount_amount'] ?? 0,
                    'net_unit_price'=>$linePricing['net_unit_price'] ?? $rate,
                    'line_total'=>$linePricing['line_total'] ?? round($qty*$rate,4),
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
            }
            if($insert){
                DB::table('rcm_dispatch_lines')->insert($insert);
            }

            return $d;
        });
    }

    public function createAndApprove(int $businessId,int $userId,array $data,array $lines): Dispatch
    {
        return DB::transaction(function() use($businessId,$userId,$data,$lines){
            $dispatch=$this->create($businessId,$userId,$data,$lines);
            return $this->approve($businessId,(int)$dispatch->id,$userId);
        });
    }

    public function approve(int $businessId,int $id,int $userId): Dispatch
    {
        return DB::transaction(function() use($businessId,$id,$userId){
            $d=Dispatch::forBusiness($businessId)
                ->with(['lines:id,business_id,dispatch_id,product_id,quantity'])
                ->lockForUpdate()
                ->findOrFail($id);
            if($d->status!=='draft') {
                throw new \RuntimeException('Only draft dispatches can be approved.');
            }

            // Aggregate duplicate product lines, lock all products once, then
            // write stock movements in one insert. This removes the old N+1
            // product query + movement insert pattern on approval.
            $qtyByProduct=[];
            foreach($d->lines as $line){
                $pid=(int)$line->product_id;
                $qtyByProduct[$pid]=($qtyByProduct[$pid]??0)+(float)$line->quantity;
            }
            $productIds=array_keys($qtyByProduct);
            $products=RiceProduct::forBusiness($businessId)
                ->whereIn('id',$productIds)
                ->lockForUpdate()
                ->get(['id','business_id','name','current_qty'])
                ->keyBy('id');
            abort_unless($products->count()===count($productIds),422,'One of the selected Rice Products is no longer available.');

            $movements=[];
            $now=now();
            foreach($qtyByProduct as $productId=>$qty){
                $p=$products->get($productId);
                $new=(float)$p->current_qty-$qty;
                if($new<-0.0005){
                    throw new \RuntimeException('Insufficient stock for '.$p->name.'.');
                }
                $p->update(['current_qty'=>$new]);
                $movements[]=[
                    'business_id'=>$businessId,
                    'location_id'=>$d->location_id,
                    'store_id'=>$d->store_id,
                    'product_id'=>$p->id,
                    'movement_date'=>$d->dispatch_date,
                    'movement_type'=>'dispatch',
                    'quantity'=>$qty,
                    'signed_quantity'=>-$qty,
                    'reference_type'=>'dispatch',
                    'reference_id'=>$d->id,
                    'note'=>null,
                    'created_by'=>$userId,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ];
            }
            if($movements){
                DB::table('rcm_finished_stock_movements')->insert($movements);
            }

            $d->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now()]);
            $d = $d->fresh();
            $this->financeAccounts->syncDispatch($businessId,$userId,$d);
            $this->finance->queue($businessId,'customer_receivable','dispatch',$d->id,[
                'reference_no'=>$d->dispatch_no,
                'customer_id'=>$d->customer_id,
                'amount'=>(float)$d->net_total,
                'transaction_date'=>(string)$d->dispatch_date,
                'location_id'=>$d->location_id,
            ]);
            return $d;
        });
    }
}
