<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Http\Requests\StorePriceChangeRequest;
use Modules\PriceChangeNew\Http\Requests\UpdatePriceChangeRequest;
use Modules\PriceChangeNew\Services\DraftPriceChangeService;
use Modules\PriceChangeNew\Services\PriceChangeContext;
use Modules\PriceChangeNew\Services\PriceChangeSettingsService;
use Yajra\DataTables\Facades\DataTables;

class PriceChangeController extends Controller
{
    public function __construct(
        private PriceChangeContext $context,
        private DraftPriceChangeService $drafts,
        private PriceChangeSettingsService $settings
    ) {
    }

    public function index()
    {
        return view('pricechangenew::changes.index', [
            'locations' => $this->context->locations(),
            'statuses' => config('pricechangenew.statuses', []),
            'canCreateChanges' => $this->context->canAny(['pricechangenew.changes.create']),
        ]);
    }

    public function data(Request $request)
    {
        $businessId = $this->context->businessId();
        $query = PriceChange::query()
            ->forBusiness($businessId)
            ->leftJoin('users as creator', 'creator.id', '=', 'pcn_price_changes.created_by')
            ->select([
                'pcn_price_changes.id', 'pcn_price_changes.reference_no', 'pcn_price_changes.title',
                'pcn_price_changes.status', 'pcn_price_changes.stock_price_mode',
                'pcn_price_changes.application_scope', 'pcn_price_changes.effective_at',
                'pcn_price_changes.created_at', 'pcn_price_changes.failure_message',
                DB::raw("TRIM(CONCAT(COALESCE(creator.first_name, ''), ' ', COALESCE(creator.last_name, ''))) as created_by_name"),
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
            $query->where('pcn_price_changes.status', (string) $request->input('status'));
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
        if ($request->filled('start_date')) {
            $query->whereDate('pcn_price_changes.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('pcn_price_changes.created_at', '<=', $request->input('end_date'));
        }

        $permissions = [
            'edit' => $this->context->canAny(['pricechangenew.changes.edit']),
            'delete' => $this->context->canAny(['pricechangenew.changes.delete']),
            'submit' => $this->context->canAny(['pricechangenew.changes.submit']),
            'approve' => $this->context->canAny(['pricechangenew.approvals.approve']),
            'reject' => $this->context->canAny(['pricechangenew.approvals.reject']),
            'apply' => $this->context->canAny(['pricechangenew.changes.apply']),
            'cancel' => $this->context->canAny(['pricechangenew.changes.cancel']),
        ];

        return DataTables::of($query)
            ->editColumn('status', fn ($row): string => $this->statusBadge((string) $row->status))
            ->editColumn('application_scope', fn ($row): string => $row->application_scope === 'location_price_groups'
                ? 'Location price groups'
                : 'Business base price')
            ->editColumn('effective_at', fn ($row): string => $row->effective_at
                ? \Carbon\Carbon::parse($row->effective_at)->format('d M Y, h:i A') : '-')
            ->editColumn('created_at', fn ($row): string => $row->created_at
                ? \Carbon\Carbon::parse($row->created_at)->format('d M Y, h:i A') : '-')
            ->addColumn('action', fn ($row): string => $this->actionHtml($row, $permissions))
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $locations = $this->context->locations();
        $selectedLocationIds = old('location_ids', $locations->take(1)->pluck('id')->all());
        $defaultApplicationScope = (string) $this->settings->get(
            $this->context->businessId(),
            'default_application_scope',
            'business_base'
        );

        return view('pricechangenew::changes.create', compact(
            'locations', 'selectedLocationIds', 'defaultApplicationScope'
        ));
    }

    public function store(StorePriceChangeRequest $request)
    {
        $change = $this->drafts->create($request->priceChangeData());

        return redirect()->route('pricechangenew.changes.show', $change->id)
            ->with('status', 'Price change draft ' . $change->reference_no . ' was created successfully.');
    }

    public function show(int $id)
    {
        $change = $this->findForBusiness($id)->load([
            'lines.scopePrices', 'scopes', 'audits', 'applications.lines',
        ]);
        $ids = collect([
            $change->created_by, $change->submitted_by, $change->approved_by,
            $change->applied_by, $change->cancelled_by,
        ])->merge($change->audits->pluck('user_id'))->filter()->unique()->values();
        $userNames = $ids->isEmpty()
            ? []
            : DB::table('users')->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn ($user) => [(int) $user->id => trim($user->first_name . ' ' . $user->last_name)])
                ->all();

        return view('pricechangenew::changes.show', [
            'change' => $change,
            'userNames' => $userNames,
            'canEditChanges' => $this->context->canAny(['pricechangenew.changes.edit']),
            'canSubmitChanges' => $this->context->canAny(['pricechangenew.changes.submit']),
            'canApproveChanges' => $this->context->canAny(['pricechangenew.approvals.approve']),
            'canRejectChanges' => $this->context->canAny(['pricechangenew.approvals.reject']),
            'canApplyChanges' => $this->context->canAny(['pricechangenew.changes.apply']),
            'canCancelChanges' => $this->context->canAny(['pricechangenew.changes.cancel']),
        ]);
    }

    public function edit(int $id)
    {
        $change = $this->findForBusiness($id)->load(['lines', 'scopes']);
        abort_unless($change->status === 'draft', 422, 'Only draft price changes may be edited.');
        $locations = $this->context->locations();
        $selectedLocationIds = old('location_ids', $change->scopes->pluck('location_id')->all());
        $defaultApplicationScope = $change->application_scope ?: 'business_base';

        return view('pricechangenew::changes.edit', compact(
            'change', 'locations', 'selectedLocationIds', 'defaultApplicationScope'
        ));
    }

    public function update(UpdatePriceChangeRequest $request, int $id)
    {
        $change = $this->drafts->update($this->findForBusiness($id), $request->priceChangeData());

        return redirect()->route('pricechangenew.changes.show', $change->id)
            ->with('status', 'Price change draft ' . $change->reference_no . ' was updated successfully.');
    }

    public function destroy(int $id)
    {
        $change = $this->findForBusiness($id);
        $reference = $change->reference_no;
        $this->drafts->delete($change);

        return response()->json(['success' => true, 'message' => 'Draft ' . $reference . ' was deleted.']);
    }

    private function findForBusiness(int $id): PriceChange
    {
        return PriceChange::query()
            ->forBusiness($this->context->businessId())
            ->whereKey($id)
            ->firstOrFail();
    }

    /** @param array<string, bool> $permissions */
    private function actionHtml(object $row, array $permissions): string
    {
        $items = [
            '<li><a href="' . e(route('pricechangenew.changes.show', $row->id)) . '"><i class="fa fa-eye"></i> View</a></li>',
        ];
        if ($row->status === 'draft' && $permissions['edit']) {
            $items[] = '<li><a href="' . e(route('pricechangenew.changes.edit', $row->id)) . '"><i class="fa fa-edit"></i> Edit</a></li>';
        }
        if ($row->status === 'draft' && $permissions['submit']) {
            $items[] = $this->workflowItem('Submit for Approval', 'fa-paper-plane', route('pricechangenew.changes.submit', $row->id), 'submit', 'Submit this price change for approval?');
        }
        if ($row->status === 'submitted' && $permissions['approve']) {
            $items[] = $this->workflowItem('Approve', 'fa-check text-success', route('pricechangenew.changes.approve', $row->id), 'approve', 'Approve this price change?');
        }
        if ($row->status === 'submitted' && $permissions['reject']) {
            $items[] = $this->workflowItem('Reject', 'fa-times text-danger', route('pricechangenew.changes.reject', $row->id), 'reject', 'Enter the rejection reason.');
        }
        $scheduledIsDue = $row->status === 'scheduled'
            && (! $row->effective_at || ! \Carbon\Carbon::parse($row->effective_at)->isFuture());
        $canApplyNow = in_array($row->status, ['approved', 'failed', 'partial'], true) || $scheduledIsDue;
        if ($canApplyNow && $permissions['apply']) {
            $items[] = $this->workflowItem('Apply Prices', 'fa-bolt text-warning', route('pricechangenew.changes.apply', $row->id), 'apply', 'Apply this price change to live prices?');
        }
        if (in_array($row->status, ['draft', 'submitted', 'approved', 'scheduled', 'failed'], true) && $permissions['cancel']) {
            $items[] = $this->workflowItem('Cancel', 'fa-ban text-danger', route('pricechangenew.changes.cancel', $row->id), 'cancel', 'Cancel this price change?');
        }
        if ($row->status === 'draft' && $permissions['delete']) {
            $items[] = '<li><a href="#" class="pcn-delete-draft" data-url="' . e(route('pricechangenew.changes.destroy', $row->id)) . '" data-reference="' . e($row->reference_no) . '"><i class="fa fa-trash text-danger"></i> Delete Draft</a></li>';
        }

        return '<div class="btn-group"><button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right">' . implode('', $items) . '</ul></div>';
    }

    private function workflowItem(string $label, string $icon, string $url, string $action, string $message): string
    {
        return '<li><a href="#" class="pcn-workflow-action" data-url="' . e($url) . '" data-action="' . e($action) . '" data-message="' . e($message) . '"><i class="fa ' . e($icon) . '"></i> ' . e($label) . '</a></li>';
    }

    private function statusBadge(string $status): string
    {
        return '<span class="pcn-status pcn-status-' . e($status) . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
    }
}
