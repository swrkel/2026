<?php
namespace Modules\BankingCheque\Services;
use Illuminate\Support\Facades\DB; use Modules\BankingCheque\Entities\ChequeClearingBatch;
class ChequeClearingService { public function approve(ChequeClearingBatch $batch, int $userId=null): ChequeClearingBatch { return DB::transaction(function() use ($batch,$userId){ $batch->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now()]); return $batch->refresh(); }); } }
