# LA-1094 Purchase New UI + Functional Parity (PUR014)

Source: `Purchase(6).zip`

## Pages corrected

1. Purchase (New) / Add Purchase Entry
2. Purchase (New) / List Purchase Entries
3. Purchase (New) / List Purchase Return Entries
4. Purchase (New) / Add Purchase Return Entry

## Functional scope retained/added

- Add Purchase Entry keeps the standalone PUR013 supplier, product, stock, tax, discount, payment, validation, accounting and save flows.
- Purchase Entry list includes filters, summary totals, View/Edit/Delete actions and correct paid/due values from canonical transactions/payments.
- Purchase Return creation selects the original received purchase, loads returnable purchase lines, validates quantities, saves the return transaction and lines, decreases location/store stock, updates the original purchase line returned quantity, posts module-owned accounting entries and logs failures.
- Purchase Return list includes filters, totals, View/Delete actions and the original purchase reference.
- Purchase Return deletion reverses stock, returned quantities and accounting entries before deleting the transaction.
- Purchase Return View displays the full header and returned product lines.

## Standalone rule

All changed implementation is under `Modules/Purchase`. No core Purchase controller/view/JavaScript and no other module controller/service is used. Shared tenant master and transaction tables are accessed only through services owned by the Purchase module.

## Deployment

Upload into the Laravel project root and preserve the `Modules/Purchase/...` structure, then run:

```bash
php artisan module:enable Purchase
php artisan optimize:clear
```

No migration or raw SQL is required.
