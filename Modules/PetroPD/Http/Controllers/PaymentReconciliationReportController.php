<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PetroPD\Services\PetroPdSettlementPaymentSnapshotService;
use Modules\PetroPD\Services\Reports\PaymentReconciliationReportService;
use Throwable;

class PaymentReconciliationReportController extends Controller
{
    public function index(Request $request, PaymentReconciliationReportService $service)
    {
        $businessId = $this->businessId($request);
        $this->authorizeReport($businessId);

        if ($request->ajax()) {
            return $service->dataTable($request, $businessId);
        }

        $filters = $service->filterOptions($businessId);
        $business = Business::find($businessId);
        $financialYearStartMonth = max(1, min(12, (int) ($business->fy_start_month ?? 1)));

        return view('petropd::report.payment_reconciliation', compact(
            'filters',
            'financialYearStartMonth'
        ));
    }

    public function show(Request $request, int $id, PaymentReconciliationReportService $service)
    {
        $businessId = $this->businessId($request);
        $this->authorizeReport($businessId);

        try {
            return response()->json(['success' => true, 'event' => $service->eventDetails($businessId, $id)]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function recheck(
        Request $request,
        int $id,
        PaymentReconciliationReportService $service,
        PetroPdSettlementPaymentSnapshotService $snapshotService
    ) {
        $businessId = $this->businessId($request);
        $this->authorizeReport($businessId);

        try {
            $result = $service->recheck(
                $businessId,
                $id,
                (int) auth()->id(),
                $snapshotService
            );

            return response()->json([
                'success' => true,
                'resolved' => (bool) ($result['resolved'] ?? false),
                'message' => (string) ($result['message'] ?? 'Recheck completed.'),
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function businessId(Request $request): int
    {
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: optional(auth()->user())->business_id);

        abort_if($businessId <= 0, 403, 'Business context is not available.');
        return $businessId;
    }

    private function authorizeReport(int $businessId): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->can('superadmin')
                || $user->hasRole('Admin#' . $businessId)
                || $user->can('petro_pd.view_payment_reconciliation_report')
                || $user->can('petro_pd.view_report')
            ),
            403,
            'Unauthorized Access'
        );
    }
}
