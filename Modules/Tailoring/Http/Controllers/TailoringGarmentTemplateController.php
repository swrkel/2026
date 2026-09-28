<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Entities\TailoringGarmentTemplate;
class TailoringGarmentTemplateController extends Controller
{
    public function index(){ $templates = TailoringGarmentTemplate::latest()->paginate(25); return view('tailoring::garment_templates.index', compact('templates')); }
    public function create(){ return view('tailoring::garment_templates.create'); }
    public function store(Request $request){ TailoringGarmentTemplate::create($request->all()); return redirect()->route('tailoring.garment-templates.index')->with('status', 'Garment template saved successfully'); }
}
