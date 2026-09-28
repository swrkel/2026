<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Entities\AuditLog;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->when($request->filled('event'), fn($q) => $q->where('event','like','%'.$request->event.'%'))
            ->when($request->filled('transfer_no'), fn($q) => $q->where('meta->transfer_no',$request->transfer_no))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at','>=',$request->from))
            ->when($request->filled('to'), fn($q) => $q->whereDate('created_at','<=',$request->to))
            ->latest('id')->paginate(50);
        return view('stocktransfernew::audit_security.audit_log', compact('logs'));
    }
}
