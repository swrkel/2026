<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\Admin\InterBusinessSettlementService;

class InterBusinessSettlementController extends Controller
{
    protected InterBusinessSettlementService $service;

    public function __construct(InterBusinessSettlementService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['from_business_id', 'to_business_id', 'status', 'date_from', 'date_to']);
        $summary = $this->service->summary($filters);
        $rows = $this->service->rows($filters);

        return view('stocktransfernew::admin.inter_business_settlement.index', compact('filters', 'summary', 'rows'));
    }

    public function create(Request $request)
    {
        $filters = $request->only(['from_business_id', 'to_business_id', 'date_from', 'date_to']);
        $eligibleTransfers = $this->service->eligibleTransfers($filters);

        return view('stocktransfernew::admin.inter_business_settlement.create', compact('filters', 'eligibleTransfers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_business_id' => 'required|integer',
            'to_business_id' => 'required|integer|different:from_business_id',
            'settlement_date' => 'required|date',
            'transfer_ids' => 'required|array|min:1',
            'transfer_ids.*' => 'integer',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $settlement = $this->service->createSettlement($validated, (int) auth()->id());

        return redirect()
            ->route('stock-transfer-new.inter-business-settlement.show', $settlement->id)
            ->with('status', __('stocktransfernew::inter_business_settlement.created'));
    }

    public function show(int $id)
    {
        $settlement = $this->service->find($id);
        $lines = $this->service->lines($id);
        $totals = $this->service->lineTotals($id);

        return view('stocktransfernew::admin.inter_business_settlement.show', compact('settlement', 'lines', 'totals'));
    }

    public function approve(Request $request, int $id)
    {
        $this->service->approve($id, (int) auth()->id(), (string) $request->input('remarks'));
        return redirect()->back()->with('status', __('stocktransfernew::inter_business_settlement.approved'));
    }

    public function cancel(Request $request, int $id)
    {
        $request->validate(['remarks' => 'required|string|max:2000']);
        $this->service->cancel($id, (int) auth()->id(), (string) $request->input('remarks'));
        return redirect()->back()->with('status', __('stocktransfernew::inter_business_settlement.cancelled'));
    }

    public function export(Request $request)
    {
        $filters = $request->only(['from_business_id', 'to_business_id', 'status', 'date_from', 'date_to']);
        $rows = $this->service->rows($filters, 10000);
        $filename = 'stock_transfer_inter_business_settlements_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Settlement No', 'Date', 'From Business', 'To Business', 'Transfers', 'Transfer Value', 'Variance Value', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->settlement_no,
                    $row->settlement_date,
                    $row->from_business_name,
                    $row->to_business_name,
                    $row->transfer_count,
                    number_format((float) $row->transfer_value, 4, '.', ''),
                    number_format((float) $row->variance_value, 4, '.', ''),
                    $row->status,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
