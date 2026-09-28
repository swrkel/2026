# Communication Hub Stage 019 - Operations Support & Final Sign-Off

This package continues from Stage 018 and adds the final operational support layer for production rollout.

## Added

- Operations Support Centre page
- Tenant rollout checklist
- Required table status panel
- Queue/provider summary panels
- Deployment sign-off table
- Rollout notes table
- Permission keys for operations support
- Final master SQL for Stage 019

## Files changed / added

- `Http/Controllers/CommunicationHubOperationsController.php`
- `Resources/views/operations/support.blade.php`
- `Routes/tenant.php`
- `Config/menu.php`
- `Database/SQL/20_OPERATIONS_SUPPORT_AND_FINAL_SIGNOFF.sql`
- `Database/SQL/MASTER_COMMUNICATION_HUB_SQL_STAGE_019_OPERATIONS_SUPPORT.sql`

## Tenant SQL Deployment

Run only this SQL if all previous Communication Hub stages are already applied:

```sql
Database/SQL/20_OPERATIONS_SUPPORT_AND_FINAL_SIGNOFF.sql
```

Run this master SQL if deploying Communication Hub to a fresh tenant database:

```sql
Database/SQL/MASTER_COMMUNICATION_HUB_SQL_STAGE_019_OPERATIONS_SUPPORT.sql
```

## Server Upload Verification

1. Replace the `Modules/CommunicationHub` folder with the package folder.
2. Run SQL in each tenant database.
3. Clear Laravel cache:
   - route cache
   - config cache
   - view cache
4. Login to a tenant business user.
5. Open:
   - Communication Hub → Readiness Check
   - Communication Hub → Diagnostics Centre
   - Communication Hub → Production QA
   - Communication Hub → Operations Support
6. Verify no missing tables.
7. Send one test message per enabled channel.
8. Verify report/audit rows are created.
9. Enable automation rules only after manual tests pass.

## Final Sign-Off Rule

Do not mark Communication Hub fully live for a tenant until these are confirmed:

- Menu and routes are visible.
- Business-wise data isolation is confirmed.
- Provider credentials are configured.
- Queue processing is working.
- Failed message retry is working.
- Reports and audit logs are updating.
- Automation/workflow rules are tested with a small sample.

