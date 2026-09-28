<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BudgetControlController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::budget-control.index');
    }

    public function create()
    {
        return view('expensesnew::budget-control.form');
    }

    public function store(Request $request)
    {
        return redirect()->route('expenses-new.budget-control.index')->with('status', __('expensesnew::messages.saved_successfully'));
    }

    public function edit($id)
    {
        return view('expensesnew::budget-control.form', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('expenses-new.budget-control.index')->with('status', __('expensesnew::messages.updated_successfully'));
    }
}
