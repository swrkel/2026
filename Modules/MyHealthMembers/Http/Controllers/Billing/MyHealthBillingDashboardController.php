<?php

namespace Modules\MyHealthMembers\Http\Controllers\Billing;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;
use Modules\MyHealthMembers\Entities\MyHealthBillingPayment;
use Modules\MyHealthMembers\Entities\MyHealthClaimSettlement;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthBillingDashboardController extends Controller
{
    public function index(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_billing'), 403);

        return view('myhealthmembers::billing.dashboard', [
            'todayInvoices' => MyHealthBillingInvoice::query()->whereDate('invoice_date', date('Y-m-d'))->count(),
            'unpaidInvoices' => MyHealthBillingInvoice::query()->whereIn('status', ['unpaid', 'partially_paid'])->count(),
            'todayPayments' => MyHealthBillingPayment::query()->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'pendingClaims' => MyHealthInsuranceClaim::query()->whereIn('status', ['submitted', 'approved'])->count(),
            'claimSettlements' => MyHealthClaimSettlement::query()->sum('settled_amount'),
        ]);
    }
}
