<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OnlineBookingService
{
    public function dashboard(array $filters = []): array
    {
        $bookings = $this->bookings();
        $promotions = $this->promotions();
        $calendar = $this->calendar($filters);
        $open = array_filter($bookings, fn($b) => in_array(($b->status ?? ''), ['new','confirmed','modified']));
        $cancelled = array_filter($bookings, fn($b) => ($b->status ?? '') === 'cancelled');
        $revenue = array_sum(array_map(fn($b) => (float)($b->net_amount ?? 0), $bookings));
        $advance = array_sum(array_map(fn($b) => (float)($b->advance_amount ?? 0), $bookings));
        return [
            'bookings' => $bookings,
            'promotions' => $promotions,
            'calendar' => $calendar,
            'open_bookings' => count($open),
            'cancelled_bookings' => count($cancelled),
            'net_revenue' => $revenue,
            'advance_collected' => $advance,
            'notes' => [
                'Online bookings are tenant, business and business-location scoped.',
                'Coupon and promotion values are stored with each booking for audit history.',
                'Cancellation/refund workflows do not delete bookings; they update controlled status fields.',
            ],
        ];
    }

    public function savePromotion(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_online_promotions')) return;
        DB::table('hm_online_promotions')->updateOrInsert([
            'business_id' => $this->businessId(),
            'promo_code' => strtoupper($data['promo_code']),
        ], [
            'business_location_id' => $this->locationId(),
            'promo_name' => $data['promo_name'],
            'discount_type' => $data['discount_type'],
            'discount_value' => (float)$data['discount_value'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to' => $data['valid_to'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function saveBooking(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_online_bookings')) return;
        $gross = (float)$data['gross_amount'];
        $discount = $this->discountFor($data['coupon_code'] ?? null, $gross);
        $advance = (float)($data['advance_amount'] ?? 0);
        DB::table('hm_online_bookings')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'booking_no' => $this->nextBookingNo(),
            'booking_source' => $data['booking_source'] ?? 'online_engine',
            'guest_name' => $data['guest_name'],
            'guest_mobile' => $data['guest_mobile'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'arrival_date' => $data['arrival_date'],
            'departure_date' => $data['departure_date'],
            'room_type_id' => $data['room_type_id'] ?? null,
            'rate_plan_id' => $data['rate_plan_id'] ?? null,
            'rooms' => (int)$data['rooms'],
            'adults' => (int)($data['adults'] ?? 0),
            'children' => (int)($data['children'] ?? 0),
            'coupon_code' => $data['coupon_code'] ?? null,
            'gross_amount' => $gross,
            'discount_amount' => $discount,
            'net_amount' => max(0, $gross - $discount),
            'advance_amount' => $advance,
            'balance_amount' => max(0, $gross - $discount - $advance),
            'status' => $data['status'] ?? 'new',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function modifyBooking(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_online_bookings')) return;
        $update = array_filter([
            'arrival_date' => $data['arrival_date'] ?? null,
            'departure_date' => $data['departure_date'] ?? null,
            'rooms' => $data['rooms'] ?? null,
            'status' => $data['status'] ?? 'modified',
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ], fn($v) => !is_null($v));
        DB::table('hm_online_bookings')->where('id', $id)->where('business_id', $this->businessId())->update($update);
    }

    public function cancelBooking(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_online_bookings')) return;
        DB::table('hm_online_bookings')->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => 'cancelled',
            'cancel_reason' => $data['cancel_reason'] ?? null,
            'cancelled_at' => now(),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function refundBooking(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_online_bookings')) return;
        DB::table('hm_online_bookings')->where('id', $id)->where('business_id', $this->businessId())->update([
            'refund_amount' => (float)$data['refund_amount'],
            'refund_note' => $data['refund_note'] ?? null,
            'refunded_at' => now(),
            'status' => 'refunded',
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    protected function discountFor(?string $coupon, float $gross): float
    {
        if (!$coupon || !Schema::hasTable('hm_online_promotions')) return 0;
        $promo = DB::table('hm_online_promotions')->where('business_id', $this->businessId())->where('promo_code', strtoupper($coupon))->where('is_active', 1)->first();
        if (!$promo) return 0;
        if (($promo->discount_type ?? '') === 'percent') return round($gross * ((float)$promo->discount_value / 100), 2);
        return min($gross, (float)$promo->discount_value);
    }

    protected function bookings(): array
    {
        if (!Schema::hasTable('hm_online_bookings')) return [];
        try { return DB::table('hm_online_bookings')->where('business_id', $this->businessId())->orderByDesc('id')->limit(200)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function promotions(): array
    {
        if (!Schema::hasTable('hm_online_promotions')) return [];
        try { return DB::table('hm_online_promotions')->where('business_id', $this->businessId())->orderByDesc('id')->limit(100)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function calendar(array $filters = []): array
    {
        if (!Schema::hasTable('hm_online_bookings')) return [];
        try {
            return DB::table('hm_online_bookings')->where('business_id', $this->businessId())
                ->select('arrival_date','departure_date','status', DB::raw('COUNT(*) as total_bookings'), DB::raw('SUM(rooms) as total_rooms'))
                ->groupBy('arrival_date','departure_date','status')->orderBy('arrival_date')->limit(120)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function nextBookingNo(): string
    {
        $prefix = 'WEB-' . date('ymd') . '-';
        $next = 1;
        if (Schema::hasTable('hm_online_bookings')) {
            $last = DB::table('hm_online_bookings')->where('business_id', $this->businessId())->where('booking_no','like',$prefix.'%')->orderByDesc('id')->value('booking_no');
            if ($last) $next = ((int)substr($last, -4)) + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? session('business_id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
