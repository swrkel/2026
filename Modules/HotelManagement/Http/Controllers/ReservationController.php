<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class ReservationController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::reservations.index', [
            'reservations' => $this->reservationRows(),
            'guests' => $this->activeOptions('hm_guests', 'guest_name'),
            'rooms' => $this->scopedQuery('hm_rooms')->whereIn('status', ['available','reserved'])->orderBy('room_no')->get(),
            'ratePlans' => $this->activeOptions('hm_rate_plans', 'plan_name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reservation_no' => 'nullable|string|max:50',
            'guest_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'rate_plan_id' => 'nullable|integer',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'booking_source' => 'nullable|string|max:100',
            'estimated_total' => 'nullable|numeric',
            'status' => 'nullable|string|max:30',
        ]);

        $roomId = $data['room_id'] ?? null;
        $ratePlanId = $data['rate_plan_id'] ?? null;
        unset($data['room_id'], $data['rate_plan_id']);

        $data['reservation_no'] = $data['reservation_no'] ?: $this->nextCode('hm_reservations', 'reservation_no', 'RES');
        $data['adults'] = $data['adults'] ?? 1;
        $data['children'] = $data['children'] ?? 0;
        $data['estimated_total'] = $data['estimated_total'] ?? 0;
        $data['status'] = $data['status'] ?: 'reserved';
        $data = $this->withScope($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            $id = DB::table('hm_reservations')->insertGetId($data);
            if ($roomId) {
                $this->saveReservationRoom($id, (int) $roomId, $ratePlanId, $data['arrival_date']);
                $this->scopedQuery('hm_rooms')->where('id', $roomId)->where('status', 'available')->update(['status' => 'reserved', 'updated_at' => now()]);
            }
            $this->audit('created', 'hm_reservations', $id, array_merge($data, ['room_id' => $roomId]));
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['reservation' => 'Reservation could not be saved: '.$e->getMessage()])->withInput();
        }

        return back()->with('status', 'Reservation saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'reservation_no' => 'required|string|max:50',
            'guest_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'rate_plan_id' => 'nullable|integer',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'booking_source' => 'nullable|string|max:100',
            'estimated_total' => 'nullable|numeric',
            'status' => 'nullable|string|max:30',
        ]);

        $roomId = $data['room_id'] ?? null;
        $ratePlanId = $data['rate_plan_id'] ?? null;
        unset($data['room_id'], $data['rate_plan_id']);
        $data['adults'] = $data['adults'] ?? 1;
        $data['children'] = $data['children'] ?? 0;
        $data['estimated_total'] = $data['estimated_total'] ?? 0;
        $data['status'] = $data['status'] ?: 'reserved';

        DB::beginTransaction();
        try {
            $this->updateScopedRow('hm_reservations', (int) $id, $data);
            if ($roomId) {
                $this->saveReservationRoom((int) $id, (int) $roomId, $ratePlanId, $data['arrival_date']);
                $this->scopedQuery('hm_rooms')->where('id', $roomId)->where('status', 'available')->update(['status' => 'reserved', 'updated_at' => now()]);
            }
            $this->audit('updated', 'hm_reservations', (int) $id, array_merge($data, ['room_id' => $roomId]));
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['reservation' => 'Reservation could not be updated: '.$e->getMessage()])->withInput();
        }

        return back()->with('status', 'Reservation updated successfully.');
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $roomIds = $this->scopedQuery('hm_reservation_rooms')->where('reservation_id', (int) $id)->pluck('room_id')->filter()->all();
            $this->deleteScopedRow('hm_reservations', (int) $id);
            if ($roomIds) {
                $this->scopedQuery('hm_rooms')->whereIn('id', $roomIds)->where('status', 'reserved')->update(['status' => 'available', 'updated_at' => now()]);
            }
            $this->audit('deleted', 'hm_reservations', (int) $id, ['room_ids' => $roomIds]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['reservation' => 'Reservation could not be deleted: '.$e->getMessage()]);
        }

        return back()->with('status', 'Reservation deleted successfully.');
    }

    private function reservationRows()
    {
        $rows = $this->scopedQuery('hm_reservations as r')
            ->leftJoin('hm_guests as g', 'g.id', '=', 'r.guest_id')
            ->select('r.*', 'g.guest_name')
            ->latest('r.id')
            ->limit(100)
            ->get();

        foreach ($rows as $row) {
            $rooms = $this->scopedQuery('hm_reservation_rooms as rr')
                ->leftJoin('hm_rooms as room', 'room.id', '=', 'rr.room_id')
                ->where('rr.reservation_id', $row->id)
                ->pluck('room.room_no')
                ->filter()
                ->implode(', ');
            $row->room_numbers = $rooms;
        }
        return $rows;
    }

    private function saveReservationRoom(int $reservationId, int $roomId, ?int $ratePlanId, string $stayDate): void
    {
        if (!Schema::hasTable('hm_reservation_rooms')) { return; }
        $rate = 0;
        if ($ratePlanId && Schema::hasTable('hm_rate_plans')) {
            $rate = (float) ($this->scopedQuery('hm_rate_plans')->where('id', $ratePlanId)->value('rate') ?? 0);
        }
        $existing = $this->scopedQuery('hm_reservation_rooms')->where('reservation_id', $reservationId)->first();
        $payload = $this->withScope([
            'reservation_id' => $reservationId,
            'room_id' => $roomId,
            'rate_plan_id' => $ratePlanId,
            'rate' => $rate,
            'stay_date' => $stayDate,
            'status' => 'reserved',
            'updated_at' => now(),
        ]);
        if ($existing) {
            DB::table('hm_reservation_rooms')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('hm_reservation_rooms')->insert($payload);
        }
    }
}
