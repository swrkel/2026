<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\Collection;
use Modules\EggManagement\Models\GradingRun;
use Modules\EggManagement\Models\GradingLine;
class GradingService
{
    protected $context; protected $seq; protected $stock; protected $audit;
    public function __construct(EggContext $context,SequenceService $seq,StockService $stock,AuditService $audit){$this->context=$context;$this->seq=$seq;$this->stock=$stock;$this->audit=$audit;}
    public function create(array $data)
    {
        return DB::connection(config('egg.connection'))->transaction(function() use($data){
            $collection=Collection::where('business_id',$this->context->businessId())->lockForUpdate()->findOrFail($data['collection_id']);
            if($collection->status==='graded') throw new \RuntimeException('This collection is already fully graded.');
            $already=(int)GradingRun::where('business_id',$this->context->businessId())->where('collection_id',$collection->id)->where('status','posted')->sum('output_pieces');
            $remaining=max(0,(int)$collection->good_pieces-$already);
            $lines=$data['lines']??[]; $graded=array_sum(array_map(fn($x)=>(int)($x['pieces']??0),$lines));
            if($graded<=0 || $graded>$remaining) throw new \RuntimeException('Graded pieces must be above zero and cannot exceed the ungraded good pieces ('.$remaining.').');
            $run=GradingRun::create($this->context->scopePayload([
                'location_id'=>$collection->location_id,'store_id'=>$collection->store_id,'grading_no'=>$this->seq->next('grading','EG'),'collection_id'=>$collection->id,
                'graded_on'=>$data['graded_on']??now()->toDateString(),'input_pieces'=>$graded,'output_pieces'=>$graded,'status'=>'posted','note'=>$data['note']??null,'created_by'=>$this->context->userId()
            ]));
            foreach($lines as $line){
                $pieces=(int)($line['pieces']??0); if($pieces<=0)continue;
                $gl=GradingLine::create(['business_id'=>$run->business_id,'grading_run_id'=>$run->id,'grade_id'=>(int)$line['grade_id'],'pieces'=>$pieces,'unit_cost'=>(float)($line['unit_cost']??0)]);
                $this->stock->add($gl->grade_id,$pieces,$gl->unit_cost,'grading',$run->id,['location_id'=>$collection->location_id,'store_id'=>$collection->store_id],$collection->collection_date,$line['best_before']??null);
            }
            $collection->status = ($already+$graded) >= (int)$collection->good_pieces ? 'graded' : 'partially_graded'; $collection->save();
            $this->audit->log('create','grading',$run->id,null,$run->toArray()); return $run;
        });
    }
}
