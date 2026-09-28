<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BillController extends Controller
{
    public function index()
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Bill']);
    }

    public function data()
    {
        return response()->json(['data' => []]);
    }

    public function create()
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Create Bill']);
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Bill saved successfully.');
    }

    public function show($id)
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Bill Details']);
    }

    public function edit($id)
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Edit Bill']);
    }

    public function update(Request $request, $id)
    {
        return back()->with('status', 'Bill updated successfully.');
    }

    public function finalize($order)
    {
        return back()->with('status', 'Bill finalized successfully.');
    }

    public function destroy($id)
    {
        return back()->with('status', 'Bill deleted successfully.');
    }
}
