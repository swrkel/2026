# S592 – Stock Adjustment New fixes (2 Aug 2026)

## Included corrections

1. Accounting Mapping now retains and displays the saved Category, Sub Category, Account to Link, Stock Account Group and Stock Account values.
2. Edit Mapping restores all previously selected values and uses one locally managed Select2 search control per dropdown.
3. Accounting Mappings list now has an **Action** dropdown containing **Edit** and **Delete**.
4. Create Stock Adjustment now loads the logged-in user's permitted Business Locations and the business Stores as selectable dropdowns, showing both name and ID.
5. The original **Type** (Quantity / Value / Damage / Expiry) is preserved, and a separate required **Adjustment Type** selector is available on every product line and controls **Increase** or **Decrease** independently. Mixed-direction documents are supported and posted safely as direction-specific host transactions.
6. Posting is transactional and duplicate-protected. It updates shared location/store stock, selected batch availability, uses the selected Store quantity when applicable, creates the required shared stock-adjustment transaction/lines, and posts balanced debit/credit entries using the active accounting mapping.
7. Posting failures roll back all stock and accounting changes and show a readable error instead of leaving a partial posting.

## Accounting direction

- **Increase**: Debit Stock Account; Credit Account to Link.
- **Decrease**: Debit Account to Link; Credit Stock Account.

Both accounts are required in every active mapping used for posting.

## Database deployment

The module auto-schema service adds the separate direction and posting audit columns. For manual tenant deployment, run:

```text
Modules/StockAdjustmentNew/SQL/12_S592_Posting_And_Reference_Fix.sql
```

or run the complete idempotent tenant installer:

```text
Modules/StockAdjustmentNew/SQL/10_MASTER_INSTALL.sql
```

The SQL scripts use existence checks and are safe to run repeatedly.

## Deployment

Replace the complete `Modules/StockAdjustmentNew` folder, then run:

```bash
php artisan optimize:clear
```

The module serves its own CSS and JavaScript directly, so a separate asset publish step is not required for these fixes.
