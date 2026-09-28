<?php

namespace Modules\CommunicationHub\Services\Workflow;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationWorkflowService
{
    public function dashboardStats($businessId = null, $locationId = null): array
    {
        return [
            'registered_events' => $this->count('communication_hub_workflow_events', [], $businessId, $locationId),
            'active_events' => $this->count('communication_hub_workflow_events', ['status' => 'active'], $businessId, $locationId),
            'workflow_rules' => $this->count('communication_hub_workflow_rules', [], $businessId, $locationId),
            'active_rules' => $this->count('communication_hub_workflow_rules', ['status' => 'active'], $businessId, $locationId),
            'event_logs' => $this->count('communication_hub_workflow_event_logs', [], $businessId, $locationId),
            'processed_logs' => $this->count('communication_hub_workflow_event_logs', ['status' => 'processed'], $businessId, $locationId),
            'failed_logs' => $this->count('communication_hub_workflow_event_logs', ['status' => 'failed'], $businessId, $locationId),
            'queued_messages' => $this->count('communication_hub_messages', ['status' => 'pending'], $businessId, $locationId),
        ];
    }

    public function recordEvent(string $eventCode, array $payload = [], ?int $businessId = null, ?int $locationId = null, ?int $userId = null): ?int
    {
        if (!TenantConnection::hasTable('communication_hub_workflow_event_logs')) { return null; }
        $eventCode = Str::slug($eventCode, '_');
        return TenantConnection::db()->table('communication_hub_workflow_event_logs')->insertGetId($this->filterColumns('communication_hub_workflow_event_logs', [
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'event_code' => $eventCode,
            'event_name' => Str::headline(str_replace(['.', '_', '-'], ' ', $eventCode)),
            'source_module' => Arr::get($payload, 'source_module'),
            'source_record_id' => Arr::get($payload, 'source_record_id'),
            'recipient_name' => Arr::get($payload, 'recipient_name'),
            'recipient_mobile' => Arr::get($payload, 'recipient_mobile'),
            'recipient_email' => Arr::get($payload, 'recipient_email'),
            'recipient_whatsapp' => Arr::get($payload, 'recipient_whatsapp'),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'created_messages' => 0,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function processEventLog(int $logId): bool
    {
        if (!TenantConnection::hasTable('communication_hub_workflow_event_logs') || !TenantConnection::hasTable('communication_hub_workflow_rules')) { return false; }
        $log = TenantConnection::db()->table('communication_hub_workflow_event_logs')->where('id', $logId)->first();
        if (!$log) { return false; }

        $rules = TenantConnection::db()->table('communication_hub_workflow_rules')
            ->where('event_code', $log->event_code)
            ->where('status', 'active')
            ->when(TenantConnection::hasColumn('communication_hub_workflow_rules', 'business_id') && $log->business_id, function ($q) use ($log) {
                $q->where(function ($qq) use ($log) { $qq->whereNull('business_id')->orWhere('business_id', $log->business_id); });
            })
            ->orderByRaw("FIELD(priority,'urgent','high','normal','low')")
            ->orderBy('id')
            ->get();

        $created = 0;
        foreach ($rules as $rule) {
            if ($this->passesCondition($rule, $log)) { $created += $this->createMessages($rule, $log); }
        }

        TenantConnection::db()->table('communication_hub_workflow_event_logs')->where('id', $logId)->update($this->filterColumns('communication_hub_workflow_event_logs', [
            'status' => $created > 0 ? 'processed' : 'no_matching_rule',
            'created_messages' => $created,
            'processed_at' => now(),
            'updated_at' => now(),
        ]));
        return true;
    }

    protected function passesCondition($rule, $log): bool
    {
        if (empty($rule->condition_json)) { return true; }
        $conditions = json_decode($rule->condition_json, true);
        if (!is_array($conditions)) { return true; }
        $payload = json_decode($log->payload_json ?? '{}', true) ?: [];
        foreach ($conditions as $field => $expected) {
            if ((string) Arr::get($payload, $field) !== (string) $expected) { return false; }
        }
        return true;
    }

    protected function createMessages($rule, $log): int
    {
        if (!TenantConnection::hasTable('communication_hub_messages')) { return 0; }
        $payload = json_decode($log->payload_json ?? '{}', true) ?: [];
        $channels = array_filter(array_map('trim', explode(',', $rule->channels ?? '')));
        $created = 0;
        foreach ($channels as $channel) {
            $recipient = $this->recipientFor($channel, $rule, $log, $payload);
            if (!$recipient) { continue; }
            $body = $this->render($rule->message_body ?? '', $payload, $log);
            TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
                'business_id' => $log->business_id,
                'business_location_id' => $log->business_location_id,
                'channel' => $channel,
                'recipient' => $recipient,
                'body' => $body,
                'message' => $body,
                'status' => empty($rule->delay_minutes) ? 'pending' : 'scheduled',
                'priority' => $rule->priority ?? 'normal',
                'source' => 'workflow',
                'source_module' => $log->source_module,
                'source_record_id' => $log->source_record_id,
                'workflow_rule_id' => $rule->id ?? null,
                'workflow_event_log_id' => $log->id ?? null,
                'scheduled_at' => empty($rule->delay_minutes) ? now() : now()->addMinutes((int) $rule->delay_minutes),
                'created_by' => $log->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $created++;
        }
        return $created;
    }

    protected function recipientFor(string $channel, $rule, $log, array $payload): ?string
    {
        if (!empty($rule->recipient_field)) { $custom = Arr::get($payload, $rule->recipient_field); if (!empty($custom)) { return (string) $custom; } }
        return match ($channel) {
            'email' => $payload['recipient_email'] ?? $log->recipient_email ?? null,
            'whatsapp' => $payload['recipient_whatsapp'] ?? $log->recipient_whatsapp ?? $payload['recipient_mobile'] ?? $log->recipient_mobile ?? null,
            'push', 'in_app' => $payload['recipient_user_id'] ?? $payload['recipient_device_token'] ?? null,
            default => $payload['recipient_mobile'] ?? $log->recipient_mobile ?? null,
        };
    }

    protected function render(string $text, array $payload, $log): string
    {
        $vars = array_merge($payload, ['event_code' => $log->event_code ?? '', 'event_name' => $log->event_name ?? '', 'recipient_name' => $log->recipient_name ?? '']);
        foreach ($vars as $key => $value) { if (is_scalar($value) || $value === null) { $text = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string) $value, $text); } }
        return $text;
    }

    protected function count(string $table, array $where = [], $businessId = null, $locationId = null): int
    {
        if (!TenantConnection::hasTable($table)) { return 0; }
        $q = TenantConnection::db()->table($table);
        foreach ($where as $k => $v) { $q->where($k, $v); }
        if ($businessId && TenantConnection::hasColumn($table, 'business_id')) { $q->where('business_id', $businessId); }
        if ($locationId && TenantConnection::hasColumn($table, 'business_location_id')) { $q->where(function($qq) use ($locationId){ $qq->whereNull('business_location_id')->orWhere('business_location_id', $locationId); }); }
        return (int) $q->count();
    }

    protected function filterColumns(string $table, array $data): array
    {
        if (!TenantConnection::hasTable($table)) { return $data; }
        return array_intersect_key($data, array_flip(TenantConnection::columns($table)));
    }
}
