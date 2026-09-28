# Universal User ID rollout (safe for running databases)

This change **does not replace or renumber `users.id`**. It adds `users.global_user_id` as an immutable cross-database identity.

## Format

- Column: `global_user_id`
- Type: `CHAR(36)`
- New values: UUID v4
- Per-database unique index: `users_global_user_id_unique`
- Existing tenant UID/business identity remains unchanged.

UUID v4 is used instead of introducing a second tenant/business ID scheme. The current codebase already uses 36-character UUID identities for business identity, and UUID support is available on the installed Laravel version.

## Why phase 1 stays nullable

The system is live and has multiple databases. Making the column `NOT NULL` before every current tenant has been backfilled could break user creation or migration on a partially upgraded tenant. The application fills the value automatically after the column exists, and the reconciliation command backfills existing rows. After the final audit reports zero missing IDs, `NOT NULL` can be considered as a later hardening step.

## First rollout - do this in order

1. Upload the changed files.
2. Clear Laravel caches:

   `php artisan optimize:clear`

3. Read-only audit (safe during working hours):

   `php artisan users:identity-audit`

4. Dry-run the whole estate:

   `php artisan users:reconcile-identity --all --dry-run`

5. Review the output. It must show only schema additions and MINT actions for users that do not yet have an ID. If unexpected REMINT actions appear, investigate before continuing.
6. Apply:

   `php artisan users:reconcile-identity --all`

7. Final read-only verification:

   `php artisan users:identity-audit`

The final audit should show:

- `global_user_id` column present in every users database
- zero missing IDs
- zero local duplicate groups
- unique index present
- zero estate collision groups

## After importing/restoring one tenant database

Run the existing business identity reconciliation as usual, then run the user identity command against the imported database before allowing users to work in it:

`php artisan users:reconcile-identity nivasa_TENANT_ID --dry-run`

Then, after checking the output:

`php artisan users:reconcile-identity nivasa_TENANT_ID`

If the imported database is a copy of another tenant, copied user IDs are automatically reminted because the same global_user_id already exists elsewhere. If it is a restore of the same tenant and no duplicate exists elsewhere, its existing user IDs are preserved.

## Safety guarantees

The reconciliation code never deletes users and never changes:

- `users.id`
- `business_id`
- username/password
- roles or permissions
- location access
- any foreign-key user references

Normal application saves cannot change an existing `global_user_id`. Eloquent replication/new-user creation always receives a fresh UUID.
