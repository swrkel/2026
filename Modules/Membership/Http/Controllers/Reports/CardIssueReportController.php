<?php

namespace Modules\Membership\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Membership\Services\Reports\CardIssueReportService;
use Modules\Membership\Utils\MembershipReportFormatUtil;
use Yajra\DataTables\Facades\DataTables;

class CardIssueReportController extends Controller
{
    protected $service;
    protected $format;

    public function __construct(CardIssueReportService $service, MembershipReportFormatUtil $format)
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
            ->addColumn('issue_date', function ($row) use ($format) {
                return $format->date($row->card_issue_date ?? $row->created_at);
            })
            ->addColumn('member_no', function ($row) {
                return $row->member_number ?? $row->membership_code ?? '';
            })
            ->addColumn('member_name', function ($row) {
                return $row->member_name ?? '';
            })
            ->addColumn('card_number', function ($row) {
                return $row->card_number ?? '';
            })
            ->make(true);
    }
}
