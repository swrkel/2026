<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Entities\TailoringQcChecklist;
use Modules\Tailoring\Services\TailoringQcService;
class TailoringQcController extends Controller
{
    public function index(){ $records = TailoringQcChecklist::latest()->paginate(25); return view('tailoring::quality.index', compact('records')); }
    public function store(Request $request, TailoringQcService $service){ $service->saveChecklist($request->all()); return back()->with('status', 'Quality checklist saved successfully'); }
}
