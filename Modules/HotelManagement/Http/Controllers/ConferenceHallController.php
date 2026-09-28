<?php

namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class ConferenceHallController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::conference.index', [
            'rooms' => $this->safeRows('hm_conference_rooms', 100),
            'bookings' => $this->bookings(),
        ]);
    }

    public function storeRoom(Request $request)
    {
        $data = $request->validate([
            'room_name' => 'required|string|max:191',
            'room_code' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'half_day_rate' => 'nullable|numeric|min:0',
            'full_day_rate' => 'nullable|numeric|min:0',
            'setup_style' => 'nullable|string|max:80',
            'equipment' => 'nullable|string',
            'status' => 'nullable|string|max:30',
        ]);

        $payload = $this->withScope([
            'room_name' => $data['room_name'],
            'room_code' => $data['room_code'] ?: $this->nextCode('hm_conference_rooms', 'room_code', 'HCR'),
            'capacity' => $data['capacity'] ?? 0,
            'hourly_rate' => $data['hourly_rate'] ?? 0,
            'half_day_rate' => $data['half_day_rate'] ?? 0,
            'full_day_rate' => $data['full_day_rate'] ?? 0,
            'setup_style' => $data['setup_style'] ?? null,
            'equipment' => $data['equipment'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('hm_conference_rooms')->insertGetId($payload);
        $this->audit('created', 'hm_conference_rooms', $id, $payload);
        return back()->with('status', 'Conference room saved successfully.');
    }

    public function updateRoom(Request $request, $id)
    {
        $data = $request->validate([
            'room_name' => 'required|string|max:191',
            'capacity' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'half_day_rate' => 'nullable|numeric|min:0',
            'full_day_rate' => 'nullable|numeric|min:0',
            'setup_style' => 'nullable|string|max:80',
            'equipment' => 'nullable|string',
            'status' => 'nullable|string|max:30',
        ]);
        $this->updateScopedRow('hm_conference_rooms', (int) $id, $data);
        $this->audit('updated', 'hm_conference_rooms', (int) $id, $data);
        return back()->with('status', 'Conference room updated successfully.');
    }

    public function storeBooking(Request $request)
    {
        $data = $request->validate([
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'conference_room_id' => 'required|integer',
            'meeting_title' => 'required|string|max:191',
            'customer_name' => 'required|string|max:191',
            'customer_mobile' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:191',
            'attendees' => 'nullable|integer|min:0',
            'setup_style' => 'nullable|string|max:80',
            'equipment_required' => 'nullable|string',
            'catering_required' => 'nullable|boolean',
            'catering_note' => 'nullable|string',
            'rental_amount' => 'nullable|numeric|min:0',
            'catering_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $room = $this->scopedQuery('hm_conference_rooms')->where('id', (int) $data['conference_room_id'])->first();
        if (!$room) {
            return back()->withErrors(['conference_room_id' => 'Selected conference room was not found for this business/location.'])->withInput();
        }
        if ($this->hasClash((int) $room->id, $data['booking_date'], $data['start_time'], $data['end_time'])) {
            return back()->withErrors(['booking_date' => 'This conference room already has an active booking for the selected date/time.'])->withInput();
        }

        $rental = (float)($data['rental_amount'] ?? 0);
        $catering = (float)($data['catering_amount'] ?? 0);
        $tax = (float)($data['tax_amount'] ?? 0);
        $discount = (float)($data['discount_amount'] ?? 0);
        $advance = (float)($data['advance_amount'] ?? 0);
        $total = round($rental + $catering + $tax - $discount, 4);

        $payload = $this->withScope([
            'booking_no' => $this->nextCode('hm_conference_bookings', 'booking_no', 'HCB'),
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'conference_room_id' => $room->id,
            'room_name' => $room->room_name,
            'meeting_title' => $data['meeting_title'],
            'customer_name' => $data['customer_name'],
            'customer_mobile' => $data['customer_mobile'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'attendees' => $data['attendees'] ?? 0,
            'setup_style' => $data['setup_style'] ?? ($room->setup_style ?? null),
            'equipment_required' => $data['equipment_required'] ?? null,
            'catering_required' => !empty($data['catering_required']) ? 1 : 0,
            'catering_note' => $data['catering_note'] ?? null,
            'rental_amount' => $rental,
            'catering_amount' => $catering,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'advance_amount' => $advance,
            'total_amount' => $total,
            'balance_amount' => round($total - $advance, 4),
            'status' => 'reserved',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('hm_conference_bookings')->insertGetId($payload);
        $this->audit('created', 'hm_conference_bookings', $id, $payload);
        return back()->with('status', 'Conference booking saved successfully.');
    }

    public function status(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|string|max:30']);
        $allowed = ['reserved', 'confirmed', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($data['status'], $allowed, true)) {
            return back()->withErrors(['status' => 'Invalid booking status.']);
        }
        $this->updateScopedRow('hm_conference_bookings', (int) $id, ['status' => $data['status']]);
        $this->audit('status_changed', 'hm_conference_bookings', (int) $id, $data);
        return back()->with('status', 'Conference booking status updated successfully.');
    }

    protected function bookings()
    {
        if (!$this->tableExists('hm_conference_bookings')) { return collect(); }
        return $this->scopedQuery('hm_conference_bookings as b')
            ->leftJoin('hm_conference_rooms as r', 'r.id', '=', 'b.conference_room_id')
            ->select('b.*', 'r.capacity')
            ->orderBy('b.booking_date', 'desc')
            ->orderBy('b.id', 'desc')
            ->limit(100)
            ->get();
    }

    protected function hasClash(int $roomId, string $date, string $start, string $end): bool
    {
        if (!$this->tableExists('hm_conference_bookings')) { return false; }
        return $this->scopedQuery('hm_conference_bookings')
            ->where('conference_room_id', $roomId)
            ->where('booking_date', $date)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($x) use ($start, $end) {
                    $x->where('start_time', '<', $end)->where('end_time', '>', $start);
                });
            })
            ->exists();
    }
}
