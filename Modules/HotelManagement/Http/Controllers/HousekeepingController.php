<?php

namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class HousekeepingController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        $rooms = $this->activeOptions('hm_rooms', 'room_no');

        return view('hotelmanagement::housekeeping.index', [
            'rooms' => $rooms,
            'tasks' => $this->safeRows('hm_housekeeping_tasks', 100),
            'schedules' => $this->safeRows('hm_housekeeping_schedules', 100),
            'lostFoundItems' => $this->safeRows('hm_lost_found_items', 100),
            'linenMovements' => $this->safeRows('hm_linen_movements', 100),
            'roomStatusSummary' => $this->roomStatusSummary(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|integer',
            'task_no' => 'nullable|string|max:50',
            'task_type' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $data['task_no'] = $data['task_no'] ?: $this->nextCode('hm_housekeeping_tasks', 'task_no', 'HK');
        $data['task_type'] = $data['task_type'] ?: 'cleaning';
        $data['priority'] = $data['priority'] ?: 'normal';
        $data['status'] = $data['status'] ?: 'pending';

        $id = $this->insertScoped('hm_housekeeping_tasks', $data);
        $this->syncRoomHousekeepingStatus((int) $data['room_id'], $data['status']);
        $this->audit('created', 'hm_housekeeping_tasks', $id, $data);

        return back()->with('status', 'Housekeeping task saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'room_id' => 'required|integer',
            'task_no' => 'nullable|string|max:50',
            'task_type' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $data['task_type'] = $data['task_type'] ?: 'cleaning';
        $data['priority'] = $data['priority'] ?: 'normal';
        $data['status'] = $data['status'] ?: 'pending';

        $this->updateScopedRow('hm_housekeeping_tasks', (int) $id, $data);
        $this->syncRoomHousekeepingStatus((int) $data['room_id'], $data['status']);
        $this->audit('updated', 'hm_housekeeping_tasks', (int) $id, $data);

        return back()->with('status', 'Housekeeping task updated successfully.');
    }

    public function destroy($id)
    {
        $this->deleteScopedRow('hm_housekeeping_tasks', (int) $id);
        $this->audit('deleted', 'hm_housekeeping_tasks', (int) $id);

        return back()->with('status', 'Housekeeping task deleted successfully.');
    }

    public function scheduleStore(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|integer',
            'attendant_id' => 'nullable|integer',
            'schedule_no' => 'nullable|string|max:50',
            'cleaning_type' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
            'cleaning_date' => 'nullable|date',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $data['schedule_no'] = $data['schedule_no'] ?: $this->nextCode('hm_housekeeping_schedules', 'schedule_no', 'HKS');
        $data['cleaning_type'] = $data['cleaning_type'] ?: 'departure';
        $data['priority'] = $data['priority'] ?: 'normal';
        $data['status'] = $data['status'] ?: 'scheduled';
        $data['cleaning_date'] = $data['cleaning_date'] ?: now()->toDateString();

        $id = $this->insertScoped('hm_housekeeping_schedules', $data);
        $this->syncRoomHousekeepingStatus((int) $data['room_id'], $data['status']);
        $this->audit('created', 'hm_housekeeping_schedules', $id, $data);

        return back()->with('status', 'Housekeeping schedule saved successfully.');
    }

    public function scheduleStatus(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|string|max:30']);
        $status = strtolower($data['status']);
        $update = ['status' => $status];
        if ($status === 'in_progress') { $update['started_at'] = now(); }
        if (in_array($status, ['completed', 'inspected'], true)) { $update['completed_at'] = now(); }

        $schedule = $this->scopedQuery('hm_housekeeping_schedules')->where('id', (int) $id)->first();
        $this->updateScopedRow('hm_housekeeping_schedules', (int) $id, $update);
        if ($schedule && $schedule->room_id) { $this->syncRoomHousekeepingStatus((int) $schedule->room_id, $status); }
        $this->audit('status_changed', 'hm_housekeeping_schedules', (int) $id, $update);

        return back()->with('status', 'Housekeeping schedule status updated.');
    }

    public function lostFoundStore(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'nullable|integer',
            'guest_id' => 'nullable|integer',
            'item_no' => 'nullable|string|max:50',
            'item_name' => 'required|string|max:191',
            'category' => 'nullable|string|max:80',
            'found_date' => 'nullable|date',
            'found_by' => 'nullable|string|max:191',
            'storage_location' => 'nullable|string|max:191',
            'status' => 'nullable|string|max:30',
            'description' => 'nullable|string',
        ]);

        $data['item_no'] = $data['item_no'] ?: $this->nextCode('hm_lost_found_items', 'item_no', 'LF');
        $data['found_date'] = $data['found_date'] ?: now()->toDateString();
        $data['status'] = $data['status'] ?: 'stored';

        $id = $this->insertScoped('hm_lost_found_items', $data);
        $this->audit('created', 'hm_lost_found_items', $id, $data);

        return back()->with('status', 'Lost & Found item saved successfully.');
    }

    public function lostFoundClaim(Request $request, $id)
    {
        $data = $request->validate([
            'claimed_by' => 'nullable|string|max:191',
            'status' => 'nullable|string|max:30',
        ]);
        $data['status'] = $data['status'] ?: 'claimed';
        $data['claimed_date'] = now()->toDateString();

        $this->updateScopedRow('hm_lost_found_items', (int) $id, $data);
        $this->audit('claimed', 'hm_lost_found_items', (int) $id, $data);

        return back()->with('status', 'Lost & Found item updated successfully.');
    }

    public function linenStore(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'nullable|integer',
            'movement_no' => 'nullable|string|max:50',
            'linen_item' => 'required|string|max:191',
            'quantity' => 'required|numeric|min:0.0001',
            'movement_type' => 'nullable|string|max:50',
            'from_location' => 'nullable|string|max:191',
            'to_location' => 'nullable|string|max:191',
            'movement_date' => 'nullable|date',
            'status' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $data['movement_no'] = $data['movement_no'] ?: $this->nextCode('hm_linen_movements', 'movement_no', 'LIN');
        $data['movement_type'] = $data['movement_type'] ?: 'issue';
        $data['movement_date'] = $data['movement_date'] ?: now()->toDateString();
        $data['status'] = $data['status'] ?: 'posted';

        $id = $this->insertScoped('hm_linen_movements', $data);
        $this->audit('created', 'hm_linen_movements', $id, $data);

        return back()->with('status', 'Linen movement posted successfully.');
    }

    protected function insertScoped(string $table, array $data): int
    {
        $data = $this->withScope($data);
        if (Schema::hasColumn($table, 'created_by')) { $data['created_by'] = Auth::id(); }
        if (Schema::hasColumn($table, 'updated_by')) { $data['updated_by'] = Auth::id(); }
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return (int) DB::table($table)->insertGetId($data);
    }

    protected function syncRoomHousekeepingStatus(int $roomId, string $status): void
    {
        if (!$roomId || !$this->tableExists('hm_rooms')) { return; }
        $roomStatus = match (strtolower($status)) {
            'completed' => 'clean',
            'inspected' => 'inspected',
            'in_progress' => 'cleaning',
            'blocked' => 'dirty',
            default => 'dirty',
        };

        try {
            $this->updateScopedRow('hm_rooms', $roomId, ['housekeeping_status' => $roomStatus]);
        } catch (\Throwable $e) {
            // Some older installs may not have the housekeeping_status column until master SQL is applied.
        }
    }

    protected function roomStatusSummary()
    {
        if (!$this->tableExists('hm_rooms') || !Schema::hasColumn('hm_rooms', 'housekeeping_status')) { return collect(); }
        return $this->scopedQuery('hm_rooms')
            ->select('housekeeping_status', DB::raw('COUNT(*) as total'))
            ->groupBy('housekeeping_status')
            ->orderBy('housekeeping_status')
            ->get();
    }
}
