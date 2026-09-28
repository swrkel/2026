# StockTransfer-New Period Close Guide

Stage STN_021 adds month-end controls for the standalone StockTransfer-New module.

## Purpose
- Preview pending transfers before closing a period.
- Block closing when transfers are pending, in transit, or have open variance.
- Save period close records for audit and future support.
- Keep all logic inside StockTransferNew files.

## Tenant database
Run `21_TENANT_PERIOD_CLOSE_STOCK_TRANSFER_NEW.sql` in each tenant database.

## Route
`/stock-transfer-new/admin/period-close`

## Notes
This does not duplicate product data. Product names/details must continue to come through the existing Products module bridge.
