<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TransportService
{
    public function dashboard(): array
    {
        $vehicles = $this->vehicles();
        $bookings = $this->bookings();
        $payments = $this->payments();
        $today = now()->toDateString();

        $todayBookings = array_filter($bookings, fn($b) => ($b->pickup_date ?? null) === $today);
        $pending = array_filter($bookings, fn($b) => in_array(($b->status ?? ''), ['requested','confirmed','assigned','started']));
        $todayRevenue = array_sum(array_map(fn($p) => (float)($p->amount ?? 0), array_filter($payments, fn($p) => ($p->payment_date ?? null) === $today)));

        return [
            'vehicles_count' => count($vehicles),
            'today_bookings' => count($todayBookings),
            'pending_count' => count($pending),
            'today_revenue' => $todayRevenue,
            'vehicles' => $vehicles,
            'bookings' => $bookings,
            'payments' => $payments,
            'notes' => [
                'Transport is scoped by tenant business and business location.',
                'Airport pickup, drop, local transfer and tour trips can be managed from this page.',
                'Room-charge support is bridge-ready by saving the hotel folio ID without duplicating Finance or POS logic.',
            ],
        ];
    }

    public function saveVehicle(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_transport_vehicles')) return;
        $values = [
            'business_location_id' => $this->locationId(),
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'driver_mobile' => $data['driver_mobile'] ?? null,
            'seating_capacity' => $data['seating_capacity'] ?? 0,
            'base_rate' => $data['base_rate'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_transport_vehicles')->where('business_id', $this->businessId())->where('vehicle_no', $data['vehicle_no']);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_transport_vehicles')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'vehicle_no' => $data['vehicle_no'],
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function saveBooking(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_transport_bookings')) return;
        $bookingNo = $data['booking_no'] ?: $this->nextBookingNo();
        $vehicle = null;
        if (!empty($data['vehicle_id']) && Schema::hasTable('hm_transport_vehicles')) {
            $vehicle = DB::table('hm_transport_vehicles')->where('id', $data['vehicle_id'])->first();
        }
        $rate = $data['rate'] ?? ($vehicle->base_rate ?? 0);
        $values = [
            'business_location_id' => $this->locationId(),
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'folio_id' => $data['folio_id'] ?? null,
            'guest_name' => $data['guest_name'],
            'mobile' => $data['mobile'] ?? null,
            'room_no' => $data['room_no'] ?? null,
            'trip_type' => $data['trip_type'],
            'pickup_date' => $data['pickup_date'] ?? now()->toDateString(),
            'pickup_time' => $data['pickup_time'] ?? null,
            'pickup_location' => $data['pickup_location'] ?? null,
            'drop_location' => $data['drop_location'] ?? null,
            'flight_no' => $data['flight_no'] ?? null,
            'driver_name' => $data['driver_name'] ?? ($vehicle->driver_name ?? null),
            'rate' => $rate,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'net_amount' => max(0, (float)$rate - (float)($data['discount_amount'] ?? 0) + (float)($data['tax_amount'] ?? 0)),
            'status' => $data['status'] ?? 'requested',
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_transport_bookings')->where('business_id', $this->businessId())->where('booking_no', $bookingNo);
        if ($query->exists()) {
            $query->update($values);
        } else {
            DB::table('hm_transport_bookings')->insert(array_merge($values, [
                'business_id' => $this->businessId(),
                'booking_no' => $bookingNo,
                'created_by' => $userId,
                'created_at' => now(),
            ]));
        }
    }

    public function updateStatus(int $id, string $status, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_transport_bookings')) return;
        DB::table('hm_transport_bookings')->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => $status,
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function postPayment(int $bookingId, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_transport_payments') || !Schema::hasTable('hm_transport_bookings')) return;
        DB::transaction(function () use ($bookingId, $data, $userId) {
            DB::table('hm_transport_payments')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'booking_id' => $bookingId,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'method' => $data['method'] ?? 'cash',
                'amount' => $data['amount'] ?? 0,
                'reference_no' => $data['reference_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_transport_bookings')->where('id', $bookingId)->where('business_id', $this->businessId())->update([
                'paid_amount' => DB::raw('COALESCE(paid_amount,0) + '.((float)($data['amount'] ?? 0))),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    protected function vehicles(): array
    {
        if (!Schema::hasTable('hm_transport_vehicles')) return [];
        try { return DB::table('hm_transport_vehicles')->where('business_id', $this->businessId())->orderBy('vehicle_no')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function bookings(): array
    {
        if (!Schema::hasTable('hm_transport_bookings')) return [];
        try {
            return DB::table('hm_transport_bookings as b')
                ->leftJoin('hm_transport_vehicles as v','b.vehicle_id','=','v.id')
                ->where('b.business_id', $this->businessId())
                ->select('b.*','v.vehicle_no','v.vehicle_type')
                ->orderByDesc('b.pickup_date')->orderByDesc('b.id')->limit(150)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function payments(): array
    {
        if (!Schema::hasTable('hm_transport_payments')) return [];
        try { return DB::table('hm_transport_payments')->where('business_id', $this->businessId())->orderByDesc('id')->limit(80)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function nextBookingNo(): string
    {
        $prefix = 'HMTR'.date('ym');
        $next = 1;
        if (Schema::hasTable('hm_transport_bookings')) {
            $last = DB::table('hm_transport_bookings')->where('booking_no','like',$prefix.'%')->orderByDesc('id')->value('booking_no');
            if ($last) $next = ((int)substr($last, -5)) + 1;
        }
        return $prefix.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
