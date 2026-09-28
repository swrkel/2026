<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Services\TailoringLifecycleService;
class TailoringLifecycleController extends Controller
{
    public function index(){ return view('tailoring::lifecycle.index'); }
    public function record(Request $request, TailoringLifecycleService $service){ $service->recordStage((int)$request->job_card_id, $request->stage, $request->status, $request->except(['job_card_id','stage','status'])); return back()->with('status', 'Stage updated successfully'); }
}
