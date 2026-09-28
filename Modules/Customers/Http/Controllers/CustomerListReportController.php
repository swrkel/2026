<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Exports\CustomerExport;
use Modules\Customers\Services\CustomerReportService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerListReportController extends CustomerReportBaseController
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

        return view('customers::reports.customer-list', $this->reportService->customerListData($this->businessId()));
    }

    public function export(CustomerExport $export)
    {
        $this->authorizeExport();

        $data = $this->reportService->customerListData($this->businessId());

        return $export->customerList('customers-list-' . date('Y-m-d') . '.csv', $data['customers'] ?? []);
    }
}
