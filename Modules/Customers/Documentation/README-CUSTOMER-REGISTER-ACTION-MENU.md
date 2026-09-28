# Customer Register — Actions menu renders broken over the table

Customers Module / Customer Register (`/customers`)

## Symptom

Opening a row's **Actions** menu leaves a white panel stuck over the table,
overlapping other rows and their buttons, with the top of the list cut off.

## Cause

The Actions menu is deliberately detached to `<body>` while open, so the
table's scroll container cannot clip it. It is positioned with
`getBoundingClientRect()` and rendered `position: fixed`. That part is correct.

The leak was on the way out. The modal-close handler did:

```js
$('.customer-actions-menu, .customers-action-menu-floating')
    .removeAttr('style').removeClass('show');
```

This stripped the menu's inline coordinates **but left the
`customers-action-menu-floating` class on it**. That class forces
`position: fixed`, `display: block` and `visibility: visible`. With `top` and
`left` gone the browser falls back to `auto`, so the menu rendered as a loose
panel over the table — the reported breakage.

It reproduces by opening a row action (Ledger, Pay Due, Statement), closing the
popup, and looking at the table.

## Fixes

**1. The modal-close handler now closes properly** instead of stripping styles.
Closing reattaches the menu to its row and drops the class along with the
styles, so nothing is left that can render loose.

**2. Menus are reattached, not destroyed.** `closeCustomerActionDropdowns()`
previously called `.remove()` on every floating menu. When the owning row still
existed, that destroyed its Actions menu permanently — it stayed gone until the
page was reloaded. Each menu now carries a reference to its row
(`data('customersOwnerGroup')`), so it is put back when the row is still there
and only discarded when the row itself has gone.

**3. Close on table redraw.** Paging, searching, sorting or an `ajax.reload()`
destroys the row the menu was detached from. Closing on `draw.dt` puts the menu
back while its row still exists.

**4. Close on scroll and resize.** The menu is `position: fixed`, placed from
the button's viewport rectangle. Scrolling moves the button but not the menu,
so within a few lines it floats beside unrelated rows.

**5. Removed a duplicate CSS rule.** `.customers-action-menu-floating` was
declared twice — once `position: absolute`, once `position: fixed` — and which
one applied depended on source order. The positioning code computes viewport
coordinates, so `fixed` is the correct one; the `absolute` duplicate is gone.

## On splitting the menu into parent/child

Not needed for this bug — the menu was rendering loose, not simply too long,
and it now stays anchored to its button.

It is worth noting separately that the menu carries about eighteen entries, and
on a short screen it still has to switch to its scrolling mode. If you would
like it grouped — a few parent items revealing children on hover or click —
that is a straightforward follow-up, but it is a design change rather than a
fix, so I have left it out of this one.

## Files changed (1)

```
Modules/Customers/Resources/views/index.blade.php
```

No database, route or controller changes.

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Customers_Customer_Register_Action_Menu.zip
cd /home/nivasa/public_html && php artisan view:clear
```

## Testing

- [ ] Open a row's Actions menu — it appears anchored under the button
- [ ] Open Ledger, close the popup — no panel left over the table
- [ ] Repeat with Pay Due Amount and Statement
- [ ] Reopen the same row's Actions menu — all items still present
- [ ] Open a menu, then scroll — it closes rather than drifting
- [ ] Open a menu, then page or search the table — it closes cleanly
- [ ] Open a menu near the bottom of the screen — it flips above the button
- [ ] Row actions still work after several open/close cycles
