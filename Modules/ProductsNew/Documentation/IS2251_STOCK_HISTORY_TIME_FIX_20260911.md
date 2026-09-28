# IS2251 - Products New Stock History Date & Time Fix - 11 Sep 2026

## Scope

Products New -> List Product -> Action -> Stock History -> Detailed Product Movement Ledger
Products New -> Stock Center -> Stock History -> Detailed Product Movement Ledger

## Reported issue

The Detailed Product Movement Ledger did not always show the actual save time for ERP transactions. The reported example expected 06:39:12 PM while an earlier/wrong time was shown.

## Root cause

The Stock History query needed explicit aliases for transaction/line `created_at` values, and historical ERP transaction rows can already contain a non-midnight time in `transaction_date` that is not the actual save time. Recovering `created_at` only when `transaction_date` was `00:00:00` was therefore not sufficient for all previous entries.

## Fix

The Stock History query explicitly aliases timestamps:

- `m.created_at AS source_created_at`
- `t.created_at AS source_created_at`
- `pl.created_at AS line_created_at`
- `sl.created_at AS line_created_at`
- `al.created_at AS line_created_at`

For host ERP purchase/sale/stock-adjustment rows, the report now:

1. preserves the DATE from `transaction_date` (so back-dated business dates remain unchanged), and
2. uses the actual transaction `created_at` TIME, with line `created_at` as fallback.

This rule is applied when the report is read, so it also corrects historical rows without rewriting posted transactions.

Standalone Products New inventory movements continue to use their own movement timestamp logic and are not forced through the legacy ERP timestamp rule.

## Expected result

- Existing and new ERP purchase/sale/stock-adjustment rows display the actual save time when `created_at` is available.
- The corrected timestamp is also used for chronological ordering and the running Balance sequence.
- IS2247 running-balance logic remains unchanged.
- IS2236 Main Store / Opening Stock handling remains unchanged.

## Database

No SQL or migration is required. No historical rows are updated. This is read-only Stock History reporting logic.
