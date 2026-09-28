Task 8046 - Customer Reference (QR)
Raw SQL for tenant databases
================================================================

THE TABLE THIS FEATURE USES
    customer_qr_references   <- NEW. Created by these scripts.

THE TABLE THIS FEATURE DOES NOT USE
    customer_references      <- EXISTING AND SHARED. Not touched.

    customer_references is the legacy "Vehicle No" table. It is read and
    written by Petro, PetroPD, PetroDirect, PetroGeneral, Vat, SettlementSW,
    PumperDashboard, EVCharging, EzyInvoice, DailyCollectionSW,
    ReportsCustomized and app-level controllers.

    Task 8046 does not read, write, alter or drop it. No script in this folder
    contains a statement that names it outside of comments and the two
    row-count checks, which are SELECTs.

RUN ORDER - PER TENANT DATABASE
    1. 01_INSPECT_TENANT.sql
       Read-only. Tells you whether the tenant is already installed, whether
       its dependencies are present, and whether a Fuel product category
       exists here. Record the legacy row count it reports.

    2. 02_CREATE_CUSTOMER_QR_REFERENCES.sql
       Creates the new table, then verifies. Confirm the legacy row count it
       reports at the end matches the one from step 1.

    Both are safe to re-run. Step 2 uses CREATE TABLE IF NOT EXISTS.

ALTERNATIVE: LARAVEL MIGRATION
    Database/Migrations/2026_08_28_000001_create_customer_qr_references_table.php
    does the same thing. Use EITHER the migration OR these scripts. Both are
    guarded, so running both is harmless, but there is no reason to.

    Because tenants here sit at different schema levels, the SQL route is
    recommended - it lets each tenant be inspected before anything is created.

UNDOING THIS
    There is deliberately no DROP script in this folder. An earlier draft
    shipped one and it was a mistake: a rollback script named after a shared
    table is a hazard sitting in the repository.

    To remove the feature from a tenant:
        DROP TABLE IF EXISTS `customer_qr_references`;

    Nothing outside this feature reads that table, so dropping it affects
    only Task 8046.

NOTES
    - No foreign keys, matching the other Customers module tables.
    - fuel_type_id NULL means the system default "Not Known".
    - The QR image is not stored. qr_payload holds the encoded text, and the
      image is rendered on demand using milon/barcode.
