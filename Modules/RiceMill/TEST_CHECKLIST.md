# Rice Mill Module acceptance checklist
- Login to Tenant A / Business A; create settings, variety, mill and rice product.
- Verify Tenant B cannot see Tenant A records.
- Create paddy purchase and approve it; verify a pending/processed Finance outbox row is created.
- Receive paddy with gross/tare; verify net weight, lot number and paddy stock ledger.
- Try consuming more than lot balance; transaction must fail without partial stock posting.
- Complete milling batch with rice + by-products; verify input stock reduces, finished rice increases, by-product movement posts, yield/loss/cost are calculated.
- Pack rice; verify packing quantity is recorded without duplicating physical stock.
- Create dispatch draft; verify stock does not reduce until approval.
- Approve dispatch; verify stock reduces once and Finance outbox entry is created.
- Verify paddy and finished-stock adjustments require permission.
- Verify reports respect business scope and date range.
- Verify a normal user without Rice Mill permission gets HTTP 403.
- Verify Super Admin can access the module even where normal role permissions are absent.


## v19 Location Boundary / Mills 403
- [ ] Settings > Mills shows a Location dropdown, not a numeric Location ID input.
- [ ] Dropdown contains only active locations for the current tenant/business that the logged-in user may access.
- [ ] New Mill defaults to the first permitted location.
- [ ] Add/Update Mill succeeds for an allowed location.
- [ ] Crafted/unauthorized location_id is rejected server-side.
- [ ] Mills outside the user's permitted locations are not listed.
- [ ] Mill table/view displays the Location Name.
- [ ] Purchase/Receipt/Production/Packing/Dispatch location dropdowns continue to load, now through the same location access boundary.

## v24 tenant/business context + reports audit
- [ ] Every `/rice-mill/*` route shows `tenant.context` and `rcm.context` before controller execution.
- [ ] Dashboard opens on tenant DB and current Business UID only.
- [ ] Settings / Mills Add/Edit shows all active locations belonging to the current tenant + business.
- [ ] Paddy Purchase form Location/Store options come from the current tenant + business operational boundary.
- [ ] Paddy Receipt form Location/Store options come from the current tenant + business operational boundary.
- [ ] Production form Location/Store options come from the current tenant + business operational boundary.
- [ ] Packing form Location/Store options come from the current tenant + business operational boundary.
- [ ] Dispatch form Location/Store options come from the current tenant + business operational boundary.
- [ ] Reports tabs preserve Location and Store selections.
- [ ] Reports cannot use a Location/Store outside the current operational boundary.
- [ ] Finished Stock filtered quantity is derived from current tenant/business stock movements.


## v25 consolidated report filters
- [ ] Every Rice Mill report Location dropdown contains `All`.
- [ ] Every Rice Mill report Store dropdown contains `All`.
- [ ] Default All + All returns Tenant + Business consolidated data.
- [ ] Selecting one Location narrows all report tabs to that Location.
- [ ] Store dropdown shows All plus stores for the selected Location.
- [ ] Selecting All Store under one Location keeps the Location filter and consolidates its stores.
- [ ] Switching report tabs preserves Location/Store selections.
- [ ] Specific Location/Store IDs outside the active Tenant/Business are rejected.

## IS2289-2 follow-up - Standard Purchase payment + deferred Purchase Tax
- [ ] Purchase Order shows Purchase Tax dropdown from business tax rates.
- [ ] Selecting a Purchase Tax does not change the Purchase Order total.
- [ ] `rcm_paddy_purchases.purchase_tax_id` and rate are saved; `purchase_tax_amount` remains 0.0000.
- [ ] No separate tax ledger/account transaction is created at PO stage.
- [ ] Cash/Card/Bank/Cheque payment creates standard `transaction_payments` linkage.
- [ ] Linked transaction is status `ordered` and sub_type `rice_mill_purchase_order`.
- [ ] Credit Purchase (Due) uses the mapped Paddy Current Liabilities account and remains Due.
- [ ] `rcm_purchase_payments.transaction_id` links to the standard transaction.
- [ ] `rcm_purchase_payments.transaction_payment_id` links to the standard payment for paid methods.
- [ ] Rice Mill approval does not create a duplicate supplier-payable Finance outbox event.
- [ ] Cheque number is retained through the standard purchase payment path.
