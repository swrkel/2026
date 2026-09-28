# Leads-New Stage 14 RC6 Release Audit

This parcel adds the final release-readiness layer for the standalone Leads-New module.

## Included

- `LeadsNewReleaseReadinessService`
- `leads-new:release-audit` console command
- Standalone dependency scan
- Route availability scan
- Required module file checklist
- Production index recommendations
- RC6 deployment checklist

## Deployment

Replace the included files under `Modules/LeadsNew` only.

## Optional verification command

```bash
php artisan leads-new:release-audit
```

or JSON:

```bash
php artisan leads-new:release-audit --json
```

## Important

This parcel is designed not to change the existing Leads module and not to modify unrelated working ERP functionality.
