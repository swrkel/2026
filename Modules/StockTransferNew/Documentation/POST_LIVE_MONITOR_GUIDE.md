# Stock Transfer-New STN_019 Post-Live Monitor Guide

This parcel adds a lightweight monitor for the first production days after go-live.

## Main page
`/stock-transfer-new/post-live/monitor`

## Purpose
- Check transfer counts by status.
- Check delayed in-transit transfers.
- Check unresolved variance records.
- Confirm critical tenant tables exist.
- Show latest activity log entries where available.

## Notes
- This package does not duplicate Products module data.
- Product details must continue to come through the existing Products module bridge.
- Run the tenant SQL in each tenant database where StockTransfer-New is enabled.
