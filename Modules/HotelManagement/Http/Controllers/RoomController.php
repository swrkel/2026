<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class RoomController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::rooms.index', ['rooms' => $this->safeRows('hm_rooms', 100),'roomTypes'=>$this->safeRows('hm_room_types',100),'hotels'=>$this->activeOptions('hm_hotels','hotel_name'),'types'=>$this->activeOptions('hm_room_types','type_name')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['room_no'=>'required|string|max:50','room_name'=>'nullable|string|max:191','hotel_id'=>'nullable|integer','room_type_id'=>'nullable|integer','phone_extension'=>'nullable|string|max:50','status'=>'nullable|string|max:30','housekeeping_status'=>'nullable|string|max:30']);

        foreach (['status' => 'active', 'unit' => 'unit', 'reorder_level' => 0, 'rate' => 0, 'adults' => 1, 'children' => 0, 'estimated_total' => 0, 'total_charges' => 0, 'total_payments' => 0] as $key => $value) {
            if (array_key_exists($key, $data) && $data[$key] === null) { $data[$key] = $value; }
        }
        if (array_key_exists('folio_type', $data) && !$data['folio_type']) { $data['folio_type'] = 'guest'; }
        if (array_key_exists('status', $data) && !$data['status']) { $data['status'] = 'active'; }
        if ('hm_rooms' === 'hm_folios') { $data['balance'] = ($data['total_charges'] ?? 0) - ($data['total_payments'] ?? 0); }
        if ('hm_rooms' === 'hm_room_charges') { $data['source_module'] = 'hotel'; if (!$data['status']) { $data['status']='posted'; } }
        if ('hm_rooms' === 'hm_rooms') { $data['status']=$data['status'] ?? 'available'; $data['housekeeping_status']=$data['housekeeping_status'] ?? 'clean'; }
        if ('hm_rooms' === 'hm_housekeeping_tasks') { $data['task_type']=$data['task_type'] ?? 'cleaning'; $data['priority']=$data['priority'] ?? 'normal'; $data['status']=$data['status'] ?? 'pending'; }
    
        $data = $this->withScope($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('hm_rooms')->insertGetId($data);
        $this->audit('created', 'hm_rooms', $id, $data);
        return back()->with('status', 'Room saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate(['room_no'=>'required|string|max:50','room_name'=>'nullable|string|max:191','hotel_id'=>'nullable|integer','room_type_id'=>'nullable|integer','phone_extension'=>'nullable|string|max:50','status'=>'nullable|string|max:30','housekeeping_status'=>'nullable|string|max:30']);

        foreach (['status' => 'active', 'unit' => 'unit', 'reorder_level' => 0, 'rate' => 0, 'adults' => 1, 'children' => 0, 'estimated_total' => 0, 'total_charges' => 0, 'total_payments' => 0] as $key => $value) {
            if (array_key_exists($key, $data) && $data[$key] === null) { $data[$key] = $value; }
        }
        if (array_key_exists('folio_type', $data) && !$data['folio_type']) { $data['folio_type'] = 'guest'; }
        if (array_key_exists('status', $data) && !$data['status']) { $data['status'] = 'active'; }
        if ('hm_rooms' === 'hm_folios') { $data['balance'] = ($data['total_charges'] ?? 0) - ($data['total_payments'] ?? 0); }
        if ('hm_rooms' === 'hm_room_charges') { $data['source_module'] = 'hotel'; if (!$data['status']) { $data['status']='posted'; } }
        if ('hm_rooms' === 'hm_rooms') { $data['status']=$data['status'] ?? 'available'; $data['housekeeping_status']=$data['housekeeping_status'] ?? 'clean'; }
        if ('hm_rooms' === 'hm_housekeeping_tasks') { $data['task_type']=$data['task_type'] ?? 'cleaning'; $data['priority']=$data['priority'] ?? 'normal'; $data['status']=$data['status'] ?? 'pending'; }
    
        $this->updateScopedRow('hm_rooms', (int) $id, $data);
        $this->audit('updated', 'hm_rooms', (int) $id, $data);
        return back()->with('status', 'Room updated successfully.');
    }

    public function destroy($id)
    {
        $this->deleteScopedRow('hm_rooms', (int) $id);
        $this->audit('deleted', 'hm_rooms', (int) $id);
        return back()->with('status', 'Room deleted successfully.');
    }
}
