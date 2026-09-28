<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Services\AutoServiceNumberService;

class ReceptionController extends AutoServiceBaseController
{
    public function index()
    {
        $b=$this->businessId();
        $rows = DB::table('auto_service_receptions')->when($b,fn($q)=>$q->where('business_id',$b))->orderByDesc('id')->paginate(25);
        return view('autoservice::receptions.index', compact('rows'));
    }
    public function create(){ return view('autoservice::receptions.form', ['row'=>null]); }
    public function store(Request $request, AutoServiceNumberService $numbers)
    {
        $data = $request->validate([
            'vehicle_id'=>'required|integer','contact_id'=>'nullable|integer','received_at'=>'nullable|date','odometer'=>'nullable|integer',
            'fuel_level'=>'nullable|string','customer_complaint'=>'nullable|string','advisor_remarks'=>'nullable|string','accessories_received'=>'nullable|string','existing_damage'=>'nullable|string'
        ]);
        $data['business_id']=$this->businessId(); $data['location_id']=$this->locationId();
        $data['reception_no']=$numbers->nextReceptionNo($data['business_id'] ?? null);
        $data['received_at']=$data['received_at'] ?? now(); $data['status']='received';
        $id = DB::table('auto_service_receptions')->insertGetId(array_merge($data, ['created_at'=>now(),'updated_at'=>now()]));
        DB::table('auto_service_timeline')->insert([
            'business_id'=>$data['business_id'],'location_id'=>$data['location_id'],'vehicle_id'=>$data['vehicle_id'],
            'event_type'=>'reception','title'=>'Vehicle received','description'=>$data['customer_complaint'] ?? null,'event_at'=>now(),'created_at'=>now(),'updated_at'=>now()
        ]);
        return redirect()->route('autoservice.receptions.index')->with('status','Vehicle reception saved successfully.');
    }
}
