# ATN-055 to ATN-062 — 8 Section Parcel

## ATN-055 — Advanced Reissue
- Reissue quote creation
- Fare, tax, penalty and service-fee differences
- Automatic quote numbering
- Detailed quote payload

## ATN-056 — Ticket Exchange
- Original and replacement ticket linkage
- Exchange-value calculation
- Automatic status updates
- Exchange numbering

## ATN-057 — Void & Refund Automation
- Airline-specific refund rules
- Fixed and percentage penalties
- Priority-based rule resolution
- Automatic refundable amount calculation

## ATN-058 — ADM / ACM
- Agency Debit Memo records
- Agency Credit Memo records
- Airline and ticket linkage
- Dispute and due-date fields
- Automatic numbering

## ATN-059 — EMD Management
- Electronic Miscellaneous Documents
- Reservation, ticket and passenger linkage
- Ancillary service types
- Automatic EMD numbering

## ATN-060 — Corporate Travel Policy
- Policy rules in structured JSON
- Maximum fare
- Allowed cabin classes
- Advance-purchase controls
- Policy-compliance evaluation

## ATN-061 — Approval Workflow
- Event-based approval matrices
- Approval levels
- Amount thresholds
- User/role approvers
- Location-aware resolution

## ATN-062 — Exception Controls
- Operational exception register
- Severity, reference and context
- Detection and resolution audit
- Exception-resolution service

## Installation
1. Merge over ATN-001 through ATN-054.
2. Include `Routes/post-ticket-advanced.php` inside the authenticated module route group.
3. Run the migration or `MASTER_ATN_055_TO_062_8_SECTION_PARCEL.sql`.
4. Assign the included permissions.
5. Publish the CSS/JS and merge the language file.
6. Run `php artisan optimize:clear`.
