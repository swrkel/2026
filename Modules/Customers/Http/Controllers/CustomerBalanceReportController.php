<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Exports\CustomerCsvExport;
use Modules\Customers\Services\CustomerBalanceReportService;
use Modules\Customers\Services\CustomerPermissionService;
use App\Business;

class CustomerBalanceReportController extends CustomerReportBaseController
{
    protected $balanceReportService;

    public function __construct(CustomerBalanceReportService $balanceReportService, CustomerPermissionService $permissionService)
    {
        parent::__construct($permissionService);
        $this->balanceReportService = $balanceReportService;
    }

    public function index()
    {
        $this->authorizeReport();

        $businessId = $this->businessId();
        $data = $this->balanceReportService->data($businessId);
        $data['printBusinessName'] = (string) (Business::where('id', $businessId)->value('name') ?: config('app.name', 'Business'));
        $data['printLocationName'] = 'All Locations';

        return view('customers::reports.balance.index', $data);
    }

    public function export(CustomerCsvExport $export)
    {
        $this->authorizeExport();

        $data = $this->balanceReportService->data($this->businessId());
        $rows = [];

        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                $row->customer_code,
                $row->customer_name,
                $row->mobile,
                number_format((float) $row->credit_limit, 2, '.', ''),
                number_format((float) $row->balance, 2, '.', ''),
                number_format((float) $row->available_credit, 2, '.', ''),
                $row->status,
            ];
        }

        return $export->download('customer-balance-' . date('Y-m-d') . '.csv', [
            'Customer Code', 'Customer', 'Mobile', 'Credit Limit', 'Balance', 'Available Credit', 'Status'
        ], $rows);
    }
}
