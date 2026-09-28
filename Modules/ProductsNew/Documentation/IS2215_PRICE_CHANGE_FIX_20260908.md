# IS2215 - Products New Profit Percentage Basis Fix - 08 Sep 2026

## Scope

Products New -> List Products -> Action -> Edit -> Pricing

## Reported issue

A VAT-inclusive product is created with **Profit Percentage On = Tax Inclusive**, but reopening Edit Pricing displays **Tax Exclusive**.

## Root cause found

The previous compatibility fix still had two weak points on older/mixed deployments:

1. A stale copied product form can omit `profit_basis` from the request. In that case `validated()` has no explicit basis to persist.
2. The original `products.profit_basis` migration defaults existing rows to `exclusive`. For a VAT-inclusive product with no explicit module-meta value, that legacy default can be mistaken for an operator choice and displayed on edit.

Products New also supports application-level view override directories. An older copied `resources/views/modules/productsnew` view can therefore override the corrected module view if it is searched first.

## Fix

- Request normalization now always supplies a valid `profit_basis`. An explicit posted value wins; if absent, `tax_type=inclusive` safely infers `inclusive`.
- The selected basis is persisted redundantly to:
  - `products.profit_basis` when that column exists; and
  - `products_new_product_meta.settings.profit_basis` when module meta settings are available.
- Edit loading now treats explicit module meta as authoritative. If no explicit meta exists and a VAT-inclusive legacy product only has the migration default `exclusive`, it displays `inclusive`.
- Once the corrected code saves a deliberate Exclusive choice, the explicit meta value preserves it even on a VAT-inclusive product.
- The module's own current views are registered before stale application-level copied Products New views.
- The create form state explicitly defaults Profit Percentage On to Inclusive.
- The browser asset version was advanced to `is2215-profit-basis-v2`.

## Database

No new table is required. The fix uses existing Products New meta storage and the existing core `profit_basis` column when present. It also remains functional when the core column is missing.
