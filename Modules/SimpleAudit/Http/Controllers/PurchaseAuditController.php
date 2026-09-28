<?php

namespace Modules\SimpleAudit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\SimpleAudit\Services\AccessService;
use Modules\SimpleAudit\Services\PurchaseAuditService;
use Modules\SimpleAudit\Services\ReportExportService;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use RuntimeException;
use Throwable;

class PurchaseAuditController extends Controller
{
    protected $tenants;
    protected $audit;
    protected $exports;
    protected $access;

    public function __construct(TenantConnectionManager $tenants, PurchaseAuditService $audit, ReportExportService $exports, AccessService $access)
    {
        $this->tenants = $tenants;
        $this->audit = $audit;
        $this->exports = $exports;
        $this->access = $access;
    }

    public function index()
    {
        $this->access->assertPermission('view');
        return view('simpleaudit::purchase-audit.index', [
            'showStoreFilter' => config('simpleaudit.show_store_filter', true),
            'defaultPageLength' => config('simpleaudit.default_page_length', 25),
        ]);
    }

    public function data(Request $request)
    {
        try {
            $this->access->assertPermission('view');
            [$connection, $tenantId, $filters] = $this->contextFromRequest($request);
            $this->assertInstalled($connection);
            $report = $this->audit->build($connection, $filters, $tenantId);
            return $this->noStoreJson($report);
        } catch (Throwable $e) {
            return $this->noStoreJson(['message' => $e->getMessage()], 422);
        }
    }

    public function details(Request $request)
    {
        try {
            $this->access->assertPermission('view');
            $request->validate([
                'section' => 'required|string',
                'key' => 'required|string|max:191',
                'column' => 'nullable|string|max:80',
            ]);
            [$connection, $tenantId, $filters] = $this->contextFromRequest($request);
            $this->assertInstalled($connection);
            $details = $this->audit->details($connection, $filters, $request->query('section'), $request->query('key'), $request->query('column'));
            return $this->noStoreJson($details);
        } catch (Throwable $e) {
            return $this->noStoreJson(['message' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request, $format)
    {
        try {
            $this->access->assertPermission($format === 'pdf' ? 'pdf' : 'export');
            [$connection, $tenantId, $filters] = $this->contextFromRequest($request);
            $this->assertInstalled($connection);
            $report = $this->audit->build($connection, $filters, $tenantId);
            $this->logActivity($connection, $filters['business_id'], 'export_' . $format, null, ['filters' => $filters]);

            if ($format === 'csv') {
                return response($this->exports->csv($report), 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $this->exports->filename($report, 'csv') . '"',
                ]);
            }
            if ($format === 'xls') {
                return response($this->exports->xls($report), 200, [
                    'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $this->exports->filename($report, 'xls') . '"',
                ]);
            }
            if ($format === 'pdf') {
                return response($this->exports->pdf($report), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $this->exports->filename($report, 'pdf') . '"',
                ]);
            }
            abort(404);
        } catch (Throwable $e) {
            return response($e->getMessage(), 422);
        }
    }

    public function printView(Request $request)
    {
        try {
            $this->access->assertPermission('print');
            [$connection, $tenantId, $filters] = $this->contextFromRequest($request);
            $this->assertInstalled($connection);
            $report = $this->audit->build($connection, $filters, $tenantId);
            $this->logActivity($connection, $filters['business_id'], 'print', null, ['filters' => $filters]);
            return view('simpleaudit::purchase-audit.print', ['report' => $report, 'public' => false]);
        } catch (Throwable $e) {
            return response($e->getMessage(), 422);
        }
    }

    public function share(Request $request)
    {
        try {
            $this->access->assertPermission('whatsapp');
            [$connection, $tenantId, $filters] = $this->contextFromRequest($request);
            $this->assertInstalled($connection);
            $share = $this->createShare($connection, $tenantId, $filters, $request->input('note'));
            return response()->json($share);
        } catch (Throwable $e) {
            return $this->noStoreJson(['message' => $e->getMessage()], 422);
        }
    }

    public function email(Request $request)
    {
        $this->access->assertPermission('email');
        $request->validate(['email' => 'required|email', 'note' => 'nullable|string|max:2000']);
        try {
            [$connection, $tenantId, $filters] = $this->contextFromRequest($request);
            $this->assertInstalled($connection);
            $share = $this->createShare($connection, $tenantId, $filters, $request->input('note'));
            $businessName = DB::connection($connection)->table('business')->where('id', $filters['business_id'])->value('name');
            $note = trim((string) $request->input('note'));
            $body = __('simpleaudit::simpleaudit.email_body_title') . "\n"
                . __('simpleaudit::simpleaudit.business') . ": {$businessName}\n"
                . __('simpleaudit::simpleaudit.date_period') . ": {$filters['from']} " . __('simpleaudit::simpleaudit.to') . " {$filters['to']}\n\n"
                . ($note !== '' ? ($note . "\n\n") : '')
                . __('simpleaudit::simpleaudit.open_report') . ": {$share['url']}\n";

            Mail::raw($body, function ($message) use ($request, $businessName) {
                $message->to($request->input('email'))
                    ->subject(__('simpleaudit::simpleaudit.email_subject', ['business' => $businessName]));
            });
            $this->logActivity($connection, $filters['business_id'], 'email', $share['token'], [
                'email' => $request->input('email'), 'filters' => $filters,
            ], $request->input('note'));
            return response()->json(['message' => __('simpleaudit::simpleaudit.email_sent'), 'url' => $share['url']]);
        } catch (Throwable $e) {
            return $this->noStoreJson(['message' => $e->getMessage()], 422);
        }
    }

    protected function noStoreJson(array $payload, $status = 200)
    {
        return response()->json($payload, $status)->withHeaders([
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function contextFromRequest(Request $request)
    {
        $filters = [
            'business_id' => (int) $request->input('business_id'),
            'location_id' => $request->input('location_id') ?: null,
            'store_id' => $request->input('store_id') ?: null,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];
        if (!$filters['business_id']) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.select_business_error'));
        }
        $tenantId = $request->input('tenant_id') ?: null;
        $this->access->assertScope($tenantId, $filters['business_id'], $filters['location_id']);
        $connection = $this->tenants->connectionForTenant($tenantId);
        if (!$tenantId) {
            $tenantId = $this->tenants->tenantIdForConnection($connection) ?: $this->tenants->currentTenantIdFromHost();
        }
        return [$connection, $tenantId, $filters];
    }

    protected function assertInstalled($connection)
    {
        foreach (['sau_settings','sau_change_events','sau_stock_snapshots','sau_report_shares','sau_activity_logs'] as $table) {
            if (!Schema::connection($connection)->hasTable($table)) {
                throw new RuntimeException(__('simpleaudit::simpleaudit.module_not_installed'));
            }
        }
    }

    protected function createShare($connection, $tenantId, array $filters, $note = null)
    {
        $token = Str::random(64);
        $hours = max(1, (int) config('simpleaudit.share_expiry_hours', 72));
        $tenantKey = $tenantId ?: 'current';
        DB::connection($connection)->table('sau_report_shares')->insert([
            'token' => $token,
            'tenant_id' => $tenantId,
            'business_id' => $filters['business_id'],
            'location_id' => $filters['location_id'] ?: null,
            'store_id' => $filters['store_id'] ?: null,
            'date_from' => $filters['from'],
            'date_to' => $filters['to'],
            'filters_json' => json_encode($filters),
            'note' => $note,
            'created_by' => auth()->check() ? auth()->id() : null,
            'expires_at' => now()->addHours($hours),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $url = route('simpleaudit.share.public', ['tenant' => $tenantKey, 'token' => $token]);
        $this->logActivity($connection, $filters['business_id'], 'share_link', $token, ['filters' => $filters], $note);
        return ['token' => $token, 'url' => $url, 'expires_at' => now()->addHours($hours)->format('Y-m-d H:i:s')];
    }

    protected function logActivity($connection, $businessId, $action, $referenceId = null, array $meta = [], $note = null)
    {
        try {
            DB::connection($connection)->table('sau_activity_logs')->insert([
                'business_id' => $businessId ?: null,
                'user_id' => auth()->check() ? auth()->id() : null,
                'action' => $action,
                'reference_type' => $referenceId ? 'report_share' : 'purchase_audit',
                'reference_id' => $referenceId,
                'note' => $note,
                'meta_json' => json_encode($meta),
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Report/export must not fail only because action logging failed.
        }
    }
}
