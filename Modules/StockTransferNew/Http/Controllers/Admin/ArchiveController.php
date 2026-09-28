<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\ArchiveRun;
use Modules\StockTransferNew\Services\Admin\ArchiveService;

class ArchiveController extends Controller
{
    protected ArchiveService $service;

    public function __construct(ArchiveService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) session('business.id');
        $summary = $this->service->summary($businessId);
        $runs = ArchiveRun::where('business_id', $businessId)
            ->latest('id')
            ->paginate(25);

        return view('stocktransfernew::admin.archive.index', compact('summary', 'runs'));
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'archive_until' => ['required', 'date'],
            'location_id' => ['nullable', 'integer'],
            'store_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['business_id'] = (int) session('business.id');

        $run = $this->service->preview($data);

        return redirect()
            ->route('stocktransfernew.archive.show', $run->id)
            ->with('status', __('stocktransfernew::archive.preview_created'));
    }

    public function show($id)
    {
        $businessId = (int) session('business.id');
        $run = ArchiveRun::with('lines')
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return view('stocktransfernew::admin.archive.show', compact('run'));
    }

    public function execute($id)
    {
        $businessId = (int) session('business.id');
        $run = ArchiveRun::where('business_id', $businessId)->findOrFail($id);
        $this->service->execute($run);

        return back()->with('status', __('stocktransfernew::archive.archive_executed'));
    }

    public function restoreRequest(Request $request)
    {
        $data = $request->validate([
            'archive_run_id' => ['nullable', 'integer'],
            'transfer_id' => ['nullable', 'integer'],
            'transfer_no' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);
        $data['business_id'] = (int) session('business.id');
        $this->service->createRestoreRequest($data);

        return back()->with('status', __('stocktransfernew::archive.restore_requested'));
    }
}
