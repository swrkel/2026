# Poultry Module

Farm management for broilers, layers, breeders and hatchery operations, built as
a self-contained module for this ERP.

---

## The design constraint, and how it is met

The requirement was that **all code stays inside `Modules/Poultry`** — no edits
to core, no new files elsewhere — while the module **shares the ERP's existing
tables**, so suppliers, customers, products and stock are never duplicated.

Those two things pull against each other, and the resolution is:

**Code isolation, data sharing.**

The module defines its own slim Eloquent models pointing at the existing core
tables (`Entities/Shared/`). So `contacts` rows are shared with the whole ERP,
but nothing in this module imports `App\Contact`. The contract depended on is
the *table name* — itself configurable in `Config/config.php` — not the class.

```php
namespace Modules\Poultry\Entities\Shared;

class Contact extends SharedModel
{
    protected $sharedTableKey = 'contacts';   // resolves via config
    protected $table = 'contacts';
}
```

### The gateway seam

Reading shared tables this way is always safe. **Writing is not.** Stock
quantity arithmetic and its accounting side effects live in the core utils —
setting `qty_available` by hand is how stock silently drifts out of agreement
with transaction history, and the drift is invisible until someone reconciles.

So exactly **two files** in this module know core exists:

| File | Wraps | Degrades to |
|---|---|---|
| `Services/StockGateway.php` | `App\Utils\ProductUtil` | records against poultry tables only |
| `Services/LedgerGateway.php` | `App\Utils\AccountTransactionUtil` | cost still written to `poultry_batch_costs` |

Both resolve the core class **by string at runtime** (`app('App\Utils\ProductUtil')`)
and check `class_exists()` first, so the module boots on an install without
those utils rather than crashing. If core is ever refactored, two files change
instead of fifty.

You can verify the seam holds at any time:

```bash
grep -rn "use App\\\\\|App\\\\Utils" Modules/Poultry --include=*.php
# should return only StockGateway.php and LedgerGateway.php
```

---

## Installation

```bash
# 1. Copy the module into place
cp -r Modules/Poultry /path/to/app/Modules/

# 2. Register it
php artisan module:enable Poultry

# 3. Create the 17 poultry_* tables (no core tables are touched)
php artisan module:migrate Poultry

# 4. Publish the CSS/JS the layout references
php artisan module:publish Poultry

# 5. Optional: seed egg grades and a baseline vaccination programme
php artisan tinker
>>> (new Modules\Poultry\Database\Seeders\PoultryDatabaseSeeder)->forBusiness(1);

# 6. Clear caches
php artisan optimize:clear
```

Then grant the poultry permissions on **Settings → Roles**. The 21 permission
keys come from `Config/module_permissions.php` and appear automatically.

### Before you go live

`Config/config.php` has `'pid' => null` as a placeholder. If your Superadmin
module gates modules by package id, assign the real one — otherwise the module
runs but is not tied to a package.

---

## First screen to configure: Settings

**The module will look broken until you do this.** The item dropdowns on the
Feed, Health, Harvest and Hatchery screens are populated from *your existing
product catalogue*, filtered by category. Until you tell the module which
categories those are, every dropdown is empty.

Go to **Poultry → Settings → Catalogue mapping** and enter the category ids for:

| Setting | Used by |
|---|---|
| `feed_category_ids` | feed issue screen |
| `medication_category_ids` | treatment recording |
| `vaccine_category_ids` | vaccination recording |
| `egg_category_ids` | mapping egg grades to sellable products |
| `live_bird_category_ids` | harvest → stock |
| `chick_category_ids` | hatchery → stock |

Then map each egg grade to a product variation on **Masters → Egg grades**.
That mapping is what makes collected eggs sellable through POS and Distribution
with no sales code in this module at all.

---

## Broiler vs layer costing

These are financially different animals, and treating them alike is the most
common way a poultry costing report produces numbers nobody trusts.

**Broiler — work in progress.** Chick, feed and medication costs accumulate on
the batch and release to cost of sales at harvest. The meaningful figure is
**cost per kg of live weight**.

**Layer — amortising asset.** Rearing cost through to point of lay is
capitalised, then amortised across the laying cycle, with the spent hen sale as
residual value. The meaningful figure is **cost per egg** — and it is only
correct if the rearing cost is spread rather than dumped into the first month
of lay.

`Services/CostingService.php` implements both and dispatches on `bird_type`.
The transition happens at **Batch → Transfer to lay**, which creates the layer
batch and carries the accumulated rearing cost across as its opening value.

---

## Drug withdrawal is enforced, not advisory

A batch treated with an antibiotic carrying a withdrawal period must not have
its produce enter the food chain until that period elapses. Because this module
posts production into **shared** stock — where POS can sell it immediately —
the check runs *before* posting, not as a warning on a report read later.

`Services/WithdrawalGuard.php` throws rather than returning false, so a caller
cannot post to stock by forgetting to check. Two deliberately different
behaviours:

- **Eggs** — still recorded (the farm needs the production figure) but **not**
  added to saleable stock. The screen explains why.
- **Harvest** — **refused outright**. Meat from a batch inside withdrawal
  should not be moving at all.

Withdrawal is counted from the **last** day of treatment, not the first.
Computing it from the start date is a common and consequential error.

---

## Shared transaction types

The module writes rows into the shared `transactions` table with these types:

- `poultry_feed_issue`
- `poultry_production`
- `poultry_harvest`

`transactions.type` is a plain indexed `string(255)` in this schema — **not an
enum** — so these needed no core migration.

**Verified before building:** Finance, FinanceReports, ManagementReport,
StockReports and `app/Utils` were grepped for negative type filters
(`where('type','!=')`, `whereNotIn('type')`). There are **zero occurrences** —
every one filters by explicit whitelist. Poultry rows therefore cannot leak
into existing report totals. Re-run that grep after any core upgrade.

Both integrations can be switched off in `Config/config.php`:

```php
'post_to_stock'  => false,   // record against poultry tables only
'post_to_ledger' => false,
```

---

## Schema

17 tables, all prefixed `poultry_`, **with no foreign key constraints** — to
core or to each other. Cross-module FKs would couple these migrations to the
presence of core tables, and on an install this size they make partial restores
and tenant-level data surgery painful. Referential integrity is enforced in the
service layer; indexes are declared everywhere an FK would have been.

| Group | Tables |
|---|---|
| Masters | `farms`, `houses`, `breeds`, `egg_grades`, `settings` |
| Batches | `batches`, `batch_transfers`, `daily_records`, `weight_samples` |
| Operations | `feed_consumptions`, `egg_collections`, `vaccination_schedules`, `vaccination_records`, `treatments`, `harvests` |
| Hatchery / cost | `hatch_sets`, `batch_costs` |

Shared, never duplicated: `contacts`, `products`, `variations`,
`variation_location_details`, `business_locations`, `transactions`, `users`.

---

## KPIs

`Services/PerformanceCalculator.php`. Everything is **recomputed from the
underlying daily rows** rather than read from a running total, because
backdated corrections are normal on a farm — a mortality miscount found three
days later has to move every dependent figure with it.

- **FCR** — kg feed per kg live weight gained. Feed is ~70% of cost of
  production, so this is the number broiler operations live on.
- **Hen-day %** — eggs as a share of hens alive that day. A good commercial
  layer peaks near 90–95% in weeks 26–30 of lay.
- **EPEF** — `(liveability% × avg weight kg) ÷ (age days × FCR) × 100`.
  Above 300 is decent, above 400 very good.
- **Uniformity CV** — below 10% is an even flock; above 15% it is splitting.

---

## Layout

`Resources/views/layouts/app.blade.php` is standalone — it does not `@extends`
any core layout, so the module renders on an install where those blades have
been customised. It uses the same AdminLTE/Bootstrap 3 classes as the rest of
the ERP, so it still looks native.

To nest it inside the core chrome instead, change the first line to
`@extends('layouts.app')` and wrap the body in `@section('content')`. Nothing
else in the module depends on that file's structure.

---

## Verification status

- ✅ Structural check: braces, namespaces, class/filename agreement, no `?>`
- ✅ All 51 views referenced by controllers exist
- ✅ All internal imports and fully-qualified references resolve
- ✅ All route names used in views are defined
- ✅ All 263 `@lang` keys used in views are defined
- ✅ Zero foreign key constraints
- ✅ Core coupling confined to the two gateway files
- ⚠️ **`php -l` has not been run** — no PHP runtime was available in the build
  environment. Lint before enabling:

```bash
find Modules/Poultry -name '*.php' ! -name '*.blade.php' -exec php -l {} \;
```

This is scaffolding of production shape, not a system that has been run against
a live database. Treat the first install as a staging exercise.
