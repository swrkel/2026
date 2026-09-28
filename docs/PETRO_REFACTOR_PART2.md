# Petro / Petro PD Settlement Refactor — Part 2

**Scope:** everything explicitly deferred from `docs/PETRO_REFACTOR_DAY1.md` plus issues uncovered during Day 1 testing. Total effort estimate: **5–8 working days** for an AI-assisted single developer, split across 7 phases. Each phase is gated independently — you can ship Phase 1 today and Phase 7 next week without breaking anything in between.

**Foundation laid in Day 1 (do NOT re-do):**
- `pump_payment_id` column on the 4 settlement_*_payments tables ✅
- `petro_settlement_id` column on `transactions` / `account_transactions` ✅
- `SettlementPaymentReconciler` with `upsertOne()` + `reconcileSet()` + `withBypass()` ✅
- `RequiresReconcilerContext` Eloquent trait (create-only, with SettlementSW allowlist) ✅
- DB UNIQUE constraint on `(business_id, settlement_no, pump_payment_id)` ✅
- CI static check (`tests/Static/NoDirectSettlementPaymentWritesTest.php`) ✅
- `SettlementPaymentQueryService` read facade (service exists, not yet consumed by controllers) ✅
- 22 in-scope Petro + PumperDashboard call sites migrated to `upsertOne()` ✅
- IS1293 edit-side bulk-update fix ✅
- Finalize block now links `pump_operator_assignments.settlement_id` ✅
- 74 characterization + lock + static tests, all green ✅

---

## Naming convention (carried forward from Day 1)

| Prefix       | Owner                                                              |
|--------------|--------------------------------------------------------------------|
| `pumper_`    | Pumper Dashboard and Petro PD flows                                |
| `petro_`     | Direct Settlement flows (Petro module proper)                      |
| `settlement_`| Existing convention — keep, do not rename                          |

No table renames in Part 2. New columns follow each table's existing convention.

---

## Hard rules for the AI executing Part 2

1. **One phase per PR.** Do not start Phase N+1 in the same PR as Phase N. Each phase has its own acceptance gate; rollbacks must be safe.
2. **Do not run the full test suite yourself.** Tell the user to run it at each gate.
3. **Do not run `php artisan migrate`.** Always run targeted migrations via `--path=database/migrations/<filename>.php`.
4. **No git commands.** The user handles version control.
5. **Per-phase acceptance gate must be green before declaring done.** No partial credit.
6. **Do not delete tests.** If a Day-1 LOCK assertion was already flipped, leave it.
7. **No new emojis.** No multi-paragraph end-of-task recaps. One-line per-phase status.
8. **Whenever an SQL migration adds a column or index, mark it idempotent** with `Schema::hasColumn` / `Schema::hasTable` / `indexExists` guards, like the Day 1 migrations do.

---

## Phase 1 — Close the locks (target: 1 day)

**Goal:** kill the SettlementSW allowlist, kill the VatSettlement gap, enable Lock 2's `updating` guard. After Phase 1, **every** settlement_*_payments write — Petro PD, Pumper Dashboard, SettlementSW, VatSettlement — goes through the Reconciler, and every UPDATE on a loaded model also goes through it.

### 1.1 SettlementSW migration (15 sites)

**Files:**
- `Modules/SettlementSW/Http/Controllers/SettlementSWController.php` — 4 sites (lines 2868, 2894, 2922, 3263)
- `Modules/SettlementSW/Http/Controllers/SWAddPaymentController.php` — 11 sites (lines 164, 368, 699, 748, 850, 1094, 1759, 2346, 2478, 2741, 3427)

**Recipe:** identical to Day 1 Step 3 per-call-site migration. Replace each `::create()` / `new + ->save()` with `app(SettlementPaymentReconciler::class)->upsertOne($businessId, $settlementNo, $table, $data)`. For sites that key on `customer_payment_id` instead of `pump_payment_id`, pass that as the 5th argument.

After all 15 sites migrate:
1. **Remove** the SettlementSW entry from `Modules/Petro/Entities/Concerns/RequiresReconcilerContext.php` — the `$reconcilerCallerAllowlist` array.
2. **Remove** the SettlementSW entry from `tests/Static/NoDirectSettlementPaymentWritesTest.php` — the `$allowed` array.
3. Both removals MUST happen in the same PR as the call-site migrations. The static test and runtime guard will both flag any remaining direct `::create()` if you missed a site.

**Acceptance:**
- `php artisan test --filter SettlementPaymentUpsertOneTest` — green (no regression).
- `php artisan test --filter ModelGuardThrowsOutsideReconcilerTest` — green.
- `php artisan test --group static-discipline` — green even with SW entries removed from the allowlist. If red, a `::create()` site was missed.
- Manual browser test: SettlementSW edit/save flow still works (cash/card/cheque save buttons don't throw `RuntimeException`).

### 1.2 VatSettlement migration (3 sites)

VatSettlement uses different model classes (`VatSettlementCardPayment`, `VatSettlementCashPayment`, `VatSettlementCreditSalePayment`). Lock 2's trait is only applied to Petro's four models, so VAT writes don't throw today — but they also don't get the idempotency or unique-key safety.

**Files:**
- `Modules/Vat/Http/Controllers/VatAddPaymentController.php` — lines 289 (cash), 361 (card), 454 (credit_sale)

**VAT is structurally different from Petro and the Reconciler must be extended, not just configured.** Three constraints discovered in review:

1. **VAT is a customer-payment flow, not a pumper-payment flow.** None of the VAT settlement tables have a `pump_payment_id` column — the natural identity is different per table:
   - `vat_settlement_cash_payments` → `customer_payment_id` (FK to `customer_payments`)
   - `vat_settlement_card_payments` → `customer_payment_id`
   - `vat_settlement_credit_sale_payments` → `transaction_id` (FK to `transactions`)
2. The current Reconciler's `ALLOWED_IDENTITY_KEYS = ['pump_payment_id', 'customer_payment_id']` does NOT include `transaction_id`, and the Reconciler's NULL-pump_payment_id orphan branch is hardcoded — it would always-insert VAT credit-sale rows even when they should upsert.
3. VAT tables have no UNIQUE constraint today. Adding one keyed on `pump_payment_id` would be wrong; it must be keyed on the appropriate identity column per table.

**Required Reconciler extensions (before any VAT call site is migrated):**

1. Extend `ALLOWED_IDENTITY_KEYS` to include `'transaction_id'`.
2. Extend `TABLE_TO_MODEL` with the 3 VAT entries.
3. Document the **canonical identity per table** in a new constant inside `SettlementPaymentReconciler`:
   ```php
   private const TABLE_DEFAULT_IDENTITY = [
       'settlement_card_payments'            => 'pump_payment_id',
       'settlement_cash_payments'            => 'pump_payment_id',
       'settlement_cheque_payments'          => 'pump_payment_id',
       'settlement_credit_sale_payments'     => 'pump_payment_id',
       'vat_settlement_card_payments'        => 'customer_payment_id',
       'vat_settlement_cash_payments'        => 'customer_payment_id',
       'vat_settlement_credit_sale_payments' => 'transaction_id',
   ];
   ```
   Make the `$identityKey` parameter of `upsertOne()` default to `TABLE_DEFAULT_IDENTITY[$table]` instead of the current hardcoded `'pump_payment_id'`. Callers that pass an explicit identity-key keep working. New callers don't need to specify one for the common case.

**Required schema changes per VAT table:**

```sql
-- vat_settlement_cash_payments — identity is customer_payment_id
ALTER TABLE `vat_settlement_cash_payments`
  ADD UNIQUE KEY `uk_vat_scp_settlement_customer_payment`
  (`business_id`, `settlement_no`, `customer_payment_id`);

-- vat_settlement_card_payments — identity is customer_payment_id
ALTER TABLE `vat_settlement_card_payments`
  ADD UNIQUE KEY `uk_vat_scdp_settlement_customer_payment`
  (`business_id`, `settlement_no`, `customer_payment_id`);

-- vat_settlement_credit_sale_payments — identity is transaction_id
ALTER TABLE `vat_settlement_credit_sale_payments`
  ADD UNIQUE KEY `uk_vat_scsp_settlement_transaction`
  (`business_id`, `settlement_no`, `transaction_id`);
```

⚠ **Per-table preflight before each ALTER**, identical pattern to Day 1's Lock 1:
```sql
SELECT business_id, settlement_no, customer_payment_id, COUNT(*) AS c
FROM vat_settlement_cash_payments
WHERE customer_payment_id IS NOT NULL
GROUP BY business_id, settlement_no, customer_payment_id HAVING c > 1;
```
Must return zero rows before the ALTER. If non-zero, surgical dedup (keep MAX(id)) per key before retrying. Repeat for the other two tables with the appropriate identity column.

**Then, in this order:**

1. Run the per-table preflights. Resolve any duplicates manually.
2. Apply the 3 ALTERs.
3. Extend the Reconciler (3 small changes: ALLOWED_IDENTITY_KEYS, TABLE_TO_MODEL, TABLE_DEFAULT_IDENTITY).
4. Apply `RequiresReconcilerContext` trait to the 3 VAT models.
5. Migrate the 3 VAT call sites to `upsertOne($businessId, $settlementNo, $table, $data)` — no explicit identity-key argument needed; the new TABLE_DEFAULT_IDENTITY map picks the right column per table.

**Acceptance:**
- New test `tests/Feature/Vat/VatSettlementPaymentLockTest.php` covers all 3 VAT models against all 3 locks (DB UNIQUE, Eloquent guard, CI static rule).
- New test asserts `upsertOne` on each VAT table uses the documented default identity (not `pump_payment_id`).
- Manual browser test: VAT settlement edit/save flow still works end-to-end across all 3 payment types.

### 1.3 Enable Lock 2 `updating` guard

Day 1 deliberately left `updating` un-guarded to avoid breaking `$model->fill($data)->save()` edit flows that hadn't been migrated through `upsertOne()` yet. Phase 1.3 turns it on after every edit flow goes through `upsertOne()`.

**Pre-requisites (MUST be done before flipping the guard):**

1. **Find every `->fill()->save()` / `$model->save()` on the 4 models** that runs OUTSIDE `withBypass`. Grep:
   ```
   SettlementCardPayment::*save\(\)
   SettlementCashPayment::*save\(\)
   SettlementChequePayment::*save\(\)
   SettlementCreditSalePayment::*save\(\)
   ```
   plus `$<var>->save()` where `$<var>` is one of those types.
   Known sites: `AddPaymentController.php:384-385, 391-392, 574`, plus the destroy-path saves (`SettlementPDController.php:7000-7068`).
2. **Migrate each through `upsertOne()`** OR wrap in `SettlementPaymentReconciler::withBypass(...)` as a temporary exception. For the destroy path, wrap in `withBypass` and mark `// PHASE5-DEFERRED` — Phase 5 handles destroy.
3. Confirm `ModelGuardAllowsUpdatingTodayTest` still passes (it asserts updates DON'T throw today).
4. Flip the test: rename to `ModelGuardThrowsOnUpdatingOutsideReconcilerTest`, change assertion to `expectException(RuntimeException::class)`.
5. **Edit the trait** `RequiresReconcilerContext::bootRequiresReconcilerContext()` to add:
   ```php
   static::updating(function ($model) {
       if (app()->bound('petro.reconciler.active')) {
           return;
       }
       $caller = self::detectCallerNamespace();
       foreach (self::$reconcilerCallerAllowlist as $allowedPrefix) {
           if ($caller !== null && str_starts_with($caller, $allowedPrefix)) {
               return;
           }
       }
       throw new \RuntimeException(static::class . " updates are restricted to SettlementPaymentReconciler.");
   });
   ```

**Acceptance:**
- All Day-1 lock tests still green.
- New test `ModelGuardThrowsOnUpdatingOutsideReconcilerTest` green.
- Manual browser test: every save flow (Add Payment edit, Settlement PD edit, Pumper Dashboard credit-sale edit) works without throwing.

---

## Phase 2 — Historical data backfill (target: 1 day)

**Goal:** populate `pump_payment_id` on legacy settlement_*_payments rows and `pump_operator_assignments.settlement_id` on historical settlements, so legacy reports and the Phase 3 FK fast-path work uniformly.

⚠ **Run preflight queries first on each client DB.** Backfills are destructive only insofar as they change column values; they don't drop rows. But verify scope before running.

### 2.1 Backfill `pump_payment_id` on the 4 settlement_*_payments tables

**Migration:** `database/migrations/2026_05_14_000001_backfill_pump_payment_id_on_legacy_settlement_rows.php`

For each table, match by composite key against `pump_operator_payments` and pair by ORDER BY id. The composite key (business_id, pump_operator_id, payment_amount, collection_form_no, settlement_no, payment_type) is NOT unique — paired-by-position is the documented strategy from Day 1 v3.

**SQL skeleton (one block per table — card / cash / cheque / credit_sale):**

```sql
-- Backfill settlement_card_payments.pump_payment_id from pump_operator_payments
UPDATE settlement_card_payments scp
JOIN (
    SELECT
        scp_inner.id AS scp_id,
        (
            SELECT pop.id FROM pump_operator_payments pop
            WHERE pop.business_id = scp_inner.business_id
              AND CAST(pop.payment_amount AS DECIMAL(15,6)) = scp_inner.amount
              AND COALESCE(pop.settlement_no, '') = COALESCE(scp_inner.settlement_no, '')
              AND pop.payment_type = 'card'
            ORDER BY pop.id ASC LIMIT 1
        ) AS pop_id
    FROM settlement_card_payments scp_inner
    WHERE scp_inner.pump_payment_id IS NULL
) AS matched ON matched.scp_id = scp.id
SET scp.pump_payment_id = matched.pop_id
WHERE matched.pop_id IS NOT NULL;
```

Repeat for `settlement_cash_payments`, `settlement_cheque_payments`, and `settlement_credit_sale_payments` (the credit_sale one also keys on `pump_operator_id` and `collection_form_no` + `is_from_pumper = 1`).

**Tracking unmatched rows:**

```sql
CREATE TABLE IF NOT EXISTS petro_refactor_unmatched_payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  table_name VARCHAR(64) NOT NULL,
  row_id INT UNSIGNED NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL,
  KEY idx_petro_refactor_unmatched_table_row (table_name, row_id)
) ENGINE=InnoDB;

-- Log unmatched after each backfill block:
INSERT INTO petro_refactor_unmatched_payments (table_name, row_id, reason, created_at)
SELECT 'settlement_card_payments', id, 'no pump_operator_payments match by composite key', NOW()
FROM settlement_card_payments
WHERE pump_payment_id IS NULL;
```

**Acceptance:**
- New test `tests/Feature/Petro/Phase2BackfillTest.php` asserts: for every settlement_*_payments row with a matching pump_operator_payments row, `pump_payment_id` is populated.
- Sum of `amount` per settlement_no in each table is unchanged (sanity).
- `petro_refactor_unmatched_payments` row count is reasonable (≤5% of rows). High mismatch ratio → review composite-key collision policy.

### 2.2 Backfill `pump_operator_assignments.settlement_id` for historical settlements

The Day 1 finalize fix only covers NEW settlements. Historical PD settlements still have assignments with `settlement_id = NULL` and `closed_in_settlement = 0`. The repair query from Day 1 testing handles one settlement at a time — this migration batches them all.

**Migration:** `database/migrations/2026_05_14_000002_backfill_assignment_settlement_link.php`

```sql
-- For each finalized Petro PD settlement (status=0), link its assignments via
-- the work_shift→shift_number→shift_id resolution.
UPDATE pump_operator_assignments poa
JOIN settlements s
  ON s.business_id = poa.business_id
 AND s.pump_operator_id = poa.pump_operator_id
 AND s.status = 0  -- finalized
JOIN JSON_TABLE(
    CASE
        WHEN JSON_VALID(s.work_shift) THEN s.work_shift
        ELSE CONCAT('[', s.work_shift, ']')
    END,
    '$[*]' COLUMNS (work_shift_no VARCHAR(50) PATH '$')
) AS ws
  ON CAST(ws.work_shift_no AS UNSIGNED) = poa.shift_number
SET poa.settlement_id = s.id,
    poa.closed_in_settlement = 1
WHERE poa.settlement_id IS NULL
  AND poa.shift_id IS NOT NULL;
```

⚠ **`JSON_TABLE` requires MySQL 8.0+ or MariaDB 10.6+.** Run this preflight first:

```sql
SELECT VERSION();
```

If the server is MariaDB <10.6 or MySQL <8.0, replace the JSON_TABLE block with a PHP-side loop that iterates settlements and updates assignments per-row.

**Preflight to count what will change:**

```sql
SELECT COUNT(DISTINCT s.id) AS settlements_to_repair,
       COUNT(*) AS assignments_to_repair
FROM pump_operator_assignments poa
JOIN settlements s
  ON s.business_id = poa.business_id
 AND s.pump_operator_id = poa.pump_operator_id
 AND s.status = 0
WHERE poa.settlement_id IS NULL
  AND poa.shift_id IS NOT NULL;
```

**Acceptance:**
- Pre-flight count > 0 → backfill runs → post-count = 0.
- New regression test in `AssignmentLinkedOnFinalizeTest`: assert `assignments.settlement_id IS NULL` count for status=0 settlements drops to 0 after the migration.
- Manual browser test: open a previously-empty edit page (one that showed "No data available in table") — should now populate.

### 2.3 Backfill `petro_settlement_id` on `transactions` / `account_transactions`

**Migration:** `database/migrations/2026_05_14_000003_backfill_petro_settlement_id_on_transactions.php`

```sql
UPDATE transactions t
JOIN settlements s
  ON s.business_id = t.business_id
 AND (
      t.invoice_no = s.settlement_no
   OR t.ref_no LIKE CONCAT('%settlement #', s.settlement_no, '%')
   OR t.ref_no LIKE CONCAT('%Settlement No: ', s.settlement_no, '%')
   OR t.ref_no LIKE CONCAT('%Settlement No.', s.settlement_no, '%')
 )
SET t.petro_settlement_id = s.id
WHERE t.petro_settlement_id IS NULL AND t.deleted_at IS NULL;

UPDATE account_transactions at
JOIN transactions t ON t.id = at.transaction_id
SET at.petro_settlement_id = t.petro_settlement_id
WHERE at.petro_settlement_id IS NULL
  AND t.petro_settlement_id IS NOT NULL
  AND at.deleted_at IS NULL;
```

**Acceptance:**
- Post-backfill: `SELECT COUNT(*) FROM transactions WHERE petro_settlement_id IS NULL AND invoice_no IN (SELECT settlement_no FROM settlements)` returns < 5% (most should match).
- Trait test from Day 1 (`TransactionsHaveSettlementFkOnNewWritesTest`) still green.

---

## Phase 3 — Writer-side FK population & remove LIKE fallback (target: 0.5 day)

**Goal:** every new transaction created by Petro PD writes `petro_settlement_id`. Once that's true AND Phase 2.3 has backfilled, the LIKE fallback in `UpdatesSettlementTransactions` can be removed.

### 3.1 Populate `petro_settlement_id` at every transaction create site

**Find every site:**
```
Transaction::create
new Transaction(
DB::table('transactions')->insert
```
Within Petro / PumperDashboard / SettlementSW modules. Add `'petro_settlement_id' => $settlement->id` where the call site has a `$settlement` in scope.

If the call site builds a transaction for something OTHER than a settlement (e.g. pure customer payment), leave `petro_settlement_id` unset. The column is nullable.

**Acceptance:** new test `tests/Feature/Petro/Phase3WriterPopulationTest.php` — creates a fresh PD settlement via the controller method (with `withBypass` for the seeder bits), asserts every resulting `transactions` row for that settlement has `petro_settlement_id` populated.

### 3.2 Retire the LIKE fallback in `UpdatesSettlementTransactions` (staged rollout)

**File:** `Modules/Petro/Http/Controllers/Traits/UpdatesSettlementTransactions.php`

Day 1 added a coexistence path: FK first, LIKE fallback for rows where `petro_settlement_id IS NULL`. After Phase 2.3 + Phase 3.1 the LIKE fallback **should** be dead code — but real client DBs may surface edge cases the backfill missed (oddly-formatted `ref_no`, rows created during the migration window, etc.). **Do not delete the LIKE branch in one shot.** Stage it over three weeks behind a config flag.

**Stage A (Phase 3.2 ships):** add a config flag, default to "FK + LIKE coexistence" — same behaviour as Day 1.

`config/petro.php` (create if absent):

```php
return [
    // FK-only mode: when true, UpdatesSettlementTransactions only uses petro_settlement_id
    // lookups and never falls back to LIKE matching on ref_no/invoice_no.
    //
    // Rollout plan:
    //   Week 0: false (default) — Day 1 behaviour
    //   Week 1: false on prod, true in staging — observe staging
    //   Week 2: true on prod, with metrics monitoring (see acceptance)
    //   Week 3: branch deleted entirely, flag retired
    'fk_only_settlement_lookup' => env('PETRO_FK_ONLY_SETTLEMENT_LOOKUP', false),
];
```

Update the trait to consult the flag:

```php
if (config('petro.fk_only_settlement_lookup', false)) {
    // Strict FK-only path. Skip LIKE branch entirely.
    $transactionIds = Transaction::where('business_id', $business_id)
        ->where('petro_settlement_id', $settlement->id)
        ->whereNull('deleted_at')
        ->pluck('id');
} else {
    // Coexistence path (Day-1 behaviour). FK preferred, LIKE fallback for
    // rows where petro_settlement_id IS NULL.
    // ...existing dual-path code...
}
```

**Stage B (one week later, staging-only):** set `PETRO_FK_ONLY_SETTLEMENT_LOOKUP=true` in staging env. Run a soak test for 5 working days. Watch for:
- Date-change cascade misses (settlement transaction_date edited but linked transactions stayed on old date)
- Any error log entry mentioning `petro_settlement_id`
- Settlement-edit save flows still completing in < 2s

**Stage C (one week later, prod):** flip prod env flag to `true`. Continue monitoring for one week.

**Stage D (one week later):** delete the LIKE branch from the trait. Remove the config flag. Remove the `Schema::hasColumn` guard around the FK branch (the column is guaranteed present at this point — Day 1's migration is in every environment).

**Per-stage acceptance:**

| Stage | Acceptance |
|---|---|
| A | Day 1 cascade test still passes. Flag exists, defaults to false. Both code paths exercised by tests. |
| B (staging) | Run the cascade test against a snapshot of prod data. Zero settlement-edit failures across 5 days of staging traffic. |
| C (prod) | Real-user settlement edits succeed. The `petro_settlement_id IS NULL` row count on `transactions` for status=0 settlements stays at zero or near-zero. Performance smoke test: a settlement with 50+ transactions edits in < 200ms (was multiple seconds under LIKE). |
| D | LIKE branch deleted. Day 1 cascade test STILL passes (asserts on the FK branch only). |

Do not skip stages.

---

## Phase 4 — API formalization (target: 1 day)

### 4.1 `SettlementPaymentEditService` write facade

**Goal:** controllers should not call `SettlementPaymentReconciler::upsertOne()` directly. They should call a domain-facade like `SettlementPaymentEditService::editCreditSale($settlementId, $scspId, $data)` that handles the full transaction — settlement_*_payments update + linked pump_operator_payment update + linked daily_voucher/daily_collection update + transaction/account_transaction update + ledger update.

This is the IS1293-fix block at `AddPaymentController.php:5230-5310` extracted into a dedicated service.

**File:** `Modules/Petro/Services/SettlementPaymentEditService.php`

**Methods:**
```php
public function editCreditSale(int $businessId, int $scspId, array $data): SettlementCreditSalePayment;
public function editCardPayment(int $businessId, int $scpId, array $data): SettlementCardPayment;
public function editCashPayment(int $businessId, int $sccpId, array $data): SettlementCashPayment;
public function editChequePayment(int $businessId, int $scqpId, array $data): SettlementChequePayment;
public function deletePaymentLine(int $businessId, string $table, int $rowId): void;
```

Each method:
1. Loads the row by id (not composite).
2. Wraps in DB transaction.
3. Calls `Reconciler::upsertOne` to update the row.
4. Cascades to the source `pump_operator_payments` row via `pump_payment_id`.
5. Recomputes any aggregate totals (`daily_collections.current_amount`, `daily_vouchers.total_amount`) from SUM, not single-row assignment.
6. Updates `transactions` / `contact_ledgers` / `account_transactions` keyed on `transaction_id` (not composite).

**Migrate `AddPaymentController.php:5230-5310` to call `editCreditSale`.** Then do the same for card / cash / cheque edit blocks.

#### 4.1.a Absorb the temporary `withBypass` wraps from Phase 1.3

When Phase 1.3 turned on Lock 2's `updating` guard, nine `->save()` / `->update()` sites that previously ran outside any Reconciler context were wrapped in `SettlementPaymentReconciler::withBypass(...)` as a **temporary** fix to keep them working. They are not architecturally correct — each one is a "post-process: write one more field after `upsertOne()` returns" pattern that should be eliminated by passing the field into the original `upsertOne()` call (or its `SettlementPaymentEditService` equivalent).

**Phase 4.1 must absorb all nine sites into the EditService and remove the `withBypass` wrappers in the same PR.** After Phase 4.1 ships, none of the file:line locations below should still contain a `SettlementPaymentReconciler::withBypass` call — `git grep withBypass Modules/` should only return the seeder and the (Phase 5) destroy path.

The nine sites and the field each one writes post-upsert:

| File | Line (as of 2026-05-14) | Post-process field | EditService method that absorbs it |
|---|---|---|---|
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | 5243 | full update of credit sale fields | `editCreditSale` (legacy composite-lookup branch — kill the lookup, use scsp_id) |
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | 5258 | `pump_payment_id` linkage | `editCreditSale` — pass `pump_payment_id` in `$data` to original `upsertOne` |
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | 386, 398, 582, 4109, 4760, 4964 | various post-`upsertOne` field updates | `editCardPayment` / `editCheque…` / `editCash…` — absorb each into the service's single transaction |
| `Modules/Petro/Http/Controllers/DailyVoucherController.php` | 489 | `transaction_id` | `editCreditSale` (or new `linkTransaction($scspId, $transactionId)` method) |
| `Modules/Petro/Http/Controllers/DailyVoucherController.php` | 747 | full update of credit sale fields | `editCreditSale` |
| `Modules/Petro/Http/Controllers/PumpOperatorPaymentController.php` | 2395 | `daily_voucher_id` | `editCreditSale` (or new `linkDailyVoucher`) |
| `Modules/Petro/Http/Controllers/SettlementController.php` | 4089, 4210 | `transaction_id`, `is_from_pumper`, `is_committed` | `editCreditSale` + `commit` |
| `Modules/Petro/Http/Controllers/SettlementPDController.php` | 3571, 4722, 4865 | `customer_payment_id`, `transaction_id`, commit flags | `editCashPayment` / `editCreditSale` |
| `Modules/PumperDashboard/Http/Controllers/PumpOperatorPaymentController.php` | 2229, 2255 | `daily_voucher_id`, `transaction_id` | `editCreditSale` |
| `Modules/SettlementSW/Http/Controllers/SWAddPaymentController.php` | 1811 | `transaction_id`, `transaction_payment_id` | `editCashPayment` |

(Line numbers will drift as the codebase evolves; use `git grep "SettlementPaymentReconciler::withBypass" Modules/` to enumerate the current set when Phase 4.1 starts.)

**Acceptance addition:** new test `tests/Static/NoTemporaryWithBypassOutsideReconcilerTest.php` runs in `@group static-discipline` and asserts:
- `SettlementPaymentReconciler::withBypass` appears at most twice in `Modules/`: once in `PetroDummyDataSeeder` and once in the Phase 5 destroy-path code (`SettlementPDController::destroy` or the equivalent destroy method that orchestrates `wipeAllForSettlement`). Any other location is a violation.

**Acceptance:** new test `SettlementPaymentEditServiceTest.php` with cases per method, plus the static check above.

### 4.2 Migrate 3 read-consumers to `SettlementPaymentQueryService`

Day 1 built the service but did NOT migrate the controller consumers. Do that now:

1. **`PumpOperatorPaymentController::index()` payment-summary methods** — replace direct `PumpOperatorPayment::join(...)` query with `$service->paymentsForShift()` or `paymentsForSettlement()`.
2. **`Resources/views/pump_operators/meters_with_payments.blade.php` data source.**
3. **`RealTimeEntriesController.php`** — the GET that loads payment summary for meters-with-payments view.

**Acceptance:** the affected views render with identical shape (row count, totals, order) before and after. Test with a recorded baseline.

### 4.3 Fix `AddPaymentController.php:5143` `existing_payment` lookup

The lookup at line 5143 uses 10 `where()` clauses including `where('amount', $amount)`. When the user edits an amount, the lookup looks for a row with the NEW amount, fails, and falls through to INSERT — creating a duplicate.

**Fix:** the edit form must send the row id. The controller looks up by id:

```php
// Replace the 10-condition composite lookup:
$existing_payment = null;
if ($request->filled('scsp_id')) {
    $existing_payment = SettlementCreditSalePayment::where('id', $request->input('scsp_id'))
        ->where('business_id', $business_id)
        ->first();
}
// Legacy fallback: if no scsp_id, use the composite (creating new row is still possible).
if (! $existing_payment) {
    // ... composite lookup as before, but mark with DAY1-LEGACY-LOOKUP
}
```

**Blade template update:** edit forms must include a hidden `<input name="scsp_id" value="{{ $payment->id }}">`.

**Acceptance:** new test that edits a credit sale's amount (changing it), verifies the same row got updated (not a new one created).

---

## Phase 5 — Destroy path (target: 1 day)

**Goal:** replace the wipe-and-reinsert pattern at `SettlementPDController.php:7000-7068` with diff-and-upsert. The wipe path runs inside `if ($is_destory)` and deletes from 9 tables. Five of those tables (OtherSale, OtherIncome, CustomerPayment, SettlementCashDeposit, SettlementExpense/Excess/ShortagePayment) were not in Day 1 scope.

### 5.1 Add a destroy-specific wipe primitive to the Reconciler

⚠ **`reconcileSet(.., collect())` is NOT a safe destroy primitive.** The current implementation queries `$existing` with `whereNotNull('pump_payment_id')` — so rows with NULL `pump_payment_id` (the orphan-write path) are NEVER added to `$existing`, NEVER end up in `$toDelete`, and survive the "wipe" intact. For destroy that's a correctness bug — orphan rows for the wiped settlement would remain in the table, polluting reports and producing zombie ledger entries.

Two ways to handle this. **Recommended: add a dedicated method** rather than overloading `reconcileSet`.

**Add `Reconciler::wipeAllForSettlement($businessId, $settlementNo, string $table): int`:**

```php
/**
 * Destroy-path primitive. Deletes EVERY row in $table for the given
 * (business_id, settlement_no), including rows where pump_payment_id IS NULL.
 *
 * Use ONLY from the destroy/revert flow. Do not call from edit flows —
 * reconcileSet() and upsertOne() are the correct primitives there.
 *
 * Returns the number of rows deleted.
 */
public function wipeAllForSettlement(int $businessId, string $settlementNo, string $table): int
{
    $this->assertTable($table);

    return DB::transaction(function () use ($businessId, $settlementNo, $table) {
        app()->instance('petro.reconciler.active', true);
        try {
            return DB::table($table)
                ->where('business_id', $businessId)
                ->where('settlement_no', $settlementNo)
                ->delete();
        } finally {
            app()->forgetInstance('petro.reconciler.active');
        }
    });
}
```

**Why a separate method, not `reconcileSet(empty)`:**
- The `reconcileSet` contract is "converge the matched-by-identity rows to the desired set." Extending it to also wipe orphans would change that contract subtly and risk silent over-deletion in some future caller that passes an empty collection unintentionally.
- A method named `wipeAllForSettlement` makes the destructive intent explicit at every call site. Code review can ban it everywhere except the destroy path with a static lint rule.
- Reviewers reading the destroy block see `wipeAllForSettlement` and immediately understand the semantic, vs `reconcileSet(.., collect())` which reads like "reconcile to no rows" and hides the orphan-survival surprise.

#### 5.1.a Also generalize `reconcileSet()` to use `TABLE_DEFAULT_IDENTITY`

While in the Reconciler for Phase 5.1, fix a latent bug discovered during Phase 1 review: `reconcileSet()` is hardcoded to `pump_payment_id` at three places (the `whereNotNull('pump_payment_id')` filter on `$existing`, the `keyBy('pump_payment_id')` calls on both `$existing` and `$desired`, and the `whereIn('pump_payment_id', …)` in the delete branch). This breaks the moment any caller passes a VAT table (where the identity is `customer_payment_id` or `transaction_id`) because those tables have no `pump_payment_id` column at all — the query will throw `Unknown column`.

Today no production code calls `reconcileSet()` so the bug is latent, but Phase 1.2 added VAT tables to `TABLE_TO_MODEL` which means any future call against a VAT table would hit it. Fix the method to use `TABLE_DEFAULT_IDENTITY[$table]` (with an optional override parameter mirroring `upsertOne()`'s `$identityKey`):

```php
public function reconcileSet(
    int $businessId,
    string $settlementNo,
    string $table,
    Collection $desiredRows,
    ?string $identityKey = null
): array {
    $this->assertTable($table);
    $identityKey = $identityKey ?? self::TABLE_DEFAULT_IDENTITY[$table];
    if (! in_array($identityKey, self::ALLOWED_IDENTITY_KEYS, true)) {
        throw new \InvalidArgumentException("Unsupported identity key: {$identityKey}");
    }

    return DB::transaction(function () use ($businessId, $settlementNo, $table, $desiredRows, $identityKey) {
        app()->instance('petro.reconciler.active', true);
        try {
            $existing = DB::table($table)
                ->where('business_id', $businessId)
                ->where('settlement_no', $settlementNo)
                ->whereNotNull($identityKey)
                ->get()
                ->keyBy($identityKey);

            $desired = $desiredRows
                ->filter(fn($r) => ! is_null($r[$identityKey] ?? null))
                ->keyBy(fn($r) => $r[$identityKey]);

            $orphans = $desiredRows->filter(fn($r) => is_null($r[$identityKey] ?? null));

            $toInsert = $desired->diffKeys($existing);
            $toUpdate = $desired->intersectByKeys($existing);
            $toDelete = $existing->diffKeys($desired);

            foreach ($toInsert as $row) {
                DB::table($table)->insert(array_merge((array) $row, [
                    'business_id'   => $businessId,
                    'settlement_no' => $settlementNo,
                ]));
            }
            foreach ($toUpdate as $identityValue => $row) {
                DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->where($identityKey, $identityValue)
                    ->update((array) $row);
            }
            if ($toDelete->isNotEmpty()) {
                DB::table($table)
                    ->where('business_id', $businessId)
                    ->where('settlement_no', $settlementNo)
                    ->whereIn($identityKey, $toDelete->keys()->all())
                    ->delete();
            }
            foreach ($orphans as $row) {
                DB::table($table)->insert(array_merge((array) $row, [
                    'business_id'   => $businessId,
                    'settlement_no' => $settlementNo,
                ]));
            }

            return [
                'inserted' => $toInsert->count(),
                'updated'  => $toUpdate->count(),
                'deleted'  => $toDelete->count(),
                'orphans'  => $orphans->count(),
            ];
        } finally {
            app()->forgetInstance('petro.reconciler.active');
        }
    });
}
```

**Acceptance addition for Phase 5.1.a:** extend `SettlementPaymentReconcileSetTest.php` with two new cases:
- `reconcileSet` on `vat_settlement_credit_sale_payments` with `identityKey = 'transaction_id'` (or no explicit arg — the default picks transaction_id) works end-to-end with insert/update/delete branches.
- `reconcileSet` on `vat_settlement_cash_payments` with `identityKey = 'customer_payment_id'` works the same way.

**Rewrite the destroy block at `SettlementPDController.php:7000-7068`:**

```php
$reconciler = app(SettlementPaymentReconciler::class);
foreach ([
    'settlement_card_payments',
    'settlement_cash_payments',
    'settlement_cheque_payments',
    'settlement_credit_sale_payments',
] as $table) {
    $reconciler->wipeAllForSettlement(
        $business_id,
        (string) $settlement->id,
        $table
    );
}
```

For the OTHER five tables in the destroy block (`OtherSale`, `OtherIncome`, `CustomerPayment`, `SettlementCashDeposit`, `SettlementExpense/Excess/ShortagePayment`), two options:

- **Option A:** extend `TABLE_TO_MODEL` to include them and reuse `wipeAllForSettlement`. Clean. Requires `RequiresReconcilerContext` trait on the additional models (so Lock 2 covers them too). Adds 5 small files of work.
- **Option B:** keep the legacy `Model::where('settlement_no', $settlement->id)->delete()` lines. Mark with `// PHASE-5-PARTIAL: covered by Part 3 architectural refactor`. Less code, less coverage.

Pick Option A if you have the half-day to extend. Pick Option B if Part 5 is being shipped under time pressure and you want to defer the other five tables to a later phase.

### 5.2 Convert string-LIKE identity to FK-based deletes in the cascade

The current destroy block also tears down related transactions / account_transactions / contact_ledgers using the same LIKE patterns the `UpdatesSettlementTransactions` trait uses. After Phase 3.2 Stage D completes, those tear-down queries can swap to `WHERE petro_settlement_id = $settlement->id` for transactions and `WHERE transaction_id IN (...)` for the rest.

⚠ Do this AFTER Phase 3.2 Stage D, not before. If LIKE is still in coexistence mode, FK-only deletes on the destroy path could miss historical rows the FK doesn't cover yet.

**Acceptance:**
- New test `SettlementPDDestroyPathTest.php` — finalize a settlement, insert one orphan row directly via `withBypass` (NULL `pump_payment_id`), then destroy/revert. Assert ALL rows for that settlement_no are gone — orphan included.
- New test `WipeAllForSettlementOnlyDeletesScopedRowsTest.php` — seed rows for settlement A and settlement B, call `wipeAllForSettlement` for A, assert A's rows gone, B's rows intact.
- Manual browser test: open a finalized settlement with mixed orphan + linked payment rows, destroy/revert it, verify every payment row across all 9 tables for that settlement is removed.

---

## Phase 6 — State machine (target: 1.5 days)

**Goal:** consolidate the five flags that track shift/assignment/settlement state into a single state machine.

### Current state mess

| Column | Purpose | Where set |
|---|---|---|
| `pump_operator_assignments.status` | `open` / `close` | shift close |
| `pump_operator_assignments.is_manually_closed` | 0/1 | manual close button |
| `pump_operator_assignments.closed_in_settlement` | 0/1 | finalize (Day 1 fix) |
| `pump_operator_assignments.settlement_id` | FK to settlements | finalize (Day 1 fix) |
| `pump_operator_assignments.is_confirmed` | 0/1 | shift confirm |
| `petro_shifts.status` | 0/1/2 | shift lifecycle |
| `petro_shifts.closed_time` | timestamp | shift close |

Reads currently OR these together at e.g. [`PetroPdClosedShiftQuery.php:22-30`](Modules/PetroPD/Services/PetroPdClosedShiftQuery.php#L22-L30) — fragile.

### Design

**File:** `Modules/Petro/Services/SettlementStateMachine.php`

Single enum + transitions:

```php
enum ShiftState: string {
    case Draft = 'draft';           // assignment open, no payments yet
    case PumperClosed = 'pumper_closed'; // pumper hit Close Shift
    case InSettlement = 'in_settlement'; // Petro PD has started settling
    case Settled = 'settled';       // finalize complete
    case Reopened = 'reopened';     // settlement was revoked
}

class SettlementStateMachine {
    public function shiftState(int $shiftId): ShiftState;
    public function transition(int $shiftId, ShiftState $to): void;
}
```

Internal mapping rules from the 5 flags to a state — documented in the service. All callers stop reading the raw flags; they read `$stateMachine->shiftState($shiftId)`.

Writers transitioning state set ALL the matching flags atomically. This means the existing OR-soup in `PetroPdClosedShiftQuery::pendingClosedBase` becomes a single state filter.

**Acceptance:**
- New test suite `SettlementStateMachineTest.php` covers each transition + the 5-flag combinations that map to each state.
- The query at `PetroPdClosedShiftQuery::pendingClosedBase` is rewritten to use the state machine.
- Manual browser test: pumper dashboard shift list, Petro PD pending list, settlement list all render with the same data as before.

---

## Phase 7 — Cleanup & UI (target: 1 day — low priority, ship last)

### 7.1 Add edit options to Day Entries view

Currently `PumperDayEntryController.php:622-670` only renders Edit for meter rows. Extend the action column to render Edit for `credit_sale`, `card_payment`, `cash_payment` row types, pointing at the existing edit modal (same URL as Payment Summary).

```php
elseif ($is_admin_user && in_array($row->row_type, ['credit_sale', 'card_payment', 'cash_payment'])) {
    $payment_type = match($row->row_type) {
        'credit_sale' => 'credit',
        'card_payment' => 'card',
        'cash_payment' => 'cash',
    };
    $edit_query = '?type=' . $payment_type;
    if ($row->row_type === 'credit_sale') {
        $edit_query .= '&credit_sale_id=' . urlencode($row->id);
    }
    $html .= '<li><a href="#" data-href="' .
        url('pumper-dashboard/pump-operators/payment/' . $row->id . '/edit') .
        $edit_query . '" class="btn-modal" data-container=".view_modal">' .
        '<i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
}
```

### 7.2 UI / business-rule bugs from geetha docs (~20% of complaint corpus)

Pull each unrelated UI bug from the geetha docs into its own ticket:

- IS1209 / La 922 — cash denomination popup shows when it shouldn't
- La 949 — meter auto-load on close shift
- IS1199 — irrelevant pumps in dropdown
- La 882 — "Receive Pumps" amount should be 0
- IS1240 — post-dated cheque routing
- (… plus any uncovered during Phase 2 manual testing)

Each is its own fix. No architectural change required.

---

## What is explicitly NOT in scope for Part 2

These are tracked here so an over-eager AI doesn't start them inside Part 2's PRs. Each needs its own plan document.

### Deferred to Part 3 (month 2)

- **Splitting `pump_operator_payments` into raw-append-only + working-layer tables.** Big architectural change; needs migration plan + dual-write window + cutover.
- **New `pumper_payment_lines` table.** Same.
- **Decomposing the 6k-12k line controllers** into smaller controllers + form-request validators + view-model classes.
- **Removing `is_used` / `parent_id` / `settlement_no` column overloading** on `pump_operator_payments`. The columns currently serve multiple semantic purposes; need explicit semantics + migration.
- **Account-side audit trail for settlement edits** (separate table tracking who edited what, when).

### Won't fix in Part 2

- The PHPUnit "deprecated XML configuration" warning. Cosmetic, doesn't affect tests.
- The dummy data seeder — touched in Day 1, not revisited.

---

## End-of-each-phase acceptance checklist

For every phase, run (in order):

```bash
php artisan migrate:status   # confirm new migrations applied
php -d memory_limit=512M vendor/bin/phpunit --group=characterization
php -d memory_limit=512M vendor/bin/phpunit --group=static-discipline
php -d memory_limit=512M vendor/bin/phpunit --testsuite=Feature --filter=Petro
```

Any red = stop. Investigate before declaring the phase done. No partial-credit phases.

After Phase 1, also:
- Hand-test SettlementSW edit/save flow.
- Confirm Lock 2's `updating` test (renamed from `ModelGuardAllowsUpdatingTodayTest`) now asserts a throw.

After Phase 2, also:
- Confirm preflight counts dropped to expected post-backfill values.
- Open a previously-broken edit page (one that showed empty Meter Sale tab) — verify it populates.

After Phase 3, also:
- Performance smoke test on date-change cascade.

After Phase 4, also:
- Run the user's manual test plan from Day 1 — confirm IS1293 / S237 / S238 fixes still hold.

After Phase 5, also:
- Test destroy/revert flow on a settlement; confirm all 9 tables' rows for that settlement are gone.

After Phase 6, also:
- Confirm the state-derived queries (pending closed, settled list, draft resume) match the data they returned before.

After Phase 7, no special verification.

---

## Risk register

| Risk | Likelihood | Mitigation |
|---|---|---|
| SettlementSW migration breaks SW writes | Medium | Run Lock-3 static test BEFORE removing allowlist. Manual SW test after migration. |
| Phase 2 backfill on MariaDB <10.6 fails | High if older server | Preflight `SELECT VERSION()`; provide PHP-loop fallback. |
| Phase 3.2 removing LIKE fallback breaks legacy reports | Medium | Keep LIKE fallback behind a feature flag for 1 week post-deploy; remove only after monitoring. |
| Phase 4.3 form change breaks existing edit links bookmarked by users | Low | Make `scsp_id` an additional field, not a replacement; keep legacy composite lookup as deprecated fallback. |
| Phase 5 destroy path bug deletes wrong rows | High | New test exercises destroy on a multi-settlement DB; assert rows for OTHER settlements survive. |
| Phase 6 state machine misclassifies edge cases | High | Comprehensive truth-table tests for the 5-flag combinations. Roll out behind a feature flag. |

---

## Suggested order of execution

If you have to pick one phase to ship per day, this is the priority:

1. **Day 1 → Phase 1** (close the locks — biggest correctness win).
2. **Day 2 → Phase 2** (historical backfill — unblocks Phase 3).
3. **Day 3 → Phase 3** (kill the LIKE fallback — performance win, code cleanup).
4. **Day 4–5 → Phase 4** (API formalization — the biggest code-cleanup win, prevents future re-introductions of IS1293-class bugs).
5. **Day 6 → Phase 5** (destroy path — closes the last wipe-and-reinsert).
6. **Day 7–8 → Phase 6** (state machine — biggest architectural win, but lowest urgency since current flags work).
7. **Day 9 → Phase 7** (UI polish — ship last, no client-facing risk).

Phase 7 can also be done first if a client is blocking on a specific UI bug from the geetha docs. The other phases are sequenced because each unlocks the next.