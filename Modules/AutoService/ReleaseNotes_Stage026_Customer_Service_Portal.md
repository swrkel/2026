# AutoService Stage 026 - Customer Service Portal, Current Bill and Parts History

## Added / Improved
- Customer-facing portal now shows the current service status in a clear progress view.
- Current bill/estimated bill visible to customers, with line-by-line details.
- Full service history for the customer/vehicle.
- Parts and accessories usage history with filters for part/accessory text, job number, from date and to date.
- Parts/accessories table includes date, job no, reference, type, description, quantity, unit price, discount, tax and total amount.
- Summary cards for parts/accessories quantity, gross value, discount and net total.
- Business-safe lookup and invoice filtering.

## SQL
Run `Database/SQL/27_AUTOSERVICE_STAGE026_CUSTOMER_SERVICE_PORTAL_HISTORY.sql` against each tenant database.
`MASTER_AUTOSERVICE_SQL_STAGE026.sql` contains the same Stage 026 SQL for this package.
