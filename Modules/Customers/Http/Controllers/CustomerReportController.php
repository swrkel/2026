<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerReportService;
use Modules\Customers\Exports\CustomerExport;
use Modules\Customers\Exports\CustomerLedgerExport;
use Modules\Customers\Exports\CustomerStatementExport;
use Modules\Customers\Exports\CustomerAgingExport;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerReportController extends Controller
{
    protected $reportService;
    protected $permissionService;

    public function __construct(CustomerReportService $reportService, CustomerPermissionService $permissionService)
    {
        $this->reportService = $reportService;
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $this->permissionService->authorize('reports');

        return view('customers::reports.index', $this->reportService->indexData($this->businessId()));
    }

    public function customerList()
    {
        $this->permissionService->authorize('reports');

        return view($this->reportService->reportPages()['list'], $this->reportService->customerListData($this->businessId()));
    }

    public function customerLedger(Request $request)
    {
        $this->permissionService->authorize('reports');

        return view(
            $this->reportService->reportPages()['ledger'],
            $this->reportService->ledgerData($this->businessId(), $request->filled('customer_id') ? (int) $request->customer_id : null)
        );
    }

    public function customerStatement(Request $request)
    {
        $this->permissionService->authorize('reports');

        return view(
            $this->reportService->reportPages()['statement'],
            $this->reportService->statementData($this->businessId(), $request->filled('customer_id') ? (int) $request->customer_id : null)
        );
    }

    public function customerAging()
    {
        $this->permissionService->authorize('reports');

        return view($this->reportService->reportPages()['aging'], $this->reportService->agingData($this->businessId()));
    }

    public function inactiveCustomers()
    {
        $this->permissionService->authorize('reports');

        return view($this->reportService->reportPages()['inactive'], $this->reportService->inactiveCustomersData($this->businessId()));
    }


    public function exportCustomerList(CustomerExport $export)
    {
        $this->permissionService->authorize('export');

        $data = $this->reportService->customerListData($this->businessId());

        return $export->customerList('customers-list-' . date('Y-m-d') . '.csv', $data['customers'] ?? []);
    }

    public function exportCustomerLedger(Request $request, CustomerLedgerExport $export)
    {
        $this->permissionService->authorize('export');

        $data = $this->reportService->ledgerData(
            $this->businessId(),
            $request->filled('customer_id') ? (int) $request->customer_id : null
        );

        return $export->downloadLedger('customer-ledger-' . date('Y-m-d') . '.csv', $data['rows'] ?? []);
    }

    public function exportCustomerStatement(Request $request, CustomerStatementExport $export)
    {
        $this->permissionService->authorize('export');

        $data = $this->reportService->statementData(
            $this->businessId(),
            $request->filled('customer_id') ? (int) $request->customer_id : null
        );

        return $export->downloadStatement('customer-statement-' . date('Y-m-d') . '.csv', $data['rows'] ?? []);
    }

    public function exportCustomerAging(CustomerAgingExport $export)
    {
        $this->permissionService->authorize('export');

        $data = $this->reportService->agingData($this->businessId());

        return $export->downloadAging('customer-aging-' . date('Y-m-d') . '.csv', $data['aging'] ?? []);
    }

    public function exportInactiveCustomers(CustomerExport $export)
    {
        $this->permissionService->authorize('export');

        $data = $this->reportService->inactiveCustomersData($this->businessId());

        return $export->inactiveCustomers('inactive-customers-' . date('Y-m-d') . '.csv', $data['customers'] ?? []);
    }

    protected function businessId(): int
    {
        return (int) (request()->session()->get('business.id') ?: request()->session()->get('user.business_id'));
    }
}
