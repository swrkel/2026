<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller; use Illuminate\Http\Request;
class TailoringMaterialIssueController extends Controller
{
    public function index(Request $request) { return view('tailoring::production.material_issues'); }
    public function store(Request $request) { return redirect()->back()->with('status','Saved successfully.'); }
}
