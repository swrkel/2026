<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Services\BusinessSettingsGateway;
use Modules\ReportsOther\Services\OrganizationGateway;
use Modules\ReportsOther\Services\ReceiptService;
use Modules\ReportsOther\Support\CurrentScope;

class ReceiptEditController extends Controller
{
    public function edit(
        Receipt $receipt,
        ReceiptService $service,
        OrganizationGateway $organisation,
        BusinessSettingsGateway $settings,
        CurrentScope $scope,
    ) {
        $service->guardReceipt($receipt);
        $receipt->load(['details', 'cheques', 'audits']);

        return view('reportsother::cash-receipt.edit', [
            'receipt' => $receipt,
            'organisation' => $organisation->identity(),
            'currencyPrecision' => $settings->currencyPrecision($scope->businessId()),
        ]);
    }

    public function update(Request $request, Receipt $receipt, ReceiptService $service)
    {
        $service->guardReceipt($receipt);
        $data = $request->validate([
            'membership_no' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $service->updateManualFields($receipt, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['receipt' => $e->getMessage()]);
        }

        return redirect()->route('reports-other.cash-receipt.receipts.show', $receipt)
            ->with('status', 'Manual receipt details updated successfully.');
    }
}
