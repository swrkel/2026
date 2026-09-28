<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubEnterpriseExcellenceController extends Controller
{
    public function executiveCentre(Request $request)
    {
        $channelStats = $this->channelStats();
        $queueStats = $this->queueStats();
        $providerHealth = $this->latest('communication_hub_provider_health', 10);
        $recentActivity = $this->latest('communication_hub_messages', 15);
        $events = $this->latest('communication_hub_event_registry', 10);
        $costSummary = $this->costSummary();
        return view('communicationhub::excellence.executive_centre', compact('channelStats', 'queueStats', 'providerHealth', 'recentActivity', 'events', 'costSummary'));
    }

    public function notificationCentre(Request $request)
    {
        $notifications = $this->latest('communication_hub_global_notifications', 100);
        return view('communicationhub::excellence.notification_centre', compact('notifications'));
    }

    public function notificationStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:2000',
            'type' => 'nullable|string|max:50',
            'target_user_id' => 'nullable|integer',
            'business_location_id' => 'nullable|integer',
        ]);
        if (!TenantConnection::hasTable('communication_hub_global_notifications')) {
            return back()->with('error', 'communication_hub_global_notifications table is missing. Please run Stage 017 SQL.');
        }
        TenantConnection::db()->table('communication_hub_global_notifications')->insert($this->filterColumns('communication_hub_global_notifications', array_merge($data, [
            'business_id' => $this->currentBusinessId(),
            'status' => 'unread',
            'source_module' => 'CommunicationHub',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ])));
        return back()->with('status', 'Global notification created successfully.');
    }

    public function eventRegistry(Request $request)
    {
        $events = $this->latest('communication_hub_event_registry', 150);
        $templates = $this->safeList('communication_hub_templates', ['id','name','channel','type']);
        return view('communicationhub::excellence.event_registry', compact('events', 'templates'));
    }

    public function eventStore(Request $request)
    {
        $data = $request->validate([
            'event_key' => 'required|string|max:191',
            'event_name' => 'required|string|max:191',
            'source_module' => 'required|string|max:100',
            'channels' => 'nullable|array',
            'template_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        if (!TenantConnection::hasTable('communication_hub_event_registry')) {
            return back()->with('error', 'communication_hub_event_registry table is missing. Please run Stage 017 SQL.');
        }
        TenantConnection::db()->table('communication_hub_event_registry')->updateOrInsert(
            ['business_id' => $this->currentBusinessId(), 'event_key' => $data['event_key']],
            $this->filterColumns('communication_hub_event_registry', [
                'business_id' => $this->currentBusinessId(),
                'event_key' => $data['event_key'],
                'event_name' => $data['event_name'],
                'source_module' => $data['source_module'],
                'channels' => json_encode($data['channels'] ?? []),
                'template_id' => $data['template_id'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ])
        );
        return back()->with('status', 'ERP event registry entry saved.');
    }

    public function providerHealth(Request $request)
    {
        $providers = $this->latest('communication_hub_providers', 100);
        $health = $this->latest('communication_hub_provider_health', 100);
        return view('communicationhub::excellence.provider_health', compact('providers','health'));
    }

    public function providerHealthStore(Request $request)
    {
        $data = $request->validate([
            'provider_id' => 'nullable|integer',
            'provider_name' => 'required|string|max:191',
            'channel' => 'required|string|max:50',
            'status' => 'required|string|max:30',
            'response_time_ms' => 'nullable|integer',
            'message' => 'nullable|string|max:1000',
        ]);
        if (!TenantConnection::hasTable('communication_hub_provider_health')) {
            return back()->with('error', 'communication_hub_provider_health table is missing. Please run Stage 017 SQL.');
        }
        TenantConnection::db()->table('communication_hub_provider_health')->insert($this->filterColumns('communication_hub_provider_health', array_merge($data, [
            'business_id' => $this->currentBusinessId(),
            'checked_at' => now(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ])));
        return back()->with('status', 'Provider health status recorded.');
    }

    public function costCentre(Request $request)
    {
        $summary = $this->costSummary();
        $costs = $this->latest('communication_hub_cost_entries', 200);
        return view('communicationhub::excellence.cost_centre', compact('summary','costs'));
    }

    public function costStore(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|string|max:50',
            'provider_name' => 'nullable|string|max:191',
            'cost_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'reference_no' => 'nullable|string|max:191',
            'cost_date' => 'required|date',
            'business_location_id' => 'nullable|integer',
            'note' => 'nullable|string|max:1000',
        ]);
        if (!TenantConnection::hasTable('communication_hub_cost_entries')) {
            return back()->with('error', 'communication_hub_cost_entries table is missing. Please run Stage 017 SQL.');
        }
        TenantConnection::db()->table('communication_hub_cost_entries')->insert($this->filterColumns('communication_hub_cost_entries', array_merge($data, [
            'business_id' => $this->currentBusinessId(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ])));
        return back()->with('status', 'Communication cost entry saved.');
    }

    public function advancedScheduler(Request $request)
    {
        $schedules = $this->latest('communication_hub_advanced_schedules', 100);
        return view('communicationhub::excellence.advanced_scheduler', compact('schedules'));
    }

    public function scheduleStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'channel' => 'required|string|max:50',
            'frequency' => 'required|string|max:50',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'timezone' => 'nullable|string|max:60',
            'payload' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);
        if (!TenantConnection::hasTable('communication_hub_advanced_schedules')) {
            return back()->with('error', 'communication_hub_advanced_schedules table is missing. Please run Stage 017 SQL.');
        }
        TenantConnection::db()->table('communication_hub_advanced_schedules')->insert($this->filterColumns('communication_hub_advanced_schedules', array_merge($data, [
            'business_id' => $this->currentBusinessId(),
            'is_active' => $request->boolean('is_active'),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ])));
        return back()->with('status', 'Advanced schedule saved.');
    }

    public function publicApiPlatform(Request $request)
    {
        $clients = $this->latest('communication_hub_public_api_clients', 100);
        return view('communicationhub::excellence.public_api_platform', compact('clients'));
    }

    public function apiClientStore(Request $request)
    {
        $data = $request->validate([
            'client_name' => 'required|string|max:191',
            'allowed_channels' => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);
        if (!TenantConnection::hasTable('communication_hub_public_api_clients')) {
            return back()->with('error', 'communication_hub_public_api_clients table is missing. Please run Stage 017 SQL.');
        }
        TenantConnection::db()->table('communication_hub_public_api_clients')->insert($this->filterColumns('communication_hub_public_api_clients', [
            'business_id' => $this->currentBusinessId(),
            'client_name' => $data['client_name'],
            'api_key' => 'ch_pub_' . bin2hex(random_bytes(16)),
            'allowed_channels' => json_encode($data['allowed_channels'] ?? []),
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'is_active' => $request->boolean('is_active'),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return back()->with('status', 'Public API client created.');
    }

    public function mobileHooks(Request $request)
    {
        $devices = $this->latest('communication_hub_mobile_devices', 150);
        return view('communicationhub::excellence.mobile_hooks', compact('devices'));
    }

    public function marketplaceArchitecture(Request $request)
    {
        $packages = $this->latest('communication_hub_marketplace_packages', 100);
        return view('communicationhub::excellence.marketplace_architecture', compact('packages'));
    }

    public function finalAudit(Request $request)
    {
        $required = [
            'communication_hub_messages','communication_hub_templates','communication_hub_campaigns','communication_hub_providers',
            'communication_hub_event_registry','communication_hub_global_notifications','communication_hub_provider_health',
            'communication_hub_cost_entries','communication_hub_advanced_schedules','communication_hub_public_api_clients',
            'communication_hub_mobile_devices','communication_hub_marketplace_packages'
        ];
        $checks = collect($required)->map(fn($t) => ['table'=>$t, 'exists'=>TenantConnection::hasTable($t), 'rows'=>$this->safeCount($t)]);
        return view('communicationhub::excellence.final_audit', compact('checks'));
    }

    protected function channelStats(): array
    {
        $channels = ['sms','email','whatsapp','push','in_app','otp','chat'];
        $out = [];
        foreach ($channels as $ch) {
            $out[$ch] = [
                'total' => $this->safeCount('communication_hub_messages', ['channel'=>$ch]),
                'sent' => $this->safeCount('communication_hub_messages', ['channel'=>$ch, 'status'=>'sent']),
                'failed' => $this->safeCount('communication_hub_messages', ['channel'=>$ch, 'status'=>'failed']),
                'pending' => $this->safeCount('communication_hub_messages', ['channel'=>$ch, 'status'=>'pending']),
            ];
        }
        return $out;
    }

    protected function queueStats(): array
    {
        return ['pending'=>$this->safeCount('communication_hub_messages',['status'=>'pending']), 'scheduled'=>$this->safeCount('communication_hub_messages',['status'=>'scheduled']), 'failed'=>$this->safeCount('communication_hub_messages',['status'=>'failed']), 'sent'=>$this->safeCount('communication_hub_messages',['status'=>'sent'])];
    }

    protected function costSummary(): array
    {
        if (!TenantConnection::hasTable('communication_hub_cost_entries')) return ['total'=>0, 'sms'=>0, 'email'=>0, 'whatsapp'=>0, 'push'=>0];
        $rows = TenantConnection::db()->table('communication_hub_cost_entries')->select('channel', DB::raw('SUM(cost_amount) as total'))->when($this->currentBusinessId(), fn($q,$id)=>$q->where('business_id',$id))->groupBy('channel')->pluck('total','channel')->toArray();
        return ['total'=>array_sum($rows), 'sms'=>$rows['sms'] ?? 0, 'email'=>$rows['email'] ?? 0, 'whatsapp'=>$rows['whatsapp'] ?? 0, 'push'=>$rows['push'] ?? 0];
    }

    protected function safeCount(string $table, array $where = []): int
    {
        if (!TenantConnection::hasTable($table)) return 0;
        $q = TenantConnection::db()->table($table);
        if (TenantConnection::hasColumn($table, 'business_id') && $this->currentBusinessId()) $q->where('business_id', $this->currentBusinessId());
        foreach ($where as $k=>$v) if (TenantConnection::hasColumn($table, $k)) $q->where($k, $v);
        return (int)$q->count();
    }

    protected function latest(string $table, int $limit)
    {
        if (!TenantConnection::hasTable($table)) return collect();
        $q = TenantConnection::db()->table($table);
        if (TenantConnection::hasColumn($table, 'business_id') && $this->currentBusinessId()) $q->where('business_id', $this->currentBusinessId());
        return $q->orderByDesc(TenantConnection::hasColumn($table, 'id') ? 'id' : 'created_at')->limit($limit)->get();
    }

    protected function safeList(string $table, array $columns)
    {
        if (!TenantConnection::hasTable($table)) return collect();
        $cols = array_values(array_filter($columns, fn($c)=>TenantConnection::hasColumn($table,$c)));
        if (!$cols) $cols = ['id'];
        $q = TenantConnection::db()->table($table)->select($cols);
        if (TenantConnection::hasColumn($table, 'business_id') && $this->currentBusinessId()) $q->where('business_id', $this->currentBusinessId());
        return $q->orderByDesc('id')->limit(200)->get();
    }

    protected function filterColumns(string $table, array $payload): array
    {
        if (!TenantConnection::hasTable($table)) return $payload;
        return collect($payload)->filter(fn($v,$k)=>TenantConnection::hasColumn($table,$k))->all();
    }

    protected function currentBusinessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id ?? request()->get('business_id');
    }
}
