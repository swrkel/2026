<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\ExpensesNew\Utils\BusinessScope;

class ReportController extends Controller
{
    public function expenseSummary()
    {
        return view('expensesnew::reports.expense_summary');
    }

    public function expenseSummaryData(Request $request)
    {
        $query = DB::table('expnew_expenses as expenses')
            ->leftJoin('expnew_categories as categories', function ($join): void {
                $join->on('categories.id', '=', 'expenses.category_id')
                    ->on('categories.business_id', '=', 'expenses.business_id');
            })
            ->where('expenses.business_id', BusinessScope::businessId());

        if ($request->filled('start_date')) {
            $query->whereDate('expenses.expense_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('expenses.expense_date', '<=', $request->end_date);
        }

        $rows = $query
            ->selectRaw("COALESCE(NULLIF(categories.name, ''), 'Uncategorized') AS category")
            ->selectRaw('COUNT(expenses.id) AS expense_count')
            ->selectRaw('COALESCE(SUM(expenses.total_amount), 0) AS total_amount')
            ->selectRaw('COALESCE(SUM(expenses.paid_amount), 0) AS paid_amount')
            ->selectRaw('COALESCE(SUM(expenses.due_amount), 0) AS due_amount')
            ->groupByRaw("COALESCE(NULLIF(categories.name, ''), 'Uncategorized')")
            ->orderBy('category')
            ->get()
            ->map(static fn (object $row): array => [
                'category' => $row->category,
                'count' => (int) $row->expense_count,
                'total' => number_format((float) $row->total_amount, 4),
                'paid' => number_format((float) $row->paid_amount, 4),
                'due' => number_format((float) $row->due_amount, 4),
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }
}
