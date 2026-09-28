# 8021-2 — Financial & Profitability Analytics

## Added page

`/graphs/financial-profitability`

The page is linked beside **Stocks & Sales Analytical** and includes three internal analytics sections.

### 1. Daily Reconciliation & Cash Flow

- Fuel product-category sales amount from finalized fuel sell lines.
- Cash and card amounts from finalized settlement-owned collections.
- Credit sales from finalized fuel credit-sale transactions.
- Bank deposits from finalized settlement cash-deposit / SW cash-deposit collections.
- Reconciliation variation = recorded cash - expected cash, where expected cash = fuel sales - card sales - credit sales.
- Waterfall chart, verification summary and daily table.

Settlement-owned payments are deliberately used so Pumper Dashboard payment entries are not counted before settlement finalization.

### 2. Profit Margin per Fuel Type

- Fuel Product subcategory stacked bar chart.
- Finalized sale amount and sold quantity.
- Cost of sales from linked purchase lines where available.
- Historical/unlinked quantity falls back to the variation/product purchase cost.
- Realized commission income = Fuel Sales - Cost of Sales.
- Net Profit = realized commission income for this graph because the supplied requirement does not define an additional overhead allocation for fuel-product commission income.
- Margin % and commission per unit are shown for verification.

### 3. Debtors Ageing Analysis

- Outstanding finalized credit sales as at the selected Date Range end date.
- Payment and advance amounts up to the as-at date are deducted.
- Buckets: 0-30, 31-60, 61-90, Over 90 days.
- Clicking a bar automatically loads the customers in that bucket with invoice count, age range and outstanding amount.

## Existing system preserved

The page extends `layouts.app`, so the ERP sidebar/header continue normally. No host/core files are changed and every Graphs CSS selector is scoped to the module.
