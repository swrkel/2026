# StockTransferNew STN_024 - Operations Review Guide

This stage adds a read-only administrative review screen for post-live monitoring.

## Checks included
- Stale draft transfers older than 7 days.
- Transfers stuck in transit for more than 3 days.
- Transfer lines with zero or empty quantity.
- Old active transfer locks older than 6 hours.
- Missing core transfer table warning.

## Usage
Open: `/stock-transfer-new/admin/operations-review`

Use this screen before month-end closing, after go-live, and before any manual correction.
Export CSV can be given to testers or administrators.

## Important
This stage does not duplicate Product management and does not change the completed transfer workflow.
