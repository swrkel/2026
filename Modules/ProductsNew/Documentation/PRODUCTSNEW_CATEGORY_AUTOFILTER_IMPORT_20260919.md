# Products New Categories — Auto Filters & Category Import — 19 Sep 2026

## Scope
Page: `/products-new/categories`

## Changes
- Removed the Apply and Reset filter buttons.
- Category Level and VAT Exempt filters now submit automatically on change.
- Search auto-filters after a 350 ms debounce and submits immediately on Enter.
- Added **Import Product Categories** next to **Add Category**.
- Added an import modal containing the complete spreadsheet column guide and accepted values.
- Added a downloadable CSV sample template.
- Added category import support for `.csv`, `.txt` and `.xlsx` files (maximum 10 MB).
- Existing categories/subcategories with the same name under the same parent are skipped to prevent duplicate rows.
- Parent categories contained in the same spreadsheet are imported before subcategories.
- Import errors report the exact spreadsheet row and the whole import is transactional, so invalid rows do not leave a partial import.
- Imports are restricted to the active business and existing account references are resolved within that business.

## Required import headings
- `category_name`
- `type` (`Category` or `Subcategory`)

All other columns are optional and are documented in the import popup.

## Database
No migration or SQL change is required.
