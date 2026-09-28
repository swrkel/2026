# ZIP 276 – Customers Notes, Audit & Timeline Foundation

## Purpose
Adds Customers-owned foundation classes for future customer notes, attachments, activity logs and timeline features.

## Added
- `Modules/Customers/Entities/CustomerNote.php`
- `Modules/Customers/Entities/CustomerActivity.php`
- `Modules/Customers/Entities/CustomerAttachment.php`
- `Modules/Customers/Services/CustomerAuditService.php`
- `Modules/Customers/Services/CustomerTimelineService.php`

## Safety
No current customer screen is forced to use these new classes immediately. The services check whether optional tables exist before reading/writing, so this package should not break production if the new audit tables have not been created yet.

## Notes
This keeps future customer notes/timeline work inside the Customers module instead of depending on legacy Contact notes/history code.
