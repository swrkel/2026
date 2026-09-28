<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Services\PriceChangeContext;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct(private PriceChangeContext $context)
    {
    }

    public function history()
    {
        return view('pricechangenew::reports.history', [
            'locations' => $this->context->locations(),
            'statuses' => config('pricechangenew.statuses', []),
        ]);
    }

    public function historyData(Request $request)
    {
        $query = PriceChange::query()
            ->forBusiness($this->context->businessId())
            ->leftJoin('users as creator', 'creator.id', '=', 'pcn_price_changes.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'pcn_price_changes.approved_by')
            ->leftJoin('users as applier', 'applier.id', '=', 'pcn_price_changes.applied_by')
            ->select([
                'pcn_price_changes.id', 'pcn_price_changes.reference_no', 'pcn_price_changes.title',
                'pcn_price_changes.status', 'pcn_price_changes.application_scope', 'pcn_price_changes.effective_at',
                'pcn_price_changes.submitted_at', 'pcn_price_changes.approved_at', 'pcn_price_changes.applied_at',
                'pcn_price_changes.created_at', 'pcn_price_changes.application_attempts',
                DB::raw("TRIM(CONCAT(COALESCE(creator.first_name,''),' ',COALESCE(creator.last_name,''))) as creator_name"),
                DB::raw("TRIM(CONCAT(COALESCE(approver.first_name,''),' ',COALESCE(approver.last_name,''))) as approver_name"),
                DB::raw("TRIM(CONCAT(COALESCE(applier.first_name,''),' ',COALESCE(applier.last_name,''))) as applier_name"),
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

        if ($request->filled('status')) {
            $query->where('pcn_price_changes.status', $request->input('status'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('pcn_price_changes.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('pcn_price_changes.created_at', '<=', $request->input('end_date'));
        }
        if ($request->filled('location_id')) {
            $locationId = (int) $request->input('location_id');
            $this->context->assertLocations([$locationId]);
            $query->whereExists(function ($sub) use ($locationId): void {
                $sub->selectRaw('1')->from('pcn_price_change_scopes')
                    ->whereColumn('pcn_price_change_scopes.price_change_id', 'pcn_price_changes.id')
                    ->where('pcn_price_change_scopes.location_id', $locationId);
            });
        }

        return DataTables::of($query)
            ->editColumn('status', fn ($row): string => '<span class="pcn-status pcn-status-' . e($row->status) . '">' . e(ucwords(str_replace('_', ' ', $row->status))) . '</span>')
            ->editColumn('application_scope', fn ($row): string => $row->application_scope === 'location_price_groups'
                ? 'Location price groups' : 'Business base price')
            ->editColumn('submitted_at', fn ($row): string => $this->formatDate($row->submitted_at))
            ->editColumn('approved_at', fn ($row): string => $this->formatDate($row->approved_at))
            ->editColumn('applied_at', fn ($row): string => $this->formatDate($row->applied_at))
            ->addColumn('action', fn ($row): string => '<a class="btn btn-default btn-sm" href="' . e(route('pricechangenew.changes.show', $row->id)) . '"><i class="fa fa-eye"></i> View</a>')
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    private function formatDate($value): string
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d M Y, h:i A') : '-';
    }
}
