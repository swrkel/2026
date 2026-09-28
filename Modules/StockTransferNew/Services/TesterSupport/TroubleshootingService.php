<?php

namespace Modules\StockTransferNew\Services\TesterSupport;

class TroubleshootingService
{
    public function items(): array
    {
        return [
            [
                'issue' => 'Sidebar menu not visible',
                'check' => 'Confirm module status JSON/module registration and user permissions.',
                'safe_action' => 'Run STN permission SQL and clear Laravel cache if your deployment uses cached config/routes.',
            ],
            [
                'issue' => 'Product dropdown/search empty',
                'check' => 'Confirm the standalone Products module has products for the selected business/location/store.',
                'safe_action' => 'Do not create duplicate product tables in StockTransferNew; verify product bridge settings.',
            ],
            [
                'issue' => 'Transfer list stuck on Processing',
                'check' => 'Open Laravel log and confirm tenant DB has all STN tables/indexes and business_id filters.',
                'safe_action' => 'Run consolidated STN SQL on the current tenant database.',
            ],
            [
                'issue' => 'Approve/Dispatch/Receive button missing',
                'check' => 'Confirm user role has the matching permission and transfer status is valid for that action.',
                'safe_action' => 'Re-run permission insert SQL; do not bypass workflow locks.',
            ],
            [
                'issue' => 'Stock quantity mismatch after receive',
                'check' => 'Compare stn_stock_movements with transfer lines and variance reconciliation records.',
                'safe_action' => 'Use variance reconciliation; avoid manual product stock edits unless approved.',
            ],
        ];
    }
}
