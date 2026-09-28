# PriceChangeNew — System 9773 — Sequence 02 Large

## Included

- Dashboard card and action design matched to `Modules/POS/Resources/views/dashboard/index.blade.php` and its ERP-standard style partial.
- Draft submission.
- Approval queue.
- Approve and reject controls.
- Optional dual control and self-approval setting.
- Future effective date scheduling.
- Manual price application.
- Due-price console command for one tenant or all tenants.
- Before/after price snapshots.
- Live-price conflict detection.
- Business base price changes.
- Location selling-price-group changes through `variation_group_prices`.
- Application attempts and audit history.
- Business-specific settings.
- Raw idempotent tenant SQL.

## Safety boundaries

- The separate `Modules/PriceChanges` module used by MPCS/F17/F22 is not touched.
- Only the logged-in tenant database is used.
- Every query is restricted to the logged-in business.
- Location lists are restricted to locations assigned to the logged-in user.
- Old-stock/new-stock price layering is not enabled in Sequence 02. The only stock rule is `all_stock`.
- Location price-group scope changes selling prices only. Purchase prices remain business-wide.
- No global layout, sidebar, font, header, or CSS file is modified.

## Scheduled application

The standalone command is:

```bash
php artisan pricechangenew:apply-due --all-tenants
```

It processes a tenant only when `Auto apply due records` is enabled for the business. The existing Laravel scheduler or server cron may invoke this command. The module does not modify the global Console Kernel.
