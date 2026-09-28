<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BankingUI\Models\BankingNavigationAudit;

class BankingNavigationAuditController extends Controller
{
    public function index()
    {
        $records = BankingNavigationAudit::query()->latest('id')->limit(100)->get();
        return view('bankingui::testing.navigation_audit', compact('records'));
    }

    public function store(Request $request)
    {
        BankingNavigationAudit::create([
            'business_id' => session('business.id'),
            'user_id' => optional($request->user())->id,
            'module_key' => (string) $request->input('module_key'),
            'route_name' => (string) $request->input('route_name'),
            'url' => (string) $request->input('url'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json(['success' => true]);
    }
}
