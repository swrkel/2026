# Products New - Stock Centre duplicate single-product stock fix

Date: 14 September 2026
Trace product reported: `2T Loose Oil - 210Ltrs`

## Problem

The Stock Centre query previously started from each physical row in
`variation_location_details`. A legacy/imported single product can have more than
one internal variation and/or more than one stock row for the same logical
product/location. That makes the same product/SKU appear more than once in Stock
Centre even though List Products remains one product record.

## Fix

`StockCenterService` now pre-aggregates stock into a logical stock identity before
joining display data:

- `single` products: one stock row per product + location, across internal
  variations;
- `variable` and `combo` products: one row per variation + location;
- repeated physical VLD rows for the same logical identity: summed into the one
  displayed row.

The Products New movement quantity uses the same identity so it cannot be mixed
against a different internal variation. The Details popup omits a variation
filter for collapsed single products and therefore shows the same all-variation
location total used by the Stock Centre row.

No destructive database cleanup is performed by the Stock Centre page.

## Files changed

- `Services/StockCenterService.php`
- `Resources/views/stock_center/index.blade.php`

## Database changes

None required.

A read-only diagnostic SQL file is included at:
`Database/SQL/ProductsNew_StockCentre_Duplicate_Diagnostic_20260914.sql`.
