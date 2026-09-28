<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewOpportunity;
class LeadsNewOpportunityController extends Controller { public function index(){ $opportunities=LeadsNewOpportunity::latest()->paginate(25); return view('leadsnew::opportunities.index',compact('opportunities')); } public function store(Request $r){ LeadsNewOpportunity::create($r->all()+['business_id'=>session('business.id')]); return back()->with('status',['success'=>1,'msg'=>'Opportunity saved']); } public function update(Request $r,$id){ LeadsNewOpportunity::findOrFail($id)->update($r->all()); return back()->with('status',['success'=>1,'msg'=>'Opportunity updated']); } }
