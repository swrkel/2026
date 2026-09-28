<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TailoringPatternController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->all();
        return view('tailoring::patterns.index', compact('filters'));
    }

    public function create(Request $request)
    {
        $filters = $request->all();
        return view('tailoring::patterns.index', compact('filters'));
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('tailoring::messages.saved_successfully'));
    }
}
