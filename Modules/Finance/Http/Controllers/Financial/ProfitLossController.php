<?php

namespace Modules\Finance\Http\Controllers\Financial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Services\Accounts\FinanceAccountBalanceService;

class ProfitLossController extends Controller
{
    public function __construct(private FinanceAccountBalanceService $balanceService)
    {
    }

    public function index(Request $request)
    {
        return $this->branch($request);
    }

    public function branch(Request $request)
    {
        return $this->renderBranchStatement($request, 'Profit & Loss');
    }

    public function incomeStatement(Request $request)
    {
        return $this->renderBranchStatement($request, 'Income Statement');
    }

    public function consolidated(Request $request)
    {
        $businessId = $this->businessId($request);
        [$fromDate, $toDate] = $this->dateRange($request);

        $incomeAccounts = $this->balanceService->getStatementAccounts(
            $businessId,
            $this->balanceService->getAccountTypeIds($businessId, 'Income'),
            'credit',
            $fromDate,
            $toDate
        )->sortBy([
            ['location_id', 'asc'],
            ['name', 'asc'],
        ])->values();

        $expenseAccounts = $this->balanceService->getStatementAccounts(
            $businessId,
            $this->balanceService->getAccountTypeIds($businessId, 'Expenses'),
            'debit',
            $fromDate,
            $toDate
        )->sortBy([
            ['location_id', 'asc'],
            ['name', 'asc'],
        ])->values();

        return $this->renderProfitLoss(
            'finance::financial.profit_loss.consolidated',
            $incomeAccounts,
            $expenseAccounts,
            $fromDate,
            $toDate,
            ['report_title' => 'Consolidated Profit & Loss']
        );
    }

    private function renderBranchStatement(Request $request, string $title)
    {
        $businessId = $this->businessId($request);
        [$fromDate, $toDate] = $this->dateRange($request);
        $selectedLocationId = $request->query('location_id');

        if ($selectedLocationId === null || $selectedLocationId === '') {
            $selectedLocationId = $request->session()->get('business.location_id')
                ?: $request->session()->get('user.location_id')
                ?: 'all';
        }

        $locationFilter = $selectedLocationId === 'all' || (int) $selectedLocationId <= 0
            ? null
            : (int) $selectedLocationId;

        $locations = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');

        $incomeAccounts = $this->balanceService->getStatementAccounts(
            $businessId,
            $this->balanceService->getAccountTypeIds($businessId, 'Income'),
            'credit',
            $fromDate,
            $toDate,
            $locationFilter
        );

        $expenseAccounts = $this->balanceService->getStatementAccounts(
            $businessId,
            $this->balanceService->getAccountTypeIds($businessId, 'Expenses'),
            'debit',
            $fromDate,
            $toDate,
            $locationFilter
        );

        return $this->renderProfitLoss(
            'finance::financial.profit_loss.branch',
            $incomeAccounts,
            $expenseAccounts,
            $fromDate,
            $toDate,
            [
                'locations' => $locations,
                'selected_location_id' => $selectedLocationId,
                'report_title' => $title,
            ]
        );
    }

    private function renderProfitLoss(
        string $view,
        $incomeAccounts,
        $expenseAccounts,
        ?string $fromDate,
        ?string $toDate,
        array $extra = []
    ) {
        $totalIncome = round((float) $incomeAccounts->sum('balance'), 4);
        $totalExpense = round((float) $expenseAccounts->sum('balance'), 4);
        $netProfit = round($totalIncome - $totalExpense, 4);

        return view($view)->with(array_merge($extra, [
            'income_accounts' => $incomeAccounts,
            'expense_accounts' => $expenseAccounts,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
        ]));
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }

    private function dateRange(Request $request): array
    {
        $fromDate = $this->safeDate(
            $request->query('from_date') ?: $request->query('start_date'),
            now()->startOfMonth()->format('Y-m-d')
        );
        $toDate = $this->safeDate(
            $request->query('to_date') ?: $request->query('end_date'),
            now()->format('Y-m-d')
        );

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    private function safeDate($value, string $fallback): string
    {
        try {
            return empty($value) ? $fallback : Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
