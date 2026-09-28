<?php

namespace Modules\Membership\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Membership\Services\Reports\PointsSummaryReportService;
use Modules\Membership\Utils\MembershipReportFormatUtil;
use Yajra\DataTables\Facades\DataTables;

class PointsSummaryReportController extends Controller
{
    protected $service;
    protected $format;

    public function __construct(PointsSummaryReportService $service, MembershipReportFormatUtil $format)
    {
        $this->service = $service;
        $this->format = $format;
    }

    public function data(Request $request)
    {
        $businessId = (int) Auth::user()->business_id;
        $query = $this->service->query($businessId, $request);
        $format = $this->format;

        return DataTables::of($query)
            ->addColumn('transaction_date', function ($row) use ($format) {
                return $format->date($row->transaction_date ?? $row->created_at);
            })
            ->addColumn('member_no', function ($row) {
                return optional($row->member)->member_number ?? optional($row->member)->membership_code ?? '';
            })
            ->addColumn('member_name', function ($row) {
                return optional($row->member)->member_name ?? '';
            })
            ->addColumn('points', function ($row) use ($format) {
                return '<span class="text-right">' . $format->number($row->points ?? $row->point ?? 0, 2) . '</span>';
            })
            ->addColumn('note', function ($row) {
                return $row->note ?? $row->remarks ?? '';
            })
            ->rawColumns(['points'])
            ->make(true);
    }
}
