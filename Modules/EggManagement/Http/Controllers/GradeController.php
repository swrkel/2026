<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Models\Grade;
class GradeController extends BaseController
{
    public function index(){return view('egg::settings.grades',['rows'=>$this->scope(Grade::query())->orderBy('sort_order')->orderBy('name')->get()]);}
    public function store(Request $r){$d=$r->validate(['code'=>'required|max:30','name'=>'required|max:100','min_weight_g'=>'nullable|numeric','max_weight_g'=>'nullable|numeric']);Grade::create(array_merge($d,['business_id'=>$this->context->businessId(),'active'=>1]));return back()->with('success','Egg grade added.');}
    public function toggle(Grade $grade){abort_unless($grade->business_id==$this->context->businessId(),404);$grade->active=!$grade->active;$grade->save();return back()->with('success','Egg grade status updated.');}
}
