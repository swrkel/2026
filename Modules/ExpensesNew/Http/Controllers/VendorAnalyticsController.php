<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VendorAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::vendor-analytics.index');
    }

    public function create()
    {
        return view('expensesnew::vendor-analytics.form');
    }

    public function store(Request $request)
    {
        return redirect()->route('expenses-new.vendor-analytics.index')->with('status', __('expensesnew::messages.saved_successfully'));
    }

    public function edit($id)
    {
        return view('expensesnew::vendor-analytics.form', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('expenses-new.vendor-analytics.index')->with('status', __('expensesnew::messages.updated_successfully'));
    }
}
