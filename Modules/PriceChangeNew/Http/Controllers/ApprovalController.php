<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Services\PriceChangeContext;
use Yajra\DataTables\Facades\DataTables;

class ApprovalController extends Controller
{
    public function __construct(private PriceChangeContext $context)
    {
    }

    public function index()
    {
        return view('pricechangenew::approvals.index', [
            'locations' => $this->context->locations(),
            'canApprove' => $this->context->canAny(['pricechangenew.approvals.approve']),
            'canReject' => $this->context->canAny(['pricechangenew.approvals.reject']),
        ]);
    }

    public function data(Request $request)
    {
        $query = PriceChange::query()
            ->forBusiness($this->context->businessId())
            ->where('pcn_price_changes.status', 'submitted')
            ->leftJoin('users as submitter', 'submitter.id', '=', 'pcn_price_changes.submitted_by')
            ->select([
                'pcn_price_changes.id', 'pcn_price_changes.reference_no', 'pcn_price_changes.title',
                'pcn_price_changes.effective_at', 'pcn_price_changes.application_scope',
                'pcn_price_changes.submitted_at',
                DB::raw("TRIM(CONCAT(COALESCE(submitter.first_name, ''), ' ', COALESCE(submitter.last_name, ''))) as submitted_by_name"),
            ])
            ->selectSub(function ($sub): void {
                $sub->from('pcn_price_change_lines')->selectRaw('COUNT(*)')
                    ->whereColumn('pcn_price_change_lines.price_change_id', 'pcn_price_changes.id');
            }, 'lines_count')
            ->selectSub(function ($sub): void {
                $sub->from('pcn_price_change_scopes')
                    ->selectRaw("GROUP_CONCAT(location_name ORDER BY location_name SEPARATOR ', ')")
                    ->whereColumn('pcn_price_change_scopes.price_change_id', 'pcn_price_changes.id');
            }, 'location_names');

        if ($request->filled('location_id')) {
            $locationId = (int) $request->input('location_id');
            $this->context->assertLocations([$locationId]);
            $query->whereExists(function ($sub) use ($locationId): void {
                $sub->selectRaw('1')->from('pcn_price_change_scopes')
                    ->whereColumn('pcn_price_change_scopes.price_change_id', 'pcn_price_changes.id')
                    ->where('pcn_price_change_scopes.location_id', $locationId);
            });
        }

        $canApprove = $this->context->canAny(['pricechangenew.approvals.approve']);
        $canReject = $this->context->canAny(['pricechangenew.approvals.reject']);

        return DataTables::of($query)
            ->editColumn('effective_at', fn ($row): string => $row->effective_at
                ? \Carbon\Carbon::parse($row->effective_at)->format('d M Y, h:i A')
                : 'Immediately after approval')
            ->editColumn('submitted_at', fn ($row): string => $row->submitted_at
                ? \Carbon\Carbon::parse($row->submitted_at)->format('d M Y, h:i A') : '-')
            ->editColumn('application_scope', fn ($row): string => $row->application_scope === 'location_price_groups'
                ? 'Location price groups' : 'Business base price')
            ->addColumn('action', function ($row) use ($canApprove, $canReject): string {
                $html = '<div class="btn-group"><a href="' . e(route('pricechangenew.changes.show', $row->id)) . '" class="btn btn-default btn-sm"><i class="fa fa-eye"></i> View</a>';
                if ($canApprove) {
                    $html .= '<button type="button" class="btn btn-success btn-sm pcn-workflow-action" data-url="' . e(route('pricechangenew.changes.approve', $row->id)) . '" data-action="approve" data-message="Approve this price change?"><i class="fa fa-check"></i> Approve</button>';
                }
                if ($canReject) {
                    $html .= '<button type="button" class="btn btn-danger btn-sm pcn-workflow-action" data-url="' . e(route('pricechangenew.changes.reject', $row->id)) . '" data-action="reject" data-message="Enter the rejection reason."><i class="fa fa-times"></i> Reject</button>';
                }

                return $html . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
