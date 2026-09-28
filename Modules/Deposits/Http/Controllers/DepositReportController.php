<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Services\DepositSummaryService;

class DepositReportController extends Controller
{
    public function index(Request $request, DepositSummaryService $summaryService)
    {
        $type = $request->get('type', 'summary');
        $summary = $summaryService->dashboard();
        $accounts = $summaryService->report($type, $request->only(['location_id', 'status']));

        if ($request->get('format') === 'csv') {
            $filename = 'deposit_report_' . $type . '_' . date('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($accounts) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Account No', 'Customer', 'Product', 'Principal', 'Balance', 'Interest', 'Opened', 'Maturity', 'Status']);
                foreach ($accounts as $account) {
                    fputcsv($out, [
                        $account->account_no,
                        $account->customer_name,
                        optional($account->product)->name,
                        number_format((float) $account->principal_amount, 2, '.', ''),
                        number_format((float) $account->current_balance, 2, '.', ''),
                        number_format((float) $account->interest_accrued, 2, '.', ''),
                        $account->opened_on,
                        $account->maturity_on,
                        ucfirst($account->status),
                    ]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        $locations = $this->locations();
        return view('deposits::reports.index', compact('summary', 'accounts', 'type', 'locations'));
    }

    private function locations()
    {
        if (! Schema::hasTable('business_locations')) {
            return collect();
        }
        $query = DB::table('business_locations')->orderBy('name');
        if (session('business.id')) {
            $query->where('business_id', session('business.id'));
        }
        return $query->pluck('name', 'id');
    }
}
