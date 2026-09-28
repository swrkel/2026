<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportRun;
use Modules\ManagementReport\Support\TenantConnection;

class SavedReportController extends Controller
{
    public function index(Request $request)
    {
        TenantConnection::activate();
        $businessId = (int) session('user.business_id');
        $query = ReportRun::where('business_id', $businessId)->latest('generated_at');
        if ($request->filled('start_date')) {
            $query->whereDate('period_start', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('period_end', '<=', $request->input('end_date'));
        }
        $status = (string) $request->input('status', '__all__');
        if (in_array($status, ['generated', 'shared', 'archived'], true)) {
            $query->where('status', $status);
        }
        $runs = $query->paginate(25)->appends($request->query());

        return view('managementreport::saved.index', compact('runs'));
    }

    public function show($run)
    {
        $runModel = $this->findRun($run);
        $runModel->load(['sections', 'shares', 'reviews']);

        return view('managementreport::saved.show', ['run' => $runModel, 'report' => $runModel->snapshot_payload]);
    }

    public function destroy($run)
    {
        $runModel = $this->findRun($run);
        $runModel->delete();

        return redirect()->route('managementreport.saved.index')->with('success', 'Saved report deleted.');
    }

    protected function findRun($run): ReportRun
    {
        TenantConnection::activate();
        $model = ReportRun::query()->findOrFail((int) $run);
        abort_unless((int) $model->business_id === (int) session('user.business_id'), 404);

        return $model;
    }
}
