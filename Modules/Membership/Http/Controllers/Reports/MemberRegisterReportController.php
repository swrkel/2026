<?php

namespace Modules\Membership\Http\Controllers\Reports;

use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Membership\Entities\MembershipSetting;
use Modules\Membership\Entities\MembershipStatus;
use Modules\Membership\Services\Reports\MemberRegisterReportService;
use Modules\Membership\Utils\MembershipReportFormatUtil;
use Yajra\DataTables\Facades\DataTables;

class MemberRegisterReportController extends Controller
{
    protected $service;
    protected $format;

    public function __construct(MemberRegisterReportService $service, MembershipReportFormatUtil $format)
    {
        $this->service = $service;
        $this->format = $format;
    }

    public function index()
    {
        $businessId = (int) Auth::user()->business_id;
        $regions = MembershipSetting::where('business_id', $businessId)->pluck('region', 'id');
        $statuses = MembershipStatus::where('business_id', $businessId)->pluck('status_name', 'id');
        $businessLocations = BusinessLocation::forDropdown($businessId);

        return view('membership::reports.index', compact('regions', 'statuses', 'businessLocations'));
    }

    public function data(Request $request)
    {
        $businessId = (int) Auth::user()->business_id;
        $query = $this->service->query($businessId, $request);
        $format = $this->format;

        return DataTables::of($query)
            ->addColumn('member_no', function ($row) {
                return $row->member_number ?? $row->membership_code ?? '';
            })
            ->addColumn('member_name', function ($row) {
                return $row->member_name ?? '';
            })
            ->addColumn('region', function ($row) {
                return optional($row->membershipSetting)->region ?? '';
            })
            ->addColumn('membership_type', function ($row) {
                return optional($row->membershipType)->membership_type ?? '';
            })
            ->addColumn('status', function ($row) {
                return optional($row->membershipStatus)->status_name ?? '';
            })
            ->addColumn('date_joined', function ($row) use ($format) {
                return $format->date($row->date_joined ?? $row->created_at);
            })
            ->addColumn('share_value', function ($row) use ($format, $businessId) {
                return '<span class="display_currency text-right" data-currency_symbol="false">' . $format->amount($row->total_share_value ?? 0, $businessId) . '</span>';
            })
            ->rawColumns(['share_value'])
            ->make(true);
    }
}
