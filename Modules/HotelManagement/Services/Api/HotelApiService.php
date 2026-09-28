<?php
namespace Modules\HotelManagement\Services\Api;

use Illuminate\Support\Facades\DB;

class HotelApiService
{
    public function branches()
    {
        return DB::table('hm_hotels')->whereNull('deleted_at')->orderBy('name')->get();
    }

    public function roomTypes()
    {
        return DB::table('hm_room_types')->whereNull('deleted_at')->orderBy('name')->get();
    }

    public function availability(array $filters = []): array
    {
        return [
            'available_rooms' => DB::table('hm_rooms')->whereNull('deleted_at')->count(),
            'filters' => $filters,
        ];
    }
}
