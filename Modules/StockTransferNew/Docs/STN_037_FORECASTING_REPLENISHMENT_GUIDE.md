# STN_037 - Transfer Forecasting & Replenishment

This parcel adds a standalone forecasting/replenishment control layer for StockTransfer-New.

## Main functions
- Review open replenishment suggestions.
- Generate suggestions from StockTransfer-New minimum stock rules.
- Approve or close suggestions.
- Export open suggestions to CSV for review.

## Notes
- Product details remain sourced from the existing standalone Products module/bridge.
- This does not duplicate product master management.
- Tenant/business scope is enforced by `business_id`.
