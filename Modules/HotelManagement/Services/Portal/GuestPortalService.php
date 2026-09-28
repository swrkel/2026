<?php
namespace Modules\HotelManagement\Services\Portal;

use Illuminate\Support\Facades\DB;

class GuestPortalService
{
    public function dashboard(?int $guestId = null): array
    {
        return [
            'upcoming_reservations' => DB::table('hm_reservations')->when($guestId, fn($q) => $q->where('guest_id', $guestId))->orderByDesc('id')->limit(10)->get(),
            'folios' => DB::table('hm_folios')->when($guestId, fn($q) => $q->where('guest_id', $guestId))->orderByDesc('id')->limit(10)->get(),
            'notifications' => [],
        ];
    }
}
