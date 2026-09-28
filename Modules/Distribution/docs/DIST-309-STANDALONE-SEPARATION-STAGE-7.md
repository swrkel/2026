# DIST-309 - Distribution Standalone Separation Stage 7

Scope: utility/view dependency cleanup without changing working business logic.

Changes:
- Added Distribution-owned BusinessUtil and ContactUtil compatibility wrappers.
- Centralised customer quick-create include through `distribution::contacts.quick_create`.
- Updated Sales Order and Invoice pages to call the Distribution-owned include instead of direct `contact.create` calls.
- Added a standalone dependency scanner support class for future final audits.
- Added safe cleanup tool to move an accidentally bundled `Distribution/Ezyboat` copy to backup instead of deleting it.

Notes:
- The customer/contact modal is still compatibility-backed until the later customer/contact separation stage.
- No existing transaction, sales order, invoice, or payment business logic was changed.
