<?php

namespace Modules\Finance\Http\Controllers\Financial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Services\Accounts\FinanceAccountBalanceService;
use Modules\Finance\Utils\FinancePermissionHelper;

class BalanceSheetComparisonController extends Controller
{
    public function __construct(private FinanceAccountBalanceService $balanceService)
    {
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId($request);
        $accountAccess = FinancePermissionHelper::can('finance.accounts.view');

        if (! $accountAccess) {
            abort(403, 'Unauthorized action.');
        }

        $businessLocations = BusinessLocation::where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');
        $selectedLocation = $this->validLocation($request->input('location_id', 'all'), $businessId);
        $dates = [
            $this->safeDate($request->input('end_date'), now()),
            $this->safeDate($request->input('end_date_2'), now()->copy()->subMonth()->endOfMonth()),
            $this->safeDate($request->input('end_date_3'), now()->copy()->subYear()->endOfYear()),
        ];

        try {
            $definitions = [
                'assets' => [
                    ['Assets', 'Asset', 'Current Assets', 'Fixed Assets', 'Fixed Asset'],
                    'debit',
                ],
                'liabilities' => [
                    ['Liabilities', 'Liability', 'Current Liabilities', 'Long term Liabilities', 'Long Term Liabilities'],
                    'credit',
                ],
                'equity' => [['Equity'], 'credit'],
            ];

            $sections = [];
            foreach ($definitions as $key => [$typeNames, $normalSide]) {
                $typeIds = $this->typeIds($businessId, $typeNames);
                $snapshots = [];
                foreach ($dates as $date) {
                    $snapshots[] = $this->balanceService->getStatementAccounts(
                        $businessId,
                        $typeIds,
                        $normalSide,
                        null,
                        $date,
                        $selectedLocation
                    );
                }
                $sections[$key] = $this->mergeSnapshots($snapshots);
            }

            $incomeTypeIds = $this->typeIds($businessId, ['Income']);
            $expenseTypeIds = $this->typeIds($businessId, ['Expenses', 'Expense']);
            $earnings = [];
            foreach ($dates as $date) {
                $financialYearStart = $this->financialYearStart($request, $date);
                $income = $this->balanceService->getStatementAccounts(
                    $businessId,
                    $incomeTypeIds,
                    'credit',
                    $financialYearStart,
                    $date,
                    $selectedLocation
                );
                $expenses = $this->balanceService->getStatementAccounts(
                    $businessId,
                    $expenseTypeIds,
                    'debit',
                    $financialYearStart,
                    $date,
                    $selectedLocation
                );
                $earnings[] = round((float) $income->sum('balance') - (float) $expenses->sum('balance'), 4);
            }

            if (collect($earnings)->contains(fn ($value) => abs((float) $value) > 0.00005)) {
                $sections['equity']->push((object) [
                    'id' => 0,
                    'name' => 'Current Financial Year Earnings',
                    'account_number' => null,
                    'balance_1' => $earnings[0] ?? 0.0,
                    'balance_2' => $earnings[1] ?? 0.0,
                    'balance_3' => $earnings[2] ?? 0.0,
                ]);
            }

            $totals = [];
            foreach ($sections as $key => $rows) {
                $totals[$key] = [
                    round((float) $rows->sum('balance_1'), 4),
                    round((float) $rows->sum('balance_2'), 4),
                    round((float) $rows->sum('balance_3'), 4),
                ];
            }

            $differences = [];
            for ($index = 0; $index < 3; $index++) {
                $differences[$index] = round(
                    ($totals['assets'][$index] ?? 0)
                    - (($totals['liabilities'][$index] ?? 0) + ($totals['equity'][$index] ?? 0)),
                    4
                );
            }

            return view('finance::account_reports.balance_sheet_comparison', compact(
                'businessLocations',
                'selectedLocation',
                'dates',
                'sections',
                'totals',
                'differences',
                'accountAccess'
            ));
        } catch (\Throwable $e) {
            Log::error('Finance balance sheet comparison failed', [
                'business_id' => $businessId,
                'location_id' => $selectedLocation,
                'dates' => $dates,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return view('finance::account_reports.balance_sheet_comparison', [
                'businessLocations' => $businessLocations,
                'selectedLocation' => $selectedLocation,
                'dates' => $dates,
                'sections' => [
                    'assets' => collect(),
                    'liabilities' => collect(),
                    'equity' => collect(),
                ],
                'totals' => [
                    'assets' => [0, 0, 0],
                    'liabilities' => [0, 0, 0],
                    'equity' => [0, 0, 0],
                ],
                'differences' => [0, 0, 0],
                'accountAccess' => $accountAccess,
                'load_error' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function typeIds(int $businessId, array $typeNames): array
    {
        $ids = [];
        foreach ($typeNames as $typeName) {
            $ids = array_merge($ids, $this->balanceService->getAccountTypeIds($businessId, $typeName));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function mergeSnapshots(array $snapshots): Collection
    {
        $rows = [];
        foreach ($snapshots as $index => $snapshot) {
            foreach ($snapshot as $account) {
                $id = (int) $account->id;
                if (! isset($rows[$id])) {
                    $rows[$id] = (object) [
                        'id' => $id,
                        'name' => $account->name,
                        'account_number' => $account->account_number,
                        'balance_1' => 0.0,
                        'balance_2' => 0.0,
                        'balance_3' => 0.0,
                    ];
                }
                $property = 'balance_' . ($index + 1);
                $rows[$id]->{$property} = round((float) $account->balance, 4);
            }
        }

        return collect($rows)->sortBy('name')->values();
    }

    private function validLocation($locationId, int $businessId)
    {
        if ($locationId === null || $locationId === '' || $locationId === 'all') {
            return 'all';
        }

        return BusinessLocation::where('business_id', $businessId)
            ->where('id', (int) $locationId)
            ->exists()
                ? (int) $locationId
                : 'all';
    }

    private function safeDate($value, Carbon $fallback): string
    {
        if (empty($value)) {
            return $fallback->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback->format('Y-m-d');
        }
    }

    private function financialYearStart(Request $request, string $toDate): string
    {
        $month = (int) $request->session()->get('business.fy_start_month', 1);
        if ($month < 1 || $month > 12) {
            $month = 1;
        }

        $end = Carbon::createFromFormat('Y-m-d', $toDate)->startOfDay();
        $year = $end->month >= $month ? $end->year : $end->year - 1;

        return Carbon::create($year, $month, 1)->format('Y-m-d');
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }
}
