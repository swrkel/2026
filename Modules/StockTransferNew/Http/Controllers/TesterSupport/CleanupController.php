<?php

namespace Modules\StockTransferNew\Http\Controllers\TesterSupport;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\TesterSupport\CleanupPreviewService;

class CleanupController extends Controller
{
    protected CleanupPreviewService $cleanupPreviewService;

    public function __construct(CleanupPreviewService $cleanupPreviewService)
    {
        $this->cleanupPreviewService = $cleanupPreviewService;
    }

    public function preview(Request $request)
    {
        $filters = $request->only('business_id', 'from_date', 'to_date', 'status');
        $summary = $this->cleanupPreviewService->summary($filters);
        $transfers = $this->cleanupPreviewService->transfers($filters);

        return view('stocktransfernew::tester_support.cleanup_preview', compact('filters', 'summary', 'transfers'));
    }

    public function storeLog(Request $request)
    {
        $request->validate([
            'cleanup_note' => 'required|string|max:1000',
        ]);

        $this->cleanupPreviewService->storeLog($request->input('cleanup_note'));
        return redirect()->back()->with('status', 'Cleanup note saved for audit reference.');
    }
}
