<?php

namespace Modules\Loan\Http\Controllers;

use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Loan\Services\LoanLedgerService;

class LoanLedgerController extends Controller
{
    protected LoanLedgerService $ledgerService;

    public function __construct(LoanLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->ledgerService->ledger($filters);
        $summary = $this->ledgerService->summary($filters);
        $customers = $this->ledgerService->customers();
        $loans = $this->ledgerService->loansForDropdown();
        $business = Business::find(session('business.id') ?: session('user.business_id'));

        return view('loan::loan_ledgers.index', compact('rows', 'summary', 'customers', 'loans', 'filters', 'business'));
    }

    public function statement(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->ledgerService->ledger($filters);
        $summary = $this->ledgerService->summary($filters);
        $customers = $this->ledgerService->customers();
        $loans = $this->ledgerService->loansForDropdown();
        $business = Business::find(session('business.id') ?: session('user.business_id'));

        return view('loan::loan_ledgers.statement', compact('rows', 'summary', 'customers', 'loans', 'filters', 'business'));
    }

    public function print(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->ledgerService->ledger($filters);
        $summary = $this->ledgerService->summary($filters);
        $business = Business::find(session('business.id') ?: session('user.business_id'));

        return view('loan::loan_ledgers.print', compact('rows', 'summary', 'filters', 'business'));
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->ledgerService->ledger($filters);
        $filename = 'loan-ledger-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Transaction Date', 'System Entered Date & Time', 'Reference No', 'Description', 'Debit', 'Credit', 'Balance']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->transaction_date,
                    $row->system_date,
                    $row->reference_no,
                    $row->description,
                    number_format($row->debit, 2, '.', ''),
                    number_format($row->credit, 2, '.', ''),
                    number_format($row->balance, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    private function filters(Request $request): array
    {
        return [
            'customer_id' => $request->input('customer_id'),
            'loan_id' => $request->input('loan_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }
}
