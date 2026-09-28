<?php

namespace Modules\Audit\Services;

use Illuminate\Support\Facades\DB;

class AuditContext
{
    public $tenantKey;
    public $businessId;
    public $locationId;
    public $userId;
    public $from;
    public $to;
    public $meta;

    public function __construct($tenantKey = null, $businessId = null, $locationId = null, $userId = null, $from = null, $to = null, array $meta = [])
    {
        $this->tenantKey = $tenantKey;
        $this->businessId = $businessId;
        $this->locationId = $locationId;
        $this->userId = $userId;
        $this->from = $from;
        $this->to = $to;
        $this->meta = $meta;
    }

    public static function fromRequest($request = null): self
    {
        $request = $request ?: request();
        $user = auth()->user();

        $businessId = $request->input('business_id');
        if ($businessId === null) {
            $businessId = session('user.business_id', session('business.id'));
        }

        $locationId = $request->input('location_id');
        if ($locationId === null) {
            $locationId = session('user.location_id', session('location_id'));
        }

        return self::fromRuntime(
            $businessId ?: null,
            $locationId ?: null,
            $user ? $user->getAuthIdentifier() : null,
            $request->input('from'),
            $request->input('to')
        );
    }

    public static function fromRuntime($businessId = null, $locationId = null, $userId = null, $from = null, $to = null): self
    {
        $tenantKey = null;
        $database = null;

        try {
            if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
                $tenant = tenancy()->tenant;
                $tenantKey = method_exists($tenant, 'getTenantKey')
                    ? (string) $tenant->getTenantKey()
                    : (isset($tenant->id) ? (string) $tenant->id : null);
            }
        } catch (\Throwable $e) {
        }

        try {
            $database = (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
        }

        return new self(
            $tenantKey ?: $database ?: config('database.default'),
            $businessId,
            $locationId,
            $userId,
            $from,
            $to,
            [
                'connection' => config('database.default'),
                'database' => $database,
                'tenant_initialized' => $tenantKey !== null,
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_key' => $this->tenantKey,
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'user_id' => $this->userId,
            'from' => $this->from,
            'to' => $this->to,
            'meta' => $this->meta,
        ];
    }
}
