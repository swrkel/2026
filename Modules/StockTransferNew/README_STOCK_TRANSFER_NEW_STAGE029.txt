StockTransferNew STN_029

Focus:
- Logistics execution
- Multi-vehicle transfer dispatch
- Route/ETA tracking
- Dispatch and receiving checklist placeholders
- Transfer consolidation support

Install:
1. Copy files into the same paths.
2. Include Routes/admin_logistics.php from the module route service provider if not auto-loaded.
3. Run database/sql/stock_transfer_new/29_TENANT_LOGISTICS_EXECUTION_STOCK_TRANSFER_NEW.sql on each tenant DB.
4. Clear config/view/route cache if your server uses cache.
