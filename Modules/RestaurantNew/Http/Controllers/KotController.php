<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KotController extends Controller
{
    public function index()
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Kot']);
    }

    public function data()
    {
        return response()->json(['data' => []]);
    }

    public function create()
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Create Kot']);
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Kot saved successfully.');
    }

    public function show($id)
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Kot Details']);
    }

    public function edit($id)
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Edit Kot']);
    }

    public function update(Request $request, $id)
    {
        return back()->with('status', 'Kot updated successfully.');
    }

    public function print($order)
    {
        return back()->with('status', 'KOT print queued successfully.');
    }

    public function destroy($id)
    {
        return back()->with('status', 'Kot deleted successfully.');
    }
}

