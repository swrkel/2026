<?php

namespace Modules\ExpensesNew\Http\Controllers\Costing;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class AllocationRunController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::costing.index', ['page_title' => 'AllocationRun']);
    }

    public function create()
    {
        return view('expensesnew::costing.form', ['mode' => 'create']);
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('expensesnew::lang.saved_successfully'));
    }
}
