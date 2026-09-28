<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
use Modules\EggManagement\Models\StockLot;
use Modules\EggManagement\Models\StockMovement;
use Modules\EggManagement\Models\Grade;
class StockService
{
    protected $context;
    public function __construct(EggContext $context){$this->context=$context;}

    protected function assertGrade($gradeId){ if(!Grade::where('business_id',$this->context->businessId())->where('id',$gradeId)->where('active',1)->exists()) throw new \RuntimeException('Invalid Egg grade for the active business.'); }

    public function add($gradeId,$pieces,$unitCost,$sourceType,$sourceId,array $scope=[],$collectionDate=null,$bestBefore=null)
    {
        if($pieces<=0) return null; $this->assertGrade($gradeId);
        $lot=StockLot::create($this->context->scopePayload(array_merge($scope,[
            'grade_id'=>$gradeId,'lot_no'=>'EL'.date('ymdHis').random_int(10,99),'collection_date'=>$collectionDate ?: now()->toDateString(),
            'best_before'=>$bestBefore,'received_pieces'=>$pieces,'available_pieces'=>$pieces,'unit_cost'=>$unitCost ?: 0,'source_type'=>$sourceType,'source_id'=>$sourceId,'status'=>'available'
        ])));
        $this->movement('in',$gradeId,$pieces,$sourceType,$sourceId,$lot->id,$scope,$unitCost);
        return $lot;
    }

    public function consume($gradeId,$pieces,$sourceType,$sourceId,array $scope=[])
    {
        $need=(int)$pieces; if($need<=0) return []; $this->assertGrade($gradeId);
        $q=StockLot::where('business_id',$this->context->businessId())->where('grade_id',$gradeId)->where('available_pieces','>',0);
        foreach(['location_id','store_id'] as $k){ if(array_key_exists($k,$scope) && $scope[$k]!==null) $q->where($k,$scope[$k]); }
        $lots=$q->orderByRaw('COALESCE(best_before, collection_date) asc')->orderBy('id')->lockForUpdate()->get();
        $used=[];
        foreach($lots as $lot){ if($need<=0) break; $take=min($need,(int)$lot->available_pieces); $lot->available_pieces-=$take; if($lot->available_pieces<=0)$lot->status='depleted'; $lot->save(); $need-=$take; $used[]=['lot_id'=>$lot->id,'pieces'=>$take,'unit_cost'=>$lot->unit_cost,'collection_date'=>$lot->collection_date,'best_before'=>$lot->best_before]; $this->movement('out',$gradeId,$take,$sourceType,$sourceId,$lot->id,$scope,$lot->unit_cost); }
        if($need>0) throw new \RuntimeException('Insufficient Egg stock for the selected grade/location/store.');
        return $used;
    }

    protected function movement($direction,$gradeId,$pieces,$sourceType,$sourceId,$lotId,array $scope,$unitCost)
    {
        return StockMovement::create($this->context->scopePayload(array_merge($scope,['movement_date'=>now(),'direction'=>$direction,'grade_id'=>$gradeId,'pieces'=>$pieces,'unit_cost'=>$unitCost?:0,'stock_lot_id'=>$lotId,'source_type'=>$sourceType,'source_id'=>$sourceId,'created_by'=>$this->context->userId()])));
    }
}
