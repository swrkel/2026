# IS2316 – MPCS – 23 Sep 2026

This parcel is based on `MPCS(20260923-163155).zip` and addresses only the items listed in IS2316.

## 1. F20 Form – report/print tools and product-column filtering

- Added the system-standard DataTables report controls: Column Visibility, Export CSV, Export Excel, PDF, Print, Email, WhatsApp, Global Search, and rows-per-page selection.
- The selected F20 date and form type are preserved in the report URL, including links opened from Email/WhatsApp.
- Print/PDF/CSV/Excel use the active search and the user's visible columns.
- Product columns with no transactions for the selected date/filter are hidden and cannot be reintroduced into print/export through Column Visibility.
- Removed the old print behavior that forced every configured product column back into print preview.
- Removed the obsolete manual 100-row pagination code so DataTables is the single pagination/search/export engine.

Files:
- `Resources/views/forms/20Form/20_form.blade.php`
- `Resources/views/forms/20Form/F20_form.blade.php`

## 2. F20 Form – CDS print preview

- Rebuilt both live print preview and saved-form print preview around a clean A4 landscape report layout.
- Preserves Business, Location, Form No, Date and report identity instead of hiding the print header.
- Increased report grid type size and row height and reduced excess stretching/empty print-space behavior.
- Application chrome/tabs remain excluded from print.

Files:
- `Resources/views/forms/F20_CDS/index.blade.php`
- `Resources/views/forms/F20_CDS/print.blade.php`

## 3. F9C Credit details scrolling

- Added a dedicated, dynamically sized vertical scrolling viewport for the F9C Credit tab content.
- The available height is recalculated on load, resize and tab activation.
- Horizontal table scrolling remains inside the responsive table container.
- Print mode removes the viewport height limit so printed content is not clipped.

File:
- `Resources/views/forms/form_9ccr.blade.php`

## 4. Print font-size improvements

Print typography was increased for the requested MPCS forms, with dense landscape reports increased conservatively to preserve their established page layouts.

Files:
- `Resources/views/forms/21CForm/print_f21c_form.blade.php`
- `Resources/views/forms/form_9a.blade.php`
- `Resources/views/forms/partials/9a_form.blade.php`
- `Resources/views/forms/f9c_cash.blade.php`
- `Resources/views/forms/form_9ccr.blade.php`
- `Resources/views/forms/partials/f15_daily_report_print.blade.php`
- `Resources/views/forms/F14_form.blade.php`
- `Resources/views/forms/F16A.blade.php`
- `Resources/views/forms/partials/print_f16_form.blade.php`
- `Resources/views/forms/F18/print.blade.php`

## Database

No SQL or database schema/data change is required for IS2316.

## Deployment

Replace the MPCS module with the FULL parcel, then run:

```bash
php artisan optimize:clear
php artisan view:clear
```

Hard-refresh the browser (`Ctrl+F5`) before testing print previews and DataTables buttons.

## Validation performed

- PHP syntax lint completed across all PHP/Blade-PHP files in the module.
- No unresolved merge markers found.
- ZIP integrity is checked when the delivery parcels are built.
