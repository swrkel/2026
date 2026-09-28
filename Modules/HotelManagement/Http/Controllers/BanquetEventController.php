<?php

namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class BanquetEventController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::banquets.index', [
            'halls' => $this->safeRows('hm_banquet_halls', 100),
            'events' => $this->events(),
        ]);
    }

    public function storeHall(Request $request)
    {
        $data = $request->validate([
            'hall_name' => 'required|string|max:191',
            'hall_code' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:0',
            'base_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'description' => 'nullable|string',
        ]);
        $payload = $this->withScope([
            'hall_name' => $data['hall_name'],
            'hall_code' => $data['hall_code'] ?: $this->nextCode('hm_banquet_halls','hall_code','HBH'),
            'capacity' => $data['capacity'] ?? 0,
            'base_rate' => $data['base_rate'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'description' => $data['description'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $id = DB::table('hm_banquet_halls')->insertGetId($payload);
        $this->audit('created', 'hm_banquet_halls', $id, $payload);
        return back()->with('status', 'Banquet hall saved successfully.');
    }

    public function updateHall(Request $request, $id)
    {
        $data = $request->validate([
            'hall_name' => 'required|string|max:191',
            'capacity' => 'nullable|integer|min:0',
            'base_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'description' => 'nullable|string',
        ]);
        $this->updateScopedRow('hm_banquet_halls', (int)$id, $data);
        $this->audit('updated', 'hm_banquet_halls', (int)$id, $data);
        return back()->with('status', 'Banquet hall updated successfully.');
    }

    public function storeEvent(Request $request)
    {
        $data = $request->validate([
            'event_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'hall_id' => 'required|integer',
            'event_type' => 'required|string|max:80',
            'customer_name' => 'required|string|max:191',
            'customer_mobile' => 'nullable|string|max:50',
            'guest_count' => 'nullable|integer|min:0',
            'package_amount' => 'nullable|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);
        $hall = $this->scopedQuery('hm_banquet_halls')->where('id',(int)$data['hall_id'])->first();
        if (!$hall) { return back()->withErrors(['hall_id' => 'Selected banquet hall was not found for this business/location.'])->withInput(); }
        if ($this->hasClash((int)$data['hall_id'], $data['event_date'], $data['start_time'] ?? null, $data['end_time'] ?? null)) {
            return back()->withErrors(['event_date' => 'This hall already has an active booking for the selected date/time.'])->withInput();
        }
        $package = (float)($data['package_amount'] ?? 0);
        $tax = (float)($data['tax_amount'] ?? 0);
        $discount = (float)($data['discount_amount'] ?? 0);
        $advance = (float)($data['advance_amount'] ?? 0);
        $total = round($package + $tax - $discount, 4);
        $payload = $this->withScope([
            'event_no' => $this->nextCode('hm_banquet_events','event_no','HBE'),
            'event_date' => $data['event_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'hall_id' => $hall->id,
            'hall_name' => $hall->hall_name,
            'event_type' => $data['event_type'],
            'customer_name' => $data['customer_name'],
            'customer_mobile' => $data['customer_mobile'] ?? null,
            'guest_count' => $data['guest_count'] ?? 0,
            'package_amount' => $package,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'advance_amount' => $advance,
            'balance_amount' => round($total - $advance, 4),
            'total_amount' => $total,
            'status' => 'reserved',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $id = DB::table('hm_banquet_events')->insertGetId($payload);
        $this->audit('created', 'hm_banquet_events', $id, $payload);
        return back()->with('status', 'Banquet event booked successfully.');
    }

    public function status(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|string|max:30']);
        $allowed = ['reserved','confirmed','in_progress','completed','cancelled'];
        if (!in_array($data['status'], $allowed, true)) { return back()->withErrors(['status' => 'Invalid event status.']); }
        $this->updateScopedRow('hm_banquet_events', (int)$id, ['status' => $data['status']]);
        $this->audit('status_changed', 'hm_banquet_events', (int)$id, $data);
        return back()->with('status', 'Event status updated successfully.');
    }

    protected function events()
    {
        if (!$this->tableExists('hm_banquet_events')) { return collect(); }
        return $this->scopedQuery('hm_banquet_events as e')
            ->leftJoin('hm_banquet_halls as h','h.id','=','e.hall_id')
            ->select('e.*','h.capacity')
            ->orderBy('e.event_date','desc')->orderBy('e.id','desc')->limit(100)->get();
    }

    protected function hasClash(int $hallId, string $date, ?string $start, ?string $end): bool
    {
        if (!$this->tableExists('hm_banquet_events')) { return false; }
        $q = $this->scopedQuery('hm_banquet_events')->where('hall_id',$hallId)->where('event_date',$date)->whereNotIn('status',['cancelled','completed']);
        if ($start && $end) {
            $q->where(function($w) use ($start,$end) {
                $w->whereNull('start_time')->orWhereNull('end_time')
                  ->orWhere(function($x) use ($start,$end) { $x->where('start_time','<',$end)->where('end_time','>',$start); });
            });
        }
        return $q->exists();
    }
}
