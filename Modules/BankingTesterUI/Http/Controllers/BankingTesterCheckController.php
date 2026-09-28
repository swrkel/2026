<?php

namespace Modules\BankingTesterUI\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BankingTesterUI\Services\BankingTesterIssueService;

class BankingTesterCheckController extends Controller
{
    public function index(): Renderable
    {
        $checks = [
            ['module_key' => 'banking_core_deposits', 'check_key' => 'menu_loads', 'title' => 'Menu opens and page loads'],
            ['module_key' => 'banking_teller', 'check_key' => 'toolbar_visible', 'title' => 'Standard toolbar is visible'],
            ['module_key' => 'banking_cheque', 'check_key' => 'route_clickable', 'title' => 'All submenu links are clickable'],
            ['module_key' => 'banking_atm', 'check_key' => 'permission_visible', 'title' => 'Permission visibility works'],
            ['module_key' => 'banking_internet', 'check_key' => 'dashboard_layout', 'title' => 'Dashboard layout displays correctly'],
            ['module_key' => 'banking_mobile', 'check_key' => 'responsive_view', 'title' => 'Mobile/responsive view is acceptable'],
            ['module_key' => 'banking_corporate', 'check_key' => 'forms_open', 'title' => 'Forms open without SQL/route errors'],
            ['module_key' => 'banking_trade', 'check_key' => 'reports_shell', 'title' => 'Reports shell opens'],
        ];

        return view('bankingtesterui::checks.index', compact('checks'));
    }

    public function store(Request $request, BankingTesterIssueService $service): RedirectResponse
    {
        $service->storeCheckResult($request);
        return back()->with('status', 'Checklist result saved.');
    }
}
