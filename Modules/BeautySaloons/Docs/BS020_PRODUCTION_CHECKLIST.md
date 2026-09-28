# BS020 Beauty Saloons Final Standalone Audit & Production Hardening

## Before Server UAT

- Upload all BS001 to BS020 files together if possible.
- Run migrations in the tenant database.
- Clear route, view, config and permission caches.
- Verify the sidebar menu shows Beauty Saloons.
- Verify permissions for Owner, Branch Manager, Reception, Staff and Finance roles.

## Functional UAT Order

1. Installation and permissions
2. Branch/resource setup
3. Staff and services
4. Customers
5. Appointment scheduler
6. Reception queue
7. POS and billing
8. Inventory and retail sales
9. Memberships and packages
10. Gift vouchers
11. Wallet/prepaid packages
12. Loyalty
13. Notifications
14. Reports and dashboards
15. Customer portal and API
16. Finance integration

## Standalone Audit

All Beauty Saloons files must remain under `Modules/BeautySaloons` except approved shared ERP integrations such as Users, Business Locations, Customers, Finance and Inventory.
