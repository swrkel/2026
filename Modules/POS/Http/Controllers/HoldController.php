<?php
namespace Modules\POS\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
class HoldController extends Controller
{
    public function index(){ return view('pos::holds.index'); }
    public function status(){ return view('pos::dashboard.status'); }
    public function create(){ return view('pos::holds.create'); }
    public function store(Request $request){ return redirect()->back()->with('status','Saved successfully'); }
    public function show($id){ return view('pos::holds.show', compact('id')); }
    public function edit($id){ return view('pos::holds.edit', compact('id')); }
    public function update(Request $request, $id){ return redirect()->back()->with('status','Updated successfully'); }
    public function destroy($id){ return redirect()->back()->with('status','Deleted successfully'); }
}
