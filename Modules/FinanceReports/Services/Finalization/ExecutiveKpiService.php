<?php

namespace Modules\FinanceReports\Services\Finalization;

use Modules\FinanceReports\Services\FinanceReportsDataService;
use Modules\FinanceReports\Services\Engine\FinanceReportContext;

class ExecutiveKpiService
{
    public function calculate(FinanceReportsDataService $dataService, int $businessId, FinanceReportContext $context): array
    {
        $locationId = $context->is_consolidated ? null : $context->location_id;
        $pl = $dataService->incomeStatement($businessId, $context->start_date, $context->end_date, $locationId);
        $bs = $dataService->balanceSheet($businessId, $context->as_at, $locationId);

        $income = (float) ($pl['totals']['income'] ?? 0);
        $revenue = (float) ($pl['totals']['revenue'] ?? $income);
        $expenses = (float) ($pl['totals']['expenses'] ?? 0);
        $assets = (float) ($bs['totals']['assets'] ?? 0);
        $liabilities = (float) ($bs['totals']['liabilities'] ?? 0);
        $equity = (float) ($bs['totals']['equity'] ?? 0);
        $currentAssets = (float) ($bs['totals']['current_assets'] ?? 0);
        $currentLiabilities = (float) ($bs['totals']['current_liabilities'] ?? 0);
        $profit = (float) ($pl['totals']['net_profit'] ?? ($income - $expenses));

        return [
            ['name' => 'Financial Health Score', 'value' => $this->score($profit, $assets, $liabilities), 'basis' => 'Profit, Assets, Liabilities'],
            ['name' => 'Liquidity Score', 'value' => $this->ratioScore($currentAssets, max(abs($currentLiabilities), 1)), 'basis' => 'Current Assets / Current Liabilities'],
            ['name' => 'Profitability Score', 'value' => $this->ratioScore($profit, max(abs($revenue), 1)), 'basis' => 'Net Profit / Revenue'],
            ['name' => 'Working Capital', 'value' => number_format($currentAssets - $currentLiabilities, 2), 'basis' => 'Current Assets - Current Liabilities'],
            ['name' => 'Revenue', 'value' => number_format($revenue, 2), 'basis' => 'Sales / operating revenue for selected period'],
            ['name' => 'Expenses', 'value' => number_format($expenses, 2), 'basis' => 'Selected period'],
            ['name' => 'Equity', 'value' => number_format($equity, 2), 'basis' => 'As at date'],
        ];
    }

    private function score(float $profit, float $assets, float $liabilities): string
    {
        $score = 50;
        if ($profit > 0) { $score += 25; }
        if ($assets >= $liabilities) { $score += 25; }
        return min(100, max(0, $score)) . '%';
    }

    private function ratioScore(float $a, float $b): string
    {
        return number_format(($a / max($b, 1)) * 100, 2) . '%';
    }
}
