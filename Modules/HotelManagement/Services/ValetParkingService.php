<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ValetParkingService
{
    public function dashboard(): array
    {
        $zones = $this->zones();
        $tickets = $this->tickets();
        $payments = $this->payments();
        $today = now()->toDateString();
        $active = array_filter($tickets, fn($t) => in_array(($t->status ?? ''), ['parked','requested','retrieving']));
        $todayTickets = array_filter($tickets, fn($t) => ($t->ticket_date ?? null) === $today);
        $todayRevenue = array_sum(array_map(fn($p) => (float)($p->paid_amount ?? 0), array_filter($payments, fn($p) => ($p->payment_date ?? null) === $today)));

        return [
            'zones_count' => count($zones),
            'today_tickets' => count($todayTickets),
            'active_tickets' => count($active),
            'today_revenue' => $todayRevenue,
            'zones' => $zones,
            'tickets' => $tickets,
            'payments' => $payments,
            'notes' => [
                'Valet parking records are scoped by tenant business and business location.',
                'Ticket status supports parked, retrieving, released, paid and cancelled workflows.',
                'This feature is standalone inside HotelManagement and does not duplicate the Transport module.',
            ],
        ];
    }

    public function saveZone(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_valet_parking_zones')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'zone_name' => $data['zone_name'],
            'capacity' => $data['capacity'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_valet_parking_zones')->where('business_id', $this->businessId())->where('zone_code', $data['zone_code']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_valet_parking_zones')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'zone_code' => $data['zone_code'],
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function saveTicket(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_valet_parking_tickets')) return;
        $ticketNo = $data['ticket_no'] ?: $this->nextTicketNo();
        $rate = (float)($data['rate'] ?? 0);
        $values = [
            'business_location_id' => $this->locationId(),
            'ticket_date' => $data['ticket_date'] ?? now()->toDateString(),
            'zone_id' => $data['zone_id'] ?? null,
            'room_no' => $data['room_no'] ?? null,
            'guest_name' => $data['guest_name'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'vehicle_no' => $data['vehicle_no'],
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'vehicle_colour' => $data['vehicle_colour'] ?? null,
            'key_tag_no' => $data['key_tag_no'] ?? null,
            'parked_slot' => $data['parked_slot'] ?? null,
            'check_in_time' => $data['check_in_time'] ?? now()->format('H:i'),
            'expected_out_time' => $data['expected_out_time'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'rate' => $rate,
            'net_amount' => $rate,
            'status' => $data['status'] ?? 'parked',
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_valet_parking_tickets')->where('business_id', $this->businessId())->where('ticket_no', $ticketNo);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_valet_parking_tickets')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'ticket_no' => $ticketNo,
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function updateStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_valet_parking_tickets')) return;
        $updates = [
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        if (!empty($data['retrieved_time'])) {
            $updates['retrieved_time'] = $data['retrieved_time'];
        }
        DB::table('hm_valet_parking_tickets')->where('id', $id)->where('business_id', $this->businessId())->update($updates);
    }

    public function recordPayment(int $ticketId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_valet_parking_payments') || !Schema::hasTable('hm_valet_parking_tickets')) return;
        DB::transaction(function () use ($ticketId, $data, $userId) {
            DB::table('hm_valet_parking_payments')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'ticket_id' => $ticketId,
                'payment_date' => now()->toDateString(),
                'payment_method' => $data['payment_method'],
                'paid_amount' => $data['paid_amount'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_valet_parking_tickets')->where('id', $ticketId)->where('business_id', $this->businessId())->update([
                'paid_amount' => DB::raw('COALESCE(paid_amount,0) + '.((float)($data['paid_amount'] ?? 0))),
                'status' => 'paid',
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    protected function zones(): array
    {
        if (!Schema::hasTable('hm_valet_parking_zones')) return [];
        try { return DB::table('hm_valet_parking_zones')->where('business_id', $this->businessId())->orderBy('zone_code')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function tickets(): array
    {
        if (!Schema::hasTable('hm_valet_parking_tickets')) return [];
        try {
            return DB::table('hm_valet_parking_tickets as t')
                ->leftJoin('hm_valet_parking_zones as z','t.zone_id','=','z.id')
                ->where('t.business_id', $this->businessId())
                ->select('t.*','z.zone_code','z.zone_name')
                ->orderByDesc('t.ticket_date')->orderByDesc('t.id')->limit(150)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function payments(): array
    {
        if (!Schema::hasTable('hm_valet_parking_payments')) return [];
        try { return DB::table('hm_valet_parking_payments')->where('business_id', $this->businessId())->orderByDesc('id')->limit(80)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextTicketNo(): string
    {
        $prefix = 'HMVP'.date('ym');
        $next = 1;
        if (Schema::hasTable('hm_valet_parking_tickets')) {
            $last = DB::table('hm_valet_parking_tickets')->where('ticket_no','like',$prefix.'%')->orderByDesc('id')->value('ticket_no');
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
