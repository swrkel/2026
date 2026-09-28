# IS2124 — Cheque Deposit fixes

Finance module / List accounts / Cheque deposit

Both reported issues share a single root cause. A second, independent defect
was found that would have kept some cheques hidden even after the first was
fixed.

---

## Files changed (2)

```
Modules/Finance/Resources/views/account/partials/cheque_list.blade.php
Modules/Finance/Services/Deposits/ChequeDepositListService.php
```

No database changes. No route or controller changes.

---

## Root cause of both issues — a malformed Blade echo

`cheque_list.blade.php` line 8 read:

```
data-cheque-number="{{ $item->cheque_number ?? ''} }}"
```

There is a stray `}` inside the echo, before the closing `}}`. Blade passed it
straight through into the compiled PHP, producing a parse error.

**Blade compiles a whole template up front**, so this was not confined to the
loop it sat in. The partial failed on *every* render — including the
"no item found" branch — and `finance.account.cheque-list` returned 500 every
time it was called.

That single error produces both reported symptoms:

**Issue 1 — saved cheques not shown.** The list request never returned rows,
so the table stayed empty regardless of what had been saved.

**Issue 2 — Cheque Number and Amount dropdowns not working.** Since the CH1
rework, both dropdowns are populated by
`financePopulateChequeDepositFilters()`, which runs *inside the success
handler* of that same request. A failing request means the function is never
called, so both selects keep only their placeholder "All".

The dropdowns were never independently broken. They were downstream of the
same failure.

---

## Second defect — cheques with no cheque date were unreachable

Independent of the above, and worth fixing in the same pass because it would
have kept some cheques hidden after the parse error was corrected.

The date-range filter ran against the *raw* cheque date:

```sql
DATE(COALESCE(direct_payment.cheque_date, ...)) BETWEEN ? AND ?
```

For a cheque saved through a screen with no cheque-date field, every candidate
in that COALESCE is NULL. In SQL `DATE(NULL) BETWEEN x AND y` evaluates to
NULL, which is not TRUE — so the row was dropped **whatever range was
selected**. Those cheques were not merely undated, they were unfindable.

LA-1176 had already added a `paid_on` / `operation_date` fallback, but applied
it only to the displayed column, deliberately keeping the filter on the raw
date so as not to change which rows appeared. That reasoning was inverted: the
rows were already being wrongly excluded.

**Fix.** One `effectiveChequeDateExpression()` now drives the display, the
date-range filter and the ORDER BY. A row is matched on exactly the date shown
in its Cheque Date cell — the only behaviour that makes the range filter
intelligible to the user. Ordering moved with it, so undated cheques no longer
all bunch at the top on a value nobody can see.

`Created On` had the same NULL trap on `account_transaction.created_at` and now
falls back to `operation_date`.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Finance_IS2124.zip
cd /home/nivasa/public_html && php artisan view:clear
```

`view:clear` is **required**, not optional — the broken partial is cached in
its compiled form and will keep failing until the cache is dropped.

---

## Testing checklist

- [ ] Open Finance / List accounts / Cheque Deposit
- [ ] The table shows saved cheques, or "No item found" if there genuinely are none
- [ ] Cheque Number dropdown lists the cheque numbers from the visible rows
- [ ] Amount dropdown lists the amounts from the visible rows
- [ ] Selecting a cheque number filters the table and the dropdown keeps its options
- [ ] Selecting an amount does the same
- [ ] Changing either date range reloads the list and rebuilds both dropdowns
- [ ] A cheque saved with no cheque date now appears, dated by its payment date
- [ ] Ticking rows totals correctly into the Amount field
- [ ] Depositing to an account still posts correctly
- [ ] Encash still disables Deposit To Account and posts correctly

---

## If cheques are still missing after this

The parse error masked everything behind it, so it is worth confirming the
underlying data is what you expect. This lists the raw Cheques in Hand debits
for a business, bypassing every filter the modal applies:

```sql
SELECT at.id,
       at.amount,
       at.operation_date,
       at.created_at,
       at.cheque_number   AS at_cheque_number,
       at.cheque_date     AS at_cheque_date,
       tp.method,
       tp.cheque_number   AS tp_cheque_number,
       tp.cheque_date     AS tp_cheque_date,
       tp.paid_on,
       tp.is_deposited,
       a.name             AS account_name
FROM account_transactions at
JOIN accounts a ON a.id = at.account_id
LEFT JOIN transaction_payments tp ON tp.id = at.transaction_payment_id
WHERE at.business_id = <BUSINESS_ID>
  AND at.type = 'debit'
  AND at.deleted_at IS NULL
  AND LOWER(a.name) LIKE '%cheque%'
ORDER BY at.id DESC
LIMIT 50;
```

Rows with `is_deposited = 1` are correctly hidden — they have already been
deposited. Anything else that appears here but not in the modal should be
reported back with this output.
