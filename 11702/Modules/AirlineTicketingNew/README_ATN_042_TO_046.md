# ATN-042 to ATN-046 Production Parcel

## ATN-042 — Backup & Recovery
- Business-scoped JSON backup
- Storage disk support
- Backup history and failure logging

## ATN-043 — Deployment Utilities
- Required-table checks
- Writable-path validation
- Cache clearing
- Deployment readiness CLI command

## ATN-044 — Scheduler & Queue Operations
- Daily operational queue rebuild
- Daily analytics snapshot
- Scheduled-task logs
- Daily CLI command

## ATN-045 — Production Monitoring
- Open reservations
- Pending workflows
- Queued notifications
- Open operational tasks
- Historical performance metrics

## ATN-046 — Regression & Certification
- Required table validation
- Duplicate ticket number detection
- Unbalanced journal detection
- Negative invoice due detection
- Orphan ticket-segment detection
- Production certification CLI command

## Installation
1. Merge over ATN-001 through ATN-041.
2. Include `Routes/production.php` inside the authenticated module route group.
3. Register the included console commands in the module service provider.
4. Run the migration or `MASTER_ATN_042_TO_046_PRODUCTION_PARCEL.sql`.
5. Assign the included permissions.
6. Publish the production CSS/JS.
7. Run `php artisan optimize:clear`.
