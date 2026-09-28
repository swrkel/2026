<?php
namespace Modules\BankingCheque\Services;
use Illuminate\Support\Facades\DB; use Modules\BankingCheque\Entities\ChequeStopPayment;
class ChequeStopPaymentService { public function approve(ChequeStopPayment $stop, int $userId=null): ChequeStopPayment { return DB::transaction(function() use ($stop,$userId){ $stop->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now()]); if($stop->cheque_leaf_id){ $stop->leaf()->update(['status'=>'stopped']); } return $stop->refresh(); }); } }
