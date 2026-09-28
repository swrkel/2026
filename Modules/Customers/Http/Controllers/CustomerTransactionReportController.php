<?php

namespace Modules\Customers\Http\Controllers;

use Modules\Customers\Exports\CustomerCsvExport;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerTransactionReportService;

class CustomerTransactionReportController extends CustomerReportBaseController
{
    protected $transactionReportService;

    public function __construct(CustomerTransactionReportService $transactionReportService, CustomerPermissionService $permissionService)
    {
        parent::__construct($permissionService);
        $this->transactionReportService = $transactionReportService;
    }

    public function index()
    {
        $this->authorizeReport();

        return view('customers::reports.transactions.index', $this->transactionReportService->data($this->businessId()));
    }

    public function export(CustomerCsvExport $export)
    {
        $this->authorizeExport();

        $data = $this->transactionReportService->data($this->businessId());
        $rows = [];

        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                !empty($row->transaction_date) ? date('Y-m-d', strtotime($row->transaction_date)) : '',
                $row->customer_name ?? '',
                $row->customer_code ?? '',
                $row->invoice_no ?: $row->ref_no,
                ucwords(str_replace('_', ' ', (string) $row->type)),
                ucwords(str_replace('_', ' ', (string) $row->payment_status)),
                number_format((float) $row->final_total, 2, '.', ''),
            ];
        }

        return $export->download('customer-transactions-' . date('Y-m-d') . '.csv', [
            'Date', 'Customer', 'Customer Code', 'Reference', 'Type', 'Payment Status', 'Amount'
        ], $rows);
    }
}
