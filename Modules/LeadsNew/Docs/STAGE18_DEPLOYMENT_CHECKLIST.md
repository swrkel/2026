# Leads-New Stage 18 RC9 Deployment Checklist

## Server checks
- Module folder exists: `Modules/LeadsNew`.
- `module.json` is present.
- Service provider is loaded by the module loader.
- Routes are visible after route cache refresh.
- Permissions are seeded.
- Sidebar permission is enabled only for the selected business.

## Page checks
- `/leads-new`
- `/leads-new/dashboard`
- `/leads-new/leads`
- `/leads-new/leads/create`
- `/leads-new/settings`
- `/leads-new/reports`
- `/leads-new/release/checklist`

## Functional checks
- Add Lead.
- Edit Lead.
- View Lead.
- Archive/Restore Lead.
- Follow-up create/update.
- Report filters.
- Export buttons.
- Permission blocking for unauthorised user.
- Tenant A cannot view Tenant B data.
