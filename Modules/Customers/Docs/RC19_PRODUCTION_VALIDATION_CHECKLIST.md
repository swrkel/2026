# Customers v1 RC19 - Production Validation Checklist

## Package purpose
RC19 continues from RC18 and is intended for server-side validation of the standalone Customers module after the Contacts customer migration.

## RC19 code adjustment included
- Removed the remaining `@lang('contact.*')` labels from the Customers balance view and replaced them with `customers::lang.*` keys.
- Added Customers-owned language keys for balance labels.
- Included this checklist and a raw scan report for deployment/testing.

## Server validation order
1. Upload/replace the package files.
2. Clear Laravel cache/config/view cache.
3. Login as Super Admin and confirm the Customers module permission is visible/enabled.
4. Confirm sidebar shows Customers and does not expose customer pages under Contacts.
5. Open Customers Dashboard.
6. Open Customer Register.
7. Add Customer.
8. Edit Customer.
9. View Customer.
10. Customer Groups.
11. Customer Balance popup/page.
12. Customer Ledger.
13. Customer Statement.
14. Customer Due/Aging Reports.
15. Customer Import/Export.
16. Sales/POS customer dropdown/search.
17. Customer payments and advance payments.
18. Multi-location/branch filtering.
19. User permission testing.
20. Old customer URLs should redirect or resolve to Customers module pages.

## Notes
Some database table references such as `contacts`, `transactions.contact_id`, and `contact_id` remain intentionally because existing tenant data and ERP transaction tables still store customer records/foreign keys there. The goal of this migration is to move customer functionality/pages/logic into the standalone Customers module without breaking existing tenant data.
