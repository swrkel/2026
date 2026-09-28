<?php

namespace Modules\CommunicationHub\Services\Automation;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationAutomationService
{
    public function dashboardStats($businessId = null): array
    {
        return [
            'rules' => $this->count('communication_hub_automation_rules', [], $businessId),
            'active_rules' => $this->count('communication_hub_automation_rules', ['status' => 'active'], $businessId),
            'events' => $this->count('communication_hub_automation_events', [], $businessId),
            'pending_events' => $this->count('communication_hub_automation_events', ['status' => 'pending'], $businessId),
            'executed_events' => $this->count('communication_hub_automation_events', ['status' => 'executed'], $businessId),
            'failed_events' => $this->count('communication_hub_automation_events', ['status' => 'failed'], $businessId),
        ];
    }

    public function trigger(string $eventCode, array $payload = [], ?int $businessId = null, ?int $locationId = null, ?int $userId = null): ?int
    {
        if (!TenantConnection::hasTable('communication_hub_automation_events')) { return null; }
        $eventId = TenantConnection::db()->table('communication_hub_automation_events')->insertGetId($this->filterColumns('communication_hub_automation_events', [
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
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $this->executeEvent($eventId);
        return $eventId;
    }

    public function executeEvent(int $eventId): bool
    {
        if (!TenantConnection::hasTable('communication_hub_automation_events') || !TenantConnection::hasTable('communication_hub_automation_rules')) { return false; }
        $event = TenantConnection::db()->table('communication_hub_automation_events')->where('id', $eventId)->first();
        if (!$event) { return false; }
        $rules = TenantConnection::db()->table('communication_hub_automation_rules')
            ->where('event_code', $event->event_code)
            ->where('status', 'active')
            ->when(TenantConnection::hasColumn('communication_hub_automation_rules', 'business_id') && $event->business_id, fn($q) => $q->where(function($qq) use ($event){ $qq->whereNull('business_id')->orWhere('business_id', $event->business_id); }))
            ->orderBy('priority')
            ->get();
        $created = 0;
        foreach ($rules as $rule) { $created += $this->createRuleMessages($rule, $event); }
        TenantConnection::db()->table('communication_hub_automation_events')->where('id', $eventId)->update($this->filterColumns('communication_hub_automation_events', [
            'status' => $created > 0 ? 'executed' : 'no_rule',
            'processed_at' => now(),
            'created_messages' => $created,
            'updated_at' => now(),
        ]));
        return true;
    }

    public function processPending(int $limit = 50): int
    {
        if (!TenantConnection::hasTable('communication_hub_automation_events')) { return 0; }
        $events = TenantConnection::db()->table('communication_hub_automation_events')->where('status', 'pending')->orderBy('id')->limit($limit)->get();
        $done = 0;
        foreach ($events as $event) { if ($this->executeEvent((int)$event->id)) { $done++; } }
        return $done;
    }

    protected function createRuleMessages($rule, $event): int
    {
        $payload = json_decode($event->payload_json ?? '{}', true) ?: [];
        $channels = array_filter(array_map('trim', explode(',', $rule->channels ?? '')));
        $created = 0;
        foreach ($channels as $channel) {
            $recipient = $this->recipientFor($channel, $event, $payload);
            if (!$recipient) { continue; }
            $body = $this->render($rule->message_body ?? '', $payload, $event);
            $subject = $this->render($rule->subject ?? '', $payload, $event);
            if ($this->queueMessage($channel, $recipient, $body, $subject, $rule, $event)) { $created++; }
        }
        return $created;
    }

    protected function recipientFor(string $channel, $event, array $payload): ?string
    {
        return match ($channel) {
            'email' => $payload['recipient_email'] ?? $event->recipient_email ?? null,
            'whatsapp' => $payload['recipient_whatsapp'] ?? $event->recipient_whatsapp ?? $event->recipient_mobile ?? null,
            'push', 'in_app' => $payload['recipient_user_id'] ?? $payload['recipient_device_token'] ?? null,
            default => $payload['recipient_mobile'] ?? $event->recipient_mobile ?? null,
        };
    }

    protected function queueMessage(string $channel, string $recipient, string $body, ?string $subject, $rule, $event): bool
    {
        if (!TenantConnection::hasTable('communication_hub_messages')) { return false; }
        TenantConnection::db()->table('communication_hub_messages')->insert($this->filterColumns('communication_hub_messages', [
            'business_id' => $event->business_id,
            'business_location_id' => $event->business_location_id,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'message' => $body,
            'status' => 'pending',
            'priority' => $rule->priority ?? 'normal',
            'source' => 'automation',
            'source_module' => $event->source_module,
            'source_record_id' => $event->source_record_id,
            'automation_rule_id' => $rule->id ?? null,
            'automation_event_id' => $event->id ?? null,
            'scheduled_at' => $rule->delay_minutes ? now()->addMinutes((int)$rule->delay_minutes) : now(),
            'created_by' => $event->created_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return true;
    }

    protected function render(string $text, array $payload, $event): string
    {
        $vars = array_merge($payload, [
            'event_name' => $event->event_name ?? '',
            'event_code' => $event->event_code ?? '',
            'recipient_name' => $event->recipient_name ?? '',
        ]);
        foreach ($vars as $key => $value) {
            if (is_scalar($value) || $value === null) { $text = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string)$value, $text); }
        }
        return $text;
    }

    protected function count(string $table, array $where = [], $businessId = null): int
    {
        if (!TenantConnection::hasTable($table)) { return 0; }
        $q = TenantConnection::db()->table($table);
        foreach ($where as $k => $v) { $q->where($k, $v); }
        if ($businessId && TenantConnection::hasColumn($table, 'business_id')) { $q->where('business_id', $businessId); }
        return (int) $q->count();
    }

    protected function filterColumns(string $table, array $data): array
    {
        if (!TenantConnection::hasTable($table)) { return $data; }
        $columns = TenantConnection::columns($table);
        return array_intersect_key($data, array_flip($columns));
    }
}
