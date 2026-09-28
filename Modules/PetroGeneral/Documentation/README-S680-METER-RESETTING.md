# S 680 — Meter Resetting fixes

Petro General / Pump Management / Meter Resetting / Add Meter Reset

Three reported issues, plus four related defects found while tracing them.

---

## Files changed (3)

```
Modules/PetroGeneral/Http/Controllers/MeterResettingController.php
Modules/PetroGeneral/Resources/views/pumps/index.blade.php
Modules/PetroGeneral/Resources/views/pumps/partials/add_meter_reset.blade.php
```

No database changes. No new tables or columns.

---

## Issue 1 — "Something went wrong, please try again later" on Save

**Cause.** `store()` did this, with no null check:

```php
$last_meter_sale = MeterSale::where('pump_id', $request->pump_id)->orderBy('id','desc')->first();
MeterSale::where('id', $last_meter_sale->id)->update([...]);
```

A pump that has never been through a settlement has no meter sale, so
`$last_meter_sale` was `null` and reading `->id` threw a fatal error. The
single `catch` turned that into the generic message.

This is exactly the reported scenario — the screenshot shows Last Meter
`0.0000`, meaning a pump with no history.

**Fix.** Null-check before stamping the reset onto the latest meter sale.
Stamping is still correct when a sale exists (`ProvidesPdLookups` reads
`meter_reset_value` in preference to `closing_meter`); it just isn't always
applicable.

**Second cause, same symptom.** The date was parsed with a bare
`Carbon::parse()`. That guesses the format, and guesses wrong on `d/m/Y` —
`29/08/2026` is not a valid US date, so it threw. It would work on this
tenant's `m/d/Y` and fail on a tenant configured for `d/m/Y`.

Replaced with `parseDateAndTime()`, which tries the business's configured
format first, then ISO, then both slash orders. `d/m/Y` is tried before
`m/d/Y` because this deployment is Sri Lankan — for a date like `05/08/2026`
the two disagree silently and the wrong date would be stored with no error.

**Also.** Errors are now reported for what they are. Validation runs before
anything is written, so a missing pump or a non-numeric meter says so, and the
`catch` block is left for genuinely unexpected faults. The whole write is
wrapped in a transaction, so a partial failure no longer leaves a reset row
saved with the pump not updated.

---

## Issue 2 — Last Meter (Current Meter) shows 0.0000

**Cause.** `getPumpDetails()` returned `pumps.last_meter_reading`, and that
column is only ever written *by a meter reset*. On a pump that has been
trading but never reset, it stays at zero however much fuel has gone through
it.

**Fix.** Added `resolveCurrentMeter()`, which uses the resolution order the
rest of the system already uses (see
`Http/Controllers/Settlement/Concerns/ProvidesPdLookups.php`):

1. Latest meter sale's `meter_reset_value`, if set
2. Otherwise that sale's `closing_meter`
3. Otherwise `pumps.last_meter_reading`

So the reset form now agrees with what settlement believes the pump is
reading, instead of contradicting it. `last_meter_reading` is still returned
by the endpoint so any other caller keeps working.

`last_meter` is now resolved server-side on save rather than trusting the
posted value — the field is readonly, so a posted value that disagrees with
the database means a stale or tampered form.

---

## Issue 3 — Current Meter page not updated after a reset

**Cause.** Nothing in the reset flow touched `current_meters`, which is the
table the Current Meter screen lists.

**Fix.** `applyResetToCurrentMeter()` appends a row recording the reset:
`last_time_meter` = old reading, `current_meter` = new reading, `sold_ltr` = 0.

**A new row is added rather than editing the latest one.** Editing would
overwrite a reading an operator actually recorded, and would leave
`current_meter − last_time_meter` no longer equal to `sold_ltr` on that row,
quietly corrupting the sales figure it feeds. Appending states what happened —
at this time the meter went from X to Y — and leaves history intact.

Columns are checked with `Schema::hasColumn` before use, because
`current_meters` carries operator-entry fields that may be absent on older
tenants, and a reset must not fail over a column this feature doesn't care
about.

**Worth confirming:** `CurrentMeterController::create()` filters on a `date`
column while `store()` writes `date_and_time`. If your `current_meters` table
has both, this fix populates both. If it has only one, only that one is
written. Run this to confirm which:

```sql
SHOW COLUMNS FROM current_meters;
```

---

## Additional defects fixed

**Reason was never saved.** The save handler read
`$('input[name=meter_resettings_reason]')`, but Reason is a `<textarea>`, so
the selector matched nothing and sent `undefined`. Now `$('[name=...]')`,
which matches either.

**Duplicate reference numbers.** `$ref_no` was `count() + 1`, which repeats a
number as soon as any row is deleted — a duplicate-key error surfacing as
"Something went wrong" if the column is unique, or two resets sharing a
reference if it isn't. Now `max('id') + 1`.

**Double submission.** The Save button had no disabled state, so a
double-click posted twice and created two resets. Now disabled during the
request.

**Broken success callback.** The handler called
`dip_report_table.ajax.reload()`, but `dip_report_table` is declared in
`dip_management/index.blade.php` and does not exist on the Pump Management
page — a `ReferenceError` after the success toast. Both table reloads are now
guarded with `typeof`, and the Meter Resettings list is reloaded too, so a
saved reset appears immediately instead of after a manual page refresh.

**No error handler on the AJAX call.** A 500 produced no feedback at all. Now
reports the server's message.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/PetroGeneral_S680.zip
cd /home/nivasa/public_html && php artisan view:clear
```

No migration, no config change, no cache clear beyond views.

---

## Testing checklist

**The reported case — a pump with no meter sales**

- [ ] Add Meter Reset, pick a pump that has never settled
- [ ] Last Meter (Current Meter) shows the resolved reading, not `0.0000`
- [ ] Enter a new meter and a reason, Save
- [ ] Success message appears, not "Something went wrong"
- [ ] The reset appears in the Meter Resettings list without a page refresh
- [ ] The Reason saved is the text that was typed

**A pump that has settled**

- [ ] Last Meter shows the last settlement's closing meter
- [ ] After saving, the latest meter sale carries the new `meter_reset_value`

**Repeat reset on the same pump**

- [ ] Reopen Add Meter Reset for the same pump
- [ ] Last Meter now shows the value set by the previous reset

**Current Meter screen**

- [ ] Pump Management / Current Meter shows a row for the reset
- [ ] `last_time_meter` = old reading, `current_meter` = new reading
- [ ] Existing operator rows are unchanged

**Error paths**

- [ ] Saving with no pump selected reports the missing field, not the generic error
- [ ] Saving with a non-numeric meter reports that
- [ ] Double-clicking Save creates one reset, not two
