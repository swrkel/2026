# DIST-310 - Distribution Standalone Separation Stage 8

## Focus
Product dependency reduction.

## Completed
- Added Distribution-owned product lookup service.
- Added Distribution-owned product pricing service.
- Redirected direct product/category/unit references in Distribution controllers and entities to module-local Core wrappers.
- Kept Core wrappers extending existing ERP models to avoid changing working database behavior in this stage.

## Safety
This stage does not change existing database tables or business calculations. It creates a module-local seam first, so future stages can move logic without breaking working pages.

## Remaining
- Replace DB table product queries with `DistributionProductLookupService` function by function.
- Separate product JS and product language strings.
- Final removal of any unavoidable main ERP product dependencies after testing.
