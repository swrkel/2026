<?php
namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RestaurantNew\Entities\GoodsReceipt;
use Modules\RestaurantNew\Entities\GoodsReceiptLine;
use Modules\RestaurantNew\Entities\Ingredient;
use Modules\RestaurantNew\Entities\InventoryBalance;
use Modules\RestaurantNew\Entities\StockMovement;
use Modules\RestaurantNew\Entities\Supplier;

class ProcurementService
{
    public function __construct(private TenantScopeService $scope,private NumberService $numbers,private AuditService $audit){}

    public function supplier(array $data): Supplier
    {
        $businessId=$this->scope->businessId(); $locationId=(int)($data['location_id']??0)?:null; $this->scope->assertLocationAccess($locationId);
        return Supplier::withoutGlobalScopes()->updateOrCreate(
            ['business_id'=>$businessId,'supplier_code'=>$data['supplier_code']],
            array_merge($data,['business_id'=>$businessId,'location_id'=>$locationId,'is_active'=>true])
        );
    }

    public function createReceipt(array $data): GoodsReceipt
    {
        $businessId=$this->scope->businessId(); $locationId=(int)($data['location_id']??0)?:$this->scope->currentLocationId();
        $this->scope->assertLocationAccess($locationId); abort_unless($locationId,422,'A business location is required.');
        return DB::transaction(function()use($data,$businessId,$locationId){
            $supplierId = (int) ($data['supplier_id'] ?? 0) ?: null;
            if ($supplierId) {
                $supplier = Supplier::withoutGlobalScopes()
                    ->where('business_id', $businessId)
                    ->whereKey($supplierId)
                    ->where('is_active', true)
                    ->first();
                if (! $supplier || ($supplier->location_id !== null && (int) $supplier->location_id !== $locationId)) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'The selected supplier is not available for this business location.',
                    ]);
                }
            }
            $receipt=GoodsReceipt::withoutGlobalScopes()->create([
                'business_id'=>$businessId,'location_id'=>$locationId,'supplier_id'=>$supplierId,
                'receipt_no'=>$this->numbers->next($businessId,'goods_receipt','GRN-'),'supplier_invoice_no'=>$data['supplier_invoice_no']??null,
                'received_date'=>$data['received_date'],'status'=>'draft','notes'=>$data['notes']??null,'received_by'=>auth()->id(),
            ]);
            $subtotal=$discount=$tax=$total=0;
            foreach($data['lines'] as $index=>$row){
                $ingredient=Ingredient::withoutGlobalScopes()->where('business_id',$businessId)->whereKey((int)$row['ingredient_id'])->where('is_active',true)->first();
                if(!$ingredient) throw ValidationException::withMessages(["lines.$index.ingredient_id"=>'Invalid ingredient.']);
                $qty=(float)$row['quantity'];$unit=(float)$row['unit_cost'];$lineDiscount=(float)($row['discount_amount']??0);$lineTax=(float)($row['tax_amount']??0);
                if($qty<=0||$unit<0) throw ValidationException::withMessages(["lines.$index.quantity"=>'Quantity must be greater than zero.']);
                $lineTotal=max(0,$qty*$unit-$lineDiscount+$lineTax);
                GoodsReceiptLine::withoutGlobalScopes()->create(['business_id'=>$businessId,'goods_receipt_id'=>$receipt->id,'ingredient_id'=>$ingredient->id,'quantity'=>$qty,'unit_cost'=>$unit,'discount_amount'=>$lineDiscount,'tax_amount'=>$lineTax,'line_total'=>$lineTotal,'batch_no'=>$row['batch_no']??null,'expiry_date'=>$row['expiry_date']??null,'notes'=>$row['notes']??null]);
                $subtotal+=$qty*$unit;$discount+=$lineDiscount;$tax+=$lineTax;$total+=$lineTotal;
            }
            $receipt->update(['subtotal'=>$subtotal,'discount_total'=>$discount,'tax_total'=>$tax,'total_amount'=>$total]);
            return $receipt->fresh('lines');
        },3);
    }

    public function post(GoodsReceipt $receipt): GoodsReceipt
    {
        $this->scope->assertBusinessRecord($receipt,$this->scope->businessId());
        return DB::transaction(function()use($receipt){
            $locked=GoodsReceipt::withoutGlobalScopes()->whereKey($receipt->id)->lockForUpdate()->with('lines')->firstOrFail();
            if($locked->status==='posted')return $locked;
            if($locked->status!=='draft')throw ValidationException::withMessages(['receipt'=>'Only a draft receipt can be posted.']);
            foreach($locked->lines as $line){
                $balance=InventoryBalance::withoutGlobalScopes()->where('business_id',$locked->business_id)->where('location_id',$locked->location_id)->where('ingredient_id',$line->ingredient_id)->lockForUpdate()->first();
                if(!$balance)$balance=InventoryBalance::withoutGlobalScopes()->create(['business_id'=>$locked->business_id,'location_id'=>$locked->location_id,'ingredient_id'=>$line->ingredient_id,'quantity'=>0,'average_cost'=>0]);
                $oldQty=(float)$balance->quantity;$oldCost=(float)$balance->average_cost;$newQty=$oldQty+(float)$line->quantity;
                $newCost=$newQty>0?(($oldQty*$oldCost)+((float)$line->quantity*(float)$line->unit_cost))/$newQty:(float)$line->unit_cost;
                $balance->update(['quantity'=>$newQty,'average_cost'=>$newCost]);
                StockMovement::withoutGlobalScopes()->firstOrCreate(
                    ['source_type'=>'restnew_goods_receipt_line','source_id'=>$line->id,'ingredient_id'=>$line->ingredient_id],
                    ['business_id'=>$locked->business_id,'location_id'=>$locked->location_id,'movement_type'=>'purchase_receipt','quantity'=>$line->quantity,'unit_cost'=>$line->unit_cost,'value'=>$line->quantity*$line->unit_cost,'reference_no'=>$locked->receipt_no,'batch_no'=>$line->batch_no,'expiry_date'=>$line->expiry_date,'created_by'=>auth()->id()]
                );
                Ingredient::withoutGlobalScopes()->whereKey($line->ingredient_id)->where('business_id',$locked->business_id)->update(['unit_cost'=>$newCost]);
            }
            $locked->update(['status'=>'posted','posted_at'=>now(),'posted_by'=>auth()->id()]);
            $this->audit->record('goods_receipt.posted','goods_receipt',$locked->id,[],['receipt_no'=>$locked->receipt_no]);
            return $locked->fresh('lines');
        },3);
    }
}
