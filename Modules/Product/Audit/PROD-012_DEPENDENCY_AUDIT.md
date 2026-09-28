# PROD-012 – Product Dependency Audit & Standalone Cleanup

## Completed in this package

- Added Product module view fallback registration in `Providers/ProductServiceProvider.php`.
- Legacy view names such as `product.index`, `product.partials.*`, `brand.*`, `unit.*`, and `import_products.*` now resolve from `Modules/Product/Resources/views` first.
- Converted legacy Product controller view calls to `product::...` names where safe.
- Converted old blade includes from `product.partials.*` to `product::product.partials.*`.
- Removed avoidable dependency on main-system `layouts.partials.module_form_part` by adding a Product module fallback partial.
- Added Product module wrapper views for old `brand.*` and `unit.*` references.

## Important note

This package intentionally does not change working business logic, database writes, stock calculations, pricing calculations, or tenant resolution. It only reduces remaining view/dependency coupling and adds audit files.

## Remaining dependencies to review in PROD-FINAL

Some legacy controllers still contain business logic copied from the main system. These are retained for safety. PROD-FINAL should split these large legacy classes into smaller services/controllers only after tester confirmation.

## Files changed/added

- `Providers/ProductServiceProvider.php`
- `Resources/views/layouts/partials/module_form_part.blade.php`
- `Resources/views/brand/index.blade.php`
- `Resources/views/brand/create.blade.php`
- `Resources/views/brand/edit.blade.php`
- `Resources/views/unit/index.blade.php`
- `Resources/views/unit/create.blade.php`
- `Resources/views/unit/edit.blade.php`
- legacy Product/Brand/Unit/Import controller view references updated
- Product blade partial includes updated

## Tester checklist

1. Product list page opens.
2. Add Product page opens.
3. Edit Product page opens.
4. Product details/modal opens.
5. Brand list/create/edit opens.
6. Unit list/create/edit opens.
7. Import Products page opens.
8. Product reports page opens.
9. Product settings tabs open.
10. No `View [product...] not found`, `View [brand...] not found`, `View [unit...] not found`, or `View [layouts.partials.module_form_part] not found` errors.
