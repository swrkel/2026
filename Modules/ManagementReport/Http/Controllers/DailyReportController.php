<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportRun;
use Modules\ManagementReport\Entities\ReportSetting;
use Modules\ManagementReport\Http\Requests\ReportFilterRequest;
use Modules\ManagementReport\Http\Requests\ShareReportRequest;
use Modules\ManagementReport\Services\Delivery\PdfService;
use Modules\ManagementReport\Services\Delivery\ShareService;
use Modules\ManagementReport\Services\Reports\ReportBuilderService;
use Modules\ManagementReport\Services\Reports\SectionRegistry;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\TenantConnection;

class DailyReportController extends Controller
{
    public function index(SectionRegistry $registry, ReportBuilderService $builder)
    {
        TenantConnection::activate();

        $businessId = (int) session('user.business_id');
        $locations = TenantConnection::schema()->hasTable('business_locations')
            ? TenantConnection::db()->table('business_locations')->where('business_id', $businessId)->orderBy('name')->get()
            : collect();
        $stores = TenantConnection::schema()->hasTable('stores')
            ? TenantConnection::db()->table('stores')->where('business_id', $businessId)->orderBy('name')->get()
            : collect();
        $shifts = collect();
        foreach (['daily_shifts', 'shifts'] as $table) {
            if (TenantConnection::schema()->hasTable($table)) {
                $query = TenantConnection::db()->table($table);
                if (TenantConnection::schema()->hasColumn($table, 'business_id')) {
                    $query->where('business_id', $businessId);
                }
                $shifts = $query->orderByDesc('id')->limit(100)->get();
                break;
            }
        }
        $sections = $registry->definitions();
        $configuredDefaults = [];
        if (TenantConnection::schema()->hasTable('mgmt_report_settings')) {
            $stored = ReportSetting::where('business_id', $businessId)
                ->whereNull('location_id')->whereNull('store_id')
                ->where('setting_key', 'default_sections')->value('setting_value');
            $configuredDefaults = is_array($stored) ? $stored : (json_decode((string) $stored, true) ?: []);
        }

        // 8053 replaces the old Add / Less section with independent
        // Received In / Out / Total Add sections. Preserve existing business defaults without requiring a
        // database migration or forcing the administrator to re-save settings.
        if (in_array('add_less', $configuredDefaults, true)) {
            $configuredDefaults = array_values(array_unique(array_merge(
                array_diff($configuredDefaults, ['add_less']),
                ['received_in', 'out', 'total_add']
            )));
        }
        if ($configuredDefaults && !in_array('received_in', $configuredDefaults, true)) {
            $configuredDefaults[] = 'received_in';
        }

        if ($configuredDefaults) {
            foreach ($sections as $key => &$definition) {
                $definition['default'] = in_array($key, $configuredDefaults, true);
            }
            unset($definition);
        }

        $defaultSectionKeys = [];
        foreach ($sections as $key => $definition) {
            if (!empty($definition['default'])) {
                $defaultSectionKeys[] = $key;
            }
        }
        if (!$defaultSectionKeys) {
            $defaultSectionKeys = array_keys($sections);
        }

        $initialReport = null;
        $initialPreviewError = null;
        try {
            $previewRequest = HttpRequest::create('/', 'GET', [
                'business_id' => $businessId,
                'location_id' => session()->getOldInput('location_id', 0),
                'store_id' => session()->getOldInput('store_id', 0),
                'shift_id' => session()->getOldInput('shift_id', 0),
                'start_date' => session()->getOldInput('start_date', now()->toDateString()),
                'end_date' => session()->getOldInput('end_date', now()->toDateString()),
                'sections' => session()->getOldInput('sections', $defaultSectionKeys),
            ]);
            $initialReport = $builder->preview(ReportContext::fromRequest($previewRequest));
        } catch (\Throwable $exception) {
            report($exception);
            $initialPreviewError = config('app.debug')
                ? $exception->getMessage()
                : 'The live report could not be prepared. Please change the report scope and try again.';
        }

        return view('managementreport::daily.index', compact(
            'locations',
            'stores',
            'shifts',
            'sections',
            'initialReport',
            'initialPreviewError'
        ));
    }

    public function preview(ReportFilterRequest $request, ReportBuilderService $builder)
    {
        TenantConnection::activate();
        $report = $builder->preview(ReportContext::fromRequest($request));
        $html = view('managementreport::daily.preview', compact('report'))->render();

        return response()->json(['ok' => true, 'html' => $html, 'report' => $report]);
    }

    public function generate(ReportFilterRequest $request, ReportBuilderService $builder)
    {
        TenantConnection::activate();
        $run = $builder->persist(ReportContext::fromRequest($request));

        return redirect()->route('managementreport.saved.show', $run->getKey())
            ->with('success', 'Management report snapshot saved successfully.');
    }

    public function print($run)
    {
        $runModel = $this->findRun($run);

        return view('managementreport::daily.print', [
            'run' => $runModel,
            'report' => $runModel->snapshot_payload,
            'publicMode' => false,
        ]);
    }

    public function pdf($run, PdfService $pdf)
    {
        $runModel = $this->findRun($run);

        return response($pdf->binary($runModel), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf->filename($runModel) . '"',
        ]);
    }

    public function share(ShareReportRequest $request, $run, ShareService $service)
    {
        $runModel = $this->findRun($run);
        $result = $service->share(
            $runModel,
            $request->input('channel'),
            array_values(array_filter($request->input('recipients'))),
            $request->input('message'),
            $request->input('expiry_hours'),
            $request->boolean('attach_pdf', true)
        );

        return response()->json([
            'ok' => true,
            'message' => ucfirst($request->input('channel')) . ' delivery prepared successfully.',
            'public_url' => $result['public_url'],
            'launch_url' => $result['launch_url'],
        ]);
    }

    protected function findRun($run): ReportRun
    {
        TenantConnection::activate();
        $model = ReportRun::query()->findOrFail((int) $run);
        abort_unless((int) $model->business_id === (int) session('user.business_id'), 404);

        return $model;
    }
}
