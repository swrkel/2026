<?php

namespace Modules\BankingTesterUI\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BankingTesterUI\Entities\BankingTesterIssueReport;
use Modules\BankingTesterUI\Services\BankingTesterIssueService;

class BankingTesterIssueController extends Controller
{
    public function index(): Renderable
    {
        $issues = BankingTesterIssueReport::latest()->paginate(25);
        return view('bankingtesterui::issues.index', compact('issues'));
    }

    public function create(): Renderable
    {
        return view('bankingtesterui::issues.create');
    }

    public function store(Request $request, BankingTesterIssueService $service): RedirectResponse
    {
        $service->createIssue($request);
        return redirect()->route('banking.tester-ui.issues.index')->with('status', 'Issue recorded for the banking testing team.');
    }

    public function updateStatus(Request $request, BankingTesterIssueReport $issue): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:open,checking,fixed,retest,closed']);
        $data['fixed_at'] = $data['status'] === 'fixed' ? now() : $issue->fixed_at;
        $data['closed_at'] = $data['status'] === 'closed' ? now() : $issue->closed_at;
        $issue->update($data);
        return back()->with('status', 'Issue status updated.');
    }
}
