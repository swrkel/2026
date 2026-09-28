<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\CommunicationHub\Entities\CommunicationHubSetting;
use Modules\CommunicationHub\Support\TenantConnection;
use Modules\CommunicationHub\Support\CommunicationHubSchemaGuard;

class CommunicationHubSettingController extends Controller
{
    public function index()
    {
        $schemaReady = CommunicationHubSchemaGuard::ensureSettingsTable();

        if (! $schemaReady) {
            return view('communicationhub::settings.index', [
                'settings' => collect(),
                'schemaMissing' => true,
            ]);
        }

        $businessId = $this->currentBusinessId();
        $settings = CommunicationHubSetting::query()
            ->when($businessId, function ($query) use ($businessId) {
                $query->where(function ($scope) use ($businessId) {
                    $scope->where('business_id', $businessId)->orWhereNull('business_id');
                });
            })
            ->orderBy('group')->orderBy('key')->get();

        return view('communicationhub::settings.index', compact('settings'))->with('schemaMissing', false);
    }

    public function store(Request $request)
    {
        if (! CommunicationHubSchemaGuard::ensureSettingsTable()) {
            return back()->withInput()->with('error', 'Unable to prepare the Communication Hub settings table in the current tenant database. Please check the Laravel log and database CREATE/ALTER permissions.');
        }

        $businessId = $this->currentBusinessId();

        foreach (($request->settings ?? []) as $key => $value) {
            CommunicationHubSetting::updateOrCreate(
                ['business_id' => $businessId, 'key' => $key],
                ['value' => $value, 'group' => 'general']
            );
        }

        return back()->with('status', 'Settings saved successfully.');
    }

    protected function currentBusinessId()
    {
        return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;
    }
}

