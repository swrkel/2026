<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Customers\Exports\CustomerCsvExport;
use Modules\Customers\Services\CustomerPaymentReportService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerPaymentReportController extends CustomerReportBaseController
{
    protected $paymentReportService;

    public function __construct(CustomerPaymentReportService $paymentReportService, CustomerPermissionService $permissionService)
    {
        parent::__construct($permissionService);
        $this->paymentReportService = $paymentReportService;
    }

    public function index(Request $request)
    {
        $this->authorizeReport();

        return view(
            'customers::reports.payments.index',
            $this->paymentReportService->data($this->businessId(), 500, $this->filters($request))
        );
    }

    public function export(Request $request, CustomerCsvExport $export)
    {
        $this->authorizeExport();

        $data = $this->paymentReportService->data($this->businessId(), 5000, $this->filters($request));
        $rows = [];

        foreach (($data['rows'] ?? []) as $row) {
            $rows[] = [
                !empty($row->paid_on) ? date('Y-m-d', strtotime($row->paid_on)) : '',
                $row->customer_name ?? '',
                $row->customer_code ?? '',
                $row->payment_ref_no ?: ('PAY-' . $row->id),
                $row->invoice_no ?? '',
                ucwords(str_replace('_', ' ', (string) $row->method)),
                number_format((float) $row->amount, 2, '.', ''),
                $row->note ?? '',
            ];
        }

        return $export->download('customer-payments-' . date('Y-m-d') . '.csv', [
            'Date', 'Customer', 'Customer Code', 'Payment Reference', 'Invoice', 'Method', 'Amount', 'Note'
        ], $rows);
    }

    private function filters(Request $request): array
    {
        return [
            'customer_id' => $request->input('customer_id'),
            'start_date' => $request->input('payment_start_date'),
            'end_date' => $request->input('payment_end_date'),
        ];
    }
}
