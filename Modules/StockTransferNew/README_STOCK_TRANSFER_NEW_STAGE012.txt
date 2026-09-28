StockTransferNew STN_012 - Audit & Security Hardening

Added:
- Audit log entity and AuditTrailService.
- Transfer lock entity and TransferSecurityService.
- Duplicate request/idempotency protection table and service method.
- Integrity check service for duplicate transfer numbers, invalid status and negative qty.
- Audit/Security controller pages: audit log, locks, duplicate keys, integrity check.
- New routes under /stock-transfer-new/audit-security.
- Separate tenant SQL for create/insert permission queries.

Notes:
- This parcel is standalone inside StockTransferNew.
- Product master is still not duplicated; existing Products module remains the source of product data.
- Services are designed so previous workflow controllers can call assertEditable(), lock(), registerDuplicateKey(), and audit transfer events as needed.
