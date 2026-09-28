<?php

namespace Modules\RestaurantNew\Services\Reservation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RestaurantNew\Entities\RestaurantNewFloorPlan;
use Modules\RestaurantNew\Entities\RestaurantNewFloorTable;
use Modules\RestaurantNew\Entities\RestaurantNewReservation;
use Modules\RestaurantNew\Entities\RestaurantNewTableStatusLog;
use Modules\RestaurantNew\Entities\RestaurantNewWaitlist;

class RestaurantReservationService
{
    public function createFloorPlan(array $data): RestaurantNewFloorPlan
    {
        return RestaurantNewFloorPlan::create($data);
    }

    public function saveTable(array $data): RestaurantNewFloorTable
    {
        return RestaurantNewFloorTable::updateOrCreate(
            ['business_id' => $data['business_id'], 'location_id' => $data['location_id'] ?? null, 'floor_plan_id' => $data['floor_plan_id'], 'table_no' => $data['table_no']],
            $data
        );
    }

    public function createReservation(array $data): RestaurantNewReservation
    {
        $data['qr_token'] = $data['qr_token'] ?? Str::random(48);
        $data['status'] = $data['status'] ?? 'booked';
        return RestaurantNewReservation::create($data);
    }

    public function checkIn(RestaurantNewReservation $reservation, ?int $tableId, ?int $userId = null): RestaurantNewReservation
    {
        return DB::transaction(function () use ($reservation, $tableId, $userId) {
            if ($tableId) {
                $table = RestaurantNewFloorTable::findOrFail($tableId);
                $oldStatus = $table->status;
                $table->update(['status' => 'occupied']);
                RestaurantNewTableStatusLog::create([
                    'business_id' => $table->business_id,
                    'location_id' => $table->location_id,
                    'floor_table_id' => $table->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'occupied',
                    'reservation_id' => $reservation->id,
                    'changed_by' => $userId,
                    'note' => 'Reservation check-in',
                ]);
                $reservation->floor_table_id = $table->id;
            }
            $reservation->status = 'checked_in';
            $reservation->save();
            return $reservation->fresh();
        });
    }

    public function addToWaitlist(array $data): RestaurantNewWaitlist
    {
        $data['status'] = $data['status'] ?? 'waiting';
        return RestaurantNewWaitlist::create($data);
    }

    public function assignWaitlistTable(RestaurantNewWaitlist $waitlist, int $tableId, ?int $userId = null): RestaurantNewWaitlist
    {
        return DB::transaction(function () use ($waitlist, $tableId, $userId) {
            $table = RestaurantNewFloorTable::findOrFail($tableId);
            $oldStatus = $table->status;
            $table->update(['status' => 'occupied']);
            $waitlist->update(['assigned_table_id' => $table->id, 'status' => 'seated', 'seated_at' => now()]);
            RestaurantNewTableStatusLog::create([
                'business_id' => $table->business_id,
                'location_id' => $table->location_id,
                'floor_table_id' => $table->id,
                'old_status' => $oldStatus,
                'new_status' => 'occupied',
                'changed_by' => $userId,
                'note' => 'Waitlist seated',
            ]);
            return $waitlist->fresh();
        });
    }

    public function dashboard(int $businessId, ?int $locationId = null): array
    {
        $scope = fn($q) => $q->where('business_id', $businessId)->when($locationId, fn($qq) => $qq->where('location_id', $locationId));
        return [
            'available_tables' => RestaurantNewFloorTable::where($scope)->where('status', 'available')->count(),
            'occupied_tables' => RestaurantNewFloorTable::where($scope)->where('status', 'occupied')->count(),
            'today_reservations' => RestaurantNewReservation::where($scope)->whereDate('reservation_date', today())->count(),
            'waitlist_count' => RestaurantNewWaitlist::where($scope)->where('status', 'waiting')->count(),
            'deposit_due' => RestaurantNewReservation::where($scope)->where('deposit_status', 'pending')->sum('deposit_amount'),
        ];
    }
}
