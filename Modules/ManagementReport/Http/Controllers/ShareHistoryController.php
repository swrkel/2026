<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportShare;
use Modules\ManagementReport\Support\TenantConnection;

class ShareHistoryController extends Controller
{
    public function index(Request $request)
    {
        TenantConnection::activate();
        $businessId = (int) session('user.business_id');
        $query = ReportShare::with(['run', 'recipients'])->where('business_id', $businessId)->latest();
        $channel = (string) $request->input('channel', '__all__');
        if (in_array($channel, ['sms', 'email', 'whatsapp'], true)) {
            $query->where('channel', $channel);
        }

        $status = (string) $request->input('status', '__all__');
        if (in_array($status, ['pending', 'queued', 'sent', 'ready', 'failed', 'revoked'], true)) {
            $query->where('status', $status);
        }
        $shares = $query->paginate(25)->appends($request->query());

        return view('managementreport::shares.index', compact('shares'));
    }

    public function revoke($share)
    {
        TenantConnection::activate();
        $shareModel = ReportShare::query()->findOrFail((int) $share);
        abort_unless((int) $shareModel->business_id === (int) session('user.business_id'), 404);
        $shareModel->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
        ]);

        return back()->with('success', 'The public report link has been revoked.');
    }
}
