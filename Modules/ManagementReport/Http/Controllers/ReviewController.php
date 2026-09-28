<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportReviewStatus;
use Modules\ManagementReport\Entities\ReportRun;
use Modules\ManagementReport\Support\TenantConnection;

class ReviewController extends Controller
{
    public function store(Request $request, $run)
    {
        TenantConnection::activate();
        $runModel = ReportRun::query()->findOrFail((int) $run);
        abort_unless((int) $runModel->business_id === (int) session('user.business_id'), 404);

        $data = $request->validate([
            'review_status' => 'required|in:pending,reviewed,approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        ReportReviewStatus::create([
            'report_run_id' => $runModel->id,
            'business_id' => $runModel->business_id,
            'review_status' => $data['review_status'],
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        $runModel->update([
            'review_status' => $data['review_status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Review status updated.');
    }
}
