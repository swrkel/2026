# Graphs Module

Standalone Graphs module for the ERP. It uses the host ERP shell (`layouts.app`) only for the normal header/sidebar and keeps all Graphs CSS/JS/data logic inside this module.

## Pages

1. **Stocks & Sales Analytical** (`/graphs`)
   - Tank Storage Volume vs Current Stock vs Re-order Level
   - Fuel sales trends
   - Non-fuel sales breakdown
2. **Financial & Profitability Analytics** (`/graphs/financial-profitability`)
   - Daily Reconciliation & Cash Flow waterfall/summary
   - Profit Margin per Fuel Type (Fuel Product subcategory)
   - Debtors Ageing with clickable 0-30 / 31-60 / 61-90 / Over-90 bars and customer drill-down
3. **Operational & Loss Analytics** (`/graphs/operational-loss`)
   - Fuel Loss / Dip vs Meter Variance (Dip Stock vs System Stock)
   - Pump & Shift-wise Sales Analysis
   - Daily / Weekly / Monthly basis
4. **Customer & Payment Analytics** (`/graphs/customer-payment`)
   - Payment Method Split: Cash / Cards / Credit Sales / Online Transfers (Pie chart)
   - Pump & Shift-wise Sales Analysis (Bar chart)
   - Top Credit Customers / Fleet Analysis (Horizontal stacked bar chart)
   - Daily / Weekly / Monthly / Yearly basis
5. **Management Dashboard Structure** (`/graphs/management-dashboard`)
   - Total Sales / Today Sold Liters / Outstanding Received / Bank Deposited KPIs
   - Fuel subcategory-wise sold litres
   - Tank Stock Status gauges using Storage Volume
   - Last 30 days Daily Sales line chart
   - Credit Risk list for customers over 30 days outstanding
   - Red variance alert when absolute Dip vs System variance exceeds 100 litres

## 8021-4 data rules

- Payment Method Split reads finalized sale/settlement transaction payments only.
- Credit-sale transactions are placed in the Credit Sales slice and excluded from Cash/Card/Online Transfer collection rows to prevent later receipts from double counting the original credit sale.
- Settlement cash-deposit movements are not treated as Online Transfers because they are banking movements of cash already counted in Cash.
- Numeric/custom payment method IDs are resolved from `payment_methods` where available before classification.
- Top Credit Customers uses finalized `transactions` credit sales and groups by customer. When `transactions.fleet_id` and `fleets` are available, linked vehicle numbers are displayed as Fleet Analysis detail.
- The Pump & Shift analysis reuses the finalized meter-sale source from Operational & Loss Analytics; yearly bucketing was added without changing that source.

## Deployment

Extract the consolidated parcel into the Laravel ERP root, preserving paths, then run:

```bash
php artisan optimize:clear
```

No database migration is required for 8021 / 8021-2 / 8021-3 / 8021-4 / 8021-5.

## Isolation / safety

- No core controller, core route, sidebar Blade, global CSS or global JS is changed.
- Graphs styles are scoped to `.gr-app`.
- Existing system Date Range Picker settings are reused.
- All analytics are read-only database queries.
- Customer & Payment Analytics has its own page permission and its own data endpoints, including a separate customer-page pump/shift endpoint so disabling another Graphs page does not break this page.
