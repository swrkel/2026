<?php

namespace Modules\BankingMicrofinance\Http\Controllers\Field;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BankingMicrofinance\Entities\OfflineCollectionBatch;
use Modules\BankingMicrofinance\Services\FieldCollectionService;

class OfflineCollectionBatchController extends Controller
{
    public function index() { return view('bankingmicrofinance::field.offline_batches.index', ['rows' => OfflineCollectionBatch::latest()->paginate(20)]); }
    public function create() { return view('bankingmicrofinance::field.offline_batches.form'); }
    public function store(Request $request, FieldCollectionService $service) { $service->createOfflineBatch($request->all()); return redirect()->route('bkg.mfi.field.offline-batches.index')->with('status', 'Offline batch saved.'); }
    public function approve(OfflineCollectionBatch $batch, FieldCollectionService $service) { $service->approveBatch($batch, auth()->id() ?? 0); return back()->with('status', 'Batch approved.'); }
    public function reject(Request $request, OfflineCollectionBatch $batch, FieldCollectionService $service) { $service->rejectBatch($batch, auth()->id() ?? 0, $request->input('reason')); return back()->with('status', 'Batch rejected.'); }
}
