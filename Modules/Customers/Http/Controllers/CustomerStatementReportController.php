<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Exports\CustomerStatementExport;
use Modules\Customers\Services\CustomerReportService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerStatementReportController extends CustomerReportBaseController
{
    protected $reportService;

    public function __construct(CustomerReportService $reportService, CustomerPermissionService $permissionService)
    {
        parent::__construct($permissionService);
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $this->authorizeReport();

        return view('customers::reports.customer-statement', $this->reportService->statementData(
            $this->businessId(),
            $request->filled('customer_id') ? (int) $request->customer_id : null
        ));
    }

    public function export(Request $request, CustomerStatementExport $export)
    {
        $this->authorizeExport();

        $data = $this->reportService->statementData(
            $this->businessId(),
            $request->filled('customer_id') ? (int) $request->customer_id : null
        );

        return $export->downloadStatement('customer-statement-' . date('Y-m-d') . '.csv', $data['rows'] ?? []);
    }
}
