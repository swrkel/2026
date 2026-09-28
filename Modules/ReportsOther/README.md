# Reports - Other (ReportsOther)

Standalone Laravel module updated for specifications **8062 Reports - Other** and **8063 Reports - Other**.

## Standalone boundary

- All module-owned database tables use the `reo_` prefix.
- No imports from ProductsNew, Finance, Customers, Suppliers, Sales, User Management, Petro, SW, or any other application module.
- The host application's already-selected tenant database connection is used.
- Business + optional Location + optional Store are isolated by the module's `scope_key`.
- The module owns its models, controllers, services, middleware, routes, views, CSS, JS, language files, config, permissions, SQL and migrations.
- Existing product/sales/payment/contact data is read only through module-owned database gateways, not through another module's code.

## Cash Receipt tabs

1. **Receipt**
2. **List Receipt**
3. **Map Sub Products to Source**
4. **Prefix & Starting Nos**

## Receipt

Implemented from 8063:

- Dynamic Business name.
- Dynamic Business Location name/address.
- Receipt No previews the next number and consumes it only during a successful save.
- Prefix is optional; the configured Starting No is the first issued number.
- Database-enforced rule: one Receipt for one Source for each date within the current business/location/store scope.
- Sources come from the module's Source mapping setup; users do not type a Source name on the Receipt.
- Mapped Product Categories and Product Sub Categories are shown as separate Source Details rows with separate amounts.
- When a Product Sub Category and its parent Category are both mapped, the Sub Category takes priority for that product line so the same line is not double-counted.
- Membership No is auto-loaded when a configured membership column is available and all matched source rows resolve to one contact. Otherwise the field becomes a required manual entry.
- Total is the sum of Source Details.
- Total amount is converted to words and always ends with **Only**.
- Related cheque payments are snapshotted with cheque number, bank and cheque date. Multiple cheques are shown one per row.
- Saved Receipt values are snapshots so later source/category/payment changes do not rewrite historical Receipts.

## Receipt source data adapter

`Services/ReceiptSourceDataGateway.php` is fully contained in this module. Defaults are compatible with common Laravel ERP / UltimatePOS-style schemas:

- `transactions`
- `transaction_sell_lines`
- `products`
- `transaction_payments`
- `contacts`

Defaults and every relevant table/column are configurable in `Config/config.php` through `REO_*` environment values. This allows the module to remain independent even if the application's table names differ.

By default the Source amount is calculated from matching product lines as:

`quantity × unit_price_inc_tax`

If the system has a stored final line-amount column, set `REO_TX_LINE_AMOUNT_COLUMN` and that column is used instead.

Default transaction filters are `type=sell` and `status=final`. They can be changed using `REO_TX_TYPES` and `REO_TX_STATUSES`.

If the host system has no `contacts.membership_no` column, the Receipt does not fail; it automatically switches Membership No to manual entry. Set `REO_CONTACT_MEMBERSHIP_COLUMN` if the actual column has another name.

Cheque defaults:

- method: `cheque` or `check`
- cheque no: `cheque_number`
- bank: `bank_name`, with fallback to `bank_account_number`
- cheque date: `cheque_date`, with fallback to `paid_on`

## List Receipt

Columns:

- Action: View, Print, Edit
- Date
- Receipt No
- Source
- Total Amount
- Entered By
- Edited Details

Edit is intentionally restricted to fields that were entered manually. Currently this means Membership No only when it was manual at creation time. Auto-loaded Source, date, Receipt No, detail amounts, totals and cheque information are locked.

Every manual change is stored in `reo_receipt_audits`. The Edited Details button appears only when at least one change exists. Users can hover the button for a quick history or click it for the full popup, including old value, new value, editor and time.

## Reporting standard

List Receipt includes the standalone report toolbar:

- Date From / Date To with instant reload on change
- Universal search
- Transactions per page
- CSV
- Excel
- PDF via browser Print/Save as PDF
- Print
- Column Visibility
- Email
- SMS with downloadable link
- WhatsApp with downloadable link

Amounts use comma separators, right alignment and Business Settings currency precision through `BusinessSettingsGateway`.

## Sharing

Individual Receipt View supports Print, Email, SMS and WhatsApp. Report files are rendered to self-contained HTML snapshots, copied to the module's protected share storage, and exposed through expiring tokenized download links (`reo_share_links`).

SMS uses the module's generic gateway config:

```text
REO_SMS_ENDPOINT=https://provider.example/send
REO_SMS_TOKEN=...
REO_SMS_TO_FIELD=to
REO_SMS_MESSAGE_FIELD=message
REO_SMS_TIMEOUT=15
```

Email uses the Laravel mail configuration already available to the application. WhatsApp opens the standard WhatsApp share URL with the downloadable link.

## Database tables

Original 8062 tables:

- `reo_sources`
- `reo_source_mappings`
- `reo_number_sequences`
- `reo_share_links`

Added by 8063:

- `reo_receipts`
- `reo_receipt_details`
- `reo_receipt_cheques`
- `reo_receipt_audits`

## Install / update

Copy/replace `Modules/ReportsOther`, then:

```bash
php artisan optimize:clear
php artisan migrate
php artisan optimize:clear
```

For tenant databases managed by SQL import instead of migrations, import:

```text
Modules/ReportsOther/Database/Sql/ReportsOther_Master_Tenant_SQL.sql
```

The consolidated SQL contains both 8062 and 8063 tables.

Open:

```text
/reports-other/cash-receipt
```

## v5 instant tab performance
Cash Receipt tabs use lazy fragment loading plus background prefetch. After the first page paint, other tabs are warmed and retained in the DOM, so normal tab switches do not perform a full page reload. The server still supports normal tab URLs as a fallback. No database change is required for v5.
