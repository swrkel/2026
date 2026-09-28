<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Services\BusinessSettingsGateway;
use Modules\ReportsOther\Services\OrganizationGateway;
use Modules\ReportsOther\Services\ReceiptService;
use Modules\ReportsOther\Support\CurrentScope;

class ReceiptViewController extends Controller
{
    public function show(
        Receipt $receipt,
        ReceiptService $service,
        OrganizationGateway $organisation,
        BusinessSettingsGateway $settings,
        CurrentScope $scope,
    ) {
        $service->guardReceipt($receipt);
        $receipt->load(['details', 'cheques', 'audits']);

        return view('reportsother::cash-receipt.show', [
            'receipt' => $receipt,
            'organisation' => $organisation->identity(),
            'currencyPrecision' => $settings->currencyPrecision($scope->businessId()),
        ]);
    }

    public function print(
        Receipt $receipt,
        ReceiptService $service,
        OrganizationGateway $organisation,
        BusinessSettingsGateway $settings,
        CurrentScope $scope,
    ) {
        $service->guardReceipt($receipt);
        $receipt->load(['details', 'cheques']);

        return view('reportsother::cash-receipt.print.receipt', [
            'receipt' => $receipt,
            'organisation' => $organisation->identity(),
            'currencyPrecision' => $settings->currencyPrecision($scope->businessId()),
        ]);
    }
}
