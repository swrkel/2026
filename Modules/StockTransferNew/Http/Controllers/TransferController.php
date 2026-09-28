<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Services\StockTransferLookupService;
use Modules\StockTransferNew\Services\StockTransferNumberService;
use Modules\StockTransferNew\Services\StockTransferWorkflowService;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class TransferController extends Controller
{
    public function __construct(
        protected StockTransferWorkflowService $workflow,
        protected StockTransferLookupService $lookup,
        protected StockTransferNumberService $numbers
    ) {
    }

    public function index()
    {
        $businessId = (int) StockTransferTenant::businessId();

        $transfers = StockTransfer::query()
            ->where('business_id', $businessId)
            ->latest('id')
            ->paginate(25);

        return view('stocktransfernew::transfers.index', compact('transfers'));
    }

    public function data()
    {
        return response()->json([
            'data' => StockTransfer::query()
                ->where('business_id', (int) StockTransferTenant::businessId())
                ->latest('id')
                ->limit(100)
                ->get(),
        ]);
    }

    public function create()
    {
        // Intentionally no database queries here.
        // All lookups are fetched asynchronously after the page is visible.
        return view('stocktransfernew::transfers.create');
    }

    public function store(Request $request)
    {
        $header = $this->validatedHeader($request);

        if (empty($header['transfer_no'])) {
            $header['transfer_no'] = $this->numbers->next(
                (int) StockTransferTenant::businessId()
            );
        }

        $transfer = $this->workflow->create(
            $header,
            $request->input('lines', [])
        );

        return redirect()
            ->route('stock-transfer-new.transfers.show', $transfer)
            ->with('status', 'Stock transfer draft saved.');
    }

    public function show(StockTransfer $transfer)
    {
        $this->ensureCurrentBusiness($transfer);
        $transfer->load('lines', 'audits');

        return view('stocktransfernew::transfers.show', compact('transfer'));
    }

    public function edit(StockTransfer $transfer)
    {
        $this->ensureCurrentBusiness($transfer);

        $businessId = (int) StockTransferTenant::businessId();
        $locations = $this->lookup->locations($businessId);
        $stores = $this->lookup->stores($businessId);
        $transfer->load('lines');

        return view(
            'stocktransfernew::transfers.edit',
            compact('transfer', 'locations', 'stores')
        );
    }

    public function update(Request $request, StockTransfer $transfer)
    {
        $this->ensureCurrentBusiness($transfer);

        $header = $this->validatedHeader($request);
        unset($header['transfer_no']);

        $this->workflow->updateDraft(
            $transfer,
            $header,
            $request->input('lines', [])
        );

        return redirect()
            ->route('stock-transfer-new.transfers.show', $transfer)
            ->with('status', 'Transfer draft updated.');
    }

    public function submit(StockTransfer $transfer)
    {
        $this->ensureCurrentBusiness($transfer);
        $this->workflow->submit($transfer);

        return back()->with('status', 'Transfer submitted for approval.');
    }

    public function cancel(Request $request, StockTransfer $transfer)
    {
        $this->ensureCurrentBusiness($transfer);
        $this->workflow->cancel($transfer, $request->input('reason'));

        return back()->with('status', 'Transfer cancelled.');
    }

    public function destroy(StockTransfer $transfer)
    {
        $this->ensureCurrentBusiness($transfer);
        $transfer->delete();

        return redirect()
            ->route('stock-transfer-new.transfers.index')
            ->with('status', 'Transfer deleted.');
    }

    protected function validatedHeader(Request $request): array
    {
        return $request->validate([
            'transfer_no' => 'nullable|string|max:100',
            'transfer_date' => 'required|date',
            'from_location_id' => 'required|integer',
            'to_location_id' => 'required|integer|different:from_location_id',
            'from_store_id' => 'required|integer',
            'to_store_id' => 'required|integer|different:from_store_id',
            'reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'vehicle_no' => 'nullable|string|max:191',
            'driver_name' => 'nullable|string|max:191',
            'driver_mobile' => 'nullable|string|max:191',
        ]);
    }

    protected function ensureCurrentBusiness(StockTransfer $transfer): void
    {
        abort_unless(
            (int) $transfer->business_id === (int) StockTransferTenant::businessId(),
            404
        );
    }
}
