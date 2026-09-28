<?php

namespace Modules\FinanceReports\Services\Intelligence;

use Modules\FinanceReports\Services\FinanceReportsDataService;
use Modules\FinanceReports\Services\Engine\FinanceReportContext;

class FinancialIntelligenceService
{
    public function summary(FinanceReportsDataService $data, FinanceReportContext $context): array
    {
        $pl = $data->incomeStatement($context->business_id, $context->start_date, $context->end_date, $context->location_id);
        $bs = $data->balanceSheet($context->business_id, $context->as_at, $context->location_id);

        $income = (float) data_get($pl, 'totals.income', 0);
        $revenue = (float) data_get($pl, 'totals.revenue', $income);
        $otherIncome = (float) data_get($pl, 'totals.other_income', $income - $revenue);
        $expenses = (float) data_get($pl, 'totals.expenses', 0);
        $grossProfit = (float) data_get($pl, 'totals.gross_profit', $revenue);
        $profit = (float) data_get($pl, 'totals.net_profit', $income - $expenses);
        $assets = (float) data_get($bs, 'totals.assets', 0);
        $liabilities = (float) data_get($bs, 'totals.liabilities', 0);
        $equity = (float) data_get($bs, 'totals.equity', 0);
        $currentAssets = (float) data_get($bs, 'totals.current_assets', 0);
        $currentLiabilities = (float) data_get($bs, 'totals.current_liabilities', 0);
        $workingCapital = $currentAssets - $currentLiabilities;

        return [
            'mode' => $context->is_consolidated ? 'Consolidated' : 'Branch / Location',
            'period' => $context->start_date . ' to ' . $context->end_date,
            'cards' => [
                ['label' => 'Revenue', 'value' => $revenue],
                ['label' => 'Other Income', 'value' => $otherIncome],
                ['label' => 'Gross Profit', 'value' => $grossProfit],
                ['label' => 'Expenses', 'value' => $expenses],
                ['label' => 'Net Profit / Loss', 'value' => $profit],
                ['label' => 'Assets', 'value' => $assets],
                ['label' => 'Liabilities', 'value' => $liabilities],
                ['label' => 'Equity', 'value' => $equity],
                ['label' => 'Current Assets', 'value' => $currentAssets],
                ['label' => 'Current Liabilities', 'value' => $currentLiabilities],
                ['label' => 'Working Capital', 'value' => $workingCapital],
                ['label' => 'Financial Health Score', 'value' => $this->healthScore($revenue, $expenses, $profit, $assets, $liabilities, $currentAssets, $currentLiabilities)],
            ],
            'insights' => $this->insights($revenue, $expenses, $profit, $currentAssets, $currentLiabilities),
        ];
    }

    public function cfoDashboard(FinanceReportsDataService $data, FinanceReportContext $context): array
    {
        $summary = $this->summary($data, $context);
        $cards = collect($summary['cards'])->pluck('value', 'label')->all();
        $revenue = (float) ($cards['Revenue'] ?? 0);
        $expenses = (float) ($cards['Expenses'] ?? 0);
        $grossProfit = (float) ($cards['Gross Profit'] ?? 0);
        $profit = (float) ($cards['Net Profit / Loss'] ?? 0);
        $assets = (float) ($cards['Assets'] ?? 0);
        $liabilities = (float) ($cards['Liabilities'] ?? 0);
        $equity = (float) ($cards['Equity'] ?? 0);
        $currentAssets = (float) ($cards['Current Assets'] ?? 0);
        $currentLiabilities = (float) ($cards['Current Liabilities'] ?? 0);
        $workingCapital = $currentAssets - $currentLiabilities;

        return [
            'profitability' => [
                'gross_profit' => $grossProfit,
                'net_profit' => $profit,
                'net_margin' => abs($revenue) < 0.0001 ? 0 : round(($profit / $revenue) * 100, 4),
                'expense_ratio' => abs($revenue) < 0.0001 ? 0 : round(($expenses / $revenue) * 100, 4),
            ],
            'liquidity' => [
                'current_assets' => $currentAssets,
                'current_liabilities' => $currentLiabilities,
                'current_ratio' => abs($currentLiabilities) < 0.0001 ? null : round($currentAssets / $currentLiabilities, 4),
                'working_capital' => $workingCapital,
                'cash_risk' => $workingCapital < 0 ? 'High' : 'Normal',
            ],
            'solvency' => [
                'debt_ratio' => abs($assets) < 0.0001 ? 0 : round(($liabilities / $assets) * 100, 4),
                'equity_ratio' => abs($assets) < 0.0001 ? 0 : round(($equity / $assets) * 100, 4),
            ],
            'health_score' => $cards['Financial Health Score'] ?? 0,
        ];
    }

    public function scenario(FinanceReportsDataService $data, FinanceReportContext $context, float $revenueChange, float $expenseChange): array
    {
        $summary = $this->summary($data, $context);
        $cards = collect($summary['cards'])->pluck('value', 'label')->all();
        $revenue = (float) ($cards['Revenue'] ?? 0);
        $otherIncome = (float) ($cards['Other Income'] ?? 0);
        $expenses = (float) ($cards['Expenses'] ?? 0);
        $baseProfit = (float) ($cards['Net Profit / Loss'] ?? ($revenue + $otherIncome - $expenses));
        $projectedRevenue = $revenue * (1 + ($revenueChange / 100));
        $projectedExpenses = $expenses * (1 + ($expenseChange / 100));

        return [
            'base_revenue' => $revenue,
            'base_other_income' => $otherIncome,
            'base_expenses' => $expenses,
            'base_profit' => $baseProfit,
            'revenue_change_percent' => $revenueChange,
            'expense_change_percent' => $expenseChange,
            'projected_revenue' => $projectedRevenue,
            'projected_other_income' => $otherIncome,
            'projected_expenses' => $projectedExpenses,
            'projected_profit' => $projectedRevenue + $otherIncome - $projectedExpenses,
        ];
    }

    protected function insights(float $revenue, float $expenses, float $profit, float $currentAssets, float $currentLiabilities): array
    {
        $items = [];
        $items[] = $profit >= 0 ? 'The selected period is profitable.' : 'The selected period is showing a loss and needs review.';
        if (abs($revenue) > 0.0001 && ($expenses / abs($revenue)) > 0.9) {
            $items[] = 'Expenses are consuming more than 90% of sales / operating revenue.';
        }
        if (($currentAssets - $currentLiabilities) < 0) {
            $items[] = 'Working capital is negative for the selected scope.';
        }
        $items[] = 'All values are read-only and based on Branch/Location or Consolidated filter context.';
        return $items;
    }

    protected function healthScore(float $revenue, float $expenses, float $profit, float $assets, float $liabilities, float $currentAssets, float $currentLiabilities): int
    {
        $score = 50;
        if ($profit > 0) { $score += 20; }
        if (abs($revenue) > 0.0001 && ($expenses / abs($revenue)) < 0.8) { $score += 15; }
        if ($currentAssets >= $currentLiabilities) { $score += 10; }
        if ($assets >= $liabilities) { $score += 5; }
        if ($profit < 0) { $score -= 15; }
        return max(0, min(100, $score));
    }
}
