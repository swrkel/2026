<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\Adjustment;
use Modules\EggManagement\Models\AdjustmentLine;
use Modules\EggManagement\Integrations\FinanceGateway;
class AdjustmentService
{
    protected $context; protected $seq; protected $stock; protected $finance; protected $audit;
    public function __construct(EggContext $context,SequenceService $seq,StockService $stock,FinanceGateway $finance,AuditService $audit){$this->context=$context;$this->seq=$seq;$this->stock=$stock;$this->finance=$finance;$this->audit=$audit;}
    public function create(array $data)
    {
        return DB::connection(config('egg.connection'))->transaction(function() use($data){
            $a=Adjustment::create($this->context->scopePayload(array_merge(['location_id'=>$data['location_id']??$this->context->locationId(),'store_id'=>$data['store_id']??$this->context->storeId()],['adjustment_no'=>$this->seq->next('adjustment','EA'),'adjustment_date'=>$data['adjustment_date'],'reason'=>$data['reason'],'status'=>'approved','note'=>$data['note']??null,'created_by'=>$this->context->userId()])));
            foreach($data['lines']??[] as $line){$qty=(int)($line['pieces']??0);if(!$qty)continue;$grade=(int)$line['grade_id'];if($qty>0)$this->stock->add($grade,$qty,(float)($line['unit_cost']??0),'adjustment',$a->id,['location_id'=>$a->location_id,'store_id'=>$a->store_id]);else$this->stock->consume($grade,abs($qty),'adjustment',$a->id,['location_id'=>$a->location_id,'store_id'=>$a->store_id]);AdjustmentLine::create(['business_id'=>$a->business_id,'adjustment_id'=>$a->id,'grade_id'=>$grade,'pieces'=>$qty,'unit_cost'=>(float)($line['unit_cost']??0)]);}
            $this->finance->queue(config('egg.finance.adjustment_event'),['aggregate_type'=>'adjustment','aggregate_id'=>$a->id,'adjustment_no'=>$a->adjustment_no,'business_id'=>$a->business_id,'location_id'=>$a->location_id,'store_id'=>$a->store_id,'date'=>$a->adjustment_date]);$this->audit->log('create','adjustment',$a->id,null,$a->toArray());return $a;
        });
    }
}
