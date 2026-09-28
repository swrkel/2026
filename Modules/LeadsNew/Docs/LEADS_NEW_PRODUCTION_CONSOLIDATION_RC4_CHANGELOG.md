# Leads-New Production Consolidation RC4

## Scope
- Continued Customers-style architecture consolidation.
- Continued Communication Hub style UI cleanup.
- Removed raw setup-table names from user-facing create page.
- Fixed remaining raw language key on Lead form date field.
- Added RC4 deployment/version marker.
- Added RC4 and MASTER SQL packages with numbered scripts.

## Modified runtime files
- `Resources/views/leads/create.blade.php`
- `Resources/views/leads/partials/form.blade.php`
- `Resources/lang/en/messages.php`
- `Release/RC4_VERSION.txt`

## Deployment
Replace only `Modules/LeadsNew`, run SQL on the active tenant database, then open `/clear`.
