<?php

namespace Modules\SimpleAudit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SimpleAudit\Services\AccessService;
use Modules\SimpleAudit\Services\ContextService;
use Throwable;

class ContextController extends Controller
{
    protected $context;
    protected $access;

    public function __construct(ContextService $context, AccessService $access)
    {
        $this->context = $context;
        $this->access = $access;
    }

    public function tenants()
    {
        $this->access->assertPermission('view');
        return $this->noStoreJson(['data' => $this->access->isSuperAdmin() ? $this->context->tenants() : []]);
    }

    public function businesses(Request $request)
    {
        try {
            $this->access->assertPermission('view');
            $this->access->assertTenant($request->query('tenant_id'));
            $rows = $this->context->businesses($request->query('tenant_id'));
            return $this->noStoreJson(['data' => $this->access->filterBusinesses($rows)]);
        } catch (Throwable $e) {
            return $this->noStoreJson(['message' => $e->getMessage()], 422);
        }
    }

    public function locations(Request $request)
    {
        $request->validate(['business_id' => 'required|integer|min:1']);
        try {
            $this->access->assertScope($request->query('tenant_id'), (int)$request->query('business_id'));
            $rows = $this->context->locations($request->query('tenant_id'), (int) $request->query('business_id'));
            return $this->noStoreJson(['data' => $this->access->filterLocations($rows)]);
        } catch (Throwable $e) {
            return $this->noStoreJson(['message' => $e->getMessage()], 422);
        }
    }

    public function stores(Request $request)
    {
        $request->validate(['business_id' => 'required|integer|min:1']);
        try {
            $this->access->assertScope($request->query('tenant_id'), (int)$request->query('business_id'), $request->query('location_id') ? (int)$request->query('location_id') : null);
            return $this->noStoreJson(['data' => $this->context->stores(
                $request->query('tenant_id'),
                (int) $request->query('business_id'),
                $request->query('location_id') ? (int) $request->query('location_id') : null
            )]);
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

}
