<?php
namespace Modules\DistributionNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewBusinessLimit;
class DisnewBusinessLimitController extends Controller { public function edit($businessId){ $limits=DisnewBusinessLimit::firstOrNew(['business_id'=>$businessId]); return view('distributionnew::superadmin.limits', compact('limits','businessId')); } public function update(Request $request,$businessId){ DisnewBusinessLimit::updateOrCreate(['business_id'=>$businessId], $request->except('_token') + ['business_id'=>$businessId,'updated_by'=>auth()->id()]); return back()->with('status','Distribution New limits updated.'); } }
