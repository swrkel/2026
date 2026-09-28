<?php
namespace Modules\Graphs\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Graphs\Services\GraphAnalyticsService;
use Modules\Graphs\Services\GraphFinancialAnalyticsService;
use Modules\Graphs\Services\GraphOperationalAnalyticsService;
use Modules\Graphs\Services\GraphCustomerPaymentAnalyticsService;
use Modules\Graphs\Services\GraphManagementDashboardService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GraphsController extends Controller
{
    public function __construct(
        private GraphAnalyticsService $analytics,
        private GraphFinancialAnalyticsService $financialAnalytics,
        private GraphOperationalAnalyticsService $operationalAnalytics,
        private GraphCustomerPaymentAnalyticsService $customerPaymentAnalytics,
        private GraphManagementDashboardService $managementDashboard
    ) {}

    public function index(Request $request)
    {
        $businessId = $this->analytics->businessId();
        abort_if(!$businessId, 403, 'Business context not found.');

        return view('graphs::index', $this->commonViewData($businessId));
    }

    public function financial(Request $request)
    {
        $businessId = $this->analytics->businessId();
        abort_if(!$businessId, 403, 'Business context not found.');

        return view('graphs::financial', array_merge($this->commonViewData($businessId), [
            'currencyPrecision' => $this->financialAnalytics->currencyPrecision($businessId),
        ]));
    }

    public function operational(Request $request)
    {
        $businessId = $this->analytics->businessId();
        abort_if(!$businessId, 403, 'Business context not found.');

        return view('graphs::operational', array_merge($this->commonViewData($businessId), [
            'currencyPrecision' => $this->financialAnalytics->currencyPrecision($businessId),
        ]));
    }

    public function customerPayment(Request $request)
    {
        $businessId = $this->analytics->businessId();
        abort_if(!$businessId, 403, 'Business context not found.');

        return view('graphs::customer_payment', array_merge($this->commonViewData($businessId), [
            'currencyPrecision' => $this->financialAnalytics->currencyPrecision($businessId),
        ]));
    }

    public function managementDashboard(Request $request)
    {
        $businessId = $this->analytics->businessId();
        abort_if(!$businessId, 403, 'Business context not found.');

        return view('graphs::management_dashboard', array_merge($this->commonViewData($businessId), [
            'currencyPrecision' => $this->financialAnalytics->currencyPrecision($businessId),
        ]));
    }

    public function tanks(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        return response()->json(['data'=>$this->analytics->tanks($businessId,$this->location($request))]);
    }

    public function fuelSales(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->analytics->fuelSales($businessId,$this->period($request),$start,$end,$this->location($request)));
    }

    public function nonFuelSales(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->analytics->nonFuelSales($businessId,$this->period($request),$start,$end,$this->location($request)));
    }

    public function reconciliation(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->financialAnalytics->reconciliation($businessId,$start,$end,$this->location($request)));
    }

    public function profitability(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->financialAnalytics->profitability($businessId,$start,$end,$this->location($request)));
    }

    public function debtorsAgeing(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [, $end]=$this->dateRange($request);
        return response()->json($this->financialAnalytics->debtorsAgeing($businessId,$end,$this->location($request)));
    }

    public function debtorsAgeingCustomers(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [, $end]=$this->dateRange($request);
        $bucket=(string)$request->query('bucket','0_30');
        return response()->json($this->financialAnalytics->debtorsAgeingCustomers($businessId,$end,$bucket,$this->location($request)));
    }

    public function dipVariance(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->operationalAnalytics->dipVariance(
            $businessId, $start, $end, $this->period($request), $this->location($request)
        ));
    }

    public function pumpShiftSales(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->operationalAnalytics->pumpShiftSales(
            $businessId, $start, $end, $this->period($request), $this->location($request)
        ));
    }

    public function customerPumpShiftSales(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->operationalAnalytics->pumpShiftSales(
            $businessId, $start, $end, $this->period($request), $this->location($request)
        ));
    }

    public function paymentMethodSplit(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->customerPaymentAnalytics->paymentMethodSplit(
            $businessId, $start, $end, $this->period($request), $this->location($request)
        ));
    }

    public function topCreditCustomers(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        [$start,$end]=$this->dateRange($request);
        return response()->json($this->customerPaymentAnalytics->topCreditCustomers(
            $businessId, $start, $end, $this->period($request), $this->location($request), 10
        ));
    }

    public function managementDashboardData(Request $request)
    {
        $businessId=$this->analytics->businessId(); abort_if(!$businessId,403);
        return response()->json($this->managementDashboard->dashboard(
            $businessId, $this->location($request)
        ));
    }

    public function asset(string $type, string $file): BinaryFileResponse
    {
        $map=['css'=>base_path('Modules/Graphs/Resources/assets/css'),'js'=>base_path('Modules/Graphs/Resources/assets/js'),'vendor'=>base_path('Modules/Graphs/Resources/assets/vendor')];
        abort_unless(isset($map[$type]),404); $path=$map[$type].DIRECTORY_SEPARATOR.basename($file); abort_unless(is_file($path),404);
        return response()->file($path, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function commonViewData(int $businessId): array
    {
        $assetFiles = [
            base_path('Modules/Graphs/Resources/assets/css/graphs.css'),
            base_path('Modules/Graphs/Resources/assets/js/graphs.js'),
            base_path('Modules/Graphs/Resources/assets/js/financial.js'),
            base_path('Modules/Graphs/Resources/assets/js/operational.js'),
            base_path('Modules/Graphs/Resources/assets/js/customer_payment.js'),
            base_path('Modules/Graphs/Resources/assets/js/management_dashboard.js'),
            base_path('Modules/Graphs/Resources/assets/vendor/chart.min.js'),
        ];
        $assetVersion = collect($assetFiles)
            ->filter(fn ($file) => is_file($file))
            ->map(fn ($file) => (int) filemtime($file))
            ->max() ?: time();

        $today = now()->toDateString();

        return [
            'locations' => $this->analytics->locations($businessId),
            'alerts' => $this->analytics->reorderAlerts($businessId),
            'today' => $today,
            'dateRangeDisplay' => now()->format('d/m/Y') . ' - ' . now()->format('d/m/Y'),
            'assetVersion' => $assetVersion,
        ];
    }

    private function location(Request $r): ?int { $v=(int)$r->query('location_id',0); return $v>0?$v:null; }
    private function period(Request $r): string { $v=(string)$r->query('period','daily'); return in_array($v,['daily','weekly','monthly','yearly'],true)?$v:'daily'; }

    private function dateRange(Request $r): array
    {
        try {
            $start=Carbon::parse((string)$r->query('start_date',now()->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $start=now()->startOfDay();
        }
        try {
            $end=Carbon::parse((string)$r->query('end_date',now()->toDateString()))->endOfDay();
        } catch (\Throwable $e) {
            $end=now()->endOfDay();
        }
        if ($end->lt($start)) {
            $swap=$start;
            $start=$end->copy()->startOfDay();
            $end=$swap->copy()->endOfDay();
        }
        return [$start,$end];
    }
}
