# SUPPLIERS-SEP-008 - Utilities / Helper Separation

Completed in this stage:

- Added `SupplierMorphTypeUtil` to centralize supplier morph-type mapping inside the Suppliers module.
- Updated document, note, and audit services so they no longer hard-code main `legacy Contact morph type` references directly.
- Updated supplier form data loading to use `Modules\Suppliers\Entities\SupplierBusinessLocation` instead of `legacy BusinessLocation`.
- Added `SupplierRouteUtil` for module-local route helper usage in upcoming cleanup stages.
- Added `SupplierDateRangeUtil` for report/date filtering separation.

Standalone status after SEP-008:

- Main-system controller/model direct usage has been reduced further.
- Existing database tables are still reused because the ERP is single-code/multi-tenant and the Suppliers module must read/write the tenant supplier records.
- Final cleanup is still required before 100% standalone certification.

Remaining:

- SUPPLIERS-SEP-009: final dependency cleanup and route/view scan.
- SUPPLIERS-AUDIT-001: final 100% standalone verification report.
