# Finance Account Books central-database 404 fix

Date: 17 Sep 2026

## Root cause

The Finance Account Book routes were first registered with the correct mixed
central/tenant database resolver. A later standalone route provider registered
the same URLs again with `PreventAccessFromCentralDomains`. That final route
therefore returned an intentional 404 for businesses stored in the central
database, even though Finance / List Accounts itself could load.

## Correction

- The final standalone route owner now uses `InitializeFinanceTenantContext`.
- The hard-coded exception for one central hostname has been removed; all
  configured central hosts and valid central-database business sessions use the
  same rule.
- Finance context initialization is ordered before authentication, permission
  queries and route bindings.
- List Accounts now opens Account Books through a collision-proof
  `/finance/list-accounts-live/account-book/...` workflow. The page, contact
  filter, DataTable data, balance and main-account requests all use this same
  central/tenant-safe route family.
- Existing `/finance/account-book/...` URLs remain available for compatibility.

No SQL or migration is required.

## Deployment

Extract the corrected parcel at the Laravel project root and overwrite the
included files. The new collision-proof route works immediately through the
Finance runtime route loader. At the next maintenance opportunity, run:

```bash
php artisan optimize:clear
```

This clears any compiled copy of the previous tenant-only route.
