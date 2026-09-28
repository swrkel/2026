<?php

namespace Modules\BankingTesterUI\Services;

use Illuminate\Http\Request;
use Modules\BankingTesterUI\Entities\BankingTesterCheckResult;
use Modules\BankingTesterUI\Entities\BankingTesterIssueReport;

class BankingTesterIssueService
{
    public function issueSummary(): array
    {
        if (! class_exists(BankingTesterIssueReport::class)) {
            return ['open' => 0, 'critical' => 0, 'fixed' => 0, 'closed' => 0];
        }

        return [
            'open' => BankingTesterIssueReport::whereIn('status', ['open', 'checking', 'retest'])->count(),
            'critical' => BankingTesterIssueReport::where('severity', 'critical')->whereNotIn('status', ['closed'])->count(),
            'fixed' => BankingTesterIssueReport::where('status', 'fixed')->count(),
            'closed' => BankingTesterIssueReport::where('status', 'closed')->count(),
        ];
    }

    public function createIssue(Request $request): BankingTesterIssueReport
    {
        $data = $request->validate([
            'module_key' => 'required|string|max:100',
            'page_title' => 'nullable|string|max:191',
            'route_name' => 'nullable|string|max:191',
            'url' => 'nullable|string|max:191',
            'severity' => 'required|in:low,medium,high,critical',
            'summary' => 'required|string|max:5000',
            'steps_to_reproduce' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'actual_result' => 'nullable|string',
            'screenshot_reference' => 'nullable|string|max:191',
        ]);

        $data['business_id'] = session('business.id') ?? null;
        $data['location_id'] = session('business_location.id') ?? null;
        $data['reported_by'] = optional(auth()->user())->id;

        return BankingTesterIssueReport::create($data);
    }

    public function storeCheckResult(Request $request): BankingTesterCheckResult
    {
        $data = $request->validate([
            'module_key' => 'required|string|max:100',
            'check_key' => 'required|string|max:150',
            'check_title' => 'required|string|max:191',
            'result' => 'required|in:pending,pass,fail,blocked',
            'notes' => 'nullable|string',
        ]);

        $data['business_id'] = session('business.id') ?? null;
        $data['checked_by'] = optional(auth()->user())->id;
        $data['checked_at'] = now();

        return BankingTesterCheckResult::updateOrCreate(
            ['business_id' => $data['business_id'], 'module_key' => $data['module_key'], 'check_key' => $data['check_key']],
            $data
        );
    }
}
