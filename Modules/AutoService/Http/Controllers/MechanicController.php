<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceMechanic;
class MechanicController extends AutoServiceBaseController
{
    public function index(){ $q=AutoServiceMechanic::query(); if($this->businessId())$q->where('business_id',$this->businessId()); return view('autoservice::mechanics.index',['mechanics'=>$q->orderBy('name')->paginate(25)]); }
    public function create(){ return view('autoservice::mechanics.form',['mechanic'=>new AutoServiceMechanic()]); }
    public function store(Request $r){ $data=$this->data($r); AutoServiceMechanic::create($data); return redirect()->route('autoservice.mechanics.index')->with('status','Mechanic saved successfully.'); }
    public function edit($id){ return view('autoservice::mechanics.form',['mechanic'=>AutoServiceMechanic::findOrFail($id)]); }
    public function update(Request $r,$id){ AutoServiceMechanic::findOrFail($id)->update($this->data($r)); return redirect()->route('autoservice.mechanics.index')->with('status','Mechanic updated successfully.'); }
    private function data(Request $r){ $d=$r->only(['user_id','mechanic_code','name','mobile','speciality','hourly_rate','is_active','notes']); $d['business_id']=$this->businessId(); $d['location_id']=$this->locationId(); $d['is_active']=$r->has('is_active')?1:0; return $d; }
}
