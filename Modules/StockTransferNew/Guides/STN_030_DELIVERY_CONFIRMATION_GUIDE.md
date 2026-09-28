# STN_030 - Delivery Confirmation & Proof of Delivery

## Added
- Delivery confirmation register
- Proof-of-delivery fields: receiver, mobile, signature data, photo reference
- Damage/shortage notes per product
- Tenant/business/location/store filters
- Permission SQL with duplicate-safe inserts

## Notes
- Product IDs continue to refer to the standalone Products module.
- This parcel does not duplicate product master, finance, or POS functionality.
- Run tenant SQL in tenant databases where StockTransferNew is enabled.
