<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Services\SharedCustomerAdapter;
class VehicleController extends AutoServiceBaseController
{
 public function index(){ $q=AutoServiceVehicle::query(); if($this->businessId())$q->where('business_id',$this->businessId()); return view('autoservice::vehicles.index',['vehicles'=>$q->orderByDesc('id')->paginate(25)]); }
 public function create(){ return view('autoservice::vehicles.form',['vehicle'=>new AutoServiceVehicle(),'customers'=>app(SharedCustomerAdapter::class)->list($this->businessId())]); }
 public function store(Request $r){ $data=$r->only(['contact_id','registration_no','make','model','year','vin','engine_no','chassis_no','current_odometer','last_service_date','next_service_date','next_service_odometer','notes']); $data['business_id']=$this->businessId(); $data['location_id']=$this->locationId(); AutoServiceVehicle::create($data); return redirect()->route('autoservice.vehicles.index')->with('status','Vehicle saved successfully.'); }
 public function edit($id){ $v=AutoServiceVehicle::findOrFail($id); return view('autoservice::vehicles.form',['vehicle'=>$v,'customers'=>app(SharedCustomerAdapter::class)->list($this->businessId())]); }
 public function update(Request $r,$id){ $v=AutoServiceVehicle::findOrFail($id); $v->update($r->only(['contact_id','registration_no','make','model','year','vin','engine_no','chassis_no','current_odometer','last_service_date','next_service_date','next_service_odometer','notes'])); return redirect()->route('autoservice.vehicles.index')->with('status','Vehicle updated successfully.'); }
 public function search(Request $r){ $q=AutoServiceVehicle::query(); if($this->businessId())$q->where('business_id',$this->businessId()); if($r->term)$q->where('registration_no','like','%'.$r->term.'%'); return response()->json(['results'=>$q->limit(20)->get()->map(fn($v)=>['id'=>$v->id,'text'=>$v->registration_no.' - '.trim($v->make.' '.$v->model)])]); }
}
