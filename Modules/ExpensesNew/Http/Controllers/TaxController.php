<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::taxes.index');
    }

    public function create()
    {
        return view('expensesnew::taxes.form');
    }

    public function store(Request $request)
    {
        return redirect()->route('expenses-new.taxes.index')->with('status', __('expensesnew::messages.saved_successfully'));
    }

    public function edit($id)
    {
        return view('expensesnew::taxes.form', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('expenses-new.taxes.index')->with('status', __('expensesnew::messages.updated_successfully'));
    }
}
