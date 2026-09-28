<?php
namespace Modules\RiceMill\Services;

use Illuminate\Http\Request;

class TenantContext
{
    public function __construct(private Request $request) {}

    /**
     * Current Stancl tenant key. Rice Mill data itself remains in the already
     * initialized tenant database, so this is an identity/readiness check rather
     * than a second tenant-selection mechanism.
     */
    public function tenantId(): ?string
    {
        try {
            if (function_exists('tenant')) {
                $id = tenant('id');
                return $id === null || $id === '' ? null : (string) $id;
            }
        } catch (\Throwable $e) {
            // Keep the host tenancy middleware authoritative.
        }

        return null;
    }

    public function businessId(): int
    {
        $id = (int)($this->request->session()->get('user.business_id') ?? $this->request->session()->get('business_id') ?? 0);
        if ($id <= 0) abort(403, 'Business context is missing.');
        return $id;
    }

    public function userId(): int { return (int) optional($this->request->user())->id; }

    public function locationId(?int $requested = null): ?int
    {
        if ($requested) {
            return $requested;
        }

        // UserLocationAccess.php stores the system-wide selected location here.
        $current = $this->request->session()->get('user.current_location');
        if ($current) {
            return (int) $current;
        }

        // Backwards-compatible fallback for older sessions.
        $legacy = $this->request->session()->get('user.location_id');
        return $legacy ? (int) $legacy : null;
    }
}
