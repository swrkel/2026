# IS2212 - Products New Price Change Fix - 08 Sep 2026

## Scope

Products New -> List Products -> Action -> Edit -> Pricing

## Issues fixed

1. A saved inclusive selling price such as 250 reopened as 249.99.
2. Profit Percentage On = Tax Inclusive reopened as Tax Exclusive.

## Root causes

### Selling price drift on edit

The price calculator ran immediately when an existing product edit form opened. It treated Profit % as the initial source and recalculated the selling pair. A rounded stored exclusive value (for example 211.86 at 18% VAT) becomes 249.9948 when multiplied back by 1.18, so a saved inclusive value of 250 could display as 249.99.

### Profit basis not retained on older tenant schemas

The form supports products.profit_basis, but older tenant databases can exist without that core column. ProductWriteService correctly avoids writing columns that do not exist, so the selected basis was lost and the edit form fell back to Exclusive.

## Fix

- Existing product edit forms no longer recalculate saved prices merely on page load. The exact stored values are shown.
- On edit, saved inclusive values are treated as the source when recalculation is later required by an operator change.
- Profit Percentage On now controls the actual calculation basis:
  - Inclusive: margin is applied between purchase incl. tax and selling incl. tax.
  - Exclusive: margin is applied between purchase excl. tax and selling excl. tax.
- The selected profit basis is also persisted in Products New's own `products_new_product_meta.settings` JSON as a compatibility store.
- If `products.profit_basis` exists, it continues to be used and saved normally.
- For an older VAT-inclusive product with no saved basis anywhere, the edit form safely infers Inclusive.
- The JS asset cache key was changed so browsers request the corrected script after deployment.

## Database

No new migration is required for this fix. The existing `products.profit_basis` migration remains supported, but the compatibility meta persistence makes the fix safe for tenant databases where that migration has not yet been applied.

## Deployment note

The browser loads `public/modules/productsnew/js/productsnew.js`. After replacing the module, copy the corrected `Resources/assets/js/productsnew.js` to that public path, or use the supplied app-root changed-files parcel which includes both paths.
