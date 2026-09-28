# Customer Payments — "Ajax error" on the Customer Interest tab

Customers Module / Customer Payments

## What was happening

Opening **Customers → Customer Payments** on tenant 2003 produced:

```
DataTables warning: table id=customer_interest_table - Ajax error.
```

The tenant log gives the cause:

```
production.WARNING: Blocked direct URL access to a Manage-disabled module page.
{"business_id":2,"permission_key":"customers_customer_interest",
 "path":"customers/customer-interest"}
```

The **Customer Interest** page is switched off in Super Admin → Manage for this
business. The middleware refused the request with a 403, which is correct.
DataTables cannot distinguish a 403 from any other failure, so it reported a
bare "Ajax error".

Nothing was wrong with the query or the endpoint.

## The actual defect

The tab was rendered anyway. In `customer_payments/index.blade.php` the
visibility flags were hard-coded:

```php
$show_customer_payment_bulk = true;
$show_customer_interest     = true;
$show_interest_settings     = true;
```

So the workspace offered a tab whose page had been deliberately disabled, and
the only feedback was a browser alert.

## Fix

**Two separate systems can refuse these routes, and they do not share a rule.**
That is what made the first attempt at this fix fail.

| Gatekeeper | Enforced by | Behaviour when the key is absent |
|---|---|---|
| `CustomerPermissionService::pageEnabled()` | `customers.access` middleware (this module) | Falls back to the page registry's `default_enabled` |
| `SidebarPermissionUtil::isAutomaticPermissionEnabled()` | `EnforceBusinessSidebarModuleAccess` (app-level) | Returns **true** — only blocks a key that is present and switched off |

On this deployment the **second** one blocks Customer Interest while the first
allows it. Gating the tab on the module service alone therefore changed
nothing: the tab stayed on screen and the alert stayed with it.

Both are now consulted, and a tab renders only when neither would refuse the
request behind it:

```php
$show_customer_interest = $customersPermissionService->pageEnabled('customers_customer_interest')
    && $customersSidebarAllows('customers_customer_interest');
```

`$customersSidebarAllows` guards the call with `class_exists`, because
`App\Utils\SidebarPermissionUtil` lives outside this module and cannot be
assumed present.

Interest Settings is not a module registry page — its route is guarded by the
`settings` ability — so it is tested against that ability plus the sidebar
policy.

**List Customer Payments stays unconditional.** It is this page, and the
workspace must never render with no tabs at all.

The Customer Interest DataTable initialiser is guarded too. With the tab hidden
the table element does not exist, and initialising against nothing would leave
`customer_payments` as an empty API that the date-range handler would later
call `.ajax.reload()` on.

## Two ways to resolve it on 2003

**If Customer Interest should be available**, turn it on:
Super Admin → All Businesses → Manage → enable *Customer Interest*. The tab
then appears and works, with or without this patch.

**If it should stay off**, deploy this patch and the tab disappears instead of
erroring.

Both are worth doing: the switch reflects the business decision, and the patch
stops any disabled page producing an alert like this again.

## Files changed (1)

```
Modules/Customers/Resources/views/customer_payments/index.blade.php
```

No database, route or controller changes.

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Customers_Customer_Interest_Tab.zip
cd /home/nivasa/public_html && php artisan view:clear
```

## Testing

- [ ] With Customer Interest disabled: the tab is absent, no alert on page load
- [ ] With it enabled: the tab shows and the table loads
- [ ] Bulk Payment tab follows its own Manage switch the same way
- [ ] Interest Settings shows only for a user with the settings permission
- [ ] List Customer Payments always shows
- [ ] Changing the interest date range still reloads the table when present
