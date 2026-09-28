# StockTransferNew STN_028 - Stock Reservation Control

This add-on gives the StockTransfer-New module a standalone stock reservation control layer.

## Purpose
- Reserve stock for approved/requested transfers before physical dispatch.
- Show active, expired, released, and pending reservations.
- Allow safe manual release with mandatory reason.
- Provide CSV export for stock controller review.

## Important
This does not create or duplicate the Products module. Product fields are copied only as reference details for the transfer/reservation record.

## Installation
1. Copy the files in this package into the Laravel project.
2. Include `Modules/StockTransferNew/Routes/admin_reservations.php` from the module route service provider or module admin routes loader.
3. Run `28_TENANT_STOCK_RESERVATION_CONTROL_STOCK_TRANSFER_NEW.sql` in every tenant database where StockTransfer-New is enabled.
4. Give users the new permissions:
   - stock_transfer_new.reservations.view
   - stock_transfer_new.reservations.create
   - stock_transfer_new.reservations.release
   - stock_transfer_new.reservations.export

## Test Flow
1. Create or approve a transfer.
2. Open Reservation Candidates.
3. Reserve a line.
4. Check it appears in Stock Reservation Control.
5. Release it with a reason if cancelled or not dispatched.
6. Export CSV and verify totals.
