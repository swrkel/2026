<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Services\CustomerReportService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerReportIndexController extends CustomerReportBaseController
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

        return view('customers::reports.index', $this->reportService->indexData($this->businessId()));
    }
}
