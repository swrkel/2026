Task 8046 - Customer Reference
Raw SQL for tenant databases
================================================================

WHY THIS FOLDER EXISTS
    The module ships a Laravel migration
    (2026_08_28_000001_create_customer_references_table.php) which is the
    normal way to install this table. These raw SQL files exist for tenants
    where migrations are not run and the DBA applies schema changes by hand
    through phpMyAdmin, matching the existing IS1805 and
    customer_credit_sale_reconciliation folders.

    Run EITHER the migration OR these scripts, not both. Both are guarded
    (IF NOT EXISTS / Schema::hasTable), so running both is harmless, but there
    is no reason to.

FILES
    00_MASTER_CUSTOMER_REFERENCE.sql        Runs everything below in order.
    01_CREATE_CUSTOMER_REFERENCES.sql       Creates customer_references.
    99_ROLLBACK_DROP_CUSTOMER_REFERENCES.sql  Drops it again. Destructive.

NOTES
    - No foreign keys are declared, matching the existing Customers module
      tables. customer_id points at contacts.id and fuel_type_id points at
      categories.id, but both are enforced in application code so that
      installing this table cannot fail on a tenant with legacy orphan rows.
    - fuel_type_id NULL means the system default "Not Known".
    - The QR image is not stored. qr_payload holds the text encoded into the
      QR, and the image is rendered on demand.
