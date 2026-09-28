<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\Admin\TransferClaimService;

class TransferClaimController extends Controller
{
    protected TransferClaimService $service;

    public function __construct(TransferClaimService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'claim_type', 'date_from', 'date_to', 'from_business_id', 'to_business_id', 'from_location_id', 'to_location_id']);
        return view('stocktransfernew::admin.claims.index', [
            'filters' => $filters,
            'summary' => $this->service->summary($filters),
            'claims' => $this->service->rows($filters),
        ]);
    }

    public function create(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'from_business_id', 'to_business_id', 'from_location_id', 'to_location_id']);
        return view('stocktransfernew::admin.claims.create', [
            'filters' => $filters,
            'eligibleTransfers' => $this->service->eligibleTransfers($filters),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transfer_id' => ['required', 'integer'],
            'claim_date' => ['nullable', 'date'],
            'claim_type' => ['nullable', 'string', 'max:40'],
            'responsible_party' => ['nullable', 'string', 'max:80'],
            'remarks' => ['nullable', 'string'],
        ]);
        $claim = $this->service->createFromTransfer((int) $data['transfer_id'], $data, (int) auth()->id());
        return redirect()->route('stock-transfer-new.claims.show', $claim->id)->with('status', __('stocktransfernew::claims.created'));
    }

    public function show(int $id)
    {
        return view('stocktransfernew::admin.claims.show', [
            'claim' => $this->service->find($id),
            'lines' => $this->service->lines($id),
            'actions' => $this->service->actions($id),
        ]);
    }

    public function submit(int $id, Request $request)
    {
        $this->service->submit($id, (int) auth()->id(), $request->input('remarks'));
        return back()->with('status', __('stocktransfernew::claims.submitted'));
    }

    public function approve(int $id, Request $request)
    {
        $this->service->approve($id, (int) auth()->id(), $request->input('remarks'));
        return back()->with('status', __('stocktransfernew::claims.approved'));
    }

    public function close(int $id, Request $request)
    {
        $data = $request->validate([
            'recovered_qty' => ['nullable', 'numeric'],
            'recovered_value' => ['nullable', 'numeric'],
            'writeoff_value' => ['nullable', 'numeric'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string'],
        ]);
        $this->service->close($id, (int) auth()->id(), $data);
        return back()->with('status', __('stocktransfernew::claims.closed'));
    }

    public function cancel(int $id, Request $request)
    {
        $data = $request->validate(['reason' => ['required', 'string']]);
        $this->service->cancel($id, (int) auth()->id(), $data['reason']);
        return back()->with('status', __('stocktransfernew::claims.cancelled'));
    }
}
