<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SpaWellnessService
{
    public function dashboard(): array
    {
        $services = $this->services();
        $appointments = $this->appointments();
        $payments = $this->payments();

        $today = now()->toDateString();
        $todayAppointments = array_filter($appointments, fn($a) => ($a->appointment_date ?? null) === $today);
        $todayRevenue = array_sum(array_map(fn($p) => (float)($p->amount ?? 0), array_filter($payments, fn($p) => ($p->payment_date ?? null) === $today)));
        $pending = array_filter($appointments, fn($a) => in_array(($a->status ?? ''), ['booked','confirmed','in_progress']));

        return [
            'services_count' => count($services),
            'today_appointments' => count($todayAppointments),
            'pending_count' => count($pending),
            'today_revenue' => $todayRevenue,
            'services' => $services,
            'appointments' => $appointments,
            'payments' => $payments,
            'notes' => [
                'Spa & Wellness is fully tenant/business/location scoped inside HotelManagement.',
                'Appointments can be charged directly or posted to a room folio by entering the folio ID.',
                'This parcel does not duplicate POS or Finance modules; it keeps a clean bridge-ready hotel layer.',
            ],
        ];
    }

    public function saveService(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_spa_services')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 0,
            'price' => $data['price'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_spa_services')->where('business_id', $this->businessId())->where('code', $data['code']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_spa_services')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'code' => $data['code'],
                'created_at' => now(),
            ]));
        }
    }

    public function saveAppointment(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_spa_appointments')) return;
        $appointmentNo = $data['appointment_no'] ?? $this->nextAppointmentNo();
        $service = null;
        if (!empty($data['service_id']) && Schema::hasTable('hm_spa_services')) {
            $service = DB::table('hm_spa_services')->where('id', $data['service_id'])->first();
        }
        $amount = $data['amount'] ?? ($service->price ?? 0);
        $values = [
            'business_location_id' => $this->locationId(),
            'service_id' => $data['service_id'] ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            'folio_id' => $data['folio_id'] ?? null,
            'guest_name' => $data['guest_name'],
            'mobile' => $data['mobile'] ?? null,
            'room_no' => $data['room_no'] ?? null,
            'therapist_name' => $data['therapist_name'] ?? null,
            'appointment_date' => $data['appointment_date'] ?? now()->toDateString(),
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'amount' => $amount,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'net_amount' => max(0, (float)$amount - (float)($data['discount_amount'] ?? 0) + (float)($data['tax_amount'] ?? 0)),
            'status' => $data['status'] ?? 'booked',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_spa_appointments')->where('business_id', $this->businessId())->where('appointment_no', $appointmentNo);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_spa_appointments')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'appointment_no' => $appointmentNo,
                'created_at' => now(),
            ]));
        }
    }

    public function updateStatus(int $id, string $status, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_spa_appointments')) return;
        DB::table('hm_spa_appointments')->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => $status,
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function postPayment(int $appointmentId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_spa_payments') || !Schema::hasTable('hm_spa_appointments')) return;
        DB::transaction(function () use ($appointmentId, $data, $userId) {
            DB::table('hm_spa_payments')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'appointment_id' => $appointmentId,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'] ?? 'cash',
                'amount' => $data['amount'] ?? 0,
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_spa_appointments')->where('id', $appointmentId)->update([
                'paid_amount' => DB::raw('COALESCE(paid_amount,0) + '.((float)($data['amount'] ?? 0))),
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        });
    }

    protected function services(): array
    {
        if (!Schema::hasTable('hm_spa_services')) return [];
        try { return DB::table('hm_spa_services')->where('business_id', $this->businessId())->orderBy('category')->orderBy('name')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function appointments(): array
    {
        if (!Schema::hasTable('hm_spa_appointments')) return [];
        try {
            $q = DB::table('hm_spa_appointments as a')->leftJoin('hm_spa_services as s','a.service_id','=','s.id')
                ->where('a.business_id', $this->businessId())
                ->select('a.*','s.name as service_name')
                ->orderByDesc('a.appointment_date')->orderByDesc('a.id')->limit(150);
            return $q->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function payments(): array
    {
        if (!Schema::hasTable('hm_spa_payments')) return [];
        try { return DB::table('hm_spa_payments')->where('business_id', $this->businessId())->orderByDesc('id')->limit(80)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextAppointmentNo(): string
    {
        $prefix = 'HMSPA'.date('ym');
        $next = 1;
        if (Schema::hasTable('hm_spa_appointments')) {
            $last = DB::table('hm_spa_appointments')->where('appointment_no','like',$prefix.'%')->orderByDesc('id')->value('appointment_no');
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
