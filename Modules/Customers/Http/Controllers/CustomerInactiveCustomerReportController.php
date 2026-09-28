<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Exports\CustomerExport;
use Modules\Customers\Services\CustomerReportService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerInactiveCustomerReportController extends CustomerReportBaseController
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

        return view('customers::reports.inactive-customers', $this->reportService->inactiveCustomersData($this->businessId()));
    }

    public function export(CustomerExport $export)
    {
        $this->authorizeExport();

        $data = $this->reportService->inactiveCustomersData($this->businessId());

        return $export->inactiveCustomers('inactive-customers-' . date('Y-m-d') . '.csv', $data['customers'] ?? []);
    }
}
