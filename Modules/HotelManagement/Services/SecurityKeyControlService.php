<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityKeyControlService
{
    public function dashboard(): array
    {
        $keys = $this->rows('hm_room_key_cards');
        $visitors = $this->rows('hm_visitor_passes');
        $incidents = $this->rows('hm_security_incidents');
        return [
            'keys' => $keys,
            'visitors' => $visitors,
            'incidents' => $incidents,
            'active_keys' => count(array_filter($keys, fn($r) => in_array(($r->status ?? ''), ['issued','active']))),
            'open_visitors' => count(array_filter($visitors, fn($r) => in_array(($r->status ?? ''), ['checked_in','active']))),
            'open_incidents' => count(array_filter($incidents, fn($r) => !in_array(($r->status ?? ''), ['closed','resolved']))),
            'high_incidents' => count(array_filter($incidents, fn($r) => in_array(($r->severity ?? ''), ['high','critical']))),
            'notes' => [
                'Key/card records are tenant, business and business-location scoped.',
                'Visitor passes support check-in, check-out and blocked status tracking.',
                'Security incidents provide open/resolved/closed follow-up for audit review.'
            ],
        ];
    }

    public function keyCard(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_room_key_cards')) return;
        DB::table('hm_room_key_cards')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'key_no' => $data['key_no'] ?? $this->nextNumber('hm_room_key_cards','key_no','KEY'),
            'room_id' => $data['room_id'] ?? null, 'reservation_id' => $data['reservation_id'] ?? null,
            'guest_name' => $data['guest_name'] ?? null, 'issued_at' => $data['issued_at'] ?? now(), 'expires_at' => $data['expires_at'] ?? null,
            'status' => $data['status'] ?? 'issued', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function keyStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_room_key_cards')) return;
        DB::table('hm_room_key_cards')->where('id',$id)->where('business_id',$this->businessId())->update([
            'status' => $data['status'], 'remarks' => $data['remarks'] ?? DB::raw('remarks'), 'updated_by' => $userId, 'updated_at' => now(),
        ]);
    }

    public function visitorPass(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_visitor_passes')) return;
        DB::table('hm_visitor_passes')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'pass_no' => $data['pass_no'] ?? $this->nextNumber('hm_visitor_passes','pass_no','VST'),
            'visitor_name' => $data['visitor_name'], 'mobile' => $data['mobile'] ?? null, 'nic_no' => $data['nic_no'] ?? null,
            'guest_name' => $data['guest_name'] ?? null, 'room_id' => $data['room_id'] ?? null, 'purpose' => $data['purpose'] ?? null,
            'check_in_at' => $data['check_in_at'] ?? now(), 'check_out_at' => $data['check_out_at'] ?? null,
            'status' => $data['status'] ?? 'checked_in', 'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function visitorStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_visitor_passes')) return;
        $update = ['status'=>$data['status'], 'remarks'=>$data['remarks'] ?? DB::raw('remarks'), 'updated_by'=>$userId, 'updated_at'=>now()];
        if ($data['status'] === 'checked_out') $update['check_out_at'] = now();
        DB::table('hm_visitor_passes')->where('id',$id)->where('business_id',$this->businessId())->update($update);
    }

    public function incident(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_security_incidents')) return;
        DB::table('hm_security_incidents')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'incident_no' => $data['incident_no'] ?? $this->nextNumber('hm_security_incidents','incident_no','INC'),
            'incident_date' => $data['incident_date'] ?? date('Y-m-d'), 'incident_time' => $data['incident_time'] ?? date('H:i'),
            'incident_type' => $data['incident_type'], 'severity' => $data['severity'] ?? 'medium',
            'location_reference' => $data['location_reference'] ?? null, 'reported_by' => $data['reported_by'] ?? null, 'guest_name' => $data['guest_name'] ?? null,
            'description' => $data['description'] ?? null, 'status' => $data['status'] ?? 'open', 'action_taken' => $data['action_taken'] ?? null,
            'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function incidentStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_security_incidents')) return;
        DB::table('hm_security_incidents')->where('id',$id)->where('business_id',$this->businessId())->update([
            'status'=>$data['status'], 'action_taken'=>$data['action_taken'] ?? DB::raw('action_taken'), 'updated_by'=>$userId, 'updated_at'=>now(),
        ]);
    }

    private function rows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try { return DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get()->all(); }
        catch (Throwable $e) { return []; }
    }
    private function nextNumber(string $table, string $column, string $prefix): string
    { $last = Schema::hasTable($table) ? DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->value($column) : null; $n=1; if ($last && preg_match('/(\d+)$/',$last,$m)) $n=((int)$m[1])+1; return $prefix.'-'.date('ym').'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT); }
    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
