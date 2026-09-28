# StockTransferNew STN_020 - Data Quality Guide

This package adds a post-live data quality dashboard.

## Purpose
- Identify orphan transfer lines.
- Identify transfer records missing from/to store references.
- Identify negative movement records for admin review.
- Identify transfers stuck in dispatched/in-transit state for more than three days.
- Identify over-received transfer lines.

## Route
`/stock-transfer-new/admin/data-quality`

## Notes
- This screen is read-only except for CSV export.
- It does not duplicate the Products module.
- It uses tenant database tables only.
- It should be added to the StockTransferNew route loader if the module provider is not already auto-loading all route files.
