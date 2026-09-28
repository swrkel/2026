<?php
namespace Modules\StockTransferNew\Services;
use Illuminate\Support\Facades\DB; use Modules\StockTransferNew\Entities\StockTransfer; use Modules\StockTransferNew\Entities\StockTransferAudit; use Modules\StockTransferNew\Entities\StockTransferReturn; use Modules\StockTransferNew\Utilities\StockTransferTenant;
class StockTransferReturnService {
    public function listReceivableReturns(){ return StockTransfer::with('lines')->where('business_id',StockTransferTenant::businessId())->whereIn('status',['received','completed'])->latest('id')->paginate(25); }
    public function createReturn(StockTransfer $transfer,array $lineQty,?string $reason=null): StockTransferReturn { return DB::transaction(function() use($transfer,$lineQty,$reason){ $totalQty=0; foreach($lineQty as $qty){$totalQty+=(float)$qty;} $return=StockTransferReturn::create(['business_id'=>$transfer->business_id,'transfer_id'=>$transfer->id,'return_no'=>'STR-'.date('Ymd-His').'-'.$transfer->id,'return_qty_total'=>$totalQty,'reason'=>$reason,'status'=>'created','created_by'=>StockTransferTenant::userId()]); StockTransferAudit::create(['transfer_id'=>$transfer->id,'business_id'=>$transfer->business_id,'action'=>'return_created','note'=>'Return '.$return->return_no.' created. '.$reason,'created_by'=>StockTransferTenant::userId()]); return $return; }); }
}
