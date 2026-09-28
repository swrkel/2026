# StockTransferNew STN_026 - Inter-Business Transfer Settlement

This package adds a controlled settlement layer for stock transfers between two businesses in the same tenant environment.

## Purpose
- Group completed inter-business transfers into a settlement batch.
- Review transfer value and shortage/excess variance value before approval.
- Prevent the same transfer from being settled twice.
- Maintain approval/cancellation audit history.

## Important Architecture Notes
- This does not duplicate the standalone Products module.
- Product values are read from StockTransferNew transfer lines only.
- The transfer workflow remains unchanged; this is an add-on control layer.
- Run the tenant SQL after all previous StockTransferNew SQL files.

## Route file
Include `Modules/StockTransferNew/Routes/admin_inter_business_settlement.php` in the module RouteServiceProvider if route fragments are not auto-loaded by your installer.
