# IS2183 — Cheque deposit

Five items. **Two needed code; three are already fixed in this codebase and
appear to be undeployed on `ishadi`.**

## Files changed (2)

```
Modules/Finance/Resources/views/account/cheque_deposit.blade.php
Modules/Finance/Resources/views/account/index.blade.php
```

No database changes, no controller changes.

---

## Items 1, 2 and 3 — already fixed, please verify the deployment

These three match work already merged into the code you sent me:

| Item | Already fixed by | Evidence in this codebase |
|---|---|---|
| 1. Saved cheques not showing | IS2124 | `cheque_list.blade.php` line 25 has the corrected `{{ $item->cheque_number ?? '' }}` — the malformed echo that broke the whole partial is gone |
| 2. Cheque number / amount dropdowns | IS2124 | Same fix — both dropdowns are filled from the cheque-list response, which was failing while that partial could not compile |
| 3. "Deposit Successful" message | IS2149 | `postChequeDeposit()` and `postDeposit()` both return `'msg' => 'Deposit Successful'` |

I have not changed them, because there is nothing left to change — re-fixing
working code would only risk breaking it.

**The likely explanation is that `ishadi` has not received those releases.**
This confirms it in one command:

```bash
grep -c "Deposit Successful" \
  /home/nivasa/public_html/Modules/Finance/Http/Controllers/Account/Concerns/HandlesCheques.php
```

`1` means the fix is deployed and something else is happening — tell me and I
will dig in. `0` means the release never reached that server, and deploying it
resolves all three.

On the green background and white text for item 3: the message text is set in
the controller, but its colours come from the theme's status renderer, not from
Finance. If the wording appears correctly but the colours are wrong, that is a
separate, app-level change — say so and I will look at the renderer.

---

## Item 4 — Cheque Date could not be selected

**Cause.** The field is rendered `readonly`, so it cannot be typed into, and
**nothing ever attached a picker to it**. Clicking did nothing at all.

A picker was clearly intended: `get_realize_cheques_list()` has always read the
field as one —

```js
$('input#realize_cheque_date').data('daterangepicker').startDate
```

— but it was never initialised. And because the guard around that line tests
`.val()`, which stayed empty forever, the line never ran and the omission never
threw an error to reveal itself.

**Fix.** The picker is initialised on `shown.bs.modal`, which is where it has to
happen: the modal body arrives by AJAX, so at document ready the field does not
exist yet.

It is attached inside the modal (`parentEl`), or the panel would render behind
it and be unclickable — the same trap that broke the Journal dropdowns. Clearing
the range resets the filter rather than leaving a value stuck in a readonly box
the user cannot edit by hand.

---

## Item 5 — preset date ranges on Date Range and Created On

**This reverses an earlier decision, deliberately.**

IS2149 asked for the picker to close as soon as a range was chosen, and set
`autoApply` to do it. The plugin ignores `autoApply` when a `ranges` list is
present, so the presets were deleted — the note at the time reasoned they were
"explicit From/To pickers, not preset shortcuts".

IS2183 asks for exactly those shortcuts, so that reasoning no longer holds.

**Both requirements still hold.** With `ranges` present, clicking a preset
applies it and closes the panel immediately — which is the closing behaviour
IS2149 actually wanted. Only *Custom Range* opens the calendars and waits for
Apply, which it must: the plugin cannot know a hand-picked range is finished
until the user says so.

The presets come from the global `dateRangeSettings`, so this picker offers the
same list as every other date range in the ERP — Today, Yesterday, Last 7 Days,
Last 30 Days, This Month, Last Month, This month last year, This Year, Last
Year, Current financial year, Last financial year, Custom Range — rather than a
private copy that would drift.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Finance_IS2183_Cheque_Deposit.zip
cd /home/nivasa/public_html && php artisan view:clear
```

Then hard-refresh, since both changes are in page JavaScript.

---

## Testing

**Item 4 — Cheques to Realize**

- [ ] Clicking Cheque Date opens a date range panel
- [ ] The panel appears in front of the modal, not behind it
- [ ] Choosing a range filters the cheque list
- [ ] Clear resets the filter and reloads the full list
- [ ] Transaction Date still works as before

**Item 5 — Cheque Deposit**

- [ ] Date Range shows the full preset list
- [ ] Created On shows the same list
- [ ] Clicking a preset applies it AND closes the panel
- [ ] Custom Range opens the calendars and applies on Apply
- [ ] Either filter reloads the cheque list
- [ ] The panel still renders inside the modal

**Items 1–3 — after confirming the deployment**

- [ ] Saved cheques appear in the list
- [ ] Cheque Number and Amount dropdowns populate
- [ ] Cheque, Cash and Card deposits all confirm "Deposit Successful"
