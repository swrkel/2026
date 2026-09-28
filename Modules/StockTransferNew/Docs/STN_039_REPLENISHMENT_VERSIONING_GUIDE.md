# STN_039 - Replenishment Recommendation Versioning

Adds controlled versioning for replenishment recommendations before they become transfer requests.

## What is included
- Recommendation version register
- Draft → Approved → Converted / Cancelled status flow
- Approval locking
- Duplicate conversion prevention
- Conversion placeholder table for safe integration with existing transfer request workflow
- Full audit log for approval, cancellation, and conversion

## Notes
- This package does not duplicate Products module functionality.
- Product IDs are stored as references only.
- Run SQL in tenant databases where StockTransfer-New is enabled.
