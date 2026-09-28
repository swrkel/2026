<?php

namespace Modules\CommunicationHub\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\CommunicationHub\Support\TenantConnection;

class CommercialSmsService
{
    public function businessId()
    {
        return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;
    }

    public function locationId()
    {
        return session('business_location.id') ?? session('business_location_id') ?? session('location_id');
    }

    public function queueSingle(array $data): int
    {
        $this->assertMessagesTable();

        $cost = $this->calculateSmsCredits($data['message'] ?? '');
        $messageId = TenantConnection::db()->table('communication_hub_messages')->insertGetId($this->filterColumns('communication_hub_messages', [
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'client_id' => $data['client_id'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'sender_id' => $data['sender_id'] ?? null,
            'module' => $data['module'] ?? 'communication_hub',
            'source_module' => $data['source_module'] ?? 'CommunicationHub',
            'source_reference' => $data['source_reference'] ?? 'manual_sms',
            'channel' => 'sms',
            'recipient' => $data['recipient'],
            'body' => $data['message'],
            'message' => $data['message'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => $data['scheduled_at'] ?? null ? 'scheduled' : 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'attempts' => 0,
            'cost' => $cost,
            'estimated_cost' => $cost,
            'selling_price' => $cost,
            'gateway_cost' => 0,
            'profit_amount' => $cost,
            'wallet_charge_status' => empty($data['client_id']) ? 'not_applicable' : 'charged',
            'payload' => json_encode($data['payload'] ?? []),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->deductWalletIfPossible($data['client_id'] ?? null, $cost, 'SMS queued #'.$messageId);
        $this->audit('sms_queued', ['message_id' => $messageId, 'recipient' => $data['recipient'], 'credits' => $cost]);

        return (int) $messageId;
    }

    public function queueBulk(array $data): int
    {
        $numbers = collect(preg_split('/[\r\n,;]+/', $data['numbers'] ?? ''))
            ->map(fn ($number) => trim($number))
            ->filter()
            ->unique()
            ->values();

        $count = 0;
        foreach ($numbers as $number) {
            $this->queueSingle([
                'client_id' => $data['client_id'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'sender_id' => $data['sender_id'] ?? null,
                'recipient' => $number,
                'message' => $data['message'],
                'source_reference' => 'bulk_sms',
            ]);
            $count++;
        }

        return $count;
    }

    public function processPendingForTesting(): int
    {
        $this->assertMessagesTable();
        $query = TenantConnection::db()->table('communication_hub_messages')->whereIn('status', ['pending', 'scheduled']);
        $this->applyBusinessScope($query, 'communication_hub_messages');
        $count = (clone $query)->count();
        $query->update($this->filterColumns('communication_hub_messages', [
            'status' => 'sent',
            'attempts' => DB::raw('COALESCE(attempts,0)+1'),
            'attempted_at' => now(),
            'sent_at' => now(),
            'updated_at' => now(),
        ]));
        $this->audit('sms_pending_processed', ['count' => $count]);
        return (int) $count;
    }

    public function setMessageStatus($messageId, string $status, array $extra = []): void
    {
        $this->assertMessagesTable();
        $payload = array_merge(['status' => $status, 'updated_at' => now()], $extra);
        $query = TenantConnection::db()->table('communication_hub_messages')->where('id', $messageId);
        $this->applyBusinessScope($query, 'communication_hub_messages');
        $query->update($this->filterColumns('communication_hub_messages', $payload));
        $this->audit('sms_status_updated', ['message_id' => $messageId, 'status' => $status]);
    }

    public function calculateSmsCredits(string $message): int
    {
        $length = mb_strlen($message);
        return max(1, (int) ceil($length / 160));
    }

    public function deductWalletIfPossible($clientId, int $credits, string $note): void
    {
        try {
            if (!$clientId || !TenantConnection::hasTable('communication_hub_wallets') || !TenantConnection::hasTable('communication_hub_wallet_transactions')) {
                return;
            }
            $walletQuery = TenantConnection::db()->table('communication_hub_wallets')->where('client_id', $clientId);
            $this->applyBusinessScope($walletQuery, 'communication_hub_wallets');
            $wallet = $walletQuery->first();
            if (!$wallet) return;

            $opening = (int) $wallet->available_credits;
            $closing = max(0, $opening - $credits);
            TenantConnection::db()->table('communication_hub_wallets')->where('id', $wallet->id)->update($this->filterColumns('communication_hub_wallets', [
                'available_credits' => $closing,
                'total_used' => ((int) $wallet->total_used) + $credits,
                'updated_at' => now(),
            ]));
            TenantConnection::db()->table('communication_hub_wallet_transactions')->insert($this->filterColumns('communication_hub_wallet_transactions', [
                'business_id' => $this->businessId(),
                'client_id' => $clientId,
                'wallet_id' => $wallet->id,
                'type' => 'deduction',
                'credits' => -1 * $credits,
                'opening_balance' => $opening,
                'closing_balance' => $closing,
                'amount' => 0,
                'profit_amount' => 0,
                'reference' => 'SMS-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4)),
                'note' => $note,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (\Throwable $e) {
        }
    }

    public function applyBusinessScope($query, string $table): void
    {
        $businessId = $this->businessId();
        if ($businessId && TenantConnection::hasColumn($table, 'business_id')) {
            $query->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)->orWhereNull('business_id');
            });
        }
    }

    public function filterColumns(string $table, array $payload): array
    {
        return collect($payload)->filter(fn ($value, $column) => TenantConnection::hasColumn($table, $column))->all();
    }

    protected function assertMessagesTable(): void
    {
        if (!TenantConnection::hasTable('communication_hub_messages')) {
            throw new \RuntimeException('communication_hub_messages table is missing. Please run the tenant SQL first.');
        }
    }

    protected function audit(string $action, array $payload = []): void
    {
        try {
            if (!TenantConnection::hasTable('communication_hub_audit_logs')) return;
            TenantConnection::db()->table('communication_hub_audit_logs')->insert($this->filterColumns('communication_hub_audit_logs', [
                'business_id' => $this->businessId(),
                'user_id' => auth()->id(),
                'action' => $action,
                'payload' => json_encode($payload),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (\Throwable $e) {
        }
    }
}
