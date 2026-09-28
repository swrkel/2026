<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\{Ingredient,InventoryBalance,StockMovement,Stocktake,StocktakeLine};
class StocktakeService
{
    public function __construct(private TenantScopeService $scope,private NumberService $numbers,private AuditService $audit){}
    public function start(array $data): Stocktake
    {
        $businessId=$this->scope->businessId();$locationId=(int)($data['location_id']??0)?:$this->scope->currentLocationId();$this->scope->assertLocationAccess($locationId);abort_unless($locationId,422,'A location is required.');
        return DB::transaction(function()use($data,$businessId,$locationId){
            $stocktake=Stocktake::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$locationId,'stocktake_no'=>$this->numbers->next($businessId,'stocktake','STK-'),'stocktake_date'=>$data['stocktake_date'],'status'=>'draft','notes'=>$data['notes']??null,'counted_by'=>auth()->id()]);
            $ingredients=Ingredient::withoutGlobalScopes()->where('business_id',$businessId)->where('is_active',true)->orderBy('name')->get();
            $balances=InventoryBalance::withoutGlobalScopes()->where('business_id',$businessId)->where('location_id',$locationId)->get()->keyBy('ingredient_id');
            foreach($ingredients as $ingredient){$balance=$balances->get($ingredient->id);StocktakeLine::withoutGlobalScopes()->create(['business_id'=>$businessId,'stocktake_id'=>$stocktake->id,'ingredient_id'=>$ingredient->id,'system_qty'=>(float)($balance?->quantity??0),'counted_qty'=>(float)($balance?->quantity??0),'variance_qty'=>0,'unit_cost'=>(float)($balance?->average_cost??$ingredient->unit_cost),'variance_value'=>0]);}
            return $stocktake->fresh('lines.ingredient');
        },3);
    }
    public function saveCounts(Stocktake $stocktake,array $counts): Stocktake
    {
        $this->scope->assertBusinessRecord($stocktake,$this->scope->businessId());
        if($stocktake->status!=='draft')throw ValidationException::withMessages(['stocktake'=>'Only a draft stocktake can be edited.']);
        DB::transaction(function()use($stocktake,$counts){foreach($counts as $lineId=>$qty){$line=StocktakeLine::withoutGlobalScopes()->where('stocktake_id',$stocktake->id)->whereKey((int)$lineId)->first();if(!$line)continue;$counted=max(0,(float)$qty);$variance=$counted-(float)$line->system_qty;$line->update(['counted_qty'=>$counted,'variance_qty'=>$variance,'variance_value'=>$variance*(float)$line->unit_cost]);}});
        return $stocktake->fresh('lines.ingredient');
    }
    public function post(Stocktake $stocktake): Stocktake
    {
        $this->scope->assertBusinessRecord($stocktake,$this->scope->businessId());
        return DB::transaction(function()use($stocktake){
            $locked=Stocktake::withoutGlobalScopes()->whereKey($stocktake->id)->lockForUpdate()->with('lines')->firstOrFail();if($locked->status==='posted')return $locked;if($locked->status!=='draft')throw ValidationException::withMessages(['stocktake'=>'Only a draft stocktake can be posted.']);
            foreach($locked->lines as $line){$balance=InventoryBalance::withoutGlobalScopes()->where('business_id',$locked->business_id)->where('location_id',$locked->location_id)->where('ingredient_id',$line->ingredient_id)->lockForUpdate()->first();if(!$balance)$balance=InventoryBalance::withoutGlobalScopes()->create(['business_id'=>$locked->business_id,'location_id'=>$locked->location_id,'ingredient_id'=>$line->ingredient_id,'quantity'=>0,'average_cost'=>$line->unit_cost]);$variance=(float)$line->counted_qty-(float)$balance->quantity;$line->update(['system_qty'=>$balance->quantity,'variance_qty'=>$variance,'variance_value'=>$variance*(float)$balance->average_cost,'unit_cost'=>$balance->average_cost]);$balance->update(['quantity'=>$line->counted_qty]);if(abs($variance)>0.00005)StockMovement::withoutGlobalScopes()->firstOrCreate(['source_type'=>'restnew_stocktake_line','source_id'=>$line->id,'ingredient_id'=>$line->ingredient_id],['business_id'=>$locked->business_id,'location_id'=>$locked->location_id,'movement_type'=>'stocktake_variance','quantity'=>$variance,'unit_cost'=>$balance->average_cost,'value'=>$variance*(float)$balance->average_cost,'reference_no'=>$locked->stocktake_no,'created_by'=>auth()->id()]);}
            $locked->update(['status'=>'posted','posted_at'=>now(),'posted_by'=>auth()->id()]);$this->audit->record('stocktake.posted','stocktake',$locked->id,[],['stocktake_no'=>$locked->stocktake_no]);return $locked->fresh('lines.ingredient');
        },3);
    }
}
