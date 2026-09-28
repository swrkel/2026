# 8049 — Customer Register

Customers Module / Customer register / action / **Pay Due Amount** and **Ledger**

---

## Files changed (5)

```
Modules/Customers/Resources/views/layouts/action.blade.php
Modules/Customers/Resources/views/payments/form.blade.php
Modules/Customers/Resources/views/payments/partials/method_account_script.blade.php
Modules/Customers/Resources/views/ledger/index.blade.php
Modules/Customers/Services/CustomerLedgerService.php
```

No database changes. No route or controller changes.

---

## Pay Due Amount

### 1. Popup width halved

`layouts/action.blade.php` is shared by every customer action — Ledger,
Statement, Balance and the rest — and those need the full width for their
tables. So the narrow variant is **opt-in**: the layout accepts a `compact`
flag and only `payments/form.blade.php` passes it.

Both the percentage and the cap are halved, `95% → 48%` and `1400px → 700px`.
Halving only the cap would have left the popup unchanged on any screen below
1400px.

The fields need no separate rule. They are `col-md-4`, so each is a third of
the dialog and halves along with it. A `min-width: 420px` stops the form
collapsing into an unusable column on a mid-size window; below 768px the
existing mobile rule takes over.

### 2. Tab moves to the next field

Two things were in the way.

The calendar buttons beside the two date inputs carried `tabindex="0"`, so
tabbing out of a field stopped on an icon before reaching the next input.
They are now `tabindex="-1"` — still clickable, no longer a tab stop.

More importantly, `cleanDuplicateDropdowns()` strips the `tabindex` Select2
puts on the two native selects, but only at load, +100ms and +500ms. Any
Select2 pass that runs later re-applies `tabindex="-1"` and Tab silently skips
both dropdowns again.

Rather than keep chasing whatever re-applies it, focus is now moved
explicitly. A `keydown` handler on the form walks its own visible, enabled
controls in DOM order — the order they are read on screen.

**Deliberate trade-off:** on a native date input the browser normally uses Tab
to step through day / month / year. That is overridden, so Tab leaves the whole
field. It is what the ticket asks for, and the segments are still reachable
with the arrow keys, or by typing the digits, which advances automatically.

### 3. Typing in Amount replaces the value

The field arrives pre-filled with the full due and selects on focus, which is
correct. The problem was what happened next: typing appended, so `5,000.00`
plus a typed `200` read `5,000.00200`.

The selection was being lost before the keystroke arrived — a click places the
caret after focus has selected it, and the numeric formatting on
`.input_number` re-sets the value and puts the caret at the end.

So the outcome no longer depends on the selection surviving. The field is
flagged *pristine* while it still holds the value it was given, and the first
character typed clears it. Whatever moved the caret, the first keystroke starts
a fresh number.

Once anything has been typed the flag is off, so editing what was just entered
— backspace, more digits, clicking to reposition — behaves normally. Tab, Enter
and Escape leave the value untouched, so tabbing straight through keeps the
full due, which is the common case.

---

## Ledger — Opening Balance vs B/F Balance

The Opening Balance row used to be prepended whichever date range was showing.
Looking at, say, this month, the user saw an Opening Balance dated years
earlier sitting above transactions it had nothing to do with — and every
running balance beneath it was computed from a figure that did not belong in
the period.

What appears on top now depends on where the opening date falls:

| Opening date | First row |
|---|---|
| **Inside** the selected range | The Opening Balance row, as before |
| **Before** the range | A **B/F Balance** row, carrying the closing balance of the day before the range starts |
| **After** the range | Neither — it has not happened yet as far as this period is concerned |

With no range selected the old behaviour is kept exactly: the whole history is
on screen, so Opening Balance is the honest first row and there is nothing to
bring forward.

### How the B/F figure is produced

By asking for the ledger itself up to the day before the range and totalling
it — not by a separate hand-written aggregate. It therefore counts exactly the
same rows the ledger would have shown for that earlier period:
`contact_ledgers`, the transactions the receivable service adds back, and the
opening balance. The B/F figure and the table cannot disagree about what came
before.

The inner call passes no start date, which is what stops it recursing: that
path takes the unfiltered branch and never asks for another brought-forward
figure.

A zero B/F is omitted rather than shown as a `0.00` row.

In the table the B/F row carries the same emphasis as Opening Balance, and its
Type column reads **Brought Forward** rather than the raw `bf_balance`.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Customers_8049_Customer_Register.zip
cd /home/nivasa/public_html && php artisan view:clear
```

---

## Also worth doing — leftover from 8046

The uploaded module still contains the **first** 8046 raw-SQL folder:

```
Modules/Customers/Database/RawSQL/2026_08_28_customer_reference/
    00_MASTER_CUSTOMER_REFERENCE.sql
    01_CREATE_CUSTOMER_REFERENCES.sql     <-- creates the SHARED customer_references table
    README.txt
```

`unzip -o` overwrites files but does not delete ones the new archive no longer
contains, so this survived the v7 deploy. The migration and the module code are
correct — they use `customer_qr_references` — but this stale script still
targets the shared table that a dozen other modules depend on. It is guarded by
`CREATE TABLE IF NOT EXISTS` so it cannot damage the existing table, but it
should not be sitting in the repository inviting someone to run it.

```bash
rm -rf /home/nivasa/public_html/Modules/Customers/Database/RawSQL/2026_08_28_customer_reference
```

The correct folder, `2026_08_28_customer_qr_reference`, should be present
alongside it.

---

## Testing checklist

**Pay Due Amount — width**

- [ ] The popup is roughly half its previous width
- [ ] Fields are correspondingly narrower and still readable
- [ ] Ledger, Statement and the other actions are unchanged in width
- [ ] On a phone the popup still fills the screen

**Pay Due Amount — tab**

- [ ] Tab from Amount reaches Transaction Date, not the calendar icon
- [ ] Tab reaches Payment Method and Payment Account
- [ ] With a cheque method selected, Tab reaches Cheque No, Bank, Cheque Date
- [ ] Shift+Tab walks back in the same order
- [ ] The calendar icons still open the picker on click

**Pay Due Amount — amount**

- [ ] Opening the popup shows the full due, selected
- [ ] Typing `200` gives `200`, not the due with `200` appended
- [ ] Clicking into the field then typing also replaces
- [ ] After typing, backspace and further digits edit normally
- [ ] Tabbing straight past Amount keeps the full due
- [ ] Saving posts the amount actually shown

**Ledger**

- [ ] Range covering the opening date → Opening Balance row on top, as before
- [ ] Range after the opening date → B/F Balance row on top
- [ ] The B/F figure equals the closing balance of the day before the range
- [ ] Running balances below it are consistent with that start
- [ ] Range entirely before the opening date → neither row
- [ ] No range selected → unchanged from before
- [ ] A customer with no prior activity shows no B/F row
