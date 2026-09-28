<?php
namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\IntegrityCheck;

class IntegrityCheckService
{
    public function run(array $filters = []): IntegrityCheck
    {
        $findings = [];
        $businessId = session('business.id') ?? session('user.business_id') ?? null;

        $duplicateRefs = DB::table('stn_transfers')
            ->select('business_id','transfer_no', DB::raw('COUNT(*) as total'))
            ->when($businessId, fn($q) => $q->where('business_id',$businessId))
            ->groupBy('business_id','transfer_no')
            ->having('total','>',1)->limit(50)->get();
        if ($duplicateRefs->count()) {
            $findings[] = ['severity'=>'high','code'=>'DUPLICATE_TRANSFER_NO','message'=>'Duplicate transfer numbers found.','records'=>$duplicateRefs->toArray()];
        }

        $negativeQty = DB::table('stn_transfer_lines')
            ->where(function($q){ $q->where('qty','<',0)->orWhere('dispatched_qty','<',0)->orWhere('received_qty','<',0); })
            ->limit(50)->get();
        if ($negativeQty->count()) {
            $findings[] = ['severity'=>'high','code'=>'NEGATIVE_QTY','message'=>'Negative quantities detected in transfer lines.','records'=>$negativeQty->toArray()];
        }

        $invalidStatus = DB::table('stn_transfers')
            ->whereNotIn('status',['draft','submitted','pending_approval','approved','rejected','returned','dispatched','partially_received','received','closed','cancelled'])
            ->limit(50)->get();
        if ($invalidStatus->count()) {
            $findings[] = ['severity'=>'medium','code'=>'INVALID_STATUS','message'=>'Transfers with invalid workflow status found.','records'=>$invalidStatus->toArray()];
        }

        return IntegrityCheck::create([
            'business_id' => $businessId,
            'checked_by' => auth()->id(),
            'checked_at' => Carbon::now(),
            'status' => count($findings) ? 'issues_found' : 'passed',
            'total_findings' => count($findings),
            'findings' => $findings,
        ]);
    }
}
