# AutoService Stage 028 - Customer Documents, Approvals and Alerts

## Added
- Customer portal document/photo upload for service evidence, payment slips, vehicle photos, damage photos and documents.
- Secure customer document viewing route instead of exposing raw storage asset paths.
- Timeline entry when customers upload documents.
- Notification log entry for service advisor follow-up.
- Tenant SQL upgrade with customer upload metadata fields.

## Changed
- Customer-shared document table now uses portal authorization before display/download.
- Customer portal keeps uploaded documents business-safe and vehicle/job-scoped.

## SQL
- `SQL/29_AUTOSERVICE_STAGE028_CUSTOMER_DOCUMENTS_APPROVALS_ALERTS.sql`
- `SQL/MASTER_AUTOSERVICE_SQL_STAGE028_CUSTOMER_DOCUMENTS.sql`
