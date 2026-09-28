# 8021-5 Management Dashboard Structure

Implemented as Page 5 of the standalone Graphs module.

## Dashboard contents
- Top KPI cards: Total Sales, Today Sold Liters, Outstanding Received, Bank Deposited.
- Today Sold Liters includes a fuel-product subcategory breakdown.
- Tank Stock Status uses Current Stock / Storage Volume progress gauges and the existing re-order marker.
- Sales Trend is a line chart of finalized daily sales for the last 30 calendar days including today.
- Credit Risk lists every customer with outstanding invoices older than 30 days and shows invoice count, age span and outstanding amount.
- Variance Status uses today's latest Dip Stock vs System Stock data. If absolute variance is greater than 100 litres, the card and status are shown as a red alert.

## Data rules
- Total Sales: today's finalized `sell` transactions for the selected business/location.
- Today Sold Liters: net sold quantity (quantity less quantity returned) for products linked to fuel tanks, grouped by product subcategory.
- Outstanding Received: payments received today against finalized sales dated before today; advance payments and deleted payments are excluded.
- Bank Deposited: finalized settlement bank-deposit value from the existing Graphs reconciliation source.
- Tank Status: existing Graphs calculated tank balances; no write/update is performed.
- Credit Risk: combines 31-60, 61-90 and Over-90-day debtors from the existing Graphs debtors ageing source.

## Safety
- Read-only analytics only.
- No DB migration or SQL required.
- No core/sidebar/global CSS/global JS file changes.
- Existing pages 1-4, system layout and sidebar remain unchanged.
