<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\Sale;
use Modules\EggManagement\Models\SaleLine;
use Modules\EggManagement\Integrations\FinanceGateway;
class SalesService
{
    protected $context; protected $seq; protected $stock; protected $finance; protected $audit;
    public function __construct(EggContext $context,SequenceService $seq,StockService $stock,FinanceGateway $finance,AuditService $audit){$this->context=$context;$this->seq=$seq;$this->stock=$stock;$this->finance=$finance;$this->audit=$audit;}
    public function create(array $data)
    {
        return DB::connection(config('egg.connection'))->transaction(function() use($data){
            $sale=Sale::create($this->context->scopePayload(array_merge(['location_id'=>$data['location_id']??$this->context->locationId(),'store_id'=>$data['store_id']??$this->context->storeId()],['sale_no'=>$this->seq->next('sale','ES'),'sale_date'=>$data['sale_date'],'customer_id'=>$data['customer_id']??null,'status'=>'approved','payment_status'=>$data['payment_status']??'due','subtotal'=>0,'discount'=>(float)($data['discount']??0),'total'=>0,'note'=>$data['note']??null,'created_by'=>$this->context->userId()])));
            $subtotal=0;
            foreach($data['lines']??[] as $line){$qty=(int)($line['pieces']??0); if($qty<=0)continue; $price=(float)$line['unit_price']; $amount=$qty*$price; $this->stock->consume((int)$line['grade_id'],$qty,'sale',$sale->id,['location_id'=>$sale->location_id,'store_id'=>$sale->store_id]); SaleLine::create(['business_id'=>$sale->business_id,'sale_id'=>$sale->id,'grade_id'=>(int)$line['grade_id'],'product_id'=>$line['product_id']??null,'pieces'=>$qty,'unit_price'=>$price,'amount'=>$amount]); $subtotal+=$amount;}
            if($subtotal<=0) throw new \RuntimeException('At least one sale line is required.');
            $sale->subtotal=$subtotal; $sale->total=max(0,$subtotal-$sale->discount); $sale->save();
            $this->finance->queue(config('egg.finance.sales_event'),['aggregate_type'=>'sale','aggregate_id'=>$sale->id,'sale_no'=>$sale->sale_no,'business_id'=>$sale->business_id,'location_id'=>$sale->location_id,'store_id'=>$sale->store_id,'customer_id'=>$sale->customer_id,'amount'=>$sale->total,'date'=>$sale->sale_date]);
            if($sale->customer_id)$this->finance->queue(config('egg.finance.customer_ledger_event'),['aggregate_type'=>'sale','aggregate_id'=>$sale->id,'sale_no'=>$sale->sale_no,'business_id'=>$sale->business_id,'customer_id'=>$sale->customer_id,'amount'=>$sale->total,'date'=>$sale->sale_date]);
            $this->audit->log('create','sale',$sale->id,null,$sale->toArray()); return $sale;
        });
    }
}
