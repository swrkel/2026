<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerLedgerService;

class LedgerController extends BaseDealerApiController
{
    public function ledger(Request $request, CustomerLedgerService $ledgerService)
    {
        $rows = $ledgerService->portalStatementRows(
            $this->businessId($request),
            $this->customerId($request),
            $request->get('from'),
            $request->get('to'),
            $this->paginateLimit($request, 100, 500)
        );

        return $this->success([
            'rows' => $rows,
            'summary' => $this->portalSummary($this->businessId($request), $this->customerId($request)),
        ]);
    }

    public function statement(Request $request, CustomerLedgerService $ledgerService)
    {
        return $this->ledger($request, $ledgerService);
    }
}
