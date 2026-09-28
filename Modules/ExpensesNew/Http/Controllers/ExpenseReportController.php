<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ExpensesNew\Services\Reports\ExpenseReportRegistryService;
use Modules\ExpensesNew\Services\Reports\ExpenseReportExportService;

class ExpenseReportController extends Controller
{
    protected $registry;
    protected $exporter;

    public function __construct(ExpenseReportRegistryService $registry, ExpenseReportExportService $exporter)
    {
        $this->registry = $registry;
        $this->exporter = $exporter;
    }

    public function index(Request $request)
    {
        $reports = $this->registry->all($request->user());
        return view('expensesnew::reports.index', compact('reports'));
    }

    public function show(Request $request, $code)
    {
        $report = $this->registry->find($code);
        $data = $this->registry->run($code, $request->all());
        return view('expensesnew::reports.show', compact('report', 'data', 'code'));
    }

    public function data(Request $request, $code)
    {
        return response()->json($this->registry->datatable($code, $request->all()));
    }

    public function export(Request $request, $code, $format)
    {
        return $this->exporter->export($code, $format, $request->all());
    }
}
