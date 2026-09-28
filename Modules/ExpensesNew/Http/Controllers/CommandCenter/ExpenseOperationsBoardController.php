<?php

namespace Modules\ExpensesNew\Http\Controllers\CommandCenter;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Services\Operations\ExpenseOperationsBoardService;

class ExpenseOperationsBoardController extends Controller
{
    public function index(Request $request, ExpenseOperationsBoardService $service)
    {
        return view('expensesnew::command-center.operations-board', $service->payload($request));
    }

    public function feed(Request $request, ExpenseOperationsBoardService $service)
    {
        return response()->json($service->feed($request));
    }
}
