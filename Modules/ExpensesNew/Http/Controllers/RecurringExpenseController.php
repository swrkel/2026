<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RecurringExpenseController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::recurring.index');
    }

    public function create()
    {
        return view('expensesnew::recurring.form');
    }

    public function store(Request $request)
    {
        return redirect()->route('expenses-new.recurring.index')->with('status', __('expensesnew::messages.saved_successfully'));
    }

    public function edit($id)
    {
        return view('expensesnew::recurring.form', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('expenses-new.recurring.index')->with('status', __('expensesnew::messages.updated_successfully'));
    }
}
