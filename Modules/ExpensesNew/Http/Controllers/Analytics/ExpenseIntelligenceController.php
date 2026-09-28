<?php

namespace Modules\ExpensesNew\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpenseIntelligenceController extends Controller
{
    public function index(Request $request)
    {
        return view('expensesnew::analytics.intelligence', [
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
