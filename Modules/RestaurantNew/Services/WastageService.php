<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\{Ingredient,InventoryBalance,StockMovement,Wastage,WastageLine};
class WastageService
{
    public function __construct(private TenantScopeService $scope,private NumberService $numbers,private AuditService $audit){}
    public function create(array $data): Wastage
    {
        $businessId=$this->scope->businessId();$locationId=(int)($data['location_id']??0)?:$this->scope->currentLocationId();$this->scope->assertLocationAccess($locationId);abort_unless($locationId,422,'A location is required.');
        return DB::transaction(function()use($data,$businessId,$locationId){
            $wastage=Wastage::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$locationId,'wastage_no'=>$this->numbers->next($businessId,'wastage','WST-'),'wastage_date'=>$data['wastage_date'],'reason_code'=>$data['reason_code'],'status'=>'posted','notes'=>$data['notes']??null,'reported_by'=>auth()->id(),'approved_by'=>auth()->id(),'approved_at'=>now()]);
            foreach($data['lines'] as $i=>$row){$ingredient=Ingredient::withoutGlobalScopes()->where('business_id',$businessId)->whereKey((int)$row['ingredient_id'])->first();if(!$ingredient)throw ValidationException::withMessages(["lines.$i.ingredient_id"=>'Invalid ingredient.']);$qty=(float)$row['quantity'];if($qty<=0)throw ValidationException::withMessages(["lines.$i.quantity"=>'Quantity must be greater than zero.']);$balance=InventoryBalance::withoutGlobalScopes()->where('business_id',$businessId)->where('location_id',$locationId)->where('ingredient_id',$ingredient->id)->lockForUpdate()->first();if(!$balance||(float)$balance->quantity<$qty)throw ValidationException::withMessages(['stock'=>'Insufficient stock for '.$ingredient->name.'.']);$cost=(float)$balance->average_cost;$balance->decrement('quantity',$qty);$line=WastageLine::withoutGlobalScopes()->create(['business_id'=>$businessId,'wastage_id'=>$wastage->id,'ingredient_id'=>$ingredient->id,'quantity'=>$qty,'unit_cost'=>$cost,'value'=>$qty*$cost,'batch_no'=>$row['batch_no']??null,'notes'=>$row['notes']??null]);StockMovement::withoutGlobalScopes()->create(['business_id'=>$businessId,'location_id'=>$locationId,'ingredient_id'=>$ingredient->id,'movement_type'=>'wastage','quantity'=>-$qty,'unit_cost'=>$cost,'value'=>-$qty*$cost,'source_type'=>'restnew_wastage_line','source_id'=>$line->id,'reference_no'=>$wastage->wastage_no,'batch_no'=>$line->batch_no,'notes'=>$wastage->reason_code,'created_by'=>auth()->id()]);}
            $this->audit->record('wastage.posted','wastage',$wastage->id,[],['wastage_no'=>$wastage->wastage_no]);return $wastage->fresh('lines.ingredient');
        },3);
    }
}
