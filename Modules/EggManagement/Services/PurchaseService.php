<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\Purchase;
use Modules\EggManagement\Models\PurchaseLine;
use Modules\EggManagement\Integrations\FinanceGateway;
class PurchaseService
{
    protected $context; protected $seq; protected $stock; protected $finance; protected $audit;
    public function __construct(EggContext $context,SequenceService $seq,StockService $stock,FinanceGateway $finance,AuditService $audit){$this->context=$context;$this->seq=$seq;$this->stock=$stock;$this->finance=$finance;$this->audit=$audit;}
    public function create(array $data)
    {
        return DB::connection(config('egg.connection'))->transaction(function() use($data){
            $p=Purchase::create($this->context->scopePayload(array_merge(['location_id'=>$data['location_id']??$this->context->locationId(),'store_id'=>$data['store_id']??$this->context->storeId()],['purchase_no'=>$this->seq->next('purchase','EP'),'purchase_date'=>$data['purchase_date'],'supplier_id'=>$data['supplier_id']??null,'supplier_reference'=>$data['supplier_reference']??null,'status'=>'approved','payment_status'=>$data['payment_status']??'due','subtotal'=>0,'discount'=>(float)($data['discount']??0),'total'=>0,'note'=>$data['note']??null,'created_by'=>$this->context->userId()])));
            $subtotal=0;
            foreach($data['lines']??[] as $line){$qty=(int)($line['pieces']??0); if($qty<=0)continue; $cost=(float)$line['unit_cost'];$amount=$qty*$cost; PurchaseLine::create(['business_id'=>$p->business_id,'purchase_id'=>$p->id,'grade_id'=>(int)$line['grade_id'],'product_id'=>$line['product_id']??null,'pieces'=>$qty,'unit_cost'=>$cost,'amount'=>$amount]); $this->stock->add((int)$line['grade_id'],$qty,$cost,'purchase',$p->id,['location_id'=>$p->location_id,'store_id'=>$p->store_id],$p->purchase_date,$line['best_before']??null); $subtotal+=$amount;}
            if($subtotal<=0) throw new \RuntimeException('At least one purchase line is required.');
            $p->subtotal=$subtotal; $p->total=max(0,$subtotal-$p->discount);$p->save();
            $this->finance->queue(config('egg.finance.purchase_event'),['aggregate_type'=>'purchase','aggregate_id'=>$p->id,'purchase_no'=>$p->purchase_no,'business_id'=>$p->business_id,'location_id'=>$p->location_id,'store_id'=>$p->store_id,'supplier_id'=>$p->supplier_id,'amount'=>$p->total,'date'=>$p->purchase_date]);
            if($p->supplier_id)$this->finance->queue(config('egg.finance.supplier_ledger_event'),['aggregate_type'=>'purchase','aggregate_id'=>$p->id,'purchase_no'=>$p->purchase_no,'business_id'=>$p->business_id,'supplier_id'=>$p->supplier_id,'amount'=>$p->total,'date'=>$p->purchase_date]);
            $this->audit->log('create','purchase',$p->id,null,$p->toArray());return $p;
        });
    }
}
