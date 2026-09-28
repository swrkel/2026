<?php
namespace Modules\LeadsNew\Http\Controllers\Api;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\LeadsNew\Models\LeadsNewLead;
class LeadsNewStage8ApiController extends Controller
{
    public function summary(){ return response()->json(['total'=>LeadsNewLead::count(),'open'=>LeadsNewLead::whereNotIn('status',['Converted','Lost'])->count()]); }
    public function search(Request $request){ $q=$request->get('q'); return LeadsNewLead::where('name','like',"%{$q}%")->orWhere('mobile','like',"%{$q}%")->limit(20)->get(); }
}
