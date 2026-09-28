# S756 - Product Balance Qty consistency fix - 17 Sep 2026

## Reported issue

On **Stock Adjustment New > Create Stock Adjustment**, `System Qty` could differ from the balance shown by Products New Stock History, Stock Centre and Product Transaction Report. The reported example was **HYDROLIC 68 LOOS / SV050100**: Stock Adjustment New showed **20**, while the other stock pages showed **-150**.

## Root cause

`ProductBridgeService::attachCurrentStock()` switched from `variation_location_details` to `variation_store_details` whenever a Store was selected. The Products New stock pages use the shared location stock balance (`variation_location_details`) as their current-stock source. Historical/imported tenant data can contain a different store summary, so the two pages could show different balances for the same selected location.

A second compatibility difference was also removed: Products New treats a `single` product as one logical stock identity and sums all of its internal variations. Stock Adjustment New previously preferred one selected variation whenever a variation id existed.

## Fix

- `System Qty` now always reads the canonical `variation_location_details` balance.
- A selected Location narrows the balance to that location; no Location keeps the all-location total.
- The selected Store is still validated, saved, used by batch selection where relevant, and used by posting. It no longer changes the balance source shown in `System Qty`.
- Single products sum all internal variations, matching Products New Stock Centre.
- Variable/combo products continue using their selected variation balance.
- Tenant schemas where `variation_location_details` does not contain `product_id` are supported by resolving product ownership through `variations`.
- No historical stock/accounting rows are rewritten by this fix.
- No SQL or migration is required.
