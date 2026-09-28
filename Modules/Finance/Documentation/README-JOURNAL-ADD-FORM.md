# Finance / List Journal / Add — form changes

All ten items. Four files changed.

```
Modules/Finance/Http/Controllers/Journal/JournalController.php
Modules/Finance/Resources/views/journal/create.blade.php
Modules/Finance/Resources/views/journal/get_row.blade.php
Modules/Finance/Resources/views/journal/index.blade.php
```

No database changes, no route changes.

---

## Layout: items 1, 2, 3, 4, 7, 8, 9

The header, every entry row, the Add bar, both totals blocks and the added table
share **one CSS grid**:

```
Account Type | Sub Type | Account | Debit | Credit | action
     1fr          1fr       1fr      1fr     1fr     42px
```

**All five fields are the same width.** This supersedes the earlier revision
that made Debit and Credit 30% wider — equal columns line the row up with the
table and totals underneath, and nothing needs re-checking when a column is
added later.

Because all six blocks use the same definition, they cannot drift apart — which
is what had happened to the totals.

**1. Select Account matches Select Account Type.** Both are `1.75fr`. Sub Type is
the same, so the three dropdowns read as one group.

**2. Debit and Credit** are now equal to the other three rather than wider, per
the later instruction.

**3. The + moved into the row**, beside Credit Amount, rather than sitting in the
header next to the column title — where it read as part of the heading rather
than as "add another line like this one".

**4. Add sits directly below Credit Amount.** It reuses the grid and occupies
column 5 only, so it stays under Credit at any modal width instead of being
pushed around by a neighbouring cell.

**7. The table's Debit and Credit columns match one field column** (20%), so a
figure lines up with the box it was typed into.

**8. Space added** between the table and the totals.

**9. The totals align with their columns.** The old markup was
`col-md-6 + col-md-2 + col-md-2` — 10 of 12 columns — which left both boxes
short of the Debit and Credit fields above. Both totals blocks now use the grid.

Below 991px the grid collapses to one field per line, rather than six
unreadable slivers.

---

## Item 5 — Select Account Type shows only Account Types

**Cause.** `AccountType::forDropdown()` returns *every* row in `account_types`
with no filter on `parent_account_type_id`, so the dropdown listed the sub types
alongside the types they belong to.

That also broke item 6: choosing a sub type from that list asked
`getAccountSubTypes()` for *its* children, of which there are none, so Account
Sub Type showed "No Account Sub Types" and the form looked broken.

**Fix.** The journal controller now uses its own
`parentAccountTypesForDropdown()`, filtering to top-level rows. Applied at all
three call sites — create, edit and the added-row endpoint — so every row offers
the same list.

`forDropdown()` is an application-level helper used elsewhere, so it is left
alone and the journal form filters for itself.

Both `NULL` and `0` count as top level, since either may be used for a root row
depending on the tenant.

---

## Item 6 — Account Sub Type lists the selected type's sub types

Already correctly wired: the endpoint, the route and the cascade script all
existed. It failed only because of item 5 above, and works once that is fixed.

**One real bug was found and fixed alongside it.** `get_row.blade.php` — the
template for rows added with the + button — had **no Account Sub Type column at
all**. Added rows were missing the field the first two rows have, and were one
cell short, so what looked like the Account dropdown sat under the Sub Type
heading and every value rightwards was under the wrong title.

It now carries the same six cells in the same grid. The cascade binds by class,
so the new dropdown works with no further wiring.

---

## Account Sub Types and the Account dropdown

Two separate problems sat behind "cannot see the sub groups".

### The Account dropdown was empty for a parent type

`getAccountDropdownByAccountType()` matched accounts on the given id alone.

That is correct for a **sub type** — an account under Assets is attached to
"Current Assets", not to "Assets". But it returned **nothing** whenever a parent
type was selected with the sub type left on "All Sub Types", which is the first
thing anyone does. The accounts existed; they were hanging off the children of
the id being asked about.

The id's children are now included:

| You pick | You get |
|---|---|
| Assets | every account under all its sub types |
| Assets → Current Assets | only that sub type's accounts |
| Expenses (no children) | its own accounts, as before |

So selecting a type shows everything available, and choosing a sub type narrows
it — which is how the two dropdowns are meant to read together.

### Speed — the dropdowns are now instant

Choosing an Account Type used to cost **two sequential HTTP round trips**: one
for its sub types, then another for its accounts. Each booted Laravel, the
session and the middleware stack before returning a handful of names. The answer
was never slow — the asking was.

The whole account-type tree is now shipped with the page and both dropdowns fill
from memory, with no request at all:

```
types    [ {id:10, name:'Assets'}, ... ]          top level only
subs     { 10: [ {id:15,'Current Assets'}, ... ] }
accounts { 15: [ {id:1,'Petty Cash'}, ... ] }     keyed by whatever type holds them
```

It costs two queries on page load — one for types, one for accounts — not one
per selection.

The old endpoints are untouched and still used as a fallback, so a page cached
from before this change keeps working instead of breaking.

Selecting a parent unions its own accounts with those of its sub types, exactly
as the server does; picking a sub type narrows to that one.

### The dropdowns could not be opened — dropdownParent

Symptom: Account Type opened normally, but Account Sub Type and Select Account
looked dead — the data was in them, but clicking did nothing.

**Cause, and it was mine.** select2 appends its dropdown panel to `<body>`
unless told otherwise. Inside a Bootstrap modal that panel lands behind the
modal's stacking context, so it is invisible and unclickable.

The page already solves this when it first sets the modal up, passing
`dropdownParent` so the panel is attached inside the modal. My cascade
re-initialised the two selects after refilling them, with
`select2({width:'100%'})` — silently dropping that option.

That is exactly why the symptom split the way it did: **Account Type is never
rebuilt, so it kept working**, while Sub Type and Account are rebuilt on every
change and broke.

Every rebuild now resolves the parent through `journalDropdownParent()` — the
modal's `.modal-content`, or the modal itself — matching what the page does on
open. Stale `.select2-container` siblings are removed first as well, since a
leftover container renders on top showing the previous list.

Then this tenant may not have any. A sub type is a row in `account_types` whose
`parent_account_type_id` points at its parent, normally created from
`default_account_types` when a business is set up. If those rows were never
created, "No Account Sub Types" is the correct and truthful answer, and no code
change can conjure them.

`JOURNAL_DIAGNOSE_ACCOUNT_TYPES.sql` (read-only) answers it in three queries:
how many top-level types and sub types exist, the tree showing which types have
children, and where the accounts are actually attached.

Note that Assets and Liabilities normally have sub types while Income, Expenses
and Equity do not — so "No Account Sub Types" on those three is expected.

A fallback was also added: if a tenant somehow has **no** top-level rows at all,
the Account Type dropdown falls back to listing everything rather than coming up
empty. An untidy list beats an unusable form.

## Item 10 — green Save button

The "click Add to continue" wording is replaced by a green **Save** button.

**It is disabled, not hidden, until a line has been added.**

The button had been hidden deliberately (S-666 #1b): with an account chosen but
no line added, Submit sat ready and invited a save that posted nothing, leaving
the user believing the journal had been recorded. Simply un-hiding it would have
brought that back.

Disabled satisfies the request — a visible green Save — while keeping the
protection. The button carries the reason as its tooltip, and the hint stays
beside it while it cannot be pressed. Both clear the moment a line is added.

Green is applied through its own `fj-save` class rather than `btn-success`, so a
theme change to `btn-success` cannot silently restyle this button.

---

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/Finance_Journal_Add_Form.zip
cd /home/nivasa/public_html && php artisan view:clear
```

---

## Testing

**Layout**

- [ ] Select Account is the same width as Select Account Type
- [ ] Debit and Credit are noticeably wider than before
- [ ] The + sits in the entry row beside Credit Amount, not in the header
- [ ] Add sits directly below Credit Amount
- [ ] Both totals rows line up under the Debit and Credit columns
- [ ] There is clear space between the added table and the totals
- [ ] The table's Debit and Credit columns match the Credit Amount field
- [ ] On a narrow window the fields stack one per line

**Dropdowns**

- [ ] Select Account Type lists ONLY account types — no sub types
- [ ] Choosing Assets or Liabilities fills Account Sub Type with its sub types
- [ ] Choosing Income, Expenses or Equity shows "No Account Sub Types" — correct,
      they hold accounts directly
- [ ] Choosing a sub type narrows the Account list
- [ ] Clicking + adds a row that HAS an Account Sub Type dropdown
- [ ] That new row's cascade works the same as the first two

**Save**

- [ ] Save is green, visible and disabled when the form opens
- [ ] Hovering it explains why
- [ ] After Add, it enables and the hint disappears
- [ ] Removing every added row disables it again
- [ ] Saving posts the journal as before
- [ ] Edit Journal still opens with its Update button enabled


---

# Follow-up: entry rules

Three further changes.

## 1. Entering an amount locks the other box in that row

The locking logic already existed and fired on `input`, but `readonly` changes
nothing on screen — a field that had stopped accepting input looked identical to
one that had not, so the user clicked, typed, and nothing happened.

The locked box is now greyed with a not-allowed cursor, so the state is visible
*before* they try. Clearing the amount unlocks the other side again.

## 2. Add stays inactive until both rows have an account

Pressing Add with an incomplete row produced a toastr error and nothing else.
The button is now disabled until **every** row has an account selected, and
fewer than two rows never qualifies — a journal needs at least two lines to
balance.

Its tooltip says why, so the reason is available before the click rather than
after it.

The gate is re-evaluated when an account changes and when a row is added or
removed, deferred by a tick in the latter case so the count reflects the state
*after* the change.

## 3. Total row on the added table, and Save requires balance

The added table now carries a `Total` row in its footer, with the debit and
credit sums in their own columns. It is rebuilt with the body, so it can never
show a stale figure, and turns red with a warning icon when the two sides
differ.

**Save is now gated on that total balancing**, not merely on rows existing. An
unbalanced journal is not a journal, and it was previously possible to add
unbalanced lines, press Save, and receive the rejection from the server after a
round trip. Judging it here — on exactly the figures shown in the Total row —
means the button and the table always agree.

Totals are compared with a 0.005 tolerance. That is for float addition, not a
real difference: amounts are held to two decimals, so `0.1 + 0.2` summing to
`0.30000000000000004` must still count as matching `0.30`.

### Testing

- [ ] Type in Debit — Credit greys out and cannot be typed into
- [ ] Clear the Debit — Credit unlocks
- [ ] Same in reverse, and on rows added with +
- [ ] Add is disabled until both rows have an account
- [ ] Hovering Add while disabled explains why
- [ ] The added table shows a Total row with both sums
- [ ] Unbalanced totals show the row in red with a warning icon
- [ ] Save stays disabled while unbalanced, and its tooltip says so
- [ ] Balancing the two sides enables Save
- [ ] Removing a line re-checks both the totals and Save


---

# Follow-up: most accounts were missing from the dropdown

Selecting Liabilities offered a single account. The cause was a filter, not the
data.

## What the diagnostic showed

| Account type | Accounts | Hidden by the filter | Reaching the dropdown |
|---|---|---|---|
| Income | 19 | 13 | 6 |
| Expenses | 19 | 14 | 5 |
| Current Assets | 17 | 11 | 6 |
| Current Liabilities | 5 | 4 | 1 |
| Equity | 3 | 3 | 0 |
| Fixed Assets | 1 | 1 | 0 |

Roughly **46 of 64 accounts were excluded** — which is why the whole form had
only 18 to offer, and Liabilities exactly one.

## Cause

`whereNull('default_account_id')`, inherited from the original endpoint.

`default_account_id` does **not** mean "system account, do not use". It marks an
account created from the standard chart of accounts when the business was set up
— Cash, Bank, Accounts Receivable, Sales and the rest. `BusinessController`
stamps it at creation, and they are ordinary, postable accounts.

Excluding them left only manually-added accounts. A journal that cannot touch
Cash or Sales is not much of a journal.

## Fix

The filter is removed from all five account queries in the journal controller.

`notClosed()` stays — that is the filter which genuinely means "do not post to
this". Closed accounts remain hidden.

### Testing

- [ ] Liabilities now lists all its accounts, not one
- [ ] Income, Expenses, Assets and Equity each list their full set
- [ ] Cash, Bank and Accounts Receivable are selectable
- [ ] Choosing a sub type still narrows correctly
- [ ] A closed account does NOT appear
- [ ] Saving a journal against a standard account posts correctly


---

# Follow-up: "The selected account does not belong to the selected account type"

Saving a correct journal was rejected — Cash under Assets, Accounts Payable
under Liabilities, both balanced at 100.

## Cause — a consequence of the earlier fix

`store()` validated the posted account against the posted account type with a
strict equality check:

```php
if ($actual_type_id > 0 && $actual_type_id !== $selected_type_id) { ... reject
```

That was right while the Account Type dropdown listed sub types as though they
were types — the two ids always matched.

It is not right now. Selecting **Assets** offers the accounts of **Current
Assets** and **Fixed Assets**, so a perfectly valid row posts type `10` with an
account whose own type is `15`, and the validator refused it.

The form and the validator had drifted apart: the form was changed to be more
helpful, the rule behind it was not.

## Fix

An account is accepted when its type is the selected type **or a child of it** —
exactly what the form offers.

Anything further away is still refused, so a genuine mismatch is still caught:

| Row | Verdict |
|---|---|
| Assets + Cash (Current Assets) | accepted |
| Liabilities + Accounts Payable (Current Liabilities) | accepted |
| Expenses + an Expenses account | accepted |
| Liabilities + an Income account | rejected |
| Liabilities + a Fixed Assets account | rejected |

The child types are fetched in one query for all rows, not one per row.

Only `store()` carried this check; `update()` has no equivalent, so there is no
second place to change.

### Testing

- [ ] Assets + Cash, Liabilities + Accounts Payable, balanced — saves
- [ ] The journal appears in the list with both lines
- [ ] An account chosen under a narrowed sub type still saves
- [ ] Editing a saved journal still works


---

# Follow-up: the duplicate Total row in the footer

The same two figures were appearing three times: once above the added table,
once in the added table's own Total row, and once again in the modal footer.

The footer copy is removed.

## Why it was safe to remove

`debit_total` and `credit_total` were marked `required`, which suggests the
server depends on them. It does not. `store()` recomputes both from the row
amounts and merges them into the request **before** validation:

```php
$debit_total  = array_sum($journal_columns['debit_amount']);
$credit_total = array_sum($journal_columns['credit_amount']);
$request->merge([... 'debit_total' => $debit_total, 'credit_total' => $credit_total]);
```

Whatever the form posted was overwritten and never read.

The two names are kept as hidden inputs carrying the same classes, so the
existing `calculateFinanceJournalTotals()` — which writes to
`.debit_total_top, .debit_total` by class — keeps working untouched.

What remains: the Total row above the entry rows, and the Total row at the foot
of the added table. Two, not three, and each belongs to the section it totals.

### Testing

- [ ] The footer shows only Save and Close, with no Total row
- [ ] The Total above the table still updates as amounts are typed
- [ ] The added table's Total row still updates on Add and Remove
- [ ] Saving a balanced journal still works
- [ ] The debit/credit mismatch message still appears when unbalanced
