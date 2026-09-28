<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class FrontOfficeController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::front_office.index', [
            'checkins' => $this->scopedQuery('hm_checkins as c')
                ->leftJoin('hm_guests as g', 'g.id', '=', 'c.guest_id')
                ->leftJoin('hm_rooms as r', 'r.id', '=', 'c.room_id')
                ->select('c.*', 'g.guest_name', 'r.room_no')
                ->latest('c.id')->limit(50)->get(),
            'checkouts' => $this->safeRows('hm_checkouts', 50),
            'reservations' => $this->scopedQuery('hm_reservations')->whereIn('status', ['reserved','confirmed'])->latest('id')->limit(100)->get(),
            'guests' => $this->activeOptions('hm_guests', 'guest_name'),
            'rooms' => $this->scopedQuery('hm_rooms')->whereIn('status', ['available','reserved'])->orderBy('room_no')->get(),
            'occupiedRooms' => $this->scopedQuery('hm_rooms')->where('status', 'occupied')->orderBy('room_no')->get(),
        ]);
    }

    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'reservation_id' => 'nullable|integer',
            'guest_id' => 'nullable|integer',
            'room_id' => 'required|integer',
        ]);

        if (!empty($data['reservation_id']) && empty($data['guest_id'])) {
            $data['guest_id'] = (int) ($this->scopedQuery('hm_reservations')->where('id', $data['reservation_id'])->value('guest_id') ?? 0) ?: null;
        }

        $data = $this->withScope($data);
        $data['checked_in_at'] = now();
        $data['checked_in_by'] = auth()->id();
        $data['status'] = 'checked_in';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            $id = DB::table('hm_checkins')->insertGetId($data);
            $this->scopedQuery('hm_rooms')->where('id', $data['room_id'])->update(['status'=>'occupied','housekeeping_status'=>'clean','updated_at'=>now()]);
            if (!empty($data['reservation_id'])) {
                $this->updateScopedRow('hm_reservations', (int) $data['reservation_id'], ['status' => 'checked_in']);
            }
            $this->createOpenFolio($data['guest_id'] ?? null, $data['reservation_id'] ?? null);
            $this->audit('check_in', 'hm_checkins', $id, $data);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['check_in' => 'Check-in could not be completed: '.$e->getMessage()])->withInput();
        }

        return back()->with('status','Guest checked in successfully.');
    }

    public function checkOut(Request $request)
    {
        $data = $request->validate([
            'checkin_id' => 'nullable|integer',
            'reservation_id' => 'nullable|integer',
            'room_id' => 'required|integer',
            'final_amount' => 'nullable|numeric',
        ]);

        $data = $this->withScope($data);
        $data['checked_out_at'] = now();
        $data['checked_out_by'] = auth()->id();
        $data['status'] = 'checked_out';
        $data['final_amount'] = $data['final_amount'] ?? 0;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            $id = DB::table('hm_checkouts')->insertGetId($data);
            $this->scopedQuery('hm_rooms')->where('id', $data['room_id'])->update(['status'=>'available','housekeeping_status'=>'dirty','updated_at'=>now()]);
            if (!empty($data['checkin_id'])) {
                $this->updateScopedRow('hm_checkins', (int) $data['checkin_id'], ['status'=>'checked_out']);
            }
            if (!empty($data['reservation_id'])) {
                $this->updateScopedRow('hm_reservations', (int) $data['reservation_id'], ['status'=>'checked_out']);
                $this->scopedQuery('hm_folios')->where('reservation_id', (int) $data['reservation_id'])->where('status', 'open')->update(['status' => 'closed', 'updated_at' => now()]);
            }
            $this->audit('check_out', 'hm_checkouts', $id, $data);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['check_out' => 'Check-out could not be completed: '.$e->getMessage()])->withInput();
        }

        return back()->with('status','Guest checked out successfully.');
    }

    private function createOpenFolio(?int $guestId, ?int $reservationId): void
    {
        if (!Schema::hasTable('hm_folios')) { return; }
        $existing = $this->scopedQuery('hm_folios')
            ->when($reservationId, fn($q) => $q->where('reservation_id', $reservationId))
            ->when(!$reservationId && $guestId, fn($q) => $q->where('guest_id', $guestId))
            ->where('status', 'open')
            ->first();
        if ($existing) { return; }
        $payload = $this->withScope([
            'guest_id' => $guestId,
            'reservation_id' => $reservationId,
            'folio_no' => $this->nextCode('hm_folios', 'folio_no', 'FOL'),
            'folio_type' => 'guest',
            'folio_date' => now()->toDateString(),
            'total_charges' => 0,
            'total_payments' => 0,
            'balance' => 0,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hm_folios')->insert($payload);
    }
}
