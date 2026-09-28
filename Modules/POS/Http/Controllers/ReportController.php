<?php
namespace Modules\POS\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
class ReportController extends Controller
{
    public function index(){ return view('pos::reports.index'); }
    public function status(){ return view('pos::dashboard.status'); }
    public function create(){ return view('pos::reports.create'); }
    public function store(Request $request){ return redirect()->back()->with('status','Saved successfully'); }
    public function show($id){ return view('pos::reports.show', compact('id')); }
    public function edit($id){ return view('pos::reports.edit', compact('id')); }
    public function update(Request $request, $id){ return redirect()->back()->with('status','Updated successfully'); }
    public function destroy($id){ return redirect()->back()->with('status','Deleted successfully'); }
}
