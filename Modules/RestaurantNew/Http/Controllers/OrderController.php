<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function index()
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Order']);
    }

    public function data()
    {
        return response()->json(['data' => []]);
    }

    public function create()
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Create Order']);
    }

    public function store(Request $request)
    {
        return back()->with('status', 'Order saved successfully.');
    }

    public function show($id)
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Order Details']);
    }

    public function edit($id)
    {
        return view('restaurantnew::dashboard.placeholder', ['title' => 'Edit Order']);
    }

    public function update(Request $request, $id)
    {
        return back()->with('status', 'Order updated successfully.');
    }

    public function destroy($id)
    {
        return back()->with('status', 'Order deleted successfully.');
    }
}
