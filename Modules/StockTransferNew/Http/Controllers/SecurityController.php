<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Entities\DuplicateKey;
use Modules\StockTransferNew\Entities\IntegrityCheck;
use Modules\StockTransferNew\Entities\TransferLock;
use Modules\StockTransferNew\Services\AuditTrailService;
use Modules\StockTransferNew\Services\IntegrityCheckService;
use Modules\StockTransferNew\Services\TransferSecurityService;

class SecurityController extends Controller
{
    public function locks()
    {
        $locks = TransferLock::latest('id')->paginate(50);
        return view('stocktransfernew::audit_security.locks', compact('locks'));
    }

    public function releaseLock(Request $request, TransferLock $lock, TransferSecurityService $security, AuditTrailService $audit)
    {
        $security->release($lock, $request->input('reason'));
        $audit->record('lock_released','transfer_lock',$lock->id,[],['released'=>true],['transfer_id'=>$lock->transfer_id]);
        return back()->with('status','Lock released successfully.');
    }

    public function duplicateKeys()
    {
        $keys = DuplicateKey::latest('id')->paginate(50);
        return view('stocktransfernew::audit_security.duplicate_keys', compact('keys'));
    }

    public function voidDuplicateKey(DuplicateKey $key, AuditTrailService $audit)
    {
        $key->update(['status'=>'void']);
        $audit->record('duplicate_key_voided','duplicate_key',$key->id,[],['status'=>'void']);
        return back()->with('status','Duplicate key voided successfully.');
    }

    public function integrityCheck()
    {
        $checks = IntegrityCheck::latest('id')->paginate(20);
        return view('stocktransfernew::audit_security.integrity_check', compact('checks'));
    }

    public function runIntegrityCheck(IntegrityCheckService $service)
    {
        $check = $service->run();
        return back()->with('status','Integrity check completed: '.$check->status);
    }
}
