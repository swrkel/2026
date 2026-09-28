<?php

namespace Modules\ExpensesNew\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpenseReportRunController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::reports.run', [
            'page_title' => 'Expenses-New',
            'filters' => $request->all(),
        ]);
    }

    public function data(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'totals' => [],
        ]);
    }
}
