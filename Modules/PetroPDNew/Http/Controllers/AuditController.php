<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Entities\PdnewAuditLog;

class AuditController extends PdnewController
{
    public function index(Request $request)
    {
        $query = PdnewAuditLog::query()
            ->forBusiness($this->context->businessId())
            ->forLocation($this->context->locationId());
        if ($request->filled('action')) $query->where('action', 'like', '%' . trim((string) $request->action) . '%');
        if ($request->filled('user_id')) $query->where('user_id', (int) $request->user_id);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        $logs = $query->orderByDesc('id')->paginate(100)->withQueryString();
        return view('petropdnew::audit.index', compact('logs'));
    }
}
