# Task 8046 - Customer Reference

Standalone "List Customer Reference" feature for the Customers module.
Everything below is Customers-module owned: its own entity, services,
controllers, requests, routes, views and runtime script. No Contact-module
controllers or views are involved, consistent with the separation work from
CUS_SEP_005 onwards.

---

## 1. What was built

| Spec item | Where it lives |
|---|---|
| "List Customer Reference" page | `Resources/views/customer_references/index.blade.php` |
| "Add Customer Reference" button + popup | `partials/add_modal.blade.php` |
| Multi-row staging, save in one go | `customer-reference.js` + `CustomerReferenceService::createMany()` |
| Auto-generated QR code | `CustomerReferenceQrService` |
| Action column: View / Active-Inactive / QR Action | `partials/row_actions.blade.php` |
| QR popup: Print, PDF, WhatsApp, Email | `CustomerReferenceQrController` |
| Seven list filters | `CustomerReferenceService::applyFilters()` |
| "Type & Auto Filter" on all dropdowns | `select2` classes + `initSelect2()` |

### Files added

```
Database/Migrations/2026_08_28_000001_create_customer_qr_references_table.php
Database/RawSQL/2026_08_28_customer_qr_reference/
    01_INSPECT_TENANT.sql
    02_CREATE_CUSTOMER_QR_REFERENCES.sql
    README.txt
Entities/CustomerReference.php
Entities/CustomerReferenceFuelType.php
Services/CustomerReferenceService.php
Services/CustomerReferenceFuelTypeService.php
Services/CustomerReferenceQrService.php
Http/Controllers/CustomerReferenceController.php
Http/Controllers/CustomerReferenceQrController.php
Http/Requests/StoreCustomerReferenceRequest.php
Http/Requests/UpdateCustomerReferenceRequest.php
Resources/views/customer_references/index.blade.php
Resources/views/customer_references/qr_print.blade.php
Resources/views/customer_references/qr_email.blade.php
Resources/views/customer_references/partials/add_modal.blade.php
Resources/views/customer_references/partials/generic_modal.blade.php
Resources/views/customer_references/partials/qr_modal.blade.php
Resources/views/customer_references/partials/row_actions.blade.php
Resources/views/customer_references/partials/view_modal.blade.php
Resources/assets/js/customer-reference.js
```

### Files modified

| File | Change |
|---|---|
| `Routes/web.php` | New `customer-references` route block |
| `Config/config.php` | `fuel_category_id`, `fuel_category_names`, `qr_driver`, `pdf_driver` |
| `Config/module_permissions.php` | New `customers_customer_references` page entry |
| `Services/CustomerModulePageRegistry.php` | Existing placeholder repointed at the new page |
| `Resources/lang/en/lang.php` | Task 8046 strings |

---

## 2. The table this feature uses

**`customer_qr_references`** — a new, self-contained table.

It is **not** `customer_references`. That table already exists and is shared by
Petro, PetroPD, PetroDirect, PetroGeneral, Vat, SettlementSW, PumperDashboard,
EVCharging, EzyInvoice, DailyCollectionSW, ReportsCustomized and app-level
controllers. It backs the legacy "Vehicle No" page
(`app/Http/Controllers/CustomerReferenceController.php`) and has a completely
different shape: `contact_id`, `reference`, `opening_balance`, `barcode_src`.

Task 8046 does not read, write, alter or drop it.

### What this means in practice

References created on the new page live only in `customer_qr_references`. They
do **not** appear in the settlement screens, daily vouchers, VAT invoices or
payment dropdowns that read the shared table, and the legacy Vehicle No page
does not show them.

The two features run side by side without interfering. If the intention is for
8046 to eventually replace the legacy screen, that is a separate piece of work
— retiring the old page and migrating its rows across — and worth confirming
with whoever wrote the spec.

---

## 2b. Installation

Tenants here sit at different schema levels, so the **raw SQL is the
recommended path**: it lets each tenant be inspected before anything is
created. The migration does the same job and is provided for any tenant where
running migrations is preferred. Use one or the other.

### Per tenant, via phpMyAdmin

```
1. Database/RawSQL/2026_08_28_customer_qr_reference/01_INSPECT_TENANT.sql
   Read-only. Reports whether the tenant is already installed, whether its
   dependencies exist, and whether a Fuel product category is present.
   Record the legacy customer_references row count it prints.

2. Database/RawSQL/2026_08_28_customer_qr_reference/02_CREATE_CUSTOMER_QR_REFERENCES.sql
   Creates the table, then verifies. Confirm the legacy row count it prints
   at the end matches step 1.
```

Both are safe to re-run.

### Or via migration

```bash
php artisan module:migrate Customers
```

### Undoing

There is **no DROP script in this package**, deliberately. An earlier draft
shipped one named after the shared table, which was a mistake — a rollback
script pointed at a table a dozen modules depend on is a hazard sitting in the
repository, whether or not anyone runs it.

To remove the feature from a tenant:

```sql
DROP TABLE IF EXISTS `customer_qr_references`;
```

Nothing outside this feature reads that table.

### Permissions and menu

The page uses the module's existing abilities — `view`, `create`, `edit`,
`delete`. No new permission names, so existing roles keep working.

`CustomerModulePageRegistry` already carried a
`customers_customer_reference_tab_page` switch labelled "Vehicle No" pointing
at the master-data page. It now points at the new page. The **key is unchanged
on purpose**: it is already stored in every tenant's `package_details`, and
renaming it would silently switch the page off for every business that had
enabled it.

---

## 3. Two decisions that need your confirmation

### 3.1 How the Fuel category is identified

The spec says the Fuel Type dropdown shows *"All the Product subcategories …
linked with the Fuel product Category"*. Product categories live in the shared
`categories` table, where a sub-category is a row whose `parent_id` points at
its parent. **There is no column marking one of them as the fuel category**, so
it has to be identified some other way.

`CustomerReferenceFuelTypeService` resolves it in three steps, first match wins:

1. `config('customers.fuel_category_id')` — an explicit id.
2. `config('customers.fuel_category_names')` — case-insensitive name match.
   Defaults to `Fuel`, `Fuels`, `Fuel Products`.
3. Nothing matched — the dropdown contains only "Not Known", and the page says
   so.

Step 3 is deliberate, not an error state. A tenant that does not sell fuel should
still be able to record vehicle references, and the page must not break because a
category is missing.

**If your tenants name the category something else, set `fuel_category_id`.**
Tell me the real name or id and I will change the default.

### 3.2 PDF library

**QR is settled.** `milon/barcode` is confirmed installed — the legacy Vehicle
No page uses `DNS2D` with `'QRCODE'` — so `qr_driver` is pinned to
`milon-barcode` in the config rather than left on auto-detect. The renderer
calls `getBarcodePNG(...)`, the same call the legacy page uses, because that
exact path is known to work on this install.

**PDF is still detected at runtime**: `barryvdh/laravel-dompdf` (both facade
paths), then Snappy. If neither is present the PDF button opens the print view,
where the browser's "Save as PDF" produces the same document. Tell me which PDF
package you have and I will pin `pdf_driver` too.

---

## 4. Design notes

**The QR payload is stored; the image is not.** A QR gets printed and stuck on a
vehicle, and once printed what it says is fixed. Storing `qr_payload` at creation
time means the sticker and the database keep agreeing even after a customer is
renamed. Storing a rendered PNG would bloat the row and pin us to one size;
re-deriving the payload on every render would let a rename silently invalidate
every sticker already in the field. The payload *is* regenerated on edit, because
an edited row should produce a new code.

Payload format, per the spec:

```
Customer Name: Acme Transport (Pvt) Ltd
Vehicle No: WP-CAB-1234
Fuel Type: Petrol 92 Octane
```

The `Fuel Type` line appears only for vehicles, and the second line reads
`Reference:` rather than `Vehicle No:` when the reference is not a vehicle.
Plain text, so a phone's built-in camera shows something useful without a
companion app.

**"Not Known" is a NULL, not a row.** The system default is stored as
`fuel_type_id = NULL` and posted as the non-numeric sentinel `not_known`, so it
cannot collide with a category id and cannot be renamed, deactivated or deleted
by a user editing product categories. `fuel_type_name` keeps a name snapshot so a
later category rename does not change what an already-printed code says.

**Multi-row add is one transaction.** The popup stages rows in memory; Save
commits them together. A partial save would leave the user unsure which of their
rows made it, with no way to tell from the list.

**Post-Add reset follows the spec exactly** — the customer stays selected, every
other field resets, and the date/time jumps to the current moment.

**Fuel Type is cleared server-side for non-vehicles.** The field is hidden in the
UI when "Reference is a Vehicle" is No, but the service also nulls it on write,
so a stale hidden field cannot attach a fuel type to a non-vehicle reference.

**Tenant scoping is enforced on every read and write.** `findOrFail` is scoped by
`business_id`, and the store/update requests check that `customer_id` belongs to
this business and is a customer-type contact — so a crafted request cannot attach
a reference to another tenant's contact, or to a supplier.

**Schema tolerance.** Every `contacts`, `users` and `categories` column is checked
with `Schema::hasColumn` before use, and the customer/user display names are built
as dynamic SQL expressions. The module already installs onto tenants at different
schema versions, and a hard reference to a column that may not exist would take
the whole page down.

**Routes sit before the `/{id}` catch-all** in `web.php`. That catch-all matches
any single segment, so a block placed after it would never be reached — the same
reason the S350 parity routes sit where they do. Every id-bound route is
constrained with `whereNumber` so `/data` and `/runtime-script` cannot be
swallowed by the `{reference}` placeholder.

**WhatsApp opens a `wa.me` link** rather than sending server-side. Sending
directly needs a WhatsApp Business API account and per-tenant credentials, which
this module does not have and cannot assume. The customer's WhatsApp number is
pre-filled from the contact record and stays editable.

---

## 5. Testing checklist

**Setup**

- [ ] Table installed; page opens with no install banner
- [ ] Page still opens *before* installing, showing the banner not a 500
- [ ] "List Customer Reference" appears in the menu when the page switch is on

**Add popup**

- [ ] Every dropdown accepts typing and filters as you type
- [ ] Date & Time pre-fills with the current moment
- [ ] Fuel Type hidden when "Reference is a Vehicle" = No; shown when Yes
- [ ] Fuel Type lists "Not Known" plus the Fuel sub-categories
- [ ] Add stages a row into the table below
- [ ] After Add: customer stays, other fields reset, date/time refreshes
- [ ] Staged rows can be edited and deleted before saving
- [ ] Save commits all staged rows; list refreshes
- [ ] Save with a half-typed row stages it rather than discarding it
- [ ] Validation errors show in the popup, nothing is saved

**List page**

- [ ] Columns: Action, Date & Time, Customer, Status, Is a Vehicle, Reference No, Fuel Type, Added By
- [ ] Fuel Type is blank for non-vehicle rows
- [ ] All seven filters work, including Status = Inactive (the `0` case)
- [ ] Reset clears every filter including the date range
- [ ] Edit and Delete hidden for users without those permissions

**QR**

- [ ] QR renders in the View and QR popups
- [ ] Payload shows Customer Name, the reference labelled correctly, and Fuel Type for vehicles only
- [ ] Print opens a clean page and triggers the print dialog
- [ ] PDF downloads, or falls back to the print view with a notice
- [ ] WhatsApp opens with the number and message pre-filled
- [ ] Email sends to the contact's address, and to a hand-typed one
- [ ] Editing a reference regenerates its QR

**Security**

- [ ] A reference id from another business returns 404
- [ ] A `customer_id` from another business is rejected on save
- [ ] Direct URL access is blocked for a role without `customers.view`
