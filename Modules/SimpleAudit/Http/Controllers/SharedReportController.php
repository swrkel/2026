<?php

namespace Modules\SimpleAudit\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SimpleAudit\Services\PurchaseAuditService;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use Throwable;

class SharedReportController extends Controller
{
    protected $tenants;
    protected $audit;

    public function __construct(TenantConnectionManager $tenants, PurchaseAuditService $audit)
    {
        $this->tenants = $tenants;
        $this->audit = $audit;
    }

    public function show($tenant, $token)
    {
        try {
            $tenantId = $tenant === 'current' ? null : $tenant;
            $connection = $this->tenants->connectionForTenant($tenantId);
            if (!Schema::connection($connection)->hasTable('sau_report_shares')) abort(404);
            $share = DB::connection($connection)->table('sau_report_shares')->where('token', $token)->first();
            if (!$share) abort(404);
            if ($share->expires_at && now()->greaterThan($share->expires_at)) {
                return response(__('simpleaudit::simpleaudit.shared_report_expired'), 410);
            }
            $filters = $share->filters_json ? json_decode($share->filters_json, true) : [];
            $filters = array_merge([
                'business_id' => $share->business_id,
                'location_id' => $share->location_id,
                'store_id' => $share->store_id,
                'from' => $share->date_from,
                'to' => $share->date_to,
            ], is_array($filters) ? $filters : []);
            $report = $this->audit->build($connection, $filters, $share->tenant_id ?: $tenantId);
            return view('simpleaudit::purchase-audit.print', [
                'report' => $report,
                'public' => true,
                'shareNote' => $share->note,
            ]);
        } catch (Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) throw $e;
            return response(__('simpleaudit::simpleaudit.shared_report_open_failed', ['message' => $e->getMessage()]), 422);
        }
    }
}
