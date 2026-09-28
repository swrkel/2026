<?php

namespace Modules\BeautySaloons\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Reports\BeautyReportFilterService;
use Modules\BeautySaloons\Services\Reports\BeautyReportSummaryService;

class BeautyReportsController extends Controller
{
    public function __construct(
        protected BeautyReportFilterService $filters,
        protected BeautyReportSummaryService $summary
    ) {}

    public function dashboard(Request $request)
    {
        $filters = $this->filters->filters($request);
        $summary = $this->summary->dashboard($filters);
        return view('beautysaloons::reports.bi.dashboard', compact('filters', 'summary'));
    }

    public function appointments(Request $request)
    {
        return $this->table($request, 'bs_appointments', 'appointment_date', 'Appointment Register');
    }

    public function sales(Request $request)
    {
        return $this->table($request, 'bs_sales', 'sale_date', 'Service Sales');
    }

    public function retailSales(Request $request)
    {
        return $this->table($request, 'beauty_salon_retail_sales', 'sale_date', 'Product Sales');
    }

    public function customers(Request $request)
    {
        return $this->table($request, 'bs_customers', 'created_at', 'Customer Register');
    }

    public function staff(Request $request)
    {
        return $this->table($request, 'bs_staff', 'created_at', 'Staff Register');
    }

    public function commissions(Request $request)
    {
        return $this->table($request, 'beauty_staff_commissions', 'created_at', 'Staff Commission Report');
    }

    public function inventory(Request $request)
    {
        return $this->table($request, 'beauty_salon_stock_movements', 'transaction_date', 'Stock Movement Report');
    }

    public function memberships(Request $request)
    {
        return $this->table($request, 'bs_package_sales', 'created_at', 'Membership & Package Sales');
    }

    public function vouchers(Request $request)
    {
        return $this->table($request, 'bs_gift_vouchers', 'created_at', 'Gift Voucher Report');
    }

    public function loyalty(Request $request)
    {
        return $this->table($request, 'bs_loyalty_transactions', 'created_at', 'Loyalty Transactions');
    }

    public function payments(Request $request)
    {
        return $this->table($request, 'bs_payments', 'paid_on', 'Payment Collection Report');
    }

    protected function table(Request $request, string $table, string $dateColumn, string $title)
    {
        $filters = $this->filters->filters($request);
        $rows = $this->summary->rows($table, $filters, $dateColumn);
        return view('beautysaloons::reports.bi.table', compact('filters', 'rows', 'title', 'table'));
    }
}
