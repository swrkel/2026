<?php

namespace Modules\ExpensesNew\Http\Controllers\CommandCenter;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Services\CommandCenter\ExpenseCommandCenterService;

class ExpenseCommandCenterController extends Controller
{
    protected ExpenseCommandCenterService $service;

    public function __construct(ExpenseCommandCenterService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $data = $this->service->summary($request);
        return view('expensesnew::command-center.index', $data);
    }

    public function widgets(Request $request)
    {
        return response()->json($this->service->widgets($request));
    }
}
