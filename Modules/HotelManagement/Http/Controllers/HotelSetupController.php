<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class HotelSetupController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::setup.index', ['hotels' => $this->safeRows('hm_hotels', 100),'buildings'=>$this->safeRows('hm_buildings'),'floors'=>$this->safeRows('hm_floors'),'amenities'=>$this->safeRows('hm_amenities')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['hotel_code'=>'required|string|max:50','hotel_name'=>'required|string|max:191','star_rating'=>'nullable|string|max:50','address'=>'nullable|string','phone'=>'nullable|string|max:50','email'=>'nullable|string|max:191','status'=>'nullable|string|max:30']);

        foreach (['status' => 'active', 'unit' => 'unit', 'reorder_level' => 0, 'rate' => 0, 'adults' => 1, 'children' => 0, 'estimated_total' => 0, 'total_charges' => 0, 'total_payments' => 0] as $key => $value) {
            if (array_key_exists($key, $data) && $data[$key] === null) { $data[$key] = $value; }
        }
        if (array_key_exists('folio_type', $data) && !$data['folio_type']) { $data['folio_type'] = 'guest'; }
        if (array_key_exists('status', $data) && !$data['status']) { $data['status'] = 'active'; }
        if ('hm_hotels' === 'hm_folios') { $data['balance'] = ($data['total_charges'] ?? 0) - ($data['total_payments'] ?? 0); }
        if ('hm_hotels' === 'hm_room_charges') { $data['source_module'] = 'hotel'; if (!$data['status']) { $data['status']='posted'; } }
        if ('hm_hotels' === 'hm_rooms') { $data['status']=$data['status'] ?? 'available'; $data['housekeeping_status']=$data['housekeeping_status'] ?? 'clean'; }
        if ('hm_hotels' === 'hm_housekeeping_tasks') { $data['task_type']=$data['task_type'] ?? 'cleaning'; $data['priority']=$data['priority'] ?? 'normal'; $data['status']=$data['status'] ?? 'pending'; }
    
        $data = $this->withScope($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('hm_hotels')->insertGetId($data);
        $this->audit('created', 'hm_hotels', $id, $data);
        return back()->with('status', 'Hotel saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate(['hotel_code'=>'required|string|max:50','hotel_name'=>'required|string|max:191','star_rating'=>'nullable|string|max:50','address'=>'nullable|string','phone'=>'nullable|string|max:50','email'=>'nullable|string|max:191','status'=>'nullable|string|max:30']);

        foreach (['status' => 'active', 'unit' => 'unit', 'reorder_level' => 0, 'rate' => 0, 'adults' => 1, 'children' => 0, 'estimated_total' => 0, 'total_charges' => 0, 'total_payments' => 0] as $key => $value) {
            if (array_key_exists($key, $data) && $data[$key] === null) { $data[$key] = $value; }
        }
        if (array_key_exists('folio_type', $data) && !$data['folio_type']) { $data['folio_type'] = 'guest'; }
        if (array_key_exists('status', $data) && !$data['status']) { $data['status'] = 'active'; }
        if ('hm_hotels' === 'hm_folios') { $data['balance'] = ($data['total_charges'] ?? 0) - ($data['total_payments'] ?? 0); }
        if ('hm_hotels' === 'hm_room_charges') { $data['source_module'] = 'hotel'; if (!$data['status']) { $data['status']='posted'; } }
        if ('hm_hotels' === 'hm_rooms') { $data['status']=$data['status'] ?? 'available'; $data['housekeeping_status']=$data['housekeeping_status'] ?? 'clean'; }
        if ('hm_hotels' === 'hm_housekeeping_tasks') { $data['task_type']=$data['task_type'] ?? 'cleaning'; $data['priority']=$data['priority'] ?? 'normal'; $data['status']=$data['status'] ?? 'pending'; }
    
        $this->updateScopedRow('hm_hotels', (int) $id, $data);
        $this->audit('updated', 'hm_hotels', (int) $id, $data);
        return back()->with('status', 'Hotel updated successfully.');
    }

    public function destroy($id)
    {
        $this->deleteScopedRow('hm_hotels', (int) $id);
        $this->audit('deleted', 'hm_hotels', (int) $id);
        return back()->with('status', 'Hotel deleted successfully.');
    }
}
