StockTransferNew_STN_006

Added in this parcel:
1. Barcode/SKU scan page that reads from existing Products module only.
2. Variance reconciliation page for dispatch-vs-receive differences.
3. Transfer return note foundation for received/completed transfers.
4. Reusable transfer templates for common location/store movements.
5. New standalone entities, controllers, services, views, migration, JS, and SQL.
6. New permissions: stocktransfernew.reconcile, stocktransfernew.return, stocktransfernew.templates.

Important:
- No product master duplication was added.
- Product details remain owned by the standalone Products module.
- All new STN_006 database changes are tenant database changes.
