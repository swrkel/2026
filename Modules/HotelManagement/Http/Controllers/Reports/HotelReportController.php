<?php
namespace Modules\HotelManagement\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\Reports\HotelReportService;

class HotelReportController extends Controller
{
    public function __construct(protected HotelReportService $reports) {}

    public function index(Request $request) { return view('hotelmanagement::reports.index'); }

    public function occupancy(Request $request)
    {
        $summary = $this->reports->occupancy($request->all());
        if ($request->filled('export')) { return $this->export('occupancy_report', collect([$summary]), $request->export); }
        return view('hotelmanagement::reports.occupancy', ['summary' => $summary, 'filters' => $request->all()]);
    }

    public function revenue(Request $request)
    {
        $summary = $this->reports->revenue($request->all());
        if ($request->filled('export')) { return $this->export('revenue_report', collect([$summary]), $request->export); }
        return view('hotelmanagement::reports.revenue', ['summary' => $summary, 'filters' => $request->all()]);
    }

    public function reservationRegister(Request $request)
    {
        $rows = $this->reports->reservationRegister($request->all());
        if ($request->filled('export')) { return $this->export('reservation_register', $rows, $request->export); }
        return view('hotelmanagement::reports.reservation_register', ['rows' => $rows, 'filters' => $request->all()]);
    }

    public function checkinCheckout(Request $request)
    {
        $data = $this->reports->checkinCheckout($request->all());
        if ($request->filled('export')) {
            $rows = collect($data['checkins'])->map(fn($r) => (array) $r)->merge(collect($data['checkouts'])->map(fn($r) => (array) $r));
            return $this->export('checkin_checkout_report', $rows, $request->export);
        }
        return view('hotelmanagement::reports.checkin_checkout', $data + ['filters' => $request->all()]);
    }

    public function housekeeping(Request $request)
    {
        $rows = $this->reports->housekeeping($request->all());
        if ($request->filled('export')) { return $this->export('housekeeping_report', $rows, $request->export); }
        return view('hotelmanagement::reports.housekeeping', ['rows' => $rows, 'filters' => $request->all()]);
    }

    public function guestLedger(Request $request)
    {
        $rows = $this->reports->guestLedger($request->all());
        if ($request->filled('export')) { return $this->export('guest_ledger_report', $rows, $request->export); }
        return view('hotelmanagement::reports.guest_ledger', ['rows' => $rows, 'filters' => $request->all()]);
    }

    public function roomRevenue(Request $request) { return $this->revenue($request); }

    public function analytics(Request $request)
    {
        $summary = $this->reports->analytics($request->all());
        $snapshots = $this->reports->snapshots($request->all());
        if ($request->filled('export')) {
            return $this->export('hotel_analytics', collect([$summary['occupancy'], $summary['revenue'], $summary['counts'], $summary['department_revenue']]), $request->export);
        }
        return view('hotelmanagement::reports.analytics', ['summary' => $summary, 'snapshots' => $snapshots, 'filters' => $request->all()]);
    }

    public function snapshot(Request $request)
    {
        $id = $this->reports->saveSnapshot($request->input('report_key', 'hotel_analytics'), $request->all());
        return redirect()->route('hotel-management.reports.analytics', $request->only(['date_from','date_to','business_location_id']))
            ->with('status', $id ? 'Hotel analytics snapshot saved successfully.' : 'Snapshot table is not available yet. Please run HOTELMGT_014 SQL.');
    }

    protected function export(string $filename, $rows, string $type)
    {
        $type = strtolower($type);
        if (!in_array($type, ['csv', 'excel', 'pdf'], true)) { $type = 'csv'; }
        $extension = $type === 'excel' ? 'xls' : ($type === 'pdf' ? 'html' : 'csv');
        $headers = [
            'Content-Type' => $type === 'csv' ? 'text/csv' : 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$filename.'_'.date('Ymd_His').'.'.$extension.'"',
        ];
        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            $arrayRows = collect($rows)->map(fn($row) => (array) $row)->values();
            if ($arrayRows->isEmpty()) { fputcsv($out, ['No records']); fclose($out); return; }
            fputcsv($out, array_keys($arrayRows->first()));
            foreach ($arrayRows as $row) { fputcsv($out, $row); }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }
}
