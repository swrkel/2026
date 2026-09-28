# MEMNEW_017 — Member Full Name Fields + Responsive 4-Column Form

Date: 24 Sep 2026

## Changes
- Added `Full Name` (`mn_members.full_name`).
- Added `Full Name in Second Language` (`mn_members.full_name_second_language`).
- Both fields appear immediately after Last Name in the shared Add/Edit Member form.
- Member Add/Edit form uses 4 columns on desktop, 2 columns on tablets, and 1 column on mobiles.
- Note is the only full-width form field.
- Full Name fields are searchable from the Members page.
- View Member shows Full Name and the second-language full name.
- Existing data is preserved; both new database columns are nullable.

## Existing databases
Run the module migration:

```bash
php artisan migrate --path=Modules/MembershipNew/database/migrations --force
```

Or import:
`SQL/MembershipNew/MembershipNew_MEMBER_FULL_NAMES_20260924.sql`

Then clear caches:

```bash
php artisan optimize:clear
```
