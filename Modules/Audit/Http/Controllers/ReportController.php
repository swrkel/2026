<?php
namespace Modules\Audit\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Modules\Audit\Models\AuditFinding;
use Modules\Audit\Services\DateRangeService;
use Modules\Audit\Services\ExportService;
use Modules\Audit\Services\FilterOptionService;
use Modules\Audit\Services\SimplePdfService;

class ReportController extends Controller
{
    protected function query(DateRangeService $dates)
    {
        [$from, $to] = $dates->resolve(request('preset'), request('from'), request('to'));

        $q = AuditFinding::query()->whereBetween('last_seen_at', [$from, $to]);

        foreach (['module', 'severity', 'status', 'business_id', 'location_id'] as $field) {
            if (request($field) !== null && request($field) !== '') {
                $q->where($field, request($field));
            }
        }

        $search = trim((string) request('search', ''));
        if ($search !== '') {
            $q->where(function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where('finding_no', 'like', $like)
                    ->orWhere('module', 'like', $like)
                    ->orWhere('rule_code', 'like', $like)
                    ->orWhere('severity', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('message', 'like', $like)
                    ->orWhere('source_table', 'like', $like)
                    ->orWhere('source_id', 'like', $like);
            });
        }

        return [$q, $from, $to];
    }

    protected function decorateRows($rows, array $businesses, array $locations)
    {
        return $rows->map(function ($row) use ($businesses, $locations) {
            $businessId = $row->business_id;
            $locationId = $row->location_id;

            $row->business_name = $businessId === null
                ? '—'
                : ($businesses[$businessId] ?? ('Business #' . $businessId));

            $row->location_name = $locationId === null
                ? '—'
                : ($locations[$locationId] ?? ('Location #' . $locationId));

            $row->last_seen_display = $this->formatDate($row->last_seen_at);

            return $row;
        });
    }

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d M Y H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    public function index(DateRangeService $dates, FilterOptionService $filters)
    {
        [$q, $from, $to] = $this->query($dates);

        $businesses = $filters->businesses();
        $locations = $filters->locations(request('business_id'));

        $summaryQuery = clone $q;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'open' => (clone $summaryQuery)->whereIn('status', ['open', 'acknowledged', 'under_review', 'reopened'])->count(),
            'resolved' => (clone $summaryQuery)->where('status', 'resolved')->count(),
            'false_positive' => (clone $summaryQuery)->where('status', 'false_positive')->count(),
        ];

        $rows = $q->latest('last_seen_at')->paginate(100)->withQueryString();
        $rows->setCollection($this->decorateRows($rows->getCollection(), $businesses, $locations));

        return view('audit::reports.index', compact('rows', 'from', 'to', 'businesses', 'locations', 'summary') + [
            'modules' => AuditFinding::distinct()->orderBy('module')->pluck('module'),
        ]);
    }

    public function export(string $format, DateRangeService $dates, ExportService $export, FilterOptionService $filters, SimplePdfService $simplePdf)
    {
        [$q] = $this->query($dates);

        $businesses = $filters->businesses();
        // Export may include multiple businesses, so use all locations rather than only the selected business.
        $locations = $filters->locations(null);

        $rows = $this->decorateRows($q->latest('last_seen_at')->get(), $businesses, $locations);
        $columns = [
            'finding_no' => 'Finding No',
            'module' => 'Module',
            'rule_code' => 'Rule',
            'severity' => 'Severity',
            'status' => 'Status',
            'title' => 'Issue',
            'business_name' => 'Business',
            'location_name' => 'Location',
            'last_seen_display' => 'Last Seen',
        ];

        if ($format === 'csv') {
            return $export->csv($rows, $columns, 'audit-findings-' . date('Ymd-His') . '.csv');
        }
        if ($format === 'excel') {
            return $export->excelHtml($rows, $columns, 'audit-findings-' . date('Ymd-His') . '.xls');
        }
        if ($format === 'print') {
            return view('audit::reports.print', ['rows' => $rows, 'columns' => $columns]);
        }
        if ($format === 'pdf') {
            $filename = 'audit-findings-' . date('Ymd-His') . '.pdf';

            // Preferred renderer when the application already has Barryvdh DomPDF.
            if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
                try {
                    return \Barryvdh\DomPDF\Facade\Pdf::loadView('audit::reports.print', [
                        'rows' => $rows,
                        'columns' => $columns,
                    ])->setPaper('a4', 'landscape')->download($filename);
                } catch (\Throwable $e) {
                    // Fall through to the module-owned renderer.
                }
            }

            // Some installations have dompdf/dompdf without the Laravel facade.
            if (class_exists('Dompdf\\Dompdf')) {
                try {
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml(view('audit::reports.print', [
                        'rows' => $rows,
                        'columns' => $columns,
                    ])->render());
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $output = $dompdf->output();

                    return response($output, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        'Content-Length' => strlen($output),
                        'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                    ]);
                } catch (\Throwable $e) {
                    // Fall through to the module-owned renderer.
                }
            }

            // Guaranteed standalone fallback: create a real landscape PDF inside Audit.
            return $simplePdf->download($rows, $columns, $filename);
        }

        abort(404);
    }
}
