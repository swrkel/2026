<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GuestCommunicationService
{
    public function dashboard(): array
    {
        return [
            'templates' => $this->tableCount('hm_guest_message_templates'),
            'logs' => $this->tableCount('hm_guest_message_logs'),
            'pending' => $this->countWhere('hm_guest_message_logs', 'status', 'pending'),
            'sent' => $this->countWhere('hm_guest_message_logs', 'status', 'sent'),
            'failed' => $this->countWhere('hm_guest_message_logs', 'status', 'failed'),
            'recent' => $this->recentLogs(),
            'templates_list' => $this->templates(),
            'notes' => [
                'This page is intentionally prepared as a bridge to the existing ERP SMS/Communication module.',
                'It does not duplicate SMS engine code. It stores hotel-specific templates and outbound hotel message logs only.',
                'Use this for booking confirmations, pre-arrival reminders, check-in welcome messages, room-service alerts and check-out thank-you messages.',
            ],
        ];
    }

    public function saveTemplate(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_guest_message_templates')) {
            return;
        }
        DB::table('hm_guest_message_templates')->insert([
            'business_id' => session('business.id') ?? null,
            'business_location_id' => session('business_location_id') ?? session('business.default_location_id') ?? null,
            'code' => $data['code'] ?? 'custom',
            'name' => $data['name'] ?? 'Hotel Message',
            'channel' => $data['channel'] ?? 'sms',
            'event_key' => $data['event_key'] ?? null,
            'message_body' => $data['message_body'] ?? '',
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function queueManual(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_guest_message_logs')) {
            return;
        }
        DB::table('hm_guest_message_logs')->insert([
            'business_id' => session('business.id') ?? null,
            'business_location_id' => session('business_location_id') ?? session('business.default_location_id') ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'folio_id' => $data['folio_id'] ?? null,
            'channel' => $data['channel'] ?? 'sms',
            'recipient' => $data['recipient'] ?? '',
            'subject' => $data['subject'] ?? null,
            'message_body' => $data['message_body'] ?? '',
            'status' => 'pending',
            'source' => 'manual',
            'queued_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tableCount(string $table): int
    {
        if (!Schema::hasTable($table)) return 0;
        try { return DB::table($table)->count(); } catch (Throwable $e) { return 0; }
    }

    protected function countWhere(string $table, string $column, string $value): int
    {
        if (!Schema::hasTable($table)) return 0;
        try { return DB::table($table)->where($column, $value)->count(); } catch (Throwable $e) { return 0; }
    }

    protected function recentLogs(): array
    {
        if (!Schema::hasTable('hm_guest_message_logs')) return [];
        try {
            return DB::table('hm_guest_message_logs')->orderByDesc('id')->limit(20)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function templates(): array
    {
        if (!Schema::hasTable('hm_guest_message_templates')) return [];
        try {
            return DB::table('hm_guest_message_templates')->orderBy('event_key')->orderBy('name')->get()->toArray();
        } catch (Throwable $e) { return []; }
    }
}
