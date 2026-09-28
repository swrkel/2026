# IS2247 - Products New Stock History Balance Fix - 11 Sep 2026

## Scope

Products New -> List Product -> Action -> Stock History -> Detailed Product Movement Ledger
Products New -> Stock Center -> Stock History -> Detailed Product Movement Ledger

## Issues addressed

1. The Balance column could look incorrect because the ledger displayed non-opening transactions newest-first while Balance was calculated in chronological order.
2. A sale saved with a date-only `transaction_date` could appear as `12:00:00 AM`, which could place the sale before the opening stock/purchase and therefore affect the running balance sequence.
3. Transactions must be shown in date/time order, while Opening Stock remains the first baseline row.

## Fix

- Opening Stock remains pinned as the baseline and is applied first when calculating the running balance.
- Remaining detailed ledger rows are displayed oldest-to-newest by effective date and time.
- Running Balance uses the same order as the displayed ledger.
- For legacy purchase, sale and stock-adjustment transactions whose operation time is `00:00:00`, Stock History now recovers the time from the transaction `created_at`, with the related line `created_at` as a safe fallback.
- Only the TIME component is recovered. The selected transaction DATE is preserved.
- If timestamps are identical, transaction/movement id and then line id are used as deterministic tie-breakers.
- The fix is report/read-only logic. It does not rewrite transactions, quantities, stock, finance entries or historical database rows.

## Expected sample reconciliation

For the sequence in IS2247:

- Opening Stock +100 => Balance 100
- Purchase +50 => Balance 150
- Sale -25 => Balance 125
- Stock Adjustment Decrease -5 => Balance 120
- Stock Adjustment Increase +8 => Balance 128

## Database

No SQL or migration is required.
