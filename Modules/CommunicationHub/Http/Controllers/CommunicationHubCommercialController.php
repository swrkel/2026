<?php

namespace Modules\CommunicationHub\Http\Controllers;

use App\Services\Messaging\GlobalWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\CommunicationHub\Support\TenantConnection;
use Modules\CommunicationHub\Support\CommunicationHubSchemaGuard;

class CommunicationHubCommercialController extends Controller
{
    public function smsDashboard()
    {
        $stats = [
            'packages' => $this->safeCount('communication_hub_sms_packages'),
            'clients' => $this->safeCount('communication_hub_clients'),
            'wallets' => $this->safeCount('communication_hub_wallets'),
            'messages' => $this->safeCount('communication_hub_messages'),
            'sent' => $this->safeCount('communication_hub_messages', ['status' => 'sent']),
            'failed' => $this->safeCount('communication_hub_messages', ['status' => 'failed']),
            'pending' => $this->safeCount('communication_hub_messages', ['status' => 'pending']),
            'profit' => $this->safeSum('communication_hub_wallet_transactions', 'profit_amount'),
            'revenue' => $this->safeSum('communication_hub_wallet_transactions', 'amount'),
        ];

        $recentMessages = $this->safeLatest('communication_hub_messages', 10);
        $recentTransactions = $this->safeLatest('communication_hub_wallet_transactions', 10);

        return view('communicationhub::commercial.dashboard', compact('stats', 'recentMessages', 'recentTransactions'));
    }

    public function sendSms()
    {
        return view('communicationhub::commercial.send_sms', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'mobile', 'business_name']),
            'templates' => $this->safeList('communication_hub_templates', ['id', 'name', 'body', 'content']),
        ]);
    }

    public function sendSmsStore(Request $request)
    {
        $data = $request->validate([
            'recipient' => 'required|string|max:30',
            'message' => 'required|string|max:1000',
            'client_id' => 'nullable|integer',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'client_id' => $data['client_id'] ?? null,
            'channel' => 'sms',
            'recipient' => $data['recipient'],
            'body' => $data['message'],
            'message' => $data['message'],
            'status' => 'pending',
            'priority' => 'normal',
            'cost' => 1,
            'selling_price' => 1,
            'profit_amount' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->deductWalletIfPossible($data['client_id'] ?? null, 1, 'Manual SMS queued');

        return redirect()->route('communicationhub.commercial.send_sms')->with('status', 'SMS queued successfully.');
    }

    public function bulkSms()
    {
        return view('communicationhub::commercial.bulk_sms', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'mobile', 'business_name']),
        ]);
    }

    public function bulkSmsStore(Request $request)
    {
        $data = $request->validate([
            'numbers' => 'required|string',
            'message' => 'required|string|max:1000',
            'client_id' => 'nullable|integer',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        $numbers = collect(preg_split('/[\r\n,;]+/', $data['numbers']))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        foreach ($numbers as $number) {
            TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
                'business_id' => $this->currentBusinessId(),
                'client_id' => $data['client_id'] ?? null,
                'channel' => 'sms',
                'recipient' => $number,
                'body' => $data['message'],
                'message' => $data['message'],
                'status' => 'pending',
                'priority' => 'normal',
                'cost' => 1,
                'selling_price' => 1,
                'profit_amount' => 0,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->deductWalletIfPossible($data['client_id'] ?? null, $numbers->count(), 'Bulk SMS queued');

        return redirect()->route('communicationhub.commercial.bulk_sms')->with('status', $numbers->count() . ' SMS messages queued successfully.');
    }

    public function scheduledSms()
    {
        return view('communicationhub::commercial.scheduled_sms', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'mobile', 'business_name']),
            'scheduled' => $this->safeLatest('communication_hub_messages', 100, ['status' => 'scheduled']),
        ]);
    }

    public function scheduledSmsStore(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|integer',
            'recipient' => 'required|string|max:30',
            'message' => 'required|string|max:1000',
            'scheduled_at' => 'required|date',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'client_id' => $data['client_id'] ?? null,
            'channel' => 'sms',
            'recipient' => $data['recipient'],
            'body' => $data['message'],
            'message' => $data['message'],
            'status' => 'scheduled',
            'priority' => 'normal',
            'scheduled_at' => $data['scheduled_at'],
            'cost' => 1,
            'selling_price' => 1,
            'profit_amount' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.scheduled_sms')->with('status', 'SMS scheduled successfully.');
    }

    public function processPendingMessages()
    {
        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }
        $query = TenantConnection::db()->table('communication_hub_messages')->whereIn('status', ['pending', 'scheduled']);
        $this->applyBusinessScope($query, 'communication_hub_messages');
        $count = (clone $query)->count();
        $query->update($this->filterColumns('communication_hub_messages', [
            'status' => 'sent',
            'attempts' => DB::raw('COALESCE(attempts,0)+1'),
            'sent_at' => now(),
            'updated_at' => now(),
        ]));
        return back()->with('status', $count . ' pending messages marked as sent for testing.');
    }

    public function markMessageSent($message)
    {
        return $this->setMessageStatus($message, 'sent', ['sent_at' => now()]);
    }

    public function markMessageFailed($message)
    {
        return $this->setMessageStatus($message, 'failed', ['response_message' => 'Marked failed by user']);
    }

    public function retryMessage($message)
    {
        return $this->setMessageStatus($message, 'pending', ['response_message' => 'Retry requested by user']);
    }

    protected function setMessageStatus($message, string $status, array $extra = [])
    {
        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }
        $payload = array_merge(['status' => $status, 'updated_at' => now()], $extra);
        TenantConnection::db()->table('communication_hub_messages')->where('id', $message)->update($this->filterColumns('communication_hub_messages', $payload));
        return back()->with('status', 'Message status updated to ' . $status . '.');
    }

    public function smsPackages()
    {
        $schemaReady = CommunicationHubSchemaGuard::ensureSmsPackagesTable();

        return view('communicationhub::commercial.sms_packages', [
            'packages' => $schemaReady ? $this->safeLatest('communication_hub_sms_packages', 50) : collect(),
            'schemaMissing' => ! $schemaReady,
        ]);
    }

    public function smsPackagesStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'credits' => 'required|integer|min:1',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'validity_days' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
        ]);

        if (! CommunicationHubSchemaGuard::ensureSmsPackagesTable()) {
            return back()->withInput()->with('error', 'Unable to prepare the SMS Packages table in the current tenant database. Please check the Laravel log and database CREATE/ALTER permissions.');
        }

        TenantConnection::db()->table('communication_hub_sms_packages')->insert($this->filterColumns('communication_hub_sms_packages', [
            'business_id' => $this->currentBusinessId(),
            'name' => $data['name'],
            'code' => Str::slug($data['name']) . '-' . Str::random(5),
            'credits' => $data['credits'],
            'cost_price' => $data['cost_price'] ?? 0,
            'selling_price' => $data['selling_price'],
            'profit_amount' => ($data['selling_price'] ?? 0) - ($data['cost_price'] ?? 0),
            'validity_days' => $data['validity_days'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => 1,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.sms_packages')->with('status', 'SMS package created successfully.');
    }

    public function businessWallets()
    {
        return view('communicationhub::commercial.wallets', [
            'wallets' => $this->safeLatest('communication_hub_wallets', 50),
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'business_name']),
        ]);
    }

    public function creditRefills()
    {
        return view('communicationhub::commercial.refills', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'business_name']),
            'packages' => $this->safeList('communication_hub_sms_packages', ['id', 'name', 'credits', 'selling_price']),
            'transactions' => $this->safeLatest('communication_hub_wallet_transactions', 25),
        ]);
    }

    public function creditRefillsStore(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|integer',
            'package_id' => 'nullable|integer',
            'credits' => 'required|integer|min:1',
            'amount' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:1000',
        ]);

        if (!TenantConnection::hasTable('communication_hub_wallets') || !TenantConnection::hasTable('communication_hub_wallet_transactions')) {
            return back()->with('error', 'Wallet tables are missing. Please run the tenant SQL first.');
        }

        $wallet = TenantConnection::db()->table('communication_hub_wallets')->where('client_id', $data['client_id'])->first();
        if (!$wallet) {
            $walletId = TenantConnection::db()->table('communication_hub_wallets')->insertGetId($this->filterColumns('communication_hub_wallets', [
                'business_id' => $this->currentBusinessId(),
                'client_id' => $data['client_id'],
                'available_credits' => 0,
                'reserved_credits' => 0,
                'total_purchased' => 0,
                'total_used' => 0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $wallet = TenantConnection::db()->table('communication_hub_wallets')->where('id', $walletId)->first();
        }

        $opening = (int) $wallet->available_credits;
        $closing = $opening + (int) $data['credits'];

        TenantConnection::db()->table('communication_hub_wallets')->where('id', $wallet->id)->update($this->filterColumns('communication_hub_wallets', [
            'available_credits' => $closing,
            'total_purchased' => ((int) $wallet->total_purchased) + (int) $data['credits'],
            'updated_at' => now(),
        ]));

        TenantConnection::db()->table('communication_hub_wallet_transactions')->insert($this->filterColumns('communication_hub_wallet_transactions', [
            'business_id' => $this->currentBusinessId(),
            'client_id' => $data['client_id'],
            'wallet_id' => $wallet->id,
            'package_id' => $data['package_id'] ?? null,
            'type' => 'refill',
            'credits' => $data['credits'],
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'amount' => $data['amount'] ?? 0,
            'profit_amount' => $data['amount'] ?? 0,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.credit_refills')->with('status', 'Wallet refilled successfully.');
    }

    public function creditTransactions()
    {
        return view('communicationhub::commercial.transactions', [
            'transactions' => $this->safeLatest('communication_hub_wallet_transactions', 100),
        ]);
    }

    public function smsClients()
    {
        return view('communicationhub::commercial.clients', [
            'clients' => $this->safeLatest('communication_hub_clients', 50),
        ]);
    }

    public function smsClientsStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'business_name' => 'nullable|string|max:191',
            'mobile' => 'required|string|max:30',
            'email' => 'nullable|email|max:191',
            'client_type' => 'nullable|string|max:50',
        ]);

        if (!TenantConnection::hasTable('communication_hub_clients')) {
            return back()->with('error', 'communication_hub_clients table is missing. Please run the tenant SQL first.');
        }

        $clientId = TenantConnection::db()->table('communication_hub_clients')->insertGetId($this->filterColumns('communication_hub_clients', [
            'business_id' => $this->currentBusinessId(),
            'name' => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'client_type' => $data['client_type'] ?? 'business',
            'status' => 'active',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        if (TenantConnection::hasTable('communication_hub_wallets')) {
            TenantConnection::db()->table('communication_hub_wallets')->insert($this->filterColumns('communication_hub_wallets', [
                'business_id' => $this->currentBusinessId(),
                'client_id' => $clientId,
                'available_credits' => 0,
                'reserved_credits' => 0,
                'total_purchased' => 0,
                'total_used' => 0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return redirect()->route('communicationhub.commercial.sms_clients')->with('status', 'SMS client created successfully.');
    }

    public function resellerDashboard()
    {
        $stats = [
            'clients' => $this->safeCount('communication_hub_clients'),
            'wallets' => $this->safeCount('communication_hub_wallets'),
            'packages' => $this->safeCount('communication_hub_sms_packages'),
            'transactions' => $this->safeCount('communication_hub_wallet_transactions'),
            'available_credits' => $this->safeSum('communication_hub_wallets', 'available_credits'),
            'used_credits' => $this->safeSum('communication_hub_wallets', 'total_used'),
            'profit' => $this->safeSum('communication_hub_wallet_transactions', 'profit_amount'),
        ];

        return view('communicationhub::commercial.reseller_dashboard', compact('stats'));
    }

    public function apiTokens()
    {
        return view('communicationhub::commercial.api_tokens', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'business_name']),
            'tokens' => $this->safeLatest('communication_hub_api_clients', 50),
        ]);
    }

    public function apiTokensStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'client_id' => 'nullable|integer',
            'daily_limit' => 'nullable|integer|min:0',
        ]);

        if (!TenantConnection::hasTable('communication_hub_api_clients')) {
            return back()->with('error', 'communication_hub_api_clients table is missing. Please run the tenant SQL first.');
        }

        $plainToken = Str::random(80);
        $payload = [
            'business_id' => $this->currentBusinessId(),
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'],
            'client_name' => $data['name'],
            'module_name' => 'communication_hub',
            'token' => hash('sha256', $plainToken),
            'token_hash' => hash('sha256', $plainToken),
            'secret' => Str::random(40),
            'allowed_channels' => json_encode(['sms']),
            'permissions' => json_encode(['sms.send', 'sms.balance', 'sms.status']),
            'rate_limit_per_minute' => 60,
            'daily_limit' => $data['daily_limit'] ?? 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        TenantConnection::db()->table('communication_hub_api_clients')->insert($this->filterColumns('communication_hub_api_clients', $payload));

        return redirect()->route('communicationhub.commercial.api_tokens')->with('status', 'API token created successfully.');
    }

    public function apiLogs()
    {
        return view('communicationhub::commercial.api_logs', [
            'logs' => $this->safeLatest('communication_hub_api_request_logs', 100),
        ]);
    }

    public function apiDocumentation()
    {
        return view('communicationhub::commercial.api_documentation');
    }

    public function deliveryReports()
    {
        return view('communicationhub::commercial.delivery_reports', [
            'messages' => $this->safeLatest('communication_hub_messages', 100),
            'events' => $this->safeLatest('communication_hub_delivery_events', 100),
        ]);
    }

    public function profitReports()
    {
        return view('communicationhub::commercial.profit_reports', [
            'transactions' => $this->safeLatest('communication_hub_wallet_transactions', 100),
            'profit' => $this->safeSum('communication_hub_wallet_transactions', 'profit_amount'),
            'revenue' => $this->safeSum('communication_hub_wallet_transactions', 'amount'),
        ]);
    }

    protected function page(string $title, string $description, array $features)
    {
        return view('communicationhub::commercial.page', compact('title', 'description', 'features'));
    }

    protected function safeCount(string $table, array $where = []): int
    {
        try {
            if (!TenantConnection::hasTable($table)) return 0;
            $query = TenantConnection::db()->table($table);
            $this->applyBusinessScope($query, $table);
            foreach ($where as $column => $value) {
                if (TenantConnection::hasColumn($table, $column)) $query->where($column, $value);
            }
            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function safeSum(string $table, string $column): float
    {
        try {
            if (!TenantConnection::hasTable($table) || !TenantConnection::hasColumn($table, $column)) return 0.0;
            $query = TenantConnection::db()->table($table);
            $this->applyBusinessScope($query, $table);
            return (float) $query->sum($column);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    protected function safeLatest(string $table, int $limit, array $where = [])
    {
        try {
            if (!TenantConnection::hasTable($table)) return collect();
            $query = TenantConnection::db()->table($table);
            $this->applyBusinessScope($query, $table);
            foreach ($where as $column => $value) {
                if (TenantConnection::hasColumn($table, $column)) $query->where($column, $value);
            }
            $orderColumn = TenantConnection::hasColumn($table, 'id') ? 'id' : (TenantConnection::hasColumn($table, 'created_at') ? 'created_at' : null);
            if ($orderColumn) $query->orderByDesc($orderColumn);
            return $query->limit($limit)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function safeList(string $table, array $columns)
    {
        try {
            if (!TenantConnection::hasTable($table)) return collect();
            $existing = collect($columns)->filter(fn ($c) => TenantConnection::hasColumn($table, $c))->values()->all();
            if (empty($existing)) return collect();
            $query = TenantConnection::db()->table($table)->select($existing);
            $this->applyBusinessScope($query, $table);
            return $query->limit(200)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function applyBusinessScope($query, string $table): void
    {
        $businessId = $this->currentBusinessId();
        if ($businessId && TenantConnection::hasColumn($table, 'business_id')) {
            $query->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)->orWhereNull('business_id');
            });
        }
    }

    protected function deductWalletIfPossible($clientId, int $credits, string $note): void
    {
        try {
            if (!$clientId || !TenantConnection::hasTable('communication_hub_wallets') || !TenantConnection::hasTable('communication_hub_wallet_transactions')) return;
            $wallet = TenantConnection::db()->table('communication_hub_wallets')->where('client_id', $clientId)->first();
            if (!$wallet) return;
            $opening = (int) $wallet->available_credits;
            $closing = max(0, $opening - $credits);
            TenantConnection::db()->table('communication_hub_wallets')->where('id', $wallet->id)->update($this->filterColumns('communication_hub_wallets', [
                'available_credits' => $closing,
                'total_used' => ((int) $wallet->total_used) + $credits,
                'updated_at' => now(),
            ]));
            TenantConnection::db()->table('communication_hub_wallet_transactions')->insert($this->filterColumns('communication_hub_wallet_transactions', [
                'business_id' => $this->currentBusinessId(),
                'client_id' => $clientId,
                'wallet_id' => $wallet->id,
                'type' => 'deduction',
                'credits' => -1 * $credits,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'amount' => 0,
                'profit_amount' => 0,
                'reference' => 'SMS-' . now()->format('YmdHis'),
                'note' => $note,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (\Throwable $e) {
            // Wallet deduction must not block message queuing during early testing.
        }
    }

    protected function filterColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => TenantConnection::hasColumn($table, $column))
            ->all();
    }



    public function whatsappDashboard()
    {
        $stats = [
            'profiles' => $this->safeCount('communication_hub_whatsapp_profiles'),
            'templates' => $this->safeCount('communication_hub_whatsapp_templates'),
            'messages' => $this->safeCount('communication_hub_messages', ['channel' => 'whatsapp']),
            'sent' => $this->safeCount('communication_hub_messages', ['channel' => 'whatsapp', 'status' => 'sent']),
            'failed' => $this->safeCount('communication_hub_messages', ['channel' => 'whatsapp', 'status' => 'failed']),
            'pending' => $this->safeCount('communication_hub_messages', ['channel' => 'whatsapp', 'status' => 'pending']),
            'scheduled' => $this->safeCount('communication_hub_messages', ['channel' => 'whatsapp', 'status' => 'scheduled']),
        ];
        $messages = $this->safeLatest('communication_hub_messages', 25, ['channel' => 'whatsapp']);
        $templates = $this->safeLatest('communication_hub_whatsapp_templates', 10);
        return view('communicationhub::commercial.whatsapp_dashboard', compact('stats', 'messages', 'templates'));
    }

    public function sendWhatsapp()
    {
        return view('communicationhub::commercial.send_whatsapp', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'mobile', 'business_name']),
            'profiles' => $this->safeList('communication_hub_whatsapp_profiles', ['id', 'profile_name', 'phone_number', 'provider_name']),
            'templates' => $this->safeList('communication_hub_whatsapp_templates', ['id', 'template_name', 'language_code', 'body']),
        ]);
    }

    public function sendWhatsappStore(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|integer',
            'profile_id' => 'nullable|integer',
            'recipient' => 'required|string|max:30',
            'message' => 'required|string|max:4000',
            'message_type' => 'nullable|string|max:30',
            'media_url' => 'nullable|string|max:1000',
            'caption' => 'nullable|string|max:1000',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        $decoratedMessage = app(GlobalWhatsAppService::class)->decorate($data['message'], [
            'business_id' => $this->currentBusinessId(),
            'location_id' => $this->currentLocationId(),
            'page_title' => 'WhatsApp Message',
            'page_no' => 1,
        ]);

        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'client_id' => $data['client_id'] ?? null,
            'profile_id' => $data['profile_id'] ?? null,
            'channel' => 'whatsapp',
            'message_type' => $data['message_type'] ?? 'text',
            'recipient' => $data['recipient'],
            'body' => $decoratedMessage,
            'message' => $decoratedMessage,
            'media_url' => $data['media_url'] ?? null,
            'caption' => $data['caption'] ?? null,
            'status' => 'pending',
            'priority' => 'normal',
            'cost' => 1,
            'selling_price' => 1,
            'profit_amount' => 0,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.send_whatsapp')->with('status', 'WhatsApp message queued successfully.');
    }

    public function bulkWhatsapp()
    {
        return view('communicationhub::commercial.bulk_whatsapp', [
            'clients' => $this->safeList('communication_hub_clients', ['id', 'name', 'mobile', 'business_name']),
            'profiles' => $this->safeList('communication_hub_whatsapp_profiles', ['id', 'profile_name', 'phone_number', 'provider_name']),
            'templates' => $this->safeList('communication_hub_whatsapp_templates', ['id', 'template_name', 'language_code', 'body']),
        ]);
    }

    public function bulkWhatsappStore(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|integer',
            'profile_id' => 'nullable|integer',
            'numbers' => 'required|string',
            'message' => 'required|string|max:4000',
            'message_type' => 'nullable|string|max:30',
            'media_url' => 'nullable|string|max:1000',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        $decoratedMessage = app(GlobalWhatsAppService::class)->decorate($data['message'], [
            'business_id' => $this->currentBusinessId(),
            'location_id' => $this->currentLocationId(),
            'page_title' => 'Bulk WhatsApp Message',
            'page_no' => 1,
        ]);

        $numbers = collect(preg_split('/[\r\n,;]+/', $data['numbers']))->map(fn ($n) => trim($n))->filter()->unique()->values();
        foreach ($numbers as $number) {
            TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
                'business_id' => $this->currentBusinessId(),
                'business_location_id' => $this->currentLocationId(),
                'client_id' => $data['client_id'] ?? null,
                'profile_id' => $data['profile_id'] ?? null,
                'channel' => 'whatsapp',
                'message_type' => $data['message_type'] ?? 'text',
                'recipient' => $number,
                'body' => $decoratedMessage,
                'message' => $decoratedMessage,
                'media_url' => $data['media_url'] ?? null,
                'status' => 'pending',
                'priority' => 'normal',
                'cost' => 1,
                'selling_price' => 1,
                'profit_amount' => 0,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
        return redirect()->route('communicationhub.commercial.bulk_whatsapp')->with('status', $numbers->count() . ' WhatsApp messages queued successfully.');
    }

    public function scheduledWhatsapp()
    {
        return view('communicationhub::commercial.scheduled_whatsapp', [
            'profiles' => $this->safeList('communication_hub_whatsapp_profiles', ['id', 'profile_name', 'phone_number', 'provider_name']),
            'scheduled' => $this->safeLatest('communication_hub_messages', 100, ['channel' => 'whatsapp', 'status' => 'scheduled']),
        ]);
    }

    public function scheduledWhatsappStore(Request $request)
    {
        $data = $request->validate([
            'profile_id' => 'nullable|integer',
            'recipient' => 'required|string|max:30',
            'message' => 'required|string|max:4000',
            'message_type' => 'nullable|string|max:30',
            'media_url' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date',
        ]);
        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }
        $decoratedMessage = app(GlobalWhatsAppService::class)->decorate($data['message'], [
            'business_id' => $this->currentBusinessId(),
            'location_id' => $this->currentLocationId(),
            'page_title' => 'Scheduled WhatsApp Message',
            'date_range' => $data['scheduled_at'],
            'page_no' => 1,
        ]);
        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'profile_id' => $data['profile_id'] ?? null,
            'channel' => 'whatsapp',
            'message_type' => $data['message_type'] ?? 'text',
            'recipient' => $data['recipient'],
            'body' => $decoratedMessage,
            'message' => $decoratedMessage,
            'media_url' => $data['media_url'] ?? null,
            'status' => 'scheduled',
            'priority' => 'normal',
            'scheduled_at' => $data['scheduled_at'],
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return redirect()->route('communicationhub.commercial.scheduled_whatsapp')->with('status', 'WhatsApp message scheduled successfully.');
    }

    public function whatsappTemplates()
    {
        return view('communicationhub::commercial.whatsapp_templates', [
            'templates' => $this->safeLatest('communication_hub_whatsapp_templates', 100),
        ]);
    }

    public function whatsappTemplatesStore(Request $request)
    {
        $data = $request->validate([
            'template_name' => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'language_code' => 'nullable|string|max:20',
            'body' => 'required|string|max:4000',
            'variables' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:30',
        ]);
        if (!TenantConnection::hasTable('communication_hub_whatsapp_templates')) {
            return back()->with('error', 'communication_hub_whatsapp_templates table is missing. Please run the tenant SQL first.');
        }
        TenantConnection::db()->table('communication_hub_whatsapp_templates')->insert($this->filterColumns('communication_hub_whatsapp_templates', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'template_name' => $data['template_name'],
            'category' => $data['category'] ?? 'utility',
            'language_code' => $data['language_code'] ?? 'en',
            'body' => $data['body'],
            'variables' => $data['variables'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return redirect()->route('communicationhub.commercial.whatsapp_templates')->with('status', 'WhatsApp template saved successfully.');
    }

    public function whatsappProfiles()
    {
        return view('communicationhub::commercial.whatsapp_profiles', [
            'profiles' => $this->safeLatest('communication_hub_whatsapp_profiles', 100),
        ]);
    }

    public function whatsappProfilesStore(Request $request)
    {
        $data = $request->validate([
            'profile_name' => 'required|string|max:191',
            'provider_name' => 'required|string|max:100',
            'phone_number' => 'required|string|max:30',
            'business_account_id' => 'nullable|string|max:191',
            'phone_number_id' => 'nullable|string|max:191',
            'api_base_url' => 'nullable|string|max:500',
            'access_token' => 'nullable|string|max:2000',
            'is_default' => 'nullable|boolean',
        ]);
        if (!TenantConnection::hasTable('communication_hub_whatsapp_profiles')) {
            return back()->with('error', 'communication_hub_whatsapp_profiles table is missing. Please run the tenant SQL first.');
        }
        if (!empty($data['is_default'])) {
            $q = TenantConnection::db()->table('communication_hub_whatsapp_profiles');
            $this->applyBusinessScope($q, 'communication_hub_whatsapp_profiles');
            $q->update($this->filterColumns('communication_hub_whatsapp_profiles', ['is_default' => 0, 'updated_at' => now()]));
        }
        TenantConnection::db()->table('communication_hub_whatsapp_profiles')->insert($this->filterColumns('communication_hub_whatsapp_profiles', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'profile_name' => $data['profile_name'],
            'provider_name' => $data['provider_name'],
            'phone_number' => $data['phone_number'],
            'business_account_id' => $data['business_account_id'] ?? null,
            'phone_number_id' => $data['phone_number_id'] ?? null,
            'api_base_url' => $data['api_base_url'] ?? null,
            'access_token' => $data['access_token'] ?? null,
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'status' => 'active',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return redirect()->route('communicationhub.commercial.whatsapp_profiles')->with('status', 'WhatsApp profile saved successfully.');
    }


    public function pushDashboard()
    {
        $stats = [
            'devices' => $this->safeCount('communication_hub_push_devices'),
            'templates' => $this->safeCount('communication_hub_push_templates'),
            'messages' => $this->safeCount('communication_hub_messages', ['channel' => 'push']),
            'sent' => $this->safeCount('communication_hub_messages', ['channel' => 'push', 'status' => 'sent']),
            'failed' => $this->safeCount('communication_hub_messages', ['channel' => 'push', 'status' => 'failed']),
            'pending' => $this->safeCount('communication_hub_messages', ['channel' => 'push', 'status' => 'pending']),
            'scheduled' => $this->safeCount('communication_hub_messages', ['channel' => 'push', 'status' => 'scheduled']),
            'opened' => $this->safeCount('communication_hub_delivery_events', ['event_type' => 'opened']),
        ];

        $messages = $this->safeLatest('communication_hub_messages', 30, ['channel' => 'push']);
        $devices = $this->safeLatest('communication_hub_push_devices', 10);

        return view('communicationhub::commercial.push_dashboard', compact('stats', 'messages', 'devices'));
    }

    public function sendPush()
    {
        return view('communicationhub::commercial.send_push', [
            'devices' => $this->safeList('communication_hub_push_devices', ['id', 'device_name', 'user_id', 'platform', 'device_token']),
            'templates' => $this->safeList('communication_hub_push_templates', ['id', 'template_name', 'title', 'body']),
        ]);
    }

    public function sendPushStore(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'nullable|integer',
            'recipient' => 'nullable|string|max:191',
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:2000',
            'url' => 'nullable|string|max:1000',
            'icon_url' => 'nullable|string|max:1000',
            'image_url' => 'nullable|string|max:1000',
            'priority' => 'nullable|string|max:30',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        $recipient = $data['recipient'] ?? null;
        if (!$recipient && !empty($data['device_id']) && TenantConnection::hasTable('communication_hub_push_devices')) {
            $device = TenantConnection::db()->table('communication_hub_push_devices')->where('id', $data['device_id'])->first();
            $recipient = $device->device_token ?? null;
        }

        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'channel' => 'push',
            'message_type' => 'notification',
            'recipient' => $recipient ?: 'all-devices',
            'subject' => $data['title'],
            'title' => $data['title'],
            'body' => $data['message'],
            'message' => $data['message'],
            'action_url' => $data['url'] ?? null,
            'icon_url' => $data['icon_url'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.send_push')->with('status', 'Push notification queued successfully.');
    }

    public function bulkPush()
    {
        return view('communicationhub::commercial.bulk_push', [
            'templates' => $this->safeList('communication_hub_push_templates', ['id', 'template_name', 'title', 'body']),
        ]);
    }

    public function bulkPushStore(Request $request)
    {
        $data = $request->validate([
            'recipients' => 'nullable|string',
            'target_group' => 'nullable|string|max:100',
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:2000',
            'url' => 'nullable|string|max:1000',
            'priority' => 'nullable|string|max:30',
        ]);

        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }

        $recipients = collect(preg_split('/[\r\n,;]+/', (string)($data['recipients'] ?? '')))->map(fn ($n) => trim($n))->filter()->unique()->values();
        if ($recipients->isEmpty()) {
            $recipients = collect([$data['target_group'] ?? 'all-active-devices']);
        }

        foreach ($recipients as $recipient) {
            TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
                'business_id' => $this->currentBusinessId(),
                'business_location_id' => $this->currentLocationId(),
                'channel' => 'push',
                'message_type' => 'notification',
                'recipient' => $recipient,
                'subject' => $data['title'],
                'title' => $data['title'],
                'body' => $data['message'],
                'message' => $data['message'],
                'action_url' => $data['url'] ?? null,
                'status' => 'pending',
                'priority' => $data['priority'] ?? 'normal',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return redirect()->route('communicationhub.commercial.bulk_push')->with('status', $recipients->count() . ' push notifications queued successfully.');
    }

    public function scheduledPush()
    {
        return view('communicationhub::commercial.scheduled_push', [
            'scheduled' => $this->safeLatest('communication_hub_messages', 100, ['channel' => 'push', 'status' => 'scheduled']),
        ]);
    }

    public function scheduledPushStore(Request $request)
    {
        $data = $request->validate([
            'recipient' => 'nullable|string|max:191',
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:2000',
            'url' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date',
            'priority' => 'nullable|string|max:30',
        ]);
        if (!TenantConnection::hasTable('communication_hub_messages')) {
            return back()->with('error', 'communication_hub_messages table is missing. Please run the tenant SQL first.');
        }
        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'channel' => 'push',
            'message_type' => 'notification',
            'recipient' => $data['recipient'] ?: 'all-active-devices',
            'subject' => $data['title'],
            'title' => $data['title'],
            'body' => $data['message'],
            'message' => $data['message'],
            'action_url' => $data['url'] ?? null,
            'status' => 'scheduled',
            'scheduled_at' => $data['scheduled_at'],
            'priority' => $data['priority'] ?? 'normal',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return redirect()->route('communicationhub.commercial.scheduled_push')->with('status', 'Push notification scheduled successfully.');
    }

    public function pushDevices()
    {
        return view('communicationhub::commercial.push_devices', [
            'devices' => $this->safeLatest('communication_hub_push_devices', 100),
        ]);
    }

    public function pushDevicesStore(Request $request)
    {
        $data = $request->validate([
            'device_name' => 'nullable|string|max:191',
            'user_id' => 'nullable|integer',
            'platform' => 'required|string|max:50',
            'device_token' => 'required|string|max:2000',
            'browser' => 'nullable|string|max:100',
            'device_group' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:30',
        ]);
        if (!TenantConnection::hasTable('communication_hub_push_devices')) {
            return back()->with('error', 'communication_hub_push_devices table is missing. Please run the tenant SQL first.');
        }
        TenantConnection::db()->table('communication_hub_push_devices')->updateOrInsert(
            ['device_token' => $data['device_token']],
            $this->filterColumns('communication_hub_push_devices', [
                'business_id' => $this->currentBusinessId(),
                'business_location_id' => $this->currentLocationId(),
                'device_name' => $data['device_name'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'platform' => $data['platform'],
                'browser' => $data['browser'] ?? null,
                'device_group' => $data['device_group'] ?? null,
                'device_token' => $data['device_token'],
                'status' => $data['status'] ?? 'active',
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ])
        );
        return redirect()->route('communicationhub.commercial.push_devices')->with('status', 'Push device saved successfully.');
    }

    public function pushTemplates()
    {
        return view('communicationhub::commercial.push_templates', [
            'templates' => $this->safeLatest('communication_hub_push_templates', 100),
        ]);
    }

    public function pushTemplatesStore(Request $request)
    {
        $data = $request->validate([
            'template_name' => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:191',
            'body' => 'required|string|max:2000',
            'action_url' => 'nullable|string|max:1000',
            'variables' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:30',
        ]);
        if (!TenantConnection::hasTable('communication_hub_push_templates')) {
            return back()->with('error', 'communication_hub_push_templates table is missing. Please run the tenant SQL first.');
        }
        TenantConnection::db()->table('communication_hub_push_templates')->insert($this->filterColumns('communication_hub_push_templates', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'template_name' => $data['template_name'],
            'category' => $data['category'] ?? 'general',
            'title' => $data['title'],
            'body' => $data['body'],
            'action_url' => $data['action_url'] ?? null,
            'variables' => $data['variables'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return redirect()->route('communicationhub.commercial.push_templates')->with('status', 'Push template saved successfully.');
    }


    public function inAppDashboard()
    {
        $stats = [
            'notifications' => $this->safeCount('communication_hub_in_app_notifications'),
            'unread' => $this->safeCount('communication_hub_in_app_notifications', ['read_at' => null]),
            'urgent' => $this->safeCount('communication_hub_in_app_notifications', ['priority' => 'urgent']),
            'templates' => $this->safeCount('communication_hub_in_app_templates'),
            'alerts' => $this->safeCount('communication_hub_in_app_notifications', ['notification_type' => 'alert']),
            'approvals' => $this->safeCount('communication_hub_in_app_notifications', ['notification_type' => 'approval']),
        ];

        $notifications = $this->safeLatest('communication_hub_in_app_notifications', 30);
        return view('communicationhub::commercial.in_app_dashboard', compact('stats', 'notifications'));
    }

    public function sendInApp()
    {
        return view('communicationhub::commercial.send_in_app', [
            'templates' => $this->safeList('communication_hub_in_app_templates', ['id', 'template_name', 'title', 'body']),
        ]);
    }

    public function sendInAppStore(Request $request)
    {
        $data = $request->validate([
            'recipient_user_id' => 'nullable|integer',
            'recipient_role' => 'nullable|string|max:100',
            'recipient_group' => 'nullable|string|max:100',
            'title' => 'required|string|max:191',
            'message' => 'required|string|max:4000',
            'notification_type' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'action_url' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date',
        ]);

        if (!TenantConnection::hasTable('communication_hub_in_app_notifications')) {
            return back()->with('error', 'communication_hub_in_app_notifications table is missing. Please run the tenant SQL first.');
        }

        TenantConnection::db()->table('communication_hub_in_app_notifications')->insert($this->filterColumns('communication_hub_in_app_notifications', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
            'recipient_role' => $data['recipient_role'] ?? null,
            'recipient_group' => $data['recipient_group'] ?? null,
            'title' => $data['title'],
            'body' => $data['message'],
            'message' => $data['message'],
            'notification_type' => $data['notification_type'] ?? 'info',
            'priority' => $data['priority'] ?? 'normal',
            'action_url' => $data['action_url'] ?? null,
            'status' => 'unread',
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.send_in_app')->with('status', 'In-app notification created successfully.');
    }

    public function inAppInbox(Request $request)
    {
        $notifications = $this->safeLatest('communication_hub_in_app_notifications', 100);
        return view('communicationhub::commercial.in_app_inbox', compact('notifications'));
    }

    public function markInAppRead($notification)
    {
        if (!TenantConnection::hasTable('communication_hub_in_app_notifications')) {
            return back()->with('error', 'communication_hub_in_app_notifications table is missing. Please run the tenant SQL first.');
        }

        $query = TenantConnection::db()->table('communication_hub_in_app_notifications')->where('id', $notification);
        $this->applyBusinessScope($query, 'communication_hub_in_app_notifications');
        $query->update($this->filterColumns('communication_hub_in_app_notifications', [
            'status' => 'read',
            'read_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('status', 'Notification marked as read.');
    }

    public function archiveInApp($notification)
    {
        if (!TenantConnection::hasTable('communication_hub_in_app_notifications')) {
            return back()->with('error', 'communication_hub_in_app_notifications table is missing. Please run the tenant SQL first.');
        }

        $query = TenantConnection::db()->table('communication_hub_in_app_notifications')->where('id', $notification);
        $this->applyBusinessScope($query, 'communication_hub_in_app_notifications');
        $query->update($this->filterColumns('communication_hub_in_app_notifications', [
            'status' => 'archived',
            'archived_at' => now(),
            'updated_at' => now(),
        ]));

        return back()->with('status', 'Notification archived.');
    }

    public function inAppTemplates()
    {
        return view('communicationhub::commercial.in_app_templates', [
            'templates' => $this->safeLatest('communication_hub_in_app_templates', 100),
        ]);
    }

    public function inAppTemplatesStore(Request $request)
    {
        $data = $request->validate([
            'template_name' => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:191',
            'body' => 'required|string|max:4000',
            'notification_type' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'action_url' => 'nullable|string|max:1000',
            'variables' => 'nullable|string|max:1000',
            'status' => 'nullable|string|max:30',
        ]);

        if (!TenantConnection::hasTable('communication_hub_in_app_templates')) {
            return back()->with('error', 'communication_hub_in_app_templates table is missing. Please run the tenant SQL first.');
        }

        TenantConnection::db()->table('communication_hub_in_app_templates')->insert($this->filterColumns('communication_hub_in_app_templates', [
            'business_id' => $this->currentBusinessId(),
            'business_location_id' => $this->currentLocationId(),
            'template_name' => $data['template_name'],
            'category' => $data['category'] ?? 'general',
            'title' => $data['title'],
            'body' => $data['body'],
            'notification_type' => $data['notification_type'] ?? 'info',
            'priority' => $data['priority'] ?? 'normal',
            'action_url' => $data['action_url'] ?? null,
            'variables' => $data['variables'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('communicationhub.commercial.in_app_templates')->with('status', 'In-app template saved successfully.');
    }


    public function chatDashboard()
    {
        $stats = [
            'open_conversations' => $this->safeCount('communication_hub_chat_conversations', ['status' => 'open']),
            'pending_conversations' => $this->safeCount('communication_hub_chat_conversations', ['status' => 'pending']),
            'closed_conversations' => $this->safeCount('communication_hub_chat_conversations', ['status' => 'closed']),
            'messages' => $this->safeCount('communication_hub_chat_messages'),
            'internal_unread' => $this->safeCount('communication_hub_internal_messages', ['status' => 'unread']),
            'templates' => $this->safeCount('communication_hub_chat_templates'),
        ];
        $conversations = $this->safeLatest('communication_hub_chat_conversations', 25);
        $messages = $this->safeLatest('communication_hub_chat_messages', 25);
        return view('communicationhub::commercial.chat_dashboard', compact('stats', 'conversations', 'messages'));
    }

    public function liveChat()
    {
        return view('communicationhub::commercial.live_chat', [
            'conversations' => $this->safeLatest('communication_hub_chat_conversations', 100),
            'messages' => $this->safeLatest('communication_hub_chat_messages', 100),
            'templates' => $this->safeLatest('communication_hub_chat_templates', 30),
        ]);
    }

    public function liveChatConversationStore(Request $request)
    {
        $data = $request->validate([
            'contact_type' => 'nullable|string|max:50', 'contact_id' => 'nullable|integer',
            'contact_name' => 'required|string|max:191', 'contact_mobile' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:191', 'subject' => 'required|string|max:191',
            'priority' => 'nullable|string|max:30', 'source_channel' => 'nullable|string|max:50',
            'assigned_to' => 'nullable|integer', 'initial_message' => 'nullable|string|max:4000',
        ]);
        if (!TenantConnection::hasTable('communication_hub_chat_conversations')) return back()->with('error', 'communication_hub_chat_conversations table is missing. Please run the tenant SQL first.');
        $conversationId = TenantConnection::db()->table('communication_hub_chat_conversations')->insertGetId($this->filterColumns('communication_hub_chat_conversations', [
            'business_id'=>$this->currentBusinessId(), 'business_location_id'=>$this->currentLocationId(),
            'contact_type'=>$data['contact_type'] ?? 'customer', 'contact_id'=>$data['contact_id'] ?? null,
            'contact_name'=>$data['contact_name'], 'contact_mobile'=>$data['contact_mobile'] ?? null, 'contact_email'=>$data['contact_email'] ?? null,
            'subject'=>$data['subject'], 'priority'=>$data['priority'] ?? 'normal', 'source_channel'=>$data['source_channel'] ?? 'manual',
            'status'=>'open', 'assigned_to'=>$data['assigned_to'] ?? auth()->id(), 'created_by'=>auth()->id(), 'last_message_at'=>now(), 'created_at'=>now(), 'updated_at'=>now(),
        ]));
        if (!empty($data['initial_message']) && TenantConnection::hasTable('communication_hub_chat_messages')) {
            TenantConnection::db()->table('communication_hub_chat_messages')->insert($this->filterColumns('communication_hub_chat_messages', [
                'business_id'=>$this->currentBusinessId(), 'business_location_id'=>$this->currentLocationId(), 'conversation_id'=>$conversationId,
                'sender_type'=>'agent', 'sender_user_id'=>auth()->id(), 'message_type'=>'text', 'message_body'=>$data['initial_message'],
                'direction'=>'outbound', 'status'=>'sent', 'created_by'=>auth()->id(), 'created_at'=>now(), 'updated_at'=>now(),
            ]));
        }
        return redirect()->route('communicationhub.commercial.live_chat')->with('status', 'Live chat conversation created successfully.');
    }

    public function liveChatMessageStore(Request $request)
    {
        $data = $request->validate(['conversation_id'=>'required|integer','sender_type'=>'nullable|string|max:30','message_type'=>'nullable|string|max:30','message_body'=>'required|string|max:4000','attachment_url'=>'nullable|string|max:1000']);
        if (!TenantConnection::hasTable('communication_hub_chat_messages')) return back()->with('error', 'communication_hub_chat_messages table is missing. Please run the tenant SQL first.');
        TenantConnection::db()->table('communication_hub_chat_messages')->insert($this->filterColumns('communication_hub_chat_messages', [
            'business_id'=>$this->currentBusinessId(), 'business_location_id'=>$this->currentLocationId(), 'conversation_id'=>$data['conversation_id'],
            'sender_type'=>$data['sender_type'] ?? 'agent', 'sender_user_id'=>auth()->id(), 'message_type'=>$data['message_type'] ?? 'text',
            'message_body'=>$data['message_body'], 'attachment_url'=>$data['attachment_url'] ?? null, 'direction'=>($data['sender_type'] ?? 'agent') === 'customer' ? 'inbound' : 'outbound',
            'status'=>'sent', 'created_by'=>auth()->id(), 'created_at'=>now(), 'updated_at'=>now(),
        ]));
        if (TenantConnection::hasTable('communication_hub_chat_conversations')) { $q=TenantConnection::db()->table('communication_hub_chat_conversations')->where('id',$data['conversation_id']); $this->applyBusinessScope($q,'communication_hub_chat_conversations'); $q->update($this->filterColumns('communication_hub_chat_conversations',['last_message_at'=>now(),'updated_at'=>now()])); }
        return redirect()->route('communicationhub.commercial.live_chat')->with('status', 'Chat message saved successfully.');
    }

    public function closeLiveChatConversation($conversation)
    {
        if (!TenantConnection::hasTable('communication_hub_chat_conversations')) return back()->with('error', 'communication_hub_chat_conversations table is missing. Please run the tenant SQL first.');
        $q=TenantConnection::db()->table('communication_hub_chat_conversations')->where('id',$conversation); $this->applyBusinessScope($q,'communication_hub_chat_conversations');
        $q->update($this->filterColumns('communication_hub_chat_conversations',['status'=>'closed','closed_at'=>now(),'closed_by'=>auth()->id(),'updated_at'=>now()]));
        return back()->with('status', 'Conversation closed successfully.');
    }

    public function internalMessaging()
    {
        return view('communicationhub::commercial.internal_messaging', ['messages'=>$this->safeLatest('communication_hub_internal_messages',100),'templates'=>$this->safeLatest('communication_hub_chat_templates',30)]);
    }

    public function internalMessageStore(Request $request)
    {
        $data=$request->validate(['recipient_user_id'=>'nullable|integer','recipient_role'=>'nullable|string|max:100','recipient_group'=>'nullable|string|max:100','subject'=>'required|string|max:191','message_body'=>'required|string|max:4000','priority'=>'nullable|string|max:30','action_url'=>'nullable|string|max:1000']);
        if (!TenantConnection::hasTable('communication_hub_internal_messages')) return back()->with('error', 'communication_hub_internal_messages table is missing. Please run the tenant SQL first.');
        TenantConnection::db()->table('communication_hub_internal_messages')->insert($this->filterColumns('communication_hub_internal_messages', [
            'business_id'=>$this->currentBusinessId(), 'business_location_id'=>$this->currentLocationId(), 'sender_user_id'=>auth()->id(), 'recipient_user_id'=>$data['recipient_user_id'] ?? null,
            'recipient_role'=>$data['recipient_role'] ?? null, 'recipient_group'=>$data['recipient_group'] ?? null, 'subject'=>$data['subject'], 'message_body'=>$data['message_body'],
            'priority'=>$data['priority'] ?? 'normal', 'action_url'=>$data['action_url'] ?? null, 'status'=>'unread', 'created_by'=>auth()->id(), 'created_at'=>now(), 'updated_at'=>now(),
        ]));
        return redirect()->route('communicationhub.commercial.internal_messaging')->with('status', 'Internal message sent successfully.');
    }

    public function markInternalMessageRead($message)
    {
        if (!TenantConnection::hasTable('communication_hub_internal_messages')) return back()->with('error', 'communication_hub_internal_messages table is missing. Please run the tenant SQL first.');
        $q=TenantConnection::db()->table('communication_hub_internal_messages')->where('id',$message); $this->applyBusinessScope($q,'communication_hub_internal_messages'); $q->update($this->filterColumns('communication_hub_internal_messages',['status'=>'read','read_at'=>now(),'updated_at'=>now()]));
        return back()->with('status', 'Internal message marked as read.');
    }

    public function chatTemplates()
    {
        return view('communicationhub::commercial.chat_templates', ['templates'=>$this->safeLatest('communication_hub_chat_templates',100)]);
    }

    public function chatTemplatesStore(Request $request)
    {
        $data=$request->validate(['template_name'=>'required|string|max:191','template_type'=>'nullable|string|max:50','category'=>'nullable|string|max:50','subject'=>'nullable|string|max:191','body'=>'required|string|max:4000','variables'=>'nullable|string|max:1000','status'=>'nullable|string|max:30']);
        if (!TenantConnection::hasTable('communication_hub_chat_templates')) return back()->with('error', 'communication_hub_chat_templates table is missing. Please run the tenant SQL first.');
        TenantConnection::db()->table('communication_hub_chat_templates')->insert($this->filterColumns('communication_hub_chat_templates', [
            'business_id'=>$this->currentBusinessId(), 'business_location_id'=>$this->currentLocationId(), 'template_name'=>$data['template_name'], 'template_type'=>$data['template_type'] ?? 'chat',
            'category'=>$data['category'] ?? 'general', 'subject'=>$data['subject'] ?? null, 'body'=>$data['body'], 'variables'=>$data['variables'] ?? null, 'status'=>$data['status'] ?? 'active',
            'created_by'=>auth()->id(), 'created_at'=>now(), 'updated_at'=>now(),
        ]));
        return redirect()->route('communicationhub.commercial.chat_templates')->with('status', 'Chat template saved successfully.');
    }


public function automationDashboard()
{
    $service = new \Modules\CommunicationHub\Services\Automation\CommunicationAutomationService();
    return view('communicationhub::commercial.automation_dashboard', [
        'stats' => $service->dashboardStats($this->currentBusinessId()),
        'rules' => $this->safeLatest('communication_hub_automation_rules', 10),
        'events' => $this->safeLatest('communication_hub_automation_events', 10),
    ]);
}

public function automationRules()
{
    return view('communicationhub::commercial.automation_rules', [
        'rules' => $this->safeLatest('communication_hub_automation_rules', 100),
    ]);
}

public function automationRulesStore(Request $request)
{
    $data = $request->validate([
        'rule_name' => 'required|string|max:191',
        'event_code' => 'required|string|max:191',
        'source_module' => 'nullable|string|max:100',
        'channels' => 'required|string|max:191',
        'subject' => 'nullable|string|max:191',
        'message_body' => 'required|string|max:4000',
        'priority' => 'nullable|string|max:30',
        'delay_minutes' => 'nullable|integer|min:0|max:10080',
        'status' => 'nullable|string|max:30',
    ]);
    if (!TenantConnection::hasTable('communication_hub_automation_rules')) {
        return back()->with('error', 'communication_hub_automation_rules table is missing. Please run the tenant SQL first.');
    }
    TenantConnection::db()->table('communication_hub_automation_rules')->insert($this->filterColumns('communication_hub_automation_rules', [
        'business_id' => $this->currentBusinessId(),
        'business_location_id' => $this->currentLocationId(),
        'rule_name' => $data['rule_name'],
        'event_code' => $data['event_code'],
        'source_module' => $data['source_module'] ?? null,
        'channels' => $data['channels'],
        'subject' => $data['subject'] ?? null,
        'message_body' => $data['message_body'],
        'priority' => $data['priority'] ?? 'normal',
        'delay_minutes' => $data['delay_minutes'] ?? 0,
        'status' => $data['status'] ?? 'active',
        'created_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    return redirect()->route('communicationhub.commercial.automation_rules')->with('status', 'Automation rule saved successfully.');
}

public function toggleAutomationRule($rule)
{
    if (!TenantConnection::hasTable('communication_hub_automation_rules')) { return back()->with('error', 'communication_hub_automation_rules table is missing. Please run the tenant SQL first.'); }
    $q = TenantConnection::db()->table('communication_hub_automation_rules')->where('id', $rule); $this->applyBusinessScope($q, 'communication_hub_automation_rules');
    $row = $q->first();
    if ($row) {
        $newStatus = ($row->status ?? 'active') === 'active' ? 'inactive' : 'active';
        $uq = TenantConnection::db()->table('communication_hub_automation_rules')->where('id', $rule); $this->applyBusinessScope($uq, 'communication_hub_automation_rules');
        $uq->update($this->filterColumns('communication_hub_automation_rules', ['status' => $newStatus, 'updated_at' => now()]));
    }
    return back()->with('status', 'Automation rule status updated.');
}

public function deleteAutomationRule($rule)
{
    if (!TenantConnection::hasTable('communication_hub_automation_rules')) { return back()->with('error', 'communication_hub_automation_rules table is missing. Please run the tenant SQL first.'); }
    $q = TenantConnection::db()->table('communication_hub_automation_rules')->where('id', $rule); $this->applyBusinessScope($q, 'communication_hub_automation_rules'); $q->delete();
    return back()->with('status', 'Automation rule deleted.');
}

public function automationEvents()
{
    return view('communicationhub::commercial.automation_events', [
        'events' => $this->safeLatest('communication_hub_automation_events', 150),
    ]);
}

public function automationTestEvent(Request $request)
{
    $data = $request->validate([
        'event_code' => 'required|string|max:191',
        'recipient_name' => 'nullable|string|max:191',
        'recipient_mobile' => 'nullable|string|max:50',
        'recipient_email' => 'nullable|string|max:191',
        'recipient_whatsapp' => 'nullable|string|max:50',
        'source_module' => 'nullable|string|max:100',
        'source_record_id' => 'nullable|string|max:100',
    ]);
    $service = new \Modules\CommunicationHub\Services\Automation\CommunicationAutomationService();
    $service->trigger($data['event_code'], $data, $this->currentBusinessId(), $this->currentLocationId(), auth()->id());
    return redirect()->route('communicationhub.commercial.automation_events')->with('status', 'Test automation event created and processed.');
}

public function processAutomationPending()
{
    $service = new \Modules\CommunicationHub\Services\Automation\CommunicationAutomationService();
    $count = $service->processPending(100);
    return back()->with('status', $count.' pending automation event(s) processed.');
}

public function executeAutomationEvent($event)
{
    $service = new \Modules\CommunicationHub\Services\Automation\CommunicationAutomationService();
    $service->executeEvent((int) $event);
    return back()->with('status', 'Automation event executed.');
}


public function workflowDashboard()
{
    $service = new \Modules\CommunicationHub\Services\Workflow\CommunicationWorkflowService();
    return view('communicationhub::commercial.workflow_dashboard', [
        'stats' => $service->dashboardStats($this->currentBusinessId(), $this->currentLocationId()),
        'events' => $this->safeLatest('communication_hub_workflow_events', 10),
        'rules' => $this->safeLatest('communication_hub_workflow_rules', 10),
        'logs' => $this->safeLatest('communication_hub_workflow_event_logs', 10),
    ]);
}

public function workflowEvents()
{
    return view('communicationhub::commercial.workflow_events', [
        'events' => $this->safeLatest('communication_hub_workflow_events', 200),
    ]);
}

public function workflowEventsStore(Request $request)
{
    $data = $request->validate([
        'event_code' => 'required|string|max:191',
        'event_name' => 'required|string|max:191',
        'source_module' => 'nullable|string|max:100',
        'description' => 'nullable|string|max:1000',
        'payload_schema' => 'nullable|string|max:4000',
        'status' => 'nullable|string|max:30',
    ]);
    if (!TenantConnection::hasTable('communication_hub_workflow_events')) {
        return back()->with('error', 'communication_hub_workflow_events table is missing. Please run the tenant SQL first.');
    }
    TenantConnection::db()->table('communication_hub_workflow_events')->insert($this->filterColumns('communication_hub_workflow_events', [
        'business_id' => $this->currentBusinessId(),
        'business_location_id' => $this->currentLocationId(),
        'event_code' => Str::slug($data['event_code'], '_'),
        'event_name' => $data['event_name'],
        'source_module' => $data['source_module'] ?? null,
        'description' => $data['description'] ?? null,
        'payload_schema' => $data['payload_schema'] ?? null,
        'status' => $data['status'] ?? 'active',
        'created_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    return redirect()->route('communicationhub.commercial.workflow_events')->with('status', 'Workflow event registered successfully.');
}

public function toggleWorkflowEvent($event)
{
    if (!TenantConnection::hasTable('communication_hub_workflow_events')) { return back()->with('error', 'communication_hub_workflow_events table is missing. Please run the tenant SQL first.'); }
    $q = TenantConnection::db()->table('communication_hub_workflow_events')->where('id', $event); $this->applyBusinessScope($q, 'communication_hub_workflow_events');
    $row = $q->first();
    if ($row) {
        $newStatus = ($row->status ?? 'active') === 'active' ? 'inactive' : 'active';
        $uq = TenantConnection::db()->table('communication_hub_workflow_events')->where('id', $event); $this->applyBusinessScope($uq, 'communication_hub_workflow_events');
        $uq->update($this->filterColumns('communication_hub_workflow_events', ['status' => $newStatus, 'updated_at' => now()]));
    }
    return back()->with('status', 'Workflow event status updated.');
}

public function workflowRules()
{
    return view('communicationhub::commercial.workflow_rules', [
        'rules' => $this->safeLatest('communication_hub_workflow_rules', 200),
        'events' => $this->safeLatest('communication_hub_workflow_events', 200),
    ]);
}

public function workflowRulesStore(Request $request)
{
    $data = $request->validate([
        'rule_name' => 'required|string|max:191',
        'event_code' => 'required|string|max:191',
        'source_module' => 'nullable|string|max:100',
        'condition_json' => 'nullable|string|max:4000',
        'channels' => 'required|string|max:191',
        'recipient_source' => 'nullable|string|max:100',
        'recipient_field' => 'nullable|string|max:100',
        'template_key' => 'nullable|string|max:191',
        'message_body' => 'required|string|max:4000',
        'priority' => 'nullable|string|max:30',
        'delay_minutes' => 'nullable|integer|min:0|max:10080',
        'status' => 'nullable|string|max:30',
    ]);
    if (!TenantConnection::hasTable('communication_hub_workflow_rules')) {
        return back()->with('error', 'communication_hub_workflow_rules table is missing. Please run the tenant SQL first.');
    }
    TenantConnection::db()->table('communication_hub_workflow_rules')->insert($this->filterColumns('communication_hub_workflow_rules', [
        'business_id' => $this->currentBusinessId(),
        'business_location_id' => $this->currentLocationId(),
        'rule_name' => $data['rule_name'],
        'event_code' => Str::slug($data['event_code'], '_'),
        'source_module' => $data['source_module'] ?? null,
        'condition_json' => $data['condition_json'] ?? null,
        'channels' => $data['channels'],
        'recipient_source' => $data['recipient_source'] ?? 'payload',
        'recipient_field' => $data['recipient_field'] ?? null,
        'template_key' => $data['template_key'] ?? null,
        'message_body' => $data['message_body'],
        'priority' => $data['priority'] ?? 'normal',
        'delay_minutes' => $data['delay_minutes'] ?? 0,
        'status' => $data['status'] ?? 'active',
        'created_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]));
    return redirect()->route('communicationhub.commercial.workflow_rules')->with('status', 'Workflow rule saved successfully.');
}

public function toggleWorkflowRule($rule)
{
    if (!TenantConnection::hasTable('communication_hub_workflow_rules')) { return back()->with('error', 'communication_hub_workflow_rules table is missing. Please run the tenant SQL first.'); }
    $q = TenantConnection::db()->table('communication_hub_workflow_rules')->where('id', $rule); $this->applyBusinessScope($q, 'communication_hub_workflow_rules');
    $row = $q->first();
    if ($row) {
        $newStatus = ($row->status ?? 'active') === 'active' ? 'inactive' : 'active';
        $uq = TenantConnection::db()->table('communication_hub_workflow_rules')->where('id', $rule); $this->applyBusinessScope($uq, 'communication_hub_workflow_rules');
        $uq->update($this->filterColumns('communication_hub_workflow_rules', ['status' => $newStatus, 'updated_at' => now()]));
    }
    return back()->with('status', 'Workflow rule status updated.');
}

public function deleteWorkflowRule($rule)
{
    if (!TenantConnection::hasTable('communication_hub_workflow_rules')) { return back()->with('error', 'communication_hub_workflow_rules table is missing. Please run the tenant SQL first.'); }
    $q = TenantConnection::db()->table('communication_hub_workflow_rules')->where('id', $rule); $this->applyBusinessScope($q, 'communication_hub_workflow_rules'); $q->delete();
    return back()->with('status', 'Workflow rule deleted.');
}

public function workflowEventLog()
{
    return view('communicationhub::commercial.workflow_event_log', [
        'logs' => $this->safeLatest('communication_hub_workflow_event_logs', 200),
        'events' => $this->safeLatest('communication_hub_workflow_events', 200),
    ]);
}

public function workflowTestEvent(Request $request)
{
    $data = $request->validate([
        'event_code' => 'required|string|max:191',
        'source_module' => 'nullable|string|max:100',
        'source_record_id' => 'nullable|string|max:100',
        'recipient_name' => 'nullable|string|max:191',
        'recipient_mobile' => 'nullable|string|max:50',
        'recipient_email' => 'nullable|string|max:191',
        'recipient_whatsapp' => 'nullable|string|max:50',
        'payload_json' => 'nullable|string|max:4000',
    ]);
    $payload = $data;
    if (!empty($data['payload_json'])) {
        $decoded = json_decode($data['payload_json'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) { $payload = array_merge($payload, $decoded); }
    }
    $service = new \Modules\CommunicationHub\Services\Workflow\CommunicationWorkflowService();
    $logId = $service->recordEvent($data['event_code'], $payload, $this->currentBusinessId(), $this->currentLocationId(), auth()->id());
    $service->processEventLog((int) $logId);
    return redirect()->route('communicationhub.commercial.workflow_event_log')->with('status', 'Workflow test event recorded and processed.');
}

public function processWorkflowLog($log)
{
    $service = new \Modules\CommunicationHub\Services\Workflow\CommunicationWorkflowService();
    $service->processEventLog((int) $log);
    return back()->with('status', 'Workflow event log processed.');
}


public function analyticsDashboard(Request $request)
{
    $channels = ['sms','email','whatsapp','push','in_app'];
    $stats = [
        'total_messages' => $this->safeCount('communication_hub_messages'),
        'sent' => $this->safeCount('communication_hub_messages', ['status' => 'sent']),
        'failed' => $this->safeCount('communication_hub_messages', ['status' => 'failed']),
        'pending' => $this->safeCount('communication_hub_messages', ['status' => 'pending']),
        'scheduled' => $this->safeCount('communication_hub_messages', ['status' => 'scheduled']),
        'api_requests' => $this->safeCount('communication_hub_api_request_logs'),
        'automation_events' => $this->safeCount('communication_hub_automation_events'),
        'workflow_logs' => $this->safeCount('communication_hub_workflow_event_logs'),
    ];
    $channelStats = [];
    foreach ($channels as $channel) {
        $channelStats[$channel] = [
            'total' => $this->safeCount('communication_hub_messages', ['channel' => $channel]),
            'sent' => $this->safeCount('communication_hub_messages', ['channel' => $channel, 'status' => 'sent']),
            'failed' => $this->safeCount('communication_hub_messages', ['channel' => $channel, 'status' => 'failed']),
            'pending' => $this->safeCount('communication_hub_messages', ['channel' => $channel, 'status' => 'pending']),
        ];
    }
    return view('communicationhub::commercial.analytics_dashboard', [
        'stats' => $stats,
        'channelStats' => $channelStats,
        'recentMessages' => $this->safeLatest('communication_hub_messages', 20),
        'recentApiLogs' => $this->safeLatest('communication_hub_api_request_logs', 20),
    ]);
}

public function messageHistoryReport(Request $request)
{
    return view('communicationhub::commercial.message_history_report', [
        'messages' => $this->reportRows('communication_hub_messages', $request, 500),
        'filters' => $request->only(['channel','status','date_from','date_to','recipient']),
    ]);
}

public function providerPerformanceReport(Request $request)
{
    return view('communicationhub::commercial.provider_performance_report', [
        'providers' => $this->safeLatest('communication_hub_providers', 200),
        'messages' => $this->reportRows('communication_hub_messages', $request, 500),
    ]);
}

public function campaignPerformanceReport(Request $request)
{
    return view('communicationhub::commercial.campaign_performance_report', [
        'campaigns' => $this->reportRows('communication_hub_campaigns', $request, 500),
        'messages' => $this->reportRows('communication_hub_messages', $request, 500),
    ]);
}

public function apiGatewayDashboard()
{
    return view('communicationhub::commercial.api_gateway_dashboard', [
        'tokens' => $this->safeLatest('communication_hub_api_clients', 50),
        'logs' => $this->safeLatest('communication_hub_api_request_logs', 100),
        'stats' => [
            'api_clients' => $this->safeCount('communication_hub_api_clients'),
            'api_logs' => $this->safeCount('communication_hub_api_request_logs'),
            'messages' => $this->safeCount('communication_hub_messages'),
            'failed' => $this->safeCount('communication_hub_messages', ['status' => 'failed']),
        ],
    ]);
}

public function createGatewayToken(Request $request)
{
    return $this->apiTokensStore($request);
}

public function communicationAuditCentre(Request $request)
{
    return view('communicationhub::commercial.communication_audit_centre', [
        'auditLogs' => $this->safeLatest('communication_hub_audit_logs', 200),
        'apiLogs' => $this->safeLatest('communication_hub_api_request_logs', 200),
        'deliveryEvents' => $this->safeLatest('communication_hub_delivery_events', 200),
    ]);
}

protected function reportRows(string $table, Request $request, int $limit = 500)
{
    try {
        if (!TenantConnection::hasTable($table)) return collect();
        $query = TenantConnection::db()->table($table);
        $this->applyBusinessScope($query, $table);
        if ($request->filled('channel') && TenantConnection::hasColumn($table, 'channel')) $query->where('channel', $request->channel);
        if ($request->filled('status') && TenantConnection::hasColumn($table, 'status')) $query->where('status', $request->status);
        if ($request->filled('recipient') && TenantConnection::hasColumn($table, 'recipient')) $query->where('recipient', 'like', '%'.$request->recipient.'%');
        if ($request->filled('date_from') && TenantConnection::hasColumn($table, 'created_at')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to') && TenantConnection::hasColumn($table, 'created_at')) $query->whereDate('created_at', '<=', $request->date_to);
        return $query->orderByDesc(TenantConnection::hasColumn($table, 'created_at') ? 'created_at' : 'id')->limit($limit)->get();
    } catch (\Throwable $e) {
        return collect();
    }
}

    protected function currentLocationId()
    {
        return session('business_location_id') ?? session('location_id') ?? optional(auth()->user())->location_id;
    }

    protected function currentBusinessId()
    {
        return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;
    }
}
