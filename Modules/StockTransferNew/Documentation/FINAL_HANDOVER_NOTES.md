# Stock Transfer-New Final Handover Notes

The module is designed as a standalone Laravel module with its own module namespace and assets.
It should not own product master creation/editing. Product information must be read through the existing standalone Products module bridge.

Recommended testing:
- Same business, different locations/stores.
- Cross-location transfer.
- Partial dispatch and partial receive.
- Approval return/reject.
- Variance reconciliation.
- Barcode/QR scan flow.
- Reports and CSV exports.
- Tenant isolation using two different tenant databases.
