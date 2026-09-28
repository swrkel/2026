# Finance Account Numbers - Super Admin Source of Truth (20 Sep 2026)

Finance / List Accounts now uses only:

Super Admin -> Super Admin Settings -> Default Accounts -> Account Numbers

## Behaviour

- `account_numbers.prefix` + `account_numbers.account_number` is the configured prefix and starting number.
- The selected Finance Account Type/Sub Type is mapped primarily through `account_types.default_account_type_id`.
- A subtype's own setup is used first; parent setup is only a compatibility fallback.
- Add Account auto-loads the next number from that series.
- Edit Account keeps the current number, but changing Account Type/Sub Type loads the next number from the new series.
- Account Number is editable in both Add and Edit forms.
- Server-side duplicate checks apply within the business and include soft-deleted accounts.
- Saving Account Numbers in Super Admin immediately renumbers existing mapped accounts for every business.
- The old Finance-owned `finance_account_number_settings` table is no longer used as a numbering source. It is intentionally left in place for backward-safe deployment; it may be removed later after all installations are confirmed upgraded.

## Initial backfill of existing accounts

After deploying both the Finance and Superadmin parcels, run on the tenant database/context:

```bash
php artisan finance:sync-superadmin-account-numbers
```

For one business only:

```bash
php artisan finance:sync-superadmin-account-numbers --business_id=123
```

The command first calculates the complete configured target map. It rejects overlapping series/collisions before replacing account numbers, and uses temporary values while renumbering so unique indexes are safe.
