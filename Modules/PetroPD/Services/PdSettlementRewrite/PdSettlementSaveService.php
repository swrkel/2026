<?php

namespace Modules\PetroPD\Services\PdSettlementRewrite;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdSettlementSaveService
{
    public function saveFromClosedShift(int $businessId, Request $request): int
    {
        return DB::transaction(function () use ($businessId, $request) {
            // PD-SETTLEMENT-REWRITE-001 skeleton.
            // The next package will implement this after the user confirms:
            // 1. Which closed-shift table/columns are final source before save.
            // 2. Which payment types must be copied to saved settlement detail tables.
            // 3. Exact formula for shortage/excess/customer loan handling.
            // 4. Exact ledger/account-book posting rules.
            throw new \RuntimeException('PD Settlement rewrite save flow is not enabled until business logic confirmation.');
        });
    }
}
