<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Models\Pledge;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Pledge::with('product')->orderBy('pledged_on', 'desc');
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $pledges = $query->paginate(30);
        return view('pawning::reports.index', compact('pledges'));
    }

    public function export(Request $request)
    {
        $filename = 'pawning_report_' . date('Ymd_His') . '.csv';
        $pledges = Pledge::with('product')->orderBy('pledged_on', 'desc')->get();
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];
        $callback = function () use ($pledges) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Pledge No', 'Customer', 'Product', 'Pledged On', 'Due On', 'Advance', 'Outstanding', 'Status']);
            foreach ($pledges as $pledge) {
                fputcsv($out, [$pledge->pledge_no, $pledge->customer_name, optional($pledge->product)->name, $pledge->pledged_on, $pledge->due_on, $pledge->advance_amount, $pledge->outstanding_amount, $pledge->status]);
            }
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }
}
