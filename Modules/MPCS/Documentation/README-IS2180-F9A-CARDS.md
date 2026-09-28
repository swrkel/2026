# IS2180 — F9A card figures

MPCS / F9A Form / Payment section

**One file changed:**
`Modules/MPCS/Http/Controllers/MPCSController.php`

No database changes. Nothing for you to run.

---

## What I got wrong first time

I sent a diagnostic query with a hard-coded `<BUSINESS_ID>` and asked you to run
it. That was wrong twice over: a business id must come from the session, never be
typed in, and you should not have to query a database each time a fault appears.
Both points taken.

Everything below was determined by reading the code.

---

## What I checked, and ruled out

**The date stamped on card transactions is correct.** I scanned every
`createAccountTransaction()` call across all modules: exactly one omits
`operation_date` — and it is in F22, not the card path. The card writer passes
`operation_date => $card_transaction->transaction_date`, and that transaction is
built from `Carbon::parse($settlement->transaction_date)` — the settlement's own
business date, not the moment it was saved.

So card amounts are dated correctly when written. The defects are in how F9A
**reads** them.

---

## What was actually wrong

Every other figure on F9A is scoped: cash filters by business and location and
excludes deleted rows; cheques do the same. The card query had none of it:

```php
DB::table('account_transactions')
    ->where('account_id', $acc->id)
    ->whereDate('operation_date', $date)
    ->where('type', 'debit')
    ->sum('amount');
```

| Missing | Consequence |
|---|---|
| `business_id` | Present on the table and filtered by every other F9A query; absent here |
| `deleted_at` | The table uses soft deletes — **card amounts from DELETED settlements were still being added in** |
| `location_id` | Cash and cheques are filtered by location; cards summed **every location's** takings into a form produced per location |

Both the "Today" figure and the "Previous Day" cumulative had the same gaps, and
were written as two separate queries that could drift apart.

---

## The fix

One shared, correctly scoped query — `$card_debit_query` — used by both Today
and Previous Day, so the two can no longer be scoped differently.

- **Business** is applied where the column exists. It is nullable on this table,
  so rows predating it are kept rather than silently dropped.
- **Deleted rows are excluded**, so a deleted settlement's cards stop counting.
- **Location** is reached through the linked transaction, since
  `account_transactions` has no location column. A row with **no** transaction
  link has no knowable location and is **kept** — excluding it would hide
  takings rather than attribute them. So this can only remove an amount that
  provably belongs to a different location.

Every column is checked with `Schema::hasColumn` first, so the form still works
on a tenant whose schema differs.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/MPCS_IS2180_F9A_Cards.zip
cd /home/nivasa/public_html && php artisan view:clear
```

---

## If the figures are still zero

Then something remains that the code cannot tell me — but it is a question about
the screen, not the database.

In your screenshot **every** payment row reads 0.00 for Today: Cash, Cheques and
all four cards, with the whole 1,554,220.00 falling to Balance in Hand. Cash and
cheques come from entirely different tables to cards, so three unrelated sources
reading zero together points at the *date being viewed* rather than at the card
logic.

So: **which date was the form showing**, and were those settlements entered
against that same date? If the form was on 3 September while the settlements
carry business dates of 17–31 August, every payment row would correctly read
0.00 for the 3rd — and the figures would appear on their own days.

That is answerable from the F9A screen and the settlement list, with no query.

---

## Testing

- [ ] Open F9A for a date where a card settlement exists — the card row shows it
- [ ] Previous Day and Total as of Today move consistently with it
- [ ] Delete a settlement — its card amount disappears from both columns
- [ ] With several locations, F9A for one location shows only that location's cards
- [ ] Cash and cheque rows are unchanged
