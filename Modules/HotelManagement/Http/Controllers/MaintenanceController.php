<?php

namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class MaintenanceController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        $orders = $this->tableExists('hm_maintenance_work_orders')
            ? $this->scopedQuery('hm_maintenance_work_orders as wo')
                ->leftJoin('hm_rooms as r', 'r.id', '=', 'wo.room_id')
                ->select('wo.*', 'r.room_no')
                ->orderByRaw("FIELD(wo.status, 'open', 'in_progress', 'on_hold', 'completed', 'cancelled')")
                ->orderBy('wo.id', 'desc')
                ->limit(100)
                ->get()
            : collect();

        return view('hotelmanagement::maintenance.index', [
            'orders' => $orders,
            'rooms' => $this->activeOptions('hm_rooms', 'room_no'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'nullable|integer',
            'work_order_no' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
            'reported_at' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
        ]);

        $data['work_order_no'] = $data['work_order_no'] ?: $this->nextCode('hm_maintenance_work_orders', 'work_order_no', 'MWO');
        $data['category'] = $data['category'] ?: 'general';
        $data['priority'] = $data['priority'] ?: 'normal';
        $data['status'] = $data['status'] ?: 'open';
        $data['reported_at'] = $data['reported_at'] ?: now();
        $data['estimated_cost'] = $data['estimated_cost'] ?? 0;
        $data = $this->withScope($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('hm_maintenance_work_orders')->insertGetId($data);
        $this->syncRoomMaintenanceStatus($data['room_id'] ?? null, $data['status']);
        $this->audit('created', 'hm_maintenance_work_orders', $id, $data);

        return back()->with('status', 'Maintenance work order saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'room_id' => 'nullable|integer',
            'work_order_no' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
            'reported_at' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
        ]);

        $data['category'] = $data['category'] ?: 'general';
        $data['priority'] = $data['priority'] ?: 'normal';
        $data['status'] = $data['status'] ?: 'open';
        $data['estimated_cost'] = $data['estimated_cost'] ?? 0;
        $this->updateScopedRow('hm_maintenance_work_orders', (int) $id, $data);
        $this->syncRoomMaintenanceStatus($data['room_id'] ?? null, $data['status']);
        $this->audit('updated', 'hm_maintenance_work_orders', (int) $id, $data);

        return back()->with('status', 'Maintenance work order updated successfully.');
    }

    public function destroy($id)
    {
        $row = $this->scopedQuery('hm_maintenance_work_orders')->where('id', (int) $id)->first();
        $this->deleteScopedRow('hm_maintenance_work_orders', (int) $id);
        if ($row && $row->room_id) { $this->syncRoomMaintenanceStatus($row->room_id, 'completed'); }
        $this->audit('deleted', 'hm_maintenance_work_orders', (int) $id);

        return back()->with('status', 'Maintenance work order deleted successfully.');
    }

    protected function syncRoomMaintenanceStatus(?int $roomId, ?string $status): void
    {
        if (!$roomId || !$this->tableExists('hm_rooms')) { return; }
        $status = (string) $status;
        if (in_array($status, ['open', 'in_progress', 'on_hold'], true)) {
            $this->updateScopedRow('hm_rooms', $roomId, ['status' => 'out_of_service', 'housekeeping_status' => 'maintenance']);
            return;
        }

        $openCount = $this->tableExists('hm_maintenance_work_orders')
            ? $this->scopedQuery('hm_maintenance_work_orders')
                ->where('room_id', $roomId)
                ->whereIn('status', ['open', 'in_progress', 'on_hold'])
                ->count()
            : 0;
        if ($openCount === 0) {
            $this->updateScopedRow('hm_rooms', $roomId, ['status' => 'available', 'housekeeping_status' => 'dirty']);
        }
    }
}
