<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Services\BannerIdleService;

class BannerManagementController extends Controller
{
    /**
     * Standalone Super Admin page for task 8051.
     *
     * This is intentionally independent of All Businesses > Manage New. The
     * setting is global/central and can target many tenants and businesses at
     * once, so it must not be stored in or rendered through one business's
     * package permission screen.
     */
    public function index(BannerIdleService $service)
    {
        $this->authorizeSuperadmin();

        $settings = $service->settings();
        $tenantOptions = $service->tenantOptions();
        $selectedTenantIds = ! empty($settings['all_tenants'])
            ? [BannerIdleService::ALL]
            : (array) ($settings['tenant_ids'] ?? []);
        $businessOptions = $service->businessOptions($selectedTenantIds);

        return view('superadmin::banner_management.index', compact(
            'settings',
            'tenantOptions',
            'businessOptions',
            'selectedTenantIds'
        ));
    }

    public function update(Request $request, BannerIdleService $service)
    {
        $this->authorizeSuperadmin();

        $validated = $request->validate([
            'banner_idle_seconds' => 'required|integer|min:0',
            'banner_image_size_percent' => 'required|integer|min:25|max:100',
            'banner_tenant_ids' => 'nullable|array',
            'banner_tenant_ids.*' => 'string|max:255',
            'banner_business_targets' => 'nullable|array',
            'banner_business_targets.*' => 'string|max:500',
        ]);

        try {
            $service->saveSettings(
                (int) ($validated['banner_idle_seconds'] ?? 0),
                (int) ($validated['banner_image_size_percent'] ?? 90),
                (array) ($validated['banner_tenant_ids'] ?? [BannerIdleService::ALL]),
                (array) ($validated['banner_business_targets'] ?? [BannerIdleService::ALL])
            );

            return redirect()
                ->route('superadmin.banner-management.index')
                ->with('status', [
                    'success' => 1,
                    'msg' => 'Banners Management settings saved successfully.',
                ]);
        } catch (\Throwable $e) {
            Log::error('Banners Management settings save failed.', [
                'user_id' => optional(auth()->user())->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('superadmin.banner-management.index')
                ->withInput()
                ->with('status', [
                    'success' => 0,
                    'msg' => $e->getMessage(),
                ]);
        }
    }

    public function businessOptions(Request $request, BannerIdleService $service)
    {
        $this->authorizeSuperadmin();

        $tenantIds = (array) $request->input('tenant_ids', []);

        return response()->json([
            'success' => 1,
            'data' => $service->businessOptions($tenantIds),
        ]);
    }

    public function idlePayload(BannerIdleService $service)
    {
        try {
            return response()->json($service->runtimePayload())
                ->header('Cache-Control', 'private, no-store, max-age=0');
        } catch (\Throwable $e) {
            Log::warning('Idle banner payload failed safely.', [
                'user_id' => optional(auth()->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'enabled' => false,
                'idle_seconds' => 0,
                'idle_minutes' => 0,
                'image_size_percent' => 90,
                'banners' => [],
            ])->header('Cache-Control', 'private, no-store, max-age=0');
        }
    }

    /**
     * Same-origin external JavaScript runtime used by the safe page loader.
     * Serving the viewer as a separate script keeps legacy page inline scripts
     * completely untouched.
     */
    public function idleRuntime()
    {
        try {
            $javascript = view('superadmin::idle_banner.runtime_js', [
                'payloadUrl' => route('superadmin.banner.idle.payload'),
            ])->render();

            return response($javascript, 200)
                ->header('Content-Type', 'application/javascript; charset=UTF-8')
                ->header('Cache-Control', 'private, no-store, max-age=0');
        } catch (\Throwable $e) {
            Log::warning('Idle banner runtime script failed safely.', [
                'user_id' => optional(auth()->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response("/* idle banner runtime unavailable */\n", 200)
                ->header('Content-Type', 'application/javascript; charset=UTF-8')
                ->header('Cache-Control', 'private, no-store, max-age=0');
        }
    }

    private function authorizeSuperadmin(): void
    {
        if (! auth()->user() || ! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
