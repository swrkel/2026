<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Exports\CustomerAgingExport;
use Modules\Customers\Services\CustomerReportService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerAgeingReportController extends CustomerReportBaseController
{
    protected $reportService;

    public function __construct(CustomerReportService $reportService, CustomerPermissionService $permissionService)
    {
        parent::__construct($permissionService);
        $this->reportService = $reportService;
    }

    public function index()
    {
        $this->authorizeReport();

        return view('customers::reports.customer-aging', $this->reportService->agingData($this->businessId()));
    }

    public function export(CustomerAgingExport $export)
    {
        $this->authorizeExport();

        $data = $this->reportService->agingData($this->businessId());

        return $export->downloadAging('customer-aging-' . date('Y-m-d') . '.csv', $data['aging'] ?? []);
    }
}
