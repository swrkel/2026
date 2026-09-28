<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewLead; use Modules\LeadsNew\Services\LeadsNewConversionService;
class LeadsNewConversionController extends Controller { public function store(Request $r,$lead){ $lead=LeadsNewLead::findOrFail($lead); app(LeadsNewConversionService::class)->convert($lead,$r->input('type','customer'),$r->input('target_id'),auth()->id()); return back()->with('status',['success'=>1,'msg'=>'Lead converted']); } }
