# IS2184 — saved cheques not appearing in Cheque Deposit

Finance / List accounts / Cheque deposit

**This is the third ticket on this symptom. My earlier diagnosis was
incomplete — here is what I missed.**

## Files changed (2)

```
Modules/Finance/Resources/views/account/cheque_deposit.blade.php
Modules/Finance/Resources/views/account/index.blade.php
```

Also included: the IS2183 items 4 and 5 changes to the same two files.

No database or controller changes.

---

## What I got wrong before

IS2124 found a malformed Blade echo that stopped the cheque-list partial
compiling at all. That was real and is fixed. When the symptom came back in
IS2183 I checked the code, saw the fix present, and concluded it was a
deployment gap.

I should have kept looking. There is a **second, independent cause**, and it was
never addressed.

## The actual cause: the form filters cheques out by default

When Cheque Deposit opens, it fills both date boxes with the **current month**:

```js
var rangeStart = moment().startOf('month');
var rangeEnd   = moment().endOf('month');
```

**Date Range** filters on the cheque's own date. So:

| Situation | Result |
|---|---|
| **Post-dated cheque** — received today, dated next month | Outside "this month" → **hidden** |
| Back-dated cheque — dated last month | Outside → **hidden** |
| Entered 31 Aug, dated 2 Sep | Invisible in the August window the form chose | 

Cheques in Hand is *exactly* where post-dated cheques live — this application
has a whole Post Dated Cheques module — so this is the common case, not an edge
case.

Nobody chose those dates. The form applied them, then hid the cheque that had
just been saved. That is the reported symptom precisely, and it survives the
IS2124 fix untouched.

**A blank box did not help either.** `get_cheques_list()` read the range from
the picker OBJECT, not the input:

```js
start_date: datePicker.startDate.format('YYYY-MM-DD')
```

A daterangepicker always holds a range — it defaults to today even if never
opened — so clearing the box changed nothing. The filter was unavoidable.

## Fix

**Both date boxes now start empty, and empty means no filter.**

Cheque Deposit is a work list of what is waiting to be deposited, so it opens
showing **all undeposited cheques**, whatever their date. The user narrows from
there.

The request builder now reads the **input**, not the picker: a blank box sends
an empty string, and `ChequeDepositListService::applyDateFilter()` already skips
a filter whose value is empty — so no server change was needed.

The list stays bounded by the service's 500-row cap and by `is_deposited = 0`,
so it shows outstanding cheques only.

## Also in this package (IS2183)

**Item 4** — Cheque Date on Cheques to Realize could not be selected. The field
is `readonly` and no picker was ever attached, though `get_realize_cheques_list()`
had always read it as one. Initialised on `shown.bs.modal`, attached inside the
modal so the panel is not rendered behind it.

**Item 5** — the preset ranges (Today, This Month, financial years, Custom
Range) are restored on both Cheque Deposit filters.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Finance_IS2184_Cheque_Deposit.zip
cd /home/nivasa/public_html && php artisan view:clear
```

Hard-refresh — the changes are in page JavaScript.

---

## Testing

**The reported case**

- [ ] Save a cheque payment dated **next month** (post-dated)
- [ ] Open Cheque Deposit — it is listed, with both date boxes empty
- [ ] Save one dated **last month** — also listed
- [ ] Save one dated today — listed

**Filtering still works**

- [ ] Pick a Date Range — the list narrows to it
- [ ] Clear it — every undeposited cheque returns
- [ ] Same for Created On
- [ ] Cheque Number and Amount dropdowns still filter
- [ ] Presets appear and apply on one click

**Nothing else broken**

- [ ] Ticking cheques totals correctly
- [ ] Depositing posts correctly and the cheque leaves the list
- [ ] Cheques to Realize: Cheque Date opens a picker and filters
