<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Models\LeaseContract;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaseContract::with('product')->orderBy('lease_contractd_on', 'desc');
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $lease_contracts = $query->paginate(30);
        return view('leasing::reports.index', compact('lease_contracts'));
    }

    public function export(Request $request)
    {
        $filename = 'leasing_report_' . date('Ymd_His') . '.csv';
        $lease_contracts = LeaseContract::with('product')->orderBy('lease_contractd_on', 'desc')->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];
        $callback = function () use ($lease_contracts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['LeaseContract No', 'Customer', 'Product', 'LeaseContractd On', 'Due On', 'Advance', 'Outstanding', 'Status']);
            foreach ($lease_contracts as $lease_contract) {
                fputcsv($out, [$lease_contract->lease_contract_no, $lease_contract->customer_name, optional($lease_contract->product)->name, $lease_contract->lease_contractd_on, $lease_contract->due_on, $lease_contract->advance_amount, $lease_contract->outstanding_amount, $lease_contract->status]);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }
}
