StockTransferNew STN_019

Purpose:
Post-live monitoring and production support layer.

Included:
- Post-live monitor controller/service/view
- Exception checks for delayed transfers and unresolved variances
- Table readiness checks
- Latest activity view
- Tenant SQL for post-live notes and permission
- CSS/JS copied to module and public paths

Install notes:
1. Copy files into the matching project paths.
2. Include Routes/post_live.php from the module route loader if not auto-loaded.
3. Run 19_TENANT_POST_LIVE_SUPPORT_STOCK_TRANSFER_NEW.sql in enabled tenant DBs.
4. Assign permission stocktransfernew.post_live.monitor to admin/support roles.
