# 8021-4 — Customer & Payment Analytics

## Added page

`/graphs/customer-payment`

The Graphs module now has a fourth top-level coloured tab: **Customer & Payment Analytics**. The active tab follows the ERP standard (white background / black text); inactive tabs retain their individual colours.

## Tab 1 — Payment Method Split

- Pie chart for Cash, Cards, Credit Sales and Online Transfers.
- Amount and percentage KPIs.
- Daily / Weekly / Monthly / Yearly period selector.
- System-standard Location and Date Range filters.
- Period verification table.

### Payment data safety

- Only finalized sale/settlement-linked `transaction_payments` are used for Cash/Card/Online Transfer slices.
- Finalized credit-sale `transactions` form the Credit Sales slice.
- Transaction payments linked to a credit-sale transaction are excluded from the paid-method slices to avoid double counting the original credit sale when it is collected later.
- `cash_deposit` is intentionally not classified as an Online Transfer because it is a movement of already-counted cash.
- Bank transfer / direct bank deposit / online transfer payment names are classified as Online Transfers.

## Tab 2 — Pump & Shift-wise Sales Analysis

- Bar chart using the existing finalized meter-sale source.
- Daily / Weekly / Monthly / Yearly basis.
- Quantity and amount verification table.
- Yearly bucketing was added to the module's existing pump/shift analytics service; existing Daily/Weekly/Monthly behaviour is unchanged.

## Tab 3 — Top Credit Customers / Fleet Analysis

- Horizontal stacked bar chart for the top 10 credit customers.
- Stacks represent the selected Daily / Weekly / Monthly / Yearly reporting buckets.
- Customer detail includes customer code, mobile, credit sales, transaction count and linked fleet/vehicle numbers where the sale has `transactions.fleet_id`.
- Period verification table for the top customers.

## Permissions / isolation

A new page permission is included:

`graphs_customer_payment_analytics`

Its data endpoints are owned by this page, including a separate customer-page pump/shift endpoint. No core files or database schema are changed.
