StockTransferNew STN_018

Purpose:
- Adds User Acceptance Testing and sign-off support after go-live readiness.
- Does not change the completed transfer workflow.
- Includes printable checklist, sign-off register, tenant data snapshot, CSS/JS and SQL.

Install:
1. Upload files.
2. Include/load Routes/uat.php if your module route loader does not auto-include route fragments.
3. Run 18_TENANT_UAT_SUPPORT_STOCK_TRANSFER_NEW.sql in every tenant database.
4. Clear Laravel cache.
