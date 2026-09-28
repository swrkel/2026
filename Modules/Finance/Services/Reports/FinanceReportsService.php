<?php

namespace Modules\Finance\Services\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\BusinessLocation;

class FinanceReportsService
{
    public function locations(int $businessId): Collection
    {
        return BusinessLocation::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function accounts(int $businessId, $locationId = null): Collection
    {
        $locationId = $this->normalizeLocation($locationId);

        return Account::query()
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->when($locationId, function ($query) use ($locationId) {
                $query->where(function ($scope) use ($locationId) {
                    $scope->where('location_id', $locationId)
                        ->orWhereNull('location_id')
                        ->orWhere('location_id', 0);
                });
            })
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function dashboard(int $businessId, $locationId, string $fromDate, string $toDate): array
    {
        $base = $this->ledgerBase($businessId, $locationId, $fromDate, $toDate);

        $movement = (clone $base)
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) AS credit_total")
            ->selectRaw('COUNT(AT.id) AS entry_count')
            ->first();

        $cashMovement = $this->cashLedgerBase($businessId, $locationId, null, $toDate)
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE -AT.amount END), 0) AS cash_balance")
            ->first();

        $activeAccounts = Account::query()
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->when($this->normalizeLocation($locationId), function ($query, $normalizedLocation) {
                $query->where(function ($scope) use ($normalizedLocation) {
                    $scope->where('location_id', $normalizedLocation)
                        ->orWhereNull('location_id')
                        ->orWhere('location_id', 0);
                });
            })
            ->count();

        return [
            'active_accounts' => (int) $activeAccounts,
            'entry_count' => (int) ($movement->entry_count ?? 0),
            'total_debit' => round((float) ($movement->debit_total ?? 0), 4),
            'total_credit' => round((float) ($movement->credit_total ?? 0), 4),
            'cash_balance' => round((float) ($cashMovement->cash_balance ?? 0), 4),
        ];
    }

    public function accountLedger(
        int $businessId,
        int $accountId,
        $locationId,
        string $fromDate,
        string $toDate,
        int $perPage = 50
    ): array {
        $account = Account::query()
            ->where('business_id', $businessId)
            ->where('id', $accountId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $opening = $this->ledgerBase($businessId, $locationId, null, Carbon::parse($fromDate)->subDay()->format('Y-m-d'))
            ->where('AT.account_id', $accountId)
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE -AT.amount END), 0) AS balance")
            ->value('balance');

        $filtered = $this->ledgerBase($businessId, $locationId, $fromDate, $toDate)
            ->where('AT.account_id', $accountId);

        $summary = (clone $filtered)
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) AS credit_total")
            ->selectRaw('COUNT(AT.id) AS row_count')
            ->first();

        $total = (int) ($summary->row_count ?? 0);
        $page = max(1, (int) request()->query('page', 1));
        $perPage = max(10, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $rowsQuery = (clone $filtered)
            ->select([
                'AT.id',
                'AT.operation_date',
                'AT.note',
                'AT.type',
                'AT.amount',
                'AT.cheque_number',
                'AT.slip_no',
                'T.ref_no as reference',
                'L.name as location_name',
            ])
            ->orderByDesc('AT.operation_date')
            ->orderByDesc('AT.id');

        $rows = (clone $rowsQuery)
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $skippedNet = 0.0;
        if ($offset > 0) {
            $skipped = (clone $filtered)
                ->selectRaw("CASE WHEN AT.type = 'debit' THEN AT.amount ELSE -AT.amount END AS signed_amount")
                ->orderByDesc('AT.operation_date')
                ->orderByDesc('AT.id')
                ->limit($offset);

            $skippedNet = (float) DB::query()
                ->fromSub($skipped, 'finance_skipped_ledger')
                ->sum('signed_amount');
        }

        $opening = (float) $opening;
        $totalDebit = (float) ($summary->debit_total ?? 0);
        $totalCredit = (float) ($summary->credit_total ?? 0);
        $closing = $opening + $totalDebit - $totalCredit;
        $pageBalance = $closing - $skippedNet;

        foreach ($rows as $row) {
            $row->running_balance = round($pageBalance, 4);
            $pageBalance -= $row->type === 'debit' ? (float) $row->amount : -1 * (float) $row->amount;
        }

        $paginator = new LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return [
            'account' => $account,
            'rows' => $paginator,
            'opening_balance' => round($opening, 4),
            'total_debit' => round($totalDebit, 4),
            'total_credit' => round($totalCredit, 4),
            'closing_balance' => round($closing, 4),
        ];
    }

    public function dayBook(
        int $businessId,
        $locationId,
        string $fromDate,
        string $toDate,
        ?int $accountId = null,
        int $perPage = 50
    ): array {
        $query = $this->ledgerBase($businessId, $locationId, $fromDate, $toDate)
            ->when($accountId, fn ($builder) => $builder->where('AT.account_id', $accountId));

        $summary = (clone $query)
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) AS debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) AS credit_total")
            ->first();

        $rows = $query
            ->select([
                'AT.id',
                'AT.operation_date',
                'AT.note',
                'AT.type',
                'AT.amount',
                'AT.cheque_number',
                'AT.slip_no',
                'A.name as account_name',
                'A.account_number',
                'T.ref_no as reference',
                'L.name as location_name',
            ])
            ->orderByDesc('AT.operation_date')
            ->orderByDesc('AT.id')
            ->paginate(max(10, min(100, $perPage)))
            ->withQueryString();

        return [
            'rows' => $rows,
            'total_debit' => round((float) ($summary->debit_total ?? 0), 4),
            'total_credit' => round((float) ($summary->credit_total ?? 0), 4),
        ];
    }

    public function cashFlow(
        int $businessId,
        $locationId,
        string $fromDate,
        string $toDate,
        int $perPage = 31
    ): array {
        $query = $this->cashLedgerBase($businessId, $locationId, $fromDate, $toDate);

        $summary = (clone $query)
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) AS inflow_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) AS outflow_total")
            ->first();

        $daily = (clone $query)
            ->selectRaw('DATE(AT.operation_date) AS flow_date')
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) AS inflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) AS outflow")
            ->groupByRaw('DATE(AT.operation_date)')
            ->orderByDesc('flow_date')
            ->paginate(max(10, min(100, $perPage)))
            ->withQueryString();

        $opening = $this->cashLedgerBase(
            $businessId,
            $locationId,
            null,
            Carbon::parse($fromDate)->subDay()->format('Y-m-d')
        )
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE -AT.amount END), 0) AS balance")
            ->value('balance');

        $inflow = (float) ($summary->inflow_total ?? 0);
        $outflow = (float) ($summary->outflow_total ?? 0);

        return [
            'rows' => $daily,
            'opening_balance' => round((float) $opening, 4),
            'total_inflow' => round($inflow, 4),
            'total_outflow' => round($outflow, 4),
            'net_cash_flow' => round($inflow - $outflow, 4),
            'closing_balance' => round((float) $opening + $inflow - $outflow, 4),
        ];
    }

    public function normalizeDate($value, string $fallback): string
    {
        try {
            return empty($value) ? $fallback : Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    public function normalizeLocation($locationId): ?int
    {
        if ($locationId === null || $locationId === '' || $locationId === 'all' || $locationId === 0 || $locationId === '0') {
            return null;
        }

        $locationId = (int) $locationId;

        return $locationId > 0 ? $locationId : null;
    }

    private function ledgerBase(int $businessId, $locationId, ?string $fromDate, ?string $toDate)
    {
        $locationId = $this->normalizeLocation($locationId);

        return DB::table('account_transactions as AT')
            ->join('accounts as A', 'A.id', '=', 'AT.account_id')
            ->leftJoin('transactions as T', 'T.id', '=', 'AT.transaction_id')
            ->leftJoin('business_locations as L', function ($join) {
                $join->on('L.id', '=', DB::raw('COALESCE(T.location_id, A.location_id)'));
            })
            ->where('A.business_id', $businessId)
            ->whereNull('A.deleted_at')
            ->whereNull('AT.deleted_at')
            ->where(function ($query) use ($businessId) {
                $query->where('AT.business_id', $businessId)
                    ->orWhereNull('AT.business_id')
                    ->orWhere('AT.business_id', 0);
            })
            ->when($fromDate, fn ($query) => $query->whereDate('AT.operation_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('AT.operation_date', '<=', $toDate))
            /*
             |------------------------------------------------------------------
             | Location filter: a business-wide account belongs to every location.
             |------------------------------------------------------------------
             |
             | accounts.location_id is a STRING that defaults to 'all' - see
             | create_accounts_table. The previous comparison was
             |
             |     COALESCE(T.location_id, A.location_id) = ?
             |
             | so for the common case - an account marked 'all', on a transaction
             | with no location of its own - MySQL compared the string 'all'
             | against a number, cast it to 0, and the row never matched. Every
             | such row was excluded, which is why all four reports came back
             | empty.
             |
             | A row is now included when it either belongs to the chosen location
             | or belongs to no particular location. The second half is the point:
             | cash and bank accounts are held business-wide, and their entries
             | must appear in a location's report or the report shows nothing.
             */
            ->when($locationId, function ($query) use ($locationId) {
                $query->where(function ($inner) use ($locationId) {
                    $inner->whereRaw('T.location_id = ?', [$locationId])
                        ->orWhereRaw('A.location_id = ?', [(string) $locationId])
                        ->orWhere(function ($businessWide) {
                            $businessWide->whereNull('T.location_id')
                                ->where(function ($account) {
                                    $account->whereNull('A.location_id')
                                        ->orWhere('A.location_id', '')
                                        ->orWhere('A.location_id', 'all');
                                });
                        });
                });
            });
    }

    private function cashLedgerBase(int $businessId, $locationId, ?string $fromDate, ?string $toDate)
    {
        return $this->ledgerBase($businessId, $locationId, $fromDate, $toDate)
            ->leftJoin('account_groups as AG', 'AG.id', '=', 'A.asset_type')
            ->leftJoin('account_types as AType', 'AType.id', '=', 'A.account_type_id')
            ->where(function ($query) {
                $query->whereRaw("LOWER(COALESCE(AG.name, '')) LIKE '%cash%'")
                    ->orWhereRaw("LOWER(COALESCE(AG.name, '')) LIKE '%bank%'")
                    ->orWhereRaw("LOWER(COALESCE(AType.name, '')) LIKE '%cash%'")
                    ->orWhereRaw("LOWER(COALESCE(AType.name, '')) LIKE '%bank%'")
                    ->orWhereRaw("LOWER(COALESCE(A.name, '')) LIKE '%cash%'")
                    ->orWhereRaw("LOWER(COALESCE(A.name, '')) LIKE '%bank%'");
            });
    }
}
