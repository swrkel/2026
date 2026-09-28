# Products New - Duplicate Product Guard + 2T Loose Oil Stock Correction

Date: 14 Sep 2026

## Current stock correction

A guarded one-time SQL is included at:

`Database/SQL/ProductsNew_2T_Loose_Oil_Qty_58_800_SAFE_FIX_20260914.sql`

It corrects only `2T Loose Oil - 210Ltrs` when exactly one product/location has the expected combined core balance `-6541.200`. The required corrected balance is `58.800`. The adjustment is made against the real `variation_location_details` balance and an inventory adjustment audit row is written.

## Permanent duplicate prevention

1. Manual Add Product now blocks duplicate SKU, duplicate barcode, and duplicate Single-product logical name within the same business.
2. A blank/manual SKU cannot bypass the check by receiving a fresh automatic SKU.
3. Edit cannot rename or re-barcode one product into another product's identity.
4. CSV Import validates duplicate SKU, barcode, and Single-product name both inside the file and against the existing product master.
5. The same service-layer guard remains active again at commit time, protecting against a product being created after CSV validation but before commit.
6. Default-variation creation is serialised per product so simultaneous saves cannot auto-create duplicate dummy variations.
7. Location/store stock row creation is serialised per variation. If exact duplicate physical stock rows already exist for a variation/location or variation/store, the next stock movement consolidates them into one canonical row while preserving the combined quantity.
8. The previous Stock Centre logical-row aggregation fix remains included.

No global unique index was added to the shared core `products` table, avoiding deployment failure on older tenant databases that may already contain historical duplicates. Protection is applied inside Products New's write paths instead.
