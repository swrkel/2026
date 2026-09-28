# Auto Service v1.0 RC5

This release continues the end-to-end workflow work started in RC4.

## Added
- Quality Control queue.
- QC checklist save flow.
- Delivery queue.
- Delivery confirmation flow.
- Job progress tracking columns.
- QC and delivery timeline entries.

## Notes
- Central Vehicle Registry migrations remain separate from tenant migrations.
- Tenant operational tables remain in tenant databases.
- Workshop privacy rule is unchanged: other workshops only see technical vehicle history, never prices, invoice values, previous workshop contact details, or internal notes.
