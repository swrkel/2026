<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Http\Requests\UpdateSettingsRequest;
use Modules\StockTakingNew\Services\AuditService;
use Modules\StockTakingNew\Services\SettingsService;
use Modules\StockTakingNew\Services\TenantScopeService;

class SettingsController extends Controller
{
    public function index(Request $request, TenantScopeService $scope, SettingsService $settings)
    {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);
        $values = $settings->all($businessId);
        return view('stocktakingnew::settings.index', compact('values'));
    }

    public function update(
        UpdateSettingsRequest $request,
        TenantScopeService $scope,
        SettingsService $settings,
        AuditService $audit
    ) {
        $businessId = $scope->businessId($request);
        abort_unless($businessId, 403);
        $data = $request->validated();
        foreach (['require_approval', 'require_recount_for_variance', 'post_to_shared_inventory'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        foreach (['sms_token', 'whatsapp_token'] as $secretKey) {
            if (! filled($data[$secretKey] ?? null)) {
                unset($data[$secretKey]);
            }
        }
        $settings->setMany($businessId, $data);
        $audit->log($businessId, null, 'settings_updated', 'settings', null, [], array_diff_key(
            $data,
            array_flip(['sms_token', 'whatsapp_token'])
        ));
        return back()->with('status', 'Stock Taking settings saved.');
    }
}
