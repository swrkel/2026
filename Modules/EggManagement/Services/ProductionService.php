<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\Collection;
use Modules\EggManagement\Models\CollectionLine;
class ProductionService
{
    protected $context; protected $seq; protected $audit;
    public function __construct(EggContext $context, SequenceService $seq, AuditService $audit){$this->context=$context;$this->seq=$seq;$this->audit=$audit;}
    public function create(array $data)
    {
        return DB::connection(config('egg.connection'))->transaction(function() use($data){
            $loss=(int)($data['broken_pieces']??0)+(int)($data['dirty_pieces']??0)+(int)($data['rejected_pieces']??0); if($loss>(int)$data['total_pieces']) throw new \RuntimeException('Broken, dirty and rejected pieces cannot exceed total collected pieces.'); $good=(int)$data['total_pieces']-$loss;
            $c=Collection::create($this->context->scopePayload(array_merge(['location_id'=>$data['location_id']??$this->context->locationId(),'store_id'=>$data['store_id']??$this->context->storeId()],[
                'collection_no'=>$this->seq->next('collection','EC'), 'collection_date'=>$data['collection_date'], 'flock_id'=>$data['flock_id']??null,
                'shift'=>$data['shift']??null, 'total_pieces'=>(int)$data['total_pieces'], 'good_pieces'=>$good,
                'broken_pieces'=>(int)($data['broken_pieces']??0),'dirty_pieces'=>(int)($data['dirty_pieces']??0),'rejected_pieces'=>(int)($data['rejected_pieces']??0),
                'status'=>'open','note'=>$data['note']??null,'created_by'=>$this->context->userId()
            ])));
            foreach(['good'=>$good,'broken'=>(int)($data['broken_pieces']??0),'dirty'=>(int)($data['dirty_pieces']??0),'rejected'=>(int)($data['rejected_pieces']??0)] as $type=>$qty){
                if($qty>0) CollectionLine::create(['business_id'=>$c->business_id,'collection_id'=>$c->id,'quality_type'=>$type,'pieces'=>$qty]);
            }
            $this->audit->log('create','collection',$c->id,null,$c->toArray());
            return $c;
        });
    }
}
