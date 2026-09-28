<?php
namespace Modules\StockTransferNew\Services;
use Illuminate\Support\Facades\DB; use Modules\StockTransferNew\Entities\StockTransfer; use Modules\StockTransferNew\Entities\StockTransferAudit; use Modules\StockTransferNew\Utilities\StockTransferTenant;
class StockTransferReconciliationService {
    public function varianceTransfers(){ return StockTransfer::with('lines')->where('business_id',StockTransferTenant::businessId())->whereIn('status',['received','completed'])->whereHas('lines',function($q){$q->whereRaw('COALESCE(qty_dispatched,0) <> COALESCE(qty_received,0)');})->latest('id')->paginate(25); }
    public function resolve(StockTransfer $transfer,string $resolution,?string $note=null): void { DB::transaction(function() use($transfer,$resolution,$note){ $transfer->update(['reconciliation_status'=>'resolved','reconciliation_resolution'=>$resolution,'reconciliation_note'=>$note,'reconciled_at'=>now(),'reconciled_by'=>StockTransferTenant::userId()]); StockTransferAudit::create(['transfer_id'=>$transfer->id,'business_id'=>$transfer->business_id,'action'=>'reconciled','note'=>trim($resolution.' - '.($note ?: 'Variance resolved.')),'created_by'=>StockTransferTenant::userId()]); }); }
}
