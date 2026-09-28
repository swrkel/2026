<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class TailoringDashboardController extends Controller
{
    public function index(Request $request)
    {
        $view = 'tailoring::' . str_replace(['tailoring', 'controller'], '', strtolower(class_basename(static::class))) . '.index';
        if (view()->exists($view)) {
            return view($view);
        }
        return view('tailoring::dashboard.index');
    }

    public function create() { return view('tailoring::dashboard.form'); }
    public function store(Request $request) { return redirect()->back()->with('status', 'Saved successfully.'); }
    public function show($id) { return view('tailoring::dashboard.show', compact('id')); }
    public function edit($id) { return view('tailoring::dashboard.form', compact('id')); }
    public function update(Request $request, $id) { return redirect()->back()->with('status', 'Updated successfully.'); }
    public function destroy($id) { return redirect()->back()->with('status', 'Deleted successfully.'); }
}
