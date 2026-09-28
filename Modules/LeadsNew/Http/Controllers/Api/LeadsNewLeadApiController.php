<?php
namespace Modules\LeadsNew\Http\Controllers\Api;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewLead;
class LeadsNewLeadApiController extends Controller { public function index(Request $r){ return response()->json(LeadsNewLead::where('business_id',$r->input('business_id',session('business.id')))->latest()->paginate(50)); } public function store(Request $r){ $lead=LeadsNewLead::create($r->all()+['business_id'=>$r->input('business_id',session('business.id'))]); return response()->json(['success'=>true,'data'=>$lead]); } }
