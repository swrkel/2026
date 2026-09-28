<?php
namespace Modules\AutoService\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends AutoServiceBaseController
{
    public function index()
    {
        return view('autoservice::reports.index');
    }

    public function serviceDue()
    {
        $q = DB::table('auto_service_reminders');
        if ($this->businessId()) {
            $q->where('business_id', $this->businessId());
        }
        return view('autoservice::reports.service_due', ['rows' => $q->orderBy('due_date')->paginate(50)]);
    }

    public function profitability(Request $request)
    {
        $q = DB::table('auto_service_jobs as j')
            ->leftJoin('auto_service_invoices as i', 'i.job_id', '=', 'j.id')
            ->select('j.*', 'i.invoice_no', 'i.grand_total', 'i.paid_total', 'i.balance_due', 'i.accounting_status');
        if ($this->businessId()) {
            $q->where('j.business_id', $this->businessId());
        }
        $this->applyDateRange($q, $request, 'j.created_at');
        return view('autoservice::reports.profitability', ['rows' => $q->orderByDesc('j.id')->paginate(50)]);
    }

    public function dailySummary(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $start = Carbon::parse($date)->startOfDay();
        $end = Carbon::parse($date)->endOfDay();
        $businessId = $this->businessId();

        $jobs = DB::table('auto_service_jobs')->whereBetween('created_at', [$start, $end]);
        $invoices = DB::table('auto_service_invoices')->whereBetween('created_at', [$start, $end]);
        $payments = DB::table('auto_service_payments')->whereBetween('created_at', [$start, $end]);
        if ($businessId) {
            $jobs->where('business_id', $businessId);
            $invoices->where('business_id', $businessId);
            $payments->where('business_id', $businessId);
        }

        return view('autoservice::reports.daily_summary', [
            'date' => $date,
            'jobs_count' => (clone $jobs)->count(),
            'completed_count' => (clone $jobs)->whereIn('status', ['ready','delivered','completed'])->count(),
            'invoice_total' => (clone $invoices)->sum('grand_total'),
            'paid_total' => (clone $payments)->sum('amount'),
            'balance_total' => (clone $invoices)->sum('balance_due'),
            'status_rows' => (clone $jobs)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->get(),
        ]);
    }

    public function mechanicPerformance(Request $request)
    {
        $q = DB::table('auto_service_mechanics as m')
            ->leftJoin('auto_service_job_mechanics as jm', 'jm.mechanic_id', '=', 'm.id')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'jm.job_id')
            ->select('m.id', 'm.name', 'm.mobile', DB::raw('COUNT(DISTINCT j.id) as jobs_count'), DB::raw("SUM(CASE WHEN j.status IN ('ready','delivered','completed') THEN 1 ELSE 0 END) as completed_count"))
            ->groupBy('m.id', 'm.name', 'm.mobile');
        if ($this->businessId()) {
            $q->where('m.business_id', $this->businessId());
        }
        $this->applyDateRange($q, $request, 'j.created_at');
        return view('autoservice::reports.mechanic_performance', ['rows' => $q->orderByDesc('jobs_count')->paginate(50)]);
    }

    public function invoiceAging(Request $request)
    {
        $q = DB::table('auto_service_invoices as i')
            ->leftJoin('auto_service_jobs as j', 'j.id', '=', 'i.job_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->select('i.*', 'j.job_no', 'v.registration_no');
        if ($this->businessId()) {
            $q->where('i.business_id', $this->businessId());
        }
        $q->where('i.balance_due', '>', 0);
        return view('autoservice::reports.invoice_aging', ['rows' => $q->orderBy('i.invoice_date')->paginate(50)]);
    }

    public function accountingPreview($invoiceId)
    {
        $adapter = app(\Modules\AutoService\Services\AutoServiceAccountingAdapter::class);
        return view('autoservice::reports.accounting_preview', [
            'summary' => $adapter->invoiceSummary((int) $invoiceId),
            'rows' => $adapter->buildInvoicePostingPreview((int) $invoiceId),
        ]);
    }

    private function applyDateRange($q, Request $request, string $column): void
    {
        if ($request->filled('start_date')) {
            $q->where($column, '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        }
        if ($request->filled('end_date')) {
            $q->where($column, '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }
    }
}
