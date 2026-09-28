<?php

namespace Modules\Finance\Http\Controllers\Financial;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Services\Accounts\FinanceAccountBalanceService;
use Modules\Finance\Utils\FinancePermissionHelper;

class BalanceSheetController extends Controller
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
        $toDate = $this->safeDate($request->input('to_date') ?: $request->input('end_date'));

        try {
            $assets = $this->statementSection(
                $businessId,
                ['Assets', 'Asset', 'Current Assets', 'Fixed Assets', 'Fixed Asset'],
                'debit',
                null,
                $toDate,
                $selectedLocation
            );
            $liabilities = $this->statementSection(
                $businessId,
                ['Liabilities', 'Liability', 'Current Liabilities', 'Long term Liabilities', 'Long Term Liabilities'],
                'credit',
                null,
                $toDate,
                $selectedLocation
            );
            $equity = $this->statementSection(
                $businessId,
                ['Equity'],
                'credit',
                null,
                $toDate,
                $selectedLocation
            );

            $financialYearStart = $this->financialYearStart($request, $toDate);
            $income = $this->statementSection(
                $businessId,
                ['Income'],
                'credit',
                $financialYearStart,
                $toDate,
                $selectedLocation
            );
            $expenses = $this->statementSection(
                $businessId,
                ['Expenses', 'Expense'],
                'debit',
                $financialYearStart,
                $toDate,
                $selectedLocation
            );
            $currentEarnings = round((float) $income->sum('balance') - (float) $expenses->sum('balance'), 4);

            if (abs($currentEarnings) > 0.00005) {
                $equity->push((object) [
                    'id' => 0,
                    'name' => 'Current Financial Year Earnings',
                    'account_number' => null,
                    'location_id' => null,
                    'location_name' => null,
                    'balance' => $currentEarnings,
                ]);
            }

            $totalAssets = round((float) $assets->sum('balance'), 4);
            $totalLiabilities = round((float) $liabilities->sum('balance'), 4);
            $totalEquity = round((float) $equity->sum('balance'), 4);
            $balanceDifference = round($totalAssets - ($totalLiabilities + $totalEquity), 4);

            return view('finance::account_reports.balance_sheet', [
                'business_locations' => $businessLocations,
                'selected_location' => $selectedLocation,
                'to_date' => $toDate,
                'financial_year_start' => $financialYearStart,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'balance_difference' => $balanceDifference,
                'account_access' => $accountAccess,
            ]);
        } catch (\Throwable $e) {
            Log::error('Finance balance sheet failed', [
                'business_id' => $businessId,
                'location_id' => $selectedLocation,
                'to_date' => $toDate,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return view('finance::account_reports.balance_sheet', [
                'business_locations' => $businessLocations,
                'selected_location' => $selectedLocation,
                'to_date' => $toDate,
                'financial_year_start' => $this->financialYearStart($request, $toDate),
                'assets' => collect(),
                'liabilities' => collect(),
                'equity' => collect(),
                'total_assets' => 0.0,
                'total_liabilities' => 0.0,
                'total_equity' => 0.0,
                'balance_difference' => 0.0,
                'account_access' => $accountAccess,
                'load_error' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function statementSection(
        int $businessId,
        array $typeNames,
        string $normalSide,
        ?string $fromDate,
        string $toDate,
        $locationId
    ) {
        $typeIds = [];
        foreach ($typeNames as $typeName) {
            $typeIds = array_merge($typeIds, $this->balanceService->getAccountTypeIds($businessId, $typeName));
        }

        return $this->balanceService->getStatementAccounts(
            $businessId,
            array_values(array_unique(array_map('intval', $typeIds))),
            $normalSide,
            $fromDate,
            $toDate,
            $locationId
        );
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

    private function safeDate($value): string
    {
        if (empty($value)) {
            return now()->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return now()->format('Y-m-d');
        }
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }
}
