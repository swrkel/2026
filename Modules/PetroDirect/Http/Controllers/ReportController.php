<?php

namespace Modules\PetroDirect\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\PetroDirect\Exports\BillingExport;
use Modules\PetroDirect\Exports\CollectionExport;
use Modules\PetroDirect\Exports\MeterSalesExport;
use Modules\PetroDirect\Exports\PumpExport;
use Modules\PetroDirect\Exports\SettlementExport;
use Modules\PetroDirect\Exports\ShiftExport;
use Modules\PetroDirect\Exports\TankExport;
use Modules\PetroDirect\Exports\VehicleExport;
use Modules\PetroDirect\Reports\BillingReport;
use Modules\PetroDirect\Reports\DailyCollectionReport;
use Modules\PetroDirect\Reports\MeterSalesReport;
use Modules\PetroDirect\Reports\PumpReport;
use Modules\PetroDirect\Reports\SettlementReport;
use Modules\PetroDirect\Reports\ShiftReport;
use Modules\PetroDirect\Reports\TankReport;
use Modules\PetroDirect\Reports\VehicleReport;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    private array $registry = [
        'settlements' => [SettlementReport::class, SettlementExport::class, 'PetroDirect Settlements'],
        'shifts' => [ShiftReport::class, ShiftExport::class, 'PetroDirect Shifts'],
        'daily-collections' => [DailyCollectionReport::class, CollectionExport::class, 'PetroDirect Daily Collections'],
        'tanks' => [TankReport::class, TankExport::class, 'PetroDirect Tanks'],
        'pumps' => [PumpReport::class, PumpExport::class, 'PetroDirect Pumps'],
        'meter-sales' => [MeterSalesReport::class, MeterSalesExport::class, 'PetroDirect Meter Sales'],
        'vehicles' => [VehicleReport::class, VehicleExport::class, 'PetroDirect Vehicles'],
        'billing' => [BillingReport::class, BillingExport::class, 'PetroDirect Billing'],
    ];

    public function index()
    {
        return view('petrodirect::reports.index', [
            'reports' => $this->registry,
        ]);
    }

    public function show(Request $request, string $report)
    {
        [$reportClass, , $title] = $this->resolve($report);

        if ($request->ajax()) {
            $query = app($reportClass)->summary($request->all());
            return DataTables::of($query)->make(true);
        }

        return view('petrodirect::reports.show', [
            'report' => $report,
            'title' => $title,
        ]);
    }

    public function export(Request $request, string $report)
    {
        [$reportClass, $exportClass, $title] = $this->resolve($report);
        $query = app($reportClass)->summary($request->all());
        $filename = strtolower(str_replace(' ', '_', $title)) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new $exportClass($query), $filename);
    }

    private function resolve(string $report): array
    {
        if (! array_key_exists($report, $this->registry)) {
            abort(404, 'PetroDirect report not found.');
        }

        return $this->registry[$report];
    }
}
