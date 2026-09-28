# Purchase (New) — Add Purchase Entry Parity (PUR013)

## Scope

This package rebuilds **Purchase (New) → Add Purchase Entry** as a standalone workflow owned entirely by `Modules/Purchase`.

No core Purchase controller, core purchase Blade, core purchase JavaScript, or another module's business service is used. The module uses the shared ERP shell and the tenant's common master/transaction tables through Purchase-owned controllers, requests, services, utilities, views, CSS, and JavaScript.

## Included functionality

- Business location and store selection with tenant ownership validation.
- Supplier type-and-filter search, supplier outstanding display, duplicate supplier invoice/reference check, and quick supplier creation.
- Product/variation search by name, SKU, sub-SKU/barcode, and variation; Enter-key selection; quick single-product creation.
- Base units and configured sub-units with multiplier conversion and decimal restrictions.
- Current stock by selected location/store and selected unit.
- Quantity, free quantity, purchase cost, per-line fixed/percentage discount, product tax, cost including tax, line totals, profit percentage, and selling price.
- Optional lot number, manufacturing date, and expiry date fields according to business settings.
- Purchase-level fixed/percentage discount, additional tax, shipping details/charges, price adjustment, notes, VAT marker, free-product value option, exchange rate, pay terms, and document upload.
- Received, Pending, and Ordered status handling. Stock, actual payments, and accounting entries are posted only for Received purchases.
- Multiple payments with cash, bank transfer, cheque, card, supplier advance, prepayment, credit due, and other methods.
- Method-specific cheque/bank/card fields and payment-account validation.
- Cash-account available-balance validation across multiple payment rows.
- Atomic transaction, purchase-line, payment, stock, price, supplier payable, and account posting.
- Save, Save & View, and Save & Add Another actions.
- Detailed validation and Laravel logging on save errors.

## Module-owned files

The feature is implemented under:

- `Http/Controllers/Entry`
- `Http/Requests`
- `Services/Entry`
- `Utils`
- `Routes/purchase_entry.php`
- `Resources/views/entries/create.blade.php`
- `Resources/assets/js/purchase-entry-create.js`
- `Resources/assets/css/purchase-entry.css`

## Deployment

Extract the ZIP into the Laravel project root so the resulting path is `Modules/Purchase/...`, then run:

```bash
php artisan module:enable Purchase
php artisan optimize:clear
```

Open:

```text
/purchase/entries/create
```

No database migration or raw SQL is required for this Add Purchase Entry rebuild. It uses the existing common tenant schema and filters optional columns at runtime for tenant-version compatibility.

## Verification checklist

1. Select location/store and supplier.
2. Confirm supplier outstanding and duplicate reference warning.
3. Add a product by search and by the quick-product form.
4. Test a configured sub-unit and decimal restriction.
5. Test line discount/tax/free quantity and purchase-level totals.
6. Save Received purchases with cash, cheque, bank, card, partial payment, and full credit.
7. Confirm stock and supplier/account ledgers.
8. Save Pending/Ordered purchases and confirm stock/payment ledgers are not posted until receipt.
9. Test all three save actions.
