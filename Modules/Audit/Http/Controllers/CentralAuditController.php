<?php

namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Services\CentralAuditRunner;
use Modules\Audit\Services\CentralReportService;
use Modules\Audit\Services\CentralScopeService;
use Modules\Audit\Services\ExportService;
use Modules\Audit\Services\ModuleRegistry;
use Modules\Audit\Services\SimplePdfService;

class CentralAuditController extends Controller
{
    public function dashboard(CentralReportService $reports)
    {
        $data = $reports->dashboard($this->filters());
        return view('audit::central.dashboard', $data);
    }

    public function run(CentralScopeService $scope, ModuleRegistry $registry)
    {
        $sourceKeys = (array) request('source_keys', []);
        $businessKeys = (array) request('business_keys', []);
        return view('audit::central.run', [
            'source_options' => $scope->sources(),
            'business_options' => $sourceKeys ? $scope->businessOptions($sourceKeys) : [],
            'location_options' => $sourceKeys ? $scope->locationOptions($sourceKeys, $businessKeys) : [],
            'modules' => $registry->modules(),
        ]);
    }

    public function storeRun(CentralAuditRunner $runner, ModuleRegistry $registry)
    {
        request()->validate([
            'source_keys' => 'nullable|array',
            'source_keys.*' => 'string|max:255',
            'business_keys' => 'nullable|array',
            'business_keys.*' => 'string|max:1000',
            'location_keys' => 'nullable|array',
            'location_keys.*' => 'string|max:1000',
            'modules' => 'required|array|min:1',
            'modules.*' => 'string|max:100',
        ]);

        $result = $runner->run(request()->all(), auth()->id());
        $message = 'Central Audit completed: ' . $result['completed'] . ' run(s) completed';
        if ($result['skipped']) $message .= ', ' . $result['skipped'] . ' skipped';
        if ($result['failed']) $message .= ', ' . $result['failed'] . ' failed safely';
        $message .= ', ' . $result['findings'] . ' finding(s).';

        return redirect(url('/audit/findings'))
            ->with('success', $message)
            ->with('central_audit_result', $result);
    }

    public function findings(CentralReportService $reports)
    {
        $filters = $this->filters();
        $data = $reports->data($filters, true);
        $data['central_run_result'] = session('central_audit_result');
        return view('audit::central.findings', $data);
    }

    public function rules(ModuleRegistry $registry)
    {
        $rules = collect();
        foreach ($registry->rules() as $class) {
            try {
                $rule = app($class);
                $rules->push([
                    'module' => $rule->module(),
                    'code' => $rule->code(),
                    'title' => $rule->title(),
                    'severity' => $rule->defaultSeverity(),
                    'description' => $rule->description(),
                ]);
            } catch (\Throwable $e) {
            }
        }
        $rules = $rules->sortBy(function ($row) {
            return $row['module'] . '|' . $row['code'];
        })->values();

        return view('audit::central.rules', [
            'rules' => $rules,
            'modules' => $rules->pluck('module')->unique()->values(),
        ]);
    }

    public function schedules(CentralReportService $reports)
    {
        $data = $reports->schedules((array) request('source_keys', []));
        $data['source_options'] = app(CentralScopeService::class)->sources();
        return view('audit::central.schedules', $data);
    }

    public function reports(CentralReportService $reports)
    {
        return view('audit::central.reports', $reports->data($this->filters(), false));
    }

    public function scopeOptions(CentralScopeService $scope)
    {
        $sourceKeys = (array) request('source_keys', []);
        $businessKeys = (array) request('business_keys', []);
        if (!$sourceKeys) {
            return response()->json(['businesses' => [], 'locations' => []]);
        }

        return response()->json([
            'businesses' => $scope->businessOptions($sourceKeys),
            'locations' => $scope->locationOptions($sourceKeys, $businessKeys),
        ]);
    }

    public function export($format, CentralReportService $reports, ExportService $export, SimplePdfService $simplePdf)
    {
        [$rows] = $reports->exportRows($this->filters());
        $columns = [
            'source_label' => 'Tenant / Data Source',
            'database' => 'Database',
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
        $filenameBase = 'central-audit-findings-' . date('Ymd-His');

        if ($format === 'csv') {
            return $export->csv($rows, $columns, $filenameBase . '.csv');
        }
        if ($format === 'excel') {
            return $export->excelHtml($rows, $columns, $filenameBase . '.xls');
        }
        if ($format === 'print') {
            return view('audit::reports.print', ['rows' => $rows, 'columns' => $columns]);
        }
        if ($format === 'pdf') {
            $filename = $filenameBase . '.pdf';
            if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
                try {
                    return \Barryvdh\DomPDF\Facade\Pdf::loadView('audit::reports.print', [
                        'rows' => $rows,
                        'columns' => $columns,
                    ])->setPaper('a4', 'landscape')->download($filename);
                } catch (\Throwable $e) {
                }
            }
            if (class_exists('Dompdf\\Dompdf')) {
                try {
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml(view('audit::reports.print', ['rows' => $rows, 'columns' => $columns])->render());
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
                }
            }
            return $simplePdf->download($rows, $columns, $filename);
        }
        abort(404);
    }

    protected function filters(): array
    {
        return [
            'preset' => request('preset'),
            'from' => request('from'),
            'to' => request('to'),
            'source_keys' => (array) request('source_keys', []),
            'business_keys' => (array) request('business_keys', []),
            'location_keys' => (array) request('location_keys', []),
            'module' => request('module'),
            'severity' => request('severity'),
            'status' => request('status'),
            'search' => request('search'),
            'per_page' => request('per_page', 100),
        ];
    }
}
