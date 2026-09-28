<?php
namespace Modules\LeadsNew\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\LeadsNew\Models\LeadsNewTerritory;
class LeadsNewTerritoryController extends Controller
{
    public function index(){ $territories=LeadsNewTerritory::orderBy('name')->paginate(25); return view('leadsnew::territories.index',compact('territories')); }
    public function store(Request $request){ $data=$request->validate(['name'=>'required|max:191','code'=>'nullable|max:50','description'=>'nullable|string','is_active'=>'nullable|boolean']); LeadsNewTerritory::create($data); return back()->with('status','Territory saved successfully'); }
}
