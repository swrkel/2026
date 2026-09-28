<?php

namespace Modules\StockTransferNew\Http\Controllers\TesterSupport;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\TesterSupport\TestCaseService;

class TestCaseController extends Controller
{
    protected TestCaseService $testCaseService;

    public function __construct(TestCaseService $testCaseService)
    {
        $this->testCaseService = $testCaseService;
    }

    public function index()
    {
        $groups = $this->testCaseService->groupedTestCases();
        return view('stocktransfernew::tester_support.test_cases', compact('groups'));
    }

    public function status(Request $request)
    {
        $request->validate([
            'test_key' => 'required|string|max:100',
            'status' => 'required|string|in:pending,passed,failed,blocked',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $this->testCaseService->saveStatus($request->only('test_key', 'status', 'remarks'));
        return redirect()->back()->with('status', 'Test case status saved.');
    }
}
