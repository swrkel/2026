<?php

namespace Modules\Finance\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Services\Reports\FinanceReportsService;

class FinanceReportsController extends Controller
{
    public function __construct(private FinanceReportsService $reports)
    {
    }

    public function dashboard(Request $request)
    {
        $businessId = $this->businessId($request);
        $locationId = $this->locationId($request);
        [$fromDate, $toDate] = $this->dateRange($request);

        return view('finance::finance_reports.dashboard')->with([
            'locations' => $this->reports->locations($businessId),
            'location_id' => $locationId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'metrics' => $this->reports->dashboard($businessId, $locationId, $fromDate, $toDate),
        ]);
    }

    public function accountLedger(Request $request, ?int $accountId = null)
    {
        $businessId = $this->businessId($request);
        $locationId = $this->locationId($request);
        [$fromDate, $toDate] = $this->dateRange($request);
        $accountId = $accountId ?: (int) $request->query('account_id', 0);

        $payload = [
            'locations' => $this->reports->locations($businessId),
            'accounts' => $this->reports->accounts($businessId, $locationId),
            'location_id' => $locationId,
            'account_id' => $accountId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'ledger' => null,
        ];

        if ($accountId > 0) {
            $payload['ledger'] = $this->reports->accountLedger(
                $businessId,
                $accountId,
                $locationId,
                $fromDate,
                $toDate,
                (int) $request->query('per_page', 50)
            );
        }

        return view('finance::finance_reports.account_ledger')->with($payload);
    }

    public function dayBook(Request $request)
    {
        $businessId = $this->businessId($request);
        $locationId = $this->locationId($request);
        [$fromDate, $toDate] = $this->dateRange($request);
        $accountId = (int) $request->query('account_id', 0);

        return view('finance::finance_reports.day_book')->with([
            'locations' => $this->reports->locations($businessId),
            'accounts' => $this->reports->accounts($businessId, $locationId),
            'location_id' => $locationId,
            'account_id' => $accountId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report' => $this->reports->dayBook(
                $businessId,
                $locationId,
                $fromDate,
                $toDate,
                $accountId > 0 ? $accountId : null,
                (int) $request->query('per_page', 50)
            ),
        ]);
    }

    public function cashFlowStatement(Request $request)
    {
        $businessId = $this->businessId($request);
        $locationId = $this->locationId($request);
        [$fromDate, $toDate] = $this->dateRange($request);

        return view('finance::finance_reports.cash_flow_statement')->with([
            'locations' => $this->reports->locations($businessId),
            'location_id' => $locationId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'report' => $this->reports->cashFlow(
                $businessId,
                $locationId,
                $fromDate,
                $toDate,
                (int) $request->query('per_page', 31)
            ),
        ]);
    }

    /**
     * Compatibility handler for older sidebar URLs that used underscores or
     * singular/plural route labels. It keeps every Finance Reports menu item
     * inside the Finance module and avoids 404 pages after an upgrade.
     */
    public function legacyPage(Request $request, string $report)
    {
        $normalized = str_replace('_', '-', strtolower(trim($report)));

        $route = match ($normalized) {
            'dashboard', 'home', 'index' => 'finance.reports.dashboard',
            'trial-balance', 'trial-balance-report' => 'finance.reports.trial_balance',
            'general-ledger' => 'finance.reports.general_ledger',
            'account-ledger' => 'finance.reports.account_ledger',
            'day-book', 'daybook' => 'finance.reports.day_book',
            'income-statement' => 'finance.reports.income_statement',
            'balance-sheet' => 'finance.reports.balance_sheet',
            'profit-loss', 'profit-and-loss', 'p-and-l', 'p-l' => 'finance.reports.profit_loss',
            'cash-flow', 'cash-flow-statement' => 'finance.reports.cash_flow_statement',
            default => 'finance.reports.dashboard',
        };

        return redirect()->route($route, $request->query());
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }

    private function locationId(Request $request)
    {
        $locationId = $request->query('location_id');

        /*
         * No location chosen means ALL locations.
         *
         * This used to fall back to session('business.location_id'), so a report
         * silently filtered by whatever location the session happened to hold
         * even though the operator had not picked one - and the filter panel
         * still showed "All". Combined with the location comparison in
         * FinanceReportsService (see the note there), that returned no rows at
         * all on every finance report.
         *
         * A report should show everything until the operator narrows it. When
         * they do choose a location the query honours it, including the
         * business-wide accounts that belong to every location.
         */
        if ($locationId === null || $locationId === '') {
            return 'all';
        }

        return $this->reports->normalizeLocation($locationId) ?: 'all';
    }

    private function dateRange(Request $request): array
    {
        $fromDate = $this->reports->normalizeDate(
            $request->query('from_date') ?: $request->query('start_date'),
            now()->startOfMonth()->format('Y-m-d')
        );
        $toDate = $this->reports->normalizeDate(
            $request->query('to_date') ?: $request->query('end_date'),
            now()->format('Y-m-d')
        );

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }
}
