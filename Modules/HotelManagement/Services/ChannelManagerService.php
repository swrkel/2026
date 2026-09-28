<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ChannelManagerService
{
    public function dashboard(): array
    {
        $channels = $this->channels();
        $rateMaps = $this->rateMaps();
        $availability = $this->availability();
        $bookings = $this->bookings();
        $activeChannels = array_filter($channels, fn($c) => (int)($c->is_active ?? 0) === 1);
        $stopSellDates = array_filter($availability, fn($a) => (int)($a->stop_sell ?? 0) === 1);
        $openBookings = array_filter($bookings, fn($b) => in_array(($b->status ?? ''), ['new','confirmed','modified']));
        $netRevenue = array_sum(array_map(fn($b) => (float)($b->net_amount ?? 0), $bookings));

        return [
            'channels_count' => count($channels),
            'active_channels' => count($activeChannels),
            'open_bookings' => count($openBookings),
            'stop_sell_dates' => count($stopSellDates),
            'net_revenue' => $netRevenue,
            'channels' => $channels,
            'rate_maps' => $rateMaps,
            'availability' => $availability,
            'bookings' => $bookings,
            'notes' => [
                'Channel Manager records are tenant, business and business-location scoped.',
                'External OTA booking references are protected against duplicates within the same business.',
                'This parcel stores channel mappings locally; live OTA API sync can be connected later through a separate bridge without changing the hotel core.',
            ],
        ];
    }

    public function saveChannel(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_sales_channels')) return;
        $code = $data['channel_code'] ?: strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['channel_name']), 0, 12));
        $values = [
            'channel_code' => $code,
            'channel_type' => $data['channel_type'] ?? 'ota',
            'contact_email' => $data['contact_email'] ?? null,
            'commission_percent' => (float)($data['commission_percent'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
        ];
        $query = DB::table('hm_sales_channels')->where('business_id', $this->businessId())->where('channel_name', $data['channel_name']);
        if ($query->exists()) $query->update($values);
        else DB::table('hm_sales_channels')->insert(array_merge($values, [
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'channel_name' => $data['channel_name'],
            'created_by' => $userId,
            'created_at' => now(),
        ]));
    }

    public function saveRateMap(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_channel_rate_maps')) return;
        DB::table('hm_channel_rate_maps')->updateOrInsert([
            'business_id' => $this->businessId(),
            'channel_id' => $data['channel_id'],
            'room_type_id' => $data['room_type_id'] ?? null,
            'rate_plan_id' => $data['rate_plan_id'] ?? null,
        ], [
            'business_location_id' => $this->locationId(),
            'external_room_code' => $data['external_room_code'] ?? null,
            'external_rate_code' => $data['external_rate_code'] ?? null,
            'sell_rate' => (float)$data['sell_rate'],
            'currency' => $data['currency'] ?? 'LKR',
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_by' => $userId,
            'updated_at' => now(),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function saveAvailability(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_channel_availability')) return;
        DB::table('hm_channel_availability')->updateOrInsert([
            'business_id' => $this->businessId(),
            'channel_id' => $data['channel_id'],
            'room_type_id' => $data['room_type_id'] ?? null,
            'available_date' => $data['available_date'],
        ], [
            'business_location_id' => $this->locationId(),
            'available_rooms' => (int)$data['available_rooms'],
            'stop_sell' => !empty($data['stop_sell']) ? 1 : 0,
            'min_stay' => (int)($data['min_stay'] ?? 0),
            'max_stay' => (int)($data['max_stay'] ?? 0),
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function saveBooking(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_channel_bookings')) return;
        $gross = (float)($data['gross_amount'] ?? 0);
        $commission = (float)($data['commission_amount'] ?? 0);
        DB::table('hm_channel_bookings')->updateOrInsert([
            'business_id' => $this->businessId(),
            'channel_id' => $data['channel_id'],
            'external_booking_ref' => $data['external_booking_ref'],
        ], [
            'business_location_id' => $this->locationId(),
            'guest_name' => $data['guest_name'],
            'guest_mobile' => $data['guest_mobile'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'arrival_date' => $data['arrival_date'],
            'departure_date' => $data['departure_date'],
            'rooms' => (int)($data['rooms'] ?? 1),
            'adults' => (int)($data['adults'] ?? 0),
            'children' => (int)($data['children'] ?? 0),
            'gross_amount' => $gross,
            'commission_amount' => $commission,
            'net_amount' => max(0, $gross - $commission),
            'status' => $data['status'] ?? 'new',
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $userId,
            'updated_at' => now(),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function bookingStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_channel_bookings')) return;
        DB::table('hm_channel_bookings')->where('id', $id)->where('business_id', $this->businessId())->update([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? DB::raw('remarks'),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    protected function channels(): array
    {
        if (!Schema::hasTable('hm_sales_channels')) return [];
        try { return DB::table('hm_sales_channels')->where('business_id', $this->businessId())->orderBy('channel_name')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function rateMaps(): array
    {
        if (!Schema::hasTable('hm_channel_rate_maps')) return [];
        try { return DB::table('hm_channel_rate_maps as m')->leftJoin('hm_sales_channels as c','m.channel_id','=','c.id')->where('m.business_id', $this->businessId())->select('m.*','c.channel_name')->orderByDesc('m.id')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function availability(): array
    {
        if (!Schema::hasTable('hm_channel_availability')) return [];
        try { return DB::table('hm_channel_availability as a')->leftJoin('hm_sales_channels as c','a.channel_id','=','c.id')->where('a.business_id', $this->businessId())->select('a.*','c.channel_name')->orderByDesc('a.available_date')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function bookings(): array
    {
        if (!Schema::hasTable('hm_channel_bookings')) return [];
        try { return DB::table('hm_channel_bookings as b')->leftJoin('hm_sales_channels as c','b.channel_id','=','c.id')->where('b.business_id', $this->businessId())->select('b.*','c.channel_name')->orderByDesc('b.id')->limit(150)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function businessId(): ?int { return session('business.id') ?? session('business_id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
