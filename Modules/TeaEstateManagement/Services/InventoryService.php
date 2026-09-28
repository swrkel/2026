<?php
namespace Modules\TeaEstateManagement\Services;

use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(private NumberingService $numbers, private TenantContextService $context) {}

    public function createLot(int $locationId,string $itemType,float $qty,float $unitCost,array $meta=[]): int
    {
        $lotNo=$this->numbers->next('lot','TEA-LOT');
        $id=DB::table('tea_inventory_lots')->insertGetId([
            'business_id'=>$this->context->businessId(),'location_id'=>$locationId,'lot_no'=>$lotNo,
            'item_type'=>$itemType,'grade_id'=>$meta['grade_id']??null,'processing_batch_id'=>$meta['processing_batch_id']??null,
            'source_type'=>$meta['source_type']??null,'source_id'=>$meta['source_id']??null,'opening_qty_kg'=>$qty,
            'current_qty_kg'=>$qty,'unit_cost'=>$unitCost,'status'=>$qty>0?'available':'closed','notes'=>$meta['notes']??null,
            'created_at'=>now(),'updated_at'=>now()
        ]);
        $this->movement($locationId,$id,'receipt',$qty,0,$unitCost,$meta['source_type']??null,$meta['source_id']??null,$meta['notes']??null);
        return $id;
    }

    public function issue(int $lotId,int $locationId,float $qty,string $referenceType,int $referenceId,?string $note=null): array
    {
        return DB::transaction(function() use($lotId,$locationId,$qty,$referenceType,$referenceId,$note){
            $lot=DB::table('tea_inventory_lots')->where('business_id',$this->context->businessId())->where('location_id',$locationId)->where('id',$lotId)->lockForUpdate()->first();
            abort_if(!$lot,404,'Tea inventory lot not found.');
            abort_if($qty<=0 || (float)$lot->current_qty_kg+0.000001<$qty,422,'Insufficient quantity in the selected tea lot.');
            $new=(float)$lot->current_qty_kg-$qty;
            DB::table('tea_inventory_lots')->where('id',$lotId)->update(['current_qty_kg'=>$new,'status'=>$new<=0.0005?'closed':'available','updated_at'=>now()]);
            $this->movement($locationId,$lotId,'issue',0,$qty,(float)$lot->unit_cost,$referenceType,$referenceId,$note);
            return ['unit_cost'=>(float)$lot->unit_cost,'cost'=>$qty*(float)$lot->unit_cost,'balance'=>$new];
        });
    }

    private function movement(int $locationId,int $lotId,string $type,float $in,float $out,float $cost,?string $refType,?int $refId,?string $note): void
    {
        DB::table('tea_stock_movements')->insert([
            'business_id'=>$this->context->businessId(),'location_id'=>$locationId,'inventory_lot_id'=>$lotId,
            'movement_no'=>$this->numbers->next('stock_movement','TEA-MOV'),'movement_date'=>now(),'movement_type'=>$type,
            'qty_in_kg'=>$in,'qty_out_kg'=>$out,'unit_cost'=>$cost,'reference_type'=>$refType,'reference_id'=>$refId,
            'note'=>$note,'created_by'=>$this->context->userId(),'created_at'=>now(),'updated_at'=>now()
        ]);
    }
}
