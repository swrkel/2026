<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ReportsOther\Models\Source;
use Modules\ReportsOther\Services\ReceiptService;
use Modules\ReportsOther\Support\CurrentScope;

class ReceiptEntryController extends Controller
{
    public function store(Request $request, CurrentScope $scope, ReceiptService $service)
    {
        $data = $request->validate([
            'source_id' => ['required', 'integer', 'min:1'],
            'receipt_date' => ['required', 'date'],
            'membership_no' => ['nullable', 'string', 'max:120'],
        ]);

        $source = Source::query()
            ->where('scope_key', $scope->key())
            ->whereKey($data['source_id'])
            ->firstOrFail();

        try {
            $receipt = $service->create($source, $data['receipt_date'], $data['membership_no'] ?? null);
        } catch (\Throwable $e) {
            return redirect()->route('reports-other.cash-receipt.index', ['tab' => 'receipt'])
                ->withInput()
                ->withErrors(['receipt' => $e->getMessage()]);
        }

        return redirect()->route('reports-other.cash-receipt.receipts.show', $receipt)
            ->with('status', 'Receipt '.$receipt->receipt_no.' saved successfully.');
    }
}
