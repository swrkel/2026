<?php

namespace Modules\StockTransferNew\Services\Uat;

class UatChecklistService
{
    public function checklist(): array
    {
        return [
            'setup' => [
                'Module visible in sidebar and routes open without localhost redirect',
                'Permissions assigned to admin, manager, warehouse, and auditor roles',
                'Businesses, locations, and stores filter correctly per logged-in tenant',
                'Products are selected from the standalone Products module bridge only',
            ],
            'transfer_workflow' => [
                'Draft transfer can be created and edited before submission',
                'Submitted transfer cannot be edited without return-for-correction',
                'Approver can approve, reject, and return with remarks',
                'Dispatch reduces source store stock only once',
                'Receive increases destination store stock only once',
                'Short/excess variance can be reconciled with audit record',
            ],
            'warehouse' => [
                'Barcode/SKU scan works for dispatch',
                'Barcode/SKU scan works for receive',
                'Mobile warehouse view is touch friendly',
                'Batch and expiry details are retained on lines',
            ],
            'reports' => [
                'Product-wise, location-wise, store-wise, user-wise and monthly reports load',
                'CSV exports include current filter values',
                'Exception and aging reports identify delayed/incomplete transfers',
            ],
            'security' => [
                'Duplicate submit/dispatch/receive requests are blocked',
                'Locked transfers cannot be changed by normal users',
                'Activity log records each workflow action',
                'Tenant/business/location/store scope cannot be bypassed',
            ],
        ];
    }

    public function summary(array $checklist): array
    {
        $total = 0;
        foreach ($checklist as $items) {
            $total += count($items);
        }
        return [
            'total_checks' => $total,
            'recommended_pass_rate' => config('stocktransfernew.uat.minimum_pass_rate', 95),
            'note' => 'Use this UAT checklist before enabling the module for live stock movement.',
        ];
    }
}
