# IS2181 — F9C Cash form Ajax warning

**Issue:** MPCS / F9C Form / F9C Cash form showed a DataTables Ajax warning after selecting a date.

## Root cause

The F9C Cash calculation uses a shared settlement-key SQL expression containing `sale_settlement.id`.
The normal sales query already joins `settlements as sale_settlement`, but the standalone POS-credit
query reused the same expression without that join. MySQL therefore returned an unknown-column error,
and DataTables displayed its generic Ajax warning.

## Fix

One runtime file changed:

`Modules/MPCS/Http/Controllers/MPCSController.php`

The standalone POS-credit query now uses the same `settlements as sale_settlement` left join as the
sales query before evaluating the shared settlement key.

No calculation formula, date logic, totals, pagination, permissions, routes, views, or database schema
was changed.

## Deployment

```bash
cd /home/nivasa/public_html/Modules
unzip -o /path/to/MPCS_IS2181_F9C_Cash_Fixed.zip
cd /home/nivasa/public_html
php artisan optimize:clear
```

No SQL is required.

## Checks

- Select a date in F9C Cash: no DataTables Ajax warning.
- Table loads for dates with and without standalone POS credit sales.
- Existing F9C credit deductions and cash totals continue using the same settlement-key logic.
