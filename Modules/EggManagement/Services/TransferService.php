<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\Transfer;
use Modules\EggManagement\Models\TransferLine;
use Modules\EggManagement\Integrations\LocationStoreGateway;
class TransferService
{
    protected $context; protected $seq; protected $stock; protected $audit; protected $scope;
    public function __construct(EggContext $context,SequenceService $seq,StockService $stock,AuditService $audit,LocationStoreGateway $scope){$this->context=$context;$this->seq=$seq;$this->stock=$stock;$this->audit=$audit;$this->scope=$scope;}
    public function create(array $data)
    {
        return DB::connection(config('egg.connection'))->transaction(function() use($data){
            $this->scope->assertScope($data['from_location_id']??null,$data['from_store_id']??null);$this->scope->assertScope($data['to_location_id']??null,$data['to_store_id']??null);
            if(($data['from_location_id']??null)==($data['to_location_id']??null) && ($data['from_store_id']??null)==($data['to_store_id']??null)) throw new \RuntimeException('Source and destination cannot be the same.');
            $t=Transfer::create(['business_id'=>$this->context->businessId(),'transfer_no'=>$this->seq->next('transfer','ET'),'transfer_date'=>$data['transfer_date'],'from_location_id'=>$data['from_location_id']??null,'from_store_id'=>$data['from_store_id']??null,'to_location_id'=>$data['to_location_id']??null,'to_store_id'=>$data['to_store_id']??null,'status'=>'received','note'=>$data['note']??null,'created_by'=>$this->context->userId()]);
            $count=0;
            foreach($data['lines']??[] as $line){
                $qty=(int)($line['pieces']??0);if($qty<=0)continue;$grade=(int)$line['grade_id'];
                $used=$this->stock->consume($grade,$qty,'transfer_out',$t->id,['location_id'=>$t->from_location_id,'store_id'=>$t->from_store_id]);
                foreach($used as $u){$this->stock->add($grade,$u['pieces'],$u['unit_cost'],'transfer_in',$t->id,['location_id'=>$t->to_location_id,'store_id'=>$t->to_store_id],$u['collection_date'],$u['best_before']);}
                TransferLine::create(['business_id'=>$t->business_id,'transfer_id'=>$t->id,'grade_id'=>$grade,'pieces'=>$qty]);$count++;
            }
            if(!$count) throw new \RuntimeException('At least one transfer line is required.');
            $this->audit->log('create','transfer',$t->id,null,$t->toArray());return $t;
        });
    }
}
