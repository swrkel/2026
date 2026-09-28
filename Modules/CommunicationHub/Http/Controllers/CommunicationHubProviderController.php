<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\CommunicationHub\Entities\CommunicationHubProvider;
use Modules\CommunicationHub\Services\Providers\ProviderDriverRegistry;
use Modules\CommunicationHub\Services\Providers\ProviderHealthService;

class CommunicationHubProviderController extends Controller
{
    public function index(ProviderDriverRegistry $registry)
    {
        $providers = CommunicationHubProvider::orderBy('channel')->orderBy('priority')->paginate(25);
        $driverOptions = $registry->options();

        return view('communicationhub::providers.index', compact('providers', 'driverOptions'));
    }

    public function create(ProviderDriverRegistry $registry)
    {
        return view('communicationhub::providers.form', [
            'provider' => new CommunicationHubProvider(['priority' => 1, 'is_active' => true]),
            'driverOptions' => $registry->options(),
        ]);
    }

    public function store(Request $request)
    {
        CommunicationHubProvider::create($this->validated($request));

        return redirect()->route('communicationhub.providers.index')->with('status', 'Provider created successfully.');
    }

    public function edit(CommunicationHubProvider $provider, ProviderDriverRegistry $registry)
    {
        return view('communicationhub::providers.form', [
            'provider' => $provider,
            'driverOptions' => $registry->options(),
        ]);
    }

    public function update(Request $request, CommunicationHubProvider $provider)
    {
        $provider->update($this->validated($request));

        return redirect()->route('communicationhub.providers.index')->with('status', 'Provider updated successfully.');
    }

    public function destroy(CommunicationHubProvider $provider)
    {
        $provider->delete();

        return back()->with('status', 'Provider deleted.');
    }

    public function health(CommunicationHubProvider $provider, ProviderHealthService $healthService)
    {
        $result = $healthService->check($provider);

        return back()->with($result['healthy'] ?? false ? 'status' : 'error', $result['response_message'] ?? 'Health check completed.');
    }

    public function toggle(CommunicationHubProvider $provider)
    {
        $provider->update(['is_active' => ! $provider->is_active]);

        return back()->with('status', 'Provider status updated.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'channel' => 'required|string|max:50',
            'driver' => 'required|string|max:100',
            'country_code' => 'nullable|string|max:10',
            'priority' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'health_status' => 'nullable|string|max:30',
            'cost_per_message' => 'nullable|numeric|min:0',
            'daily_limit' => 'nullable|integer|min:0',
            'monthly_limit' => 'nullable|integer|min:0',
            'provider_config_json' => 'nullable|string',
        ]);

        $config = [];
        if (! empty($data['provider_config_json'])) {
            $decoded = json_decode($data['provider_config_json'], true);
            if (! is_array($decoded)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'provider_config_json' => 'Provider configuration must be valid JSON.',
                ]);
            }
            $config = $decoded;
        }

        return [
            'name' => $data['name'],
            'channel' => $data['channel'],
            'driver' => $data['driver'],
            'country_code' => strtoupper($data['country_code'] ?? '') ?: null,
            'priority' => $data['priority'] ?? 1,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'health_status' => $data['health_status'] ?? 'unknown',
            'cost_per_message' => $data['cost_per_message'] ?? 0,
            'daily_limit' => $data['daily_limit'] ?? null,
            'monthly_limit' => $data['monthly_limit'] ?? null,
            'provider_config' => $config,
        ];
    }
}
