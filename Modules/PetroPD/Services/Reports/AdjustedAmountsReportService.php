<?php

namespace Modules\PetroPD\Services\Reports;

use App\Business;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Services\SettlementAmountAdjustmentService;
use Yajra\DataTables\Facades\DataTables;

class AdjustedAmountsReportService
{
    public function filterOptions(int $businessId): array
    {
        $locations = collect();
        if (Schema::hasTable('business_locations')) {
            $locations = DB::table('business_locations')
                ->where('business_id', $businessId)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $pumpOperators = collect();
        if (Schema::hasTable('pump_operators')) {
            $pumpOperators = DB::table('pump_operators')
                ->when(Schema::hasColumn('pump_operators', 'business_id'), function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $settlementNumbers = DB::table('petro_pd_amount_adjustment_requests')
            ->where('business_id', $businessId)
            ->whereNotNull('settlement_no')
            ->where('settlement_no', '<>', '')
            ->distinct()
            ->orderByDesc('settlement_no')
            ->pluck('settlement_no', 'settlement_no');

        $userIds = DB::table('petro_pd_amount_adjustment_requests')
            ->where('business_id', $businessId)
            ->select('requested_by as user_id')
            ->whereNotNull('requested_by')
            ->union(
                DB::table('petro_pd_amount_adjustment_requests')
                    ->where('business_id', $businessId)
                    ->select('approved_by as user_id')
                    ->whereNotNull('approved_by')
            )
            ->union(
                DB::table('petro_pd_amount_adjustment_requests')
                    ->where('business_id', $businessId)
                    ->select('rejected_by as user_id')
                    ->whereNotNull('rejected_by')
            )
            ->union(
                DB::table('petro_pd_amount_adjustment_requests')
                    ->where('business_id', $businessId)
                    ->select('applied_by as user_id')
                    ->whereNotNull('applied_by')
            )
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $users = collect();
        if ($userIds->isNotEmpty() && Schema::hasTable('users')) {
            $users = DB::table('users')
                ->whereIn('id', $userIds->all())
                ->orderBy('first_name')
                ->get(['id', 'surname', 'first_name', 'last_name', 'username'])
                ->mapWithKeys(function ($user) {
                    return [$user->id => $this->userName($user)];
                });
        }

        $methods = collect(SettlementAmountAdjustmentService::METHODS)
            ->mapWithKeys(function (array $definition, string $key) {
                return [$key => $definition['label']];
            });

        return compact('locations', 'pumpOperators', 'settlementNumbers', 'users', 'methods');
    }

    public function dataTable(Request $request, int $businessId)
    {
        $business = Business::find($businessId);
        $precision = max(0, min(4, (int) ($business->currency_precision ?? 2)));
        $query = $this->filteredQuery($request, $businessId);
        $summary = $this->summary($request, $businessId, $precision);

        return DataTables::of($query)
            ->filter(function (Builder $query) use ($request) {
                $search = trim((string) data_get($request->input('search', []), 'value', ''));
                if ($search === '') {
                    return;
                }

                $query->where(function (Builder $where) use ($search) {
                    $like = '%' . $search . '%';
                    $where->where('r.settlement_no', 'like', $like)
                        ->orWhere('po.name', 'like', $like)
                        ->orWhere('bl.name', 'like', $like)
                        ->orWhere('po_bl.name', 'like', $like)
                        ->orWhere('i.payment_method', 'like', $like)
                        ->orWhere('i.reason', 'like', $like)
                        ->orWhere('r.status', 'like', $like)
                        ->orWhere('requester.username', 'like', $like)
                        ->orWhere('requester.first_name', 'like', $like)
                        ->orWhere('requester.last_name', 'like', $like)
                        ->orWhere('approver.username', 'like', $like)
                        ->orWhere('approver.first_name', 'like', $like)
                        ->orWhere('approver.last_name', 'like', $like)
                        ->orWhere('applier.username', 'like', $like)
                        ->orWhere('applier.first_name', 'like', $like)
                        ->orWhere('applier.last_name', 'like', $like)
                        ->orWhere('rejecter.username', 'like', $like)
                        ->orWhere('rejecter.first_name', 'like', $like)
                        ->orWhere('rejecter.last_name', 'like', $like);
                });
            }, true)
            ->addColumn('request_reference', function ($row) {
                return 'AR-' . str_pad((string) $row->request_id, 6, '0', STR_PAD_LEFT);
            })
            ->editColumn('requested_at', function ($row) {
                return $this->formatDateTime($row->requested_at);
            })
            ->editColumn('settlement_date', function ($row) {
                return $this->formatDate($row->settlement_date);
            })
            ->editColumn('processed_at', function ($row) {
                return $this->formatDateTime($row->processed_at);
            })
            ->editColumn('shift_numbers', function ($row) {
                if (! empty($row->shift_numbers)) {
                    return e($row->shift_numbers);
                }

                $shiftIds = json_decode((string) $row->shift_ids, true);
                if (! is_array($shiftIds)) {
                    return '-';
                }

                $shiftIds = collect($shiftIds)->filter()->unique()->values()->all();
                return empty($shiftIds) ? '-' : e(implode(', ', $shiftIds));
            })
            ->editColumn('payment_method', function ($row) {
                return e(SettlementAmountAdjustmentService::METHODS[$row->payment_method]['label'] ?? ucwords(str_replace('_', ' ', $row->payment_method)));
            })
            ->editColumn('current_amount', function ($row) use ($precision) {
                return $this->moneyCell((float) $row->current_amount, $precision);
            })
            ->editColumn('requested_amount', function ($row) use ($precision) {
                return $this->moneyCell((float) $row->requested_amount, $precision);
            })
            ->editColumn('applied_amount', function ($row) use ($precision) {
                if ($row->applied_amount === null) {
                    return '<span class="text-muted">-</span>';
                }

                return $this->moneyCell((float) $row->applied_amount, $precision);
            })
            ->editColumn('difference_amount', function ($row) use ($precision) {
                $amount = (float) $row->difference_amount;
                $class = $amount > 0 ? 'ppd-difference-up' : ($amount < 0 ? 'ppd-difference-down' : 'ppd-difference-zero');
                $prefix = $amount > 0 ? '+' : '';
                return '<span class="' . $class . '" data-orig-value="' . $amount . '">' . $prefix . number_format($amount, $precision) . '</span>';
            })
            ->editColumn('status', function ($row) {
                $status = strtolower((string) $row->status);
                $class = [
                    'applied' => 'success',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    'superseded' => 'muted',
                ][$status] ?? 'info';

                return '<span class="ppd-status-badge ppd-status-' . $class . '">' . e(ucfirst($status)) . '</span>';
            })
            ->editColumn('reason', function ($row) {
                $reason = trim((string) $row->reason);
                return $reason === '' ? '-' : nl2br(e($reason));
            })
            ->editColumn('rejection_reason', function ($row) {
                $reason = trim((string) $row->rejection_reason);
                return $reason === '' ? '-' : nl2br(e($reason));
            })
            ->rawColumns([
                'current_amount',
                'requested_amount',
                'applied_amount',
                'difference_amount',
                'status',
                'reason',
                'rejection_reason',
            ])
            ->with(['summary' => $summary])
            ->make(true);
    }

    public function summary(Request $request, int $businessId, int $precision): array
    {
        $query = $this->filteredQuery($request, $businessId, true);
        $row = DB::query()
            ->fromSub($query, 'adjusted_amounts')
            ->selectRaw('COUNT(DISTINCT request_id) as request_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN status = 'pending' THEN request_id END) as pending_requests")
            ->selectRaw("COUNT(DISTINCT CASE WHEN status = 'rejected' THEN request_id END) as rejected_requests")
            ->selectRaw("SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END) as applied_items")
            ->selectRaw("SUM(CASE WHEN status = 'applied' AND difference_amount > 0 THEN difference_amount ELSE 0 END) as total_increase")
            ->selectRaw("SUM(CASE WHEN status = 'applied' AND difference_amount < 0 THEN ABS(difference_amount) ELSE 0 END) as total_decrease")
            ->selectRaw("SUM(CASE WHEN status = 'applied' THEN difference_amount ELSE 0 END) as net_change")
            ->selectRaw('SUM(current_amount) as current_total')
            ->selectRaw('SUM(requested_amount) as requested_total')
            ->selectRaw("SUM(CASE WHEN applied_amount IS NULL THEN 0 ELSE applied_amount END) as applied_total")
            ->first();

        return [
            'request_count' => (int) ($row->request_count ?? 0),
            'pending_requests' => (int) ($row->pending_requests ?? 0),
            'rejected_requests' => (int) ($row->rejected_requests ?? 0),
            'applied_items' => (int) ($row->applied_items ?? 0),
            'total_increase' => round((float) ($row->total_increase ?? 0), $precision),
            'total_decrease' => round((float) ($row->total_decrease ?? 0), $precision),
            'net_change' => round((float) ($row->net_change ?? 0), $precision),
            'current_total' => round((float) ($row->current_total ?? 0), $precision),
            'requested_total' => round((float) ($row->requested_total ?? 0), $precision),
            'applied_total' => round((float) ($row->applied_total ?? 0), $precision),
            'precision' => $precision,
        ];
    }

    private function filteredQuery(Request $request, int $businessId, bool $forSummary = false): Builder
    {
        $shiftMap = DB::table('pump_operator_assignments')
            ->select([
                'business_id',
                'settlement_id',
                DB::raw('GROUP_CONCAT(DISTINCT shift_number ORDER BY CAST(shift_number AS UNSIGNED) SEPARATOR ", ") as shift_numbers'),
            ])
            ->whereNotNull('settlement_id')
            ->groupBy('business_id', 'settlement_id');

        $processedName = "TRIM(CONCAT_WS(' ', NULLIF(applier.surname, ''), NULLIF(applier.first_name, ''), NULLIF(applier.last_name, '')))";
        $approvedName = "TRIM(CONCAT_WS(' ', NULLIF(approver.surname, ''), NULLIF(approver.first_name, ''), NULLIF(approver.last_name, '')))";
        $rejectedName = "TRIM(CONCAT_WS(' ', NULLIF(rejecter.surname, ''), NULLIF(rejecter.first_name, ''), NULLIF(rejecter.last_name, '')))";
        $requesterName = "TRIM(CONCAT_WS(' ', NULLIF(requester.surname, ''), NULLIF(requester.first_name, ''), NULLIF(requester.last_name, '')))";

        $query = DB::table('petro_pd_amount_adjustment_requests as r')
            ->join('petro_pd_amount_adjustment_items as i', 'i.adjustment_request_id', '=', 'r.id')
            ->leftJoin('settlements as s', function ($join) {
                $join->on('s.id', '=', 'r.settlement_id')
                    ->on('s.business_id', '=', 'r.business_id');
            })
            ->leftJoin('pump_operators as po', 'po.id', '=', 'r.pump_operator_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 's.location_id')
            ->leftJoin('business_locations as po_bl', 'po_bl.id', '=', 'po.location_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'r.requested_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'r.approved_by')
            ->leftJoin('users as rejecter', 'rejecter.id', '=', 'r.rejected_by')
            ->leftJoin('users as applier', 'applier.id', '=', 'r.applied_by')
            ->leftJoinSub($shiftMap, 'shift_map', function ($join) {
                $join->on('shift_map.business_id', '=', 'r.business_id')
                    ->on('shift_map.settlement_id', '=', 'r.settlement_id');
            })
            ->where('r.business_id', $businessId)
            ->select([
                'r.id as request_id',
                'r.settlement_id',
                'r.settlement_no',
                'r.shift_ids',
                'r.status',
                'r.requested_at',
                'r.approved_at',
                'r.rejected_at',
                'r.applied_at',
                'r.rejection_reason',
                's.transaction_date as settlement_date',
                's.location_id',
                DB::raw("COALESCE(bl.name, po_bl.name, '-') as location_name"),
                'r.pump_operator_id',
                'po.name as pump_operator_name',
                'shift_map.shift_numbers',
                'i.id as item_id',
                'i.payment_method',
                'i.current_amount',
                'i.requested_amount',
                'i.applied_amount',
                'i.reason',
                DB::raw("COALESCE(NULLIF({$requesterName}, ''), requester.username, '-') as requested_by_name"),
                DB::raw("COALESCE(NULLIF({$processedName}, ''), applier.username, NULLIF({$approvedName}, ''), approver.username, NULLIF({$rejectedName}, ''), rejecter.username, '-') as processed_by_name"),
                DB::raw("COALESCE(r.applied_at, r.approved_at, r.rejected_at) as processed_at"),
                DB::raw("CASE WHEN r.status = 'applied' THEN COALESCE(i.applied_amount, i.requested_amount) - i.current_amount ELSE i.requested_amount - i.current_amount END as difference_amount"),
            ]);

        $dateBasis = $request->input('date_basis') === 'applied' ? 'r.applied_at' : 'r.requested_at';
        if ($request->filled('start_date')) {
            $query->whereDate($dateBasis, '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate($dateBasis, '<=', $request->input('end_date'));
        }
        if ($request->filled('location_id')) {
            $locationId = (int) $request->input('location_id');
            $query->where(function (Builder $where) use ($locationId) {
                $where->where('s.location_id', $locationId)
                    ->orWhere('po.location_id', $locationId);
            });
        }
        if ($request->filled('pump_operator_id')) {
            $query->where('r.pump_operator_id', (int) $request->input('pump_operator_id'));
        }
        if ($request->filled('settlement_no')) {
            $query->where('r.settlement_no', $request->input('settlement_no'));
        }
        if ($request->filled('payment_method')) {
            $query->where('i.payment_method', $request->input('payment_method'));
        }
        if ($request->filled('status')) {
            $query->where('r.status', $request->input('status'));
        }
        if ($request->filled('requested_by')) {
            $query->where('r.requested_by', (int) $request->input('requested_by'));
        }
        if ($request->filled('processed_by')) {
            $processedBy = (int) $request->input('processed_by');
            $query->where(function (Builder $where) use ($processedBy) {
                $where->where('r.applied_by', $processedBy)
                    ->orWhere('r.approved_by', $processedBy)
                    ->orWhere('r.rejected_by', $processedBy);
            });
        }
        if ($request->filled('amount_direction')) {
            $direction = $request->input('amount_direction');
            $differenceSql = "CASE WHEN r.status = 'applied' THEN COALESCE(i.applied_amount, i.requested_amount) - i.current_amount ELSE i.requested_amount - i.current_amount END";
            if ($direction === 'increase') {
                $query->whereRaw("{$differenceSql} > 0");
            } elseif ($direction === 'decrease') {
                $query->whereRaw("{$differenceSql} < 0");
            } elseif ($direction === 'no_change') {
                $query->whereRaw("ABS({$differenceSql}) < 0.00001");
            }
        }

        if (! $forSummary) {
            $query->orderByDesc('r.requested_at')->orderByDesc('r.id')->orderBy('i.id');
        }

        return $query;
    }

    private function userName(object $user): string
    {
        $name = trim(implode(' ', array_filter([
            $user->surname ?? null,
            $user->first_name ?? null,
            $user->last_name ?? null,
        ])));

        return $name !== '' ? $name : (string) ($user->username ?? ('User #' . $user->id));
    }

    private function moneyCell(float $amount, int $precision): string
    {
        return '<span class="ppd-money" data-orig-value="' . $amount . '">' . number_format($amount, $precision) . '</span>';
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return e((string) $value);
        }
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return e((string) $value);
        }
    }
}
