# IS2303 – Product Category Account Dropdowns Fix – 21 Sep 2026

## Reported issue
Products New / Stock Center / Categories / Add Category did not show the **COGS Accounts** and **Sales Income Accounts** dropdowns.

## Root cause
Both account fields were rendered with the HTML `hidden` attribute and a `data-pn-weight-account-wrap` marker. The Products New category JavaScript treats that marker as belonging to the **Weight Loss / Excess Applicable** checkbox, so the two account fields were hidden whenever that checkbox was not selected.

The account mappings are category/account settings and must be available independently of the Weight Loss / Excess option.

## Fix
- Removed the `hidden` attribute from the COGS account field wrapper.
- Removed the `hidden` attribute from the Sales Income account field wrapper.
- Removed the `data-pn-weight-account-wrap` marker from both account field wrappers, so the existing Weight Loss / Excess JavaScript can no longer hide them.
- Removed the now-unused page-scoped CSS rule for hidden weight-account wrappers.

## Existing functionality preserved
- COGS and Sales Income account options continue to come from `ProductSettingLookupService` for the active business.
- Existing selected account IDs continue to load in Edit Category.
- Existing save/update logic continues to persist `cogs_account_id` and `sales_income_account_id`.
- Weight Loss / Excess Applicable remains an independent checkbox.
- No route, controller, database schema, migration, SQL, or permission changes are required.

## Deployment
Replace the ProductsNew module with the corrected parcel and run:

```bash
php artisan optimize:clear
```

Then open **Products New > Stock Center > Categories > Add Category** and confirm both account dropdowns are visible immediately.
