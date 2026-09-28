# 8021-3 — Operational & Loss Analytics

Implemented the third Graphs page at `/graphs/operational-loss`.

## Page tabs
The Graphs module now has three top-level pages:
1. Stocks & Sales Analytical
2. Financial & Profitability Analytics
3. Operational & Loss Analytics

All three use the existing Graphs system-standard coloured top tabs. Inactive tabs retain their individual colours; the active tab uses a white background with black text.

## Operational tabs
### Fuel Loss / Dip vs. Meter Variance Report
- Line chart: Dip Stock vs System Stock.
- Daily, Weekly and Monthly aggregation.
- Location + system-standard Date Range filters.
- Latest Dip Stock, System Stock, Variance and Fuel Loss KPI cards.
- Tank-level verification table.
- Source: `dip_readings`, using the stored dip reading and the stored system/fuel-balance reading captured at the same dip event.
- For each tank in each period bucket, the latest reading in that bucket is used; repeated readings are not summed.

### Pump & Shift-wise Sales Analysis
- Bar chart grouped by period and shift, with a separate series per pump.
- Daily, Weekly and Monthly aggregation.
- Sales amount, sold quantity, pump count and shift count KPI cards.
- Period / Pump / Shift verification table.
- Finalized settlement meter sources are used. Pump-operator meter details are preferred and matching synced `meter_sales` rows are suppressed to avoid double counting.

## Safety / integration
- No core file changes.
- No database changes or migrations.
- Existing sidebar/layout remains `layouts.app`.
- CSS remains scoped to `.gr-app`.
- Existing Pages 1 and 2 data logic remains unchanged.
