# Petro / Petro PD Settlement Refactor — Part 3

**Scope:** the architectural endgame deferred from Day 1 and Part 2 — column overloading cleanup, settlement edit audit trail, completion of the LIKE-fallback removal, controller decomposition, and the raw / working layer split for `pump_operator_payments`. After Part 3 the original Day-1 architectural promises are fully delivered.

**Out of scope (handled separately):** IS1313 / IS1312 bug fixes. Those are tracked as a separate hotfix sprint outside Part 3 because they are client-affecting and need to ship faster than the architectural work.

**Total effort:** ~6–9 working weeks. Each phase is independently shippable; you can stop after any phase if a particular structural move is deemed not worth the cost.

**Foundation laid in Day 1 + Part 2 (do NOT re-do):**
- `SettlementPaymentReconciler` (`upsertOne` / `reconcileSet` / `wipeAllForSettlement` / `withBypass`) ✅
- `SettlementPaymentEditService` (per-payment-type cascades) ✅
- `SettlementPaymentQueryService` (read facade with `paymentSummaryBaseQuery`) ✅
- `SettlementStateMachine` + `ShiftState` enum ✅
- DB UNIQUE constraints on the 7 settlement_*_payments tables (4 Petro + 3 VAT) ✅
- `RequiresReconcilerContext` Eloquent trait — guards `creating` and `updating` ✅
- `pump_payment_id` + `customer_payment_id` + `transaction_id` per-table identity wiring ✅
- `petro_settlement_id` FK on `transactions` + `account_transactions` with backfill ✅
- 100+ characterization + lock + reserved-api + static-discipline tests ✅
- Phase 3.2 Stage A config flag `petro.fk_only_settlement_lookup` (Stages C/D pending — Phase 3 of Part 3 completes them) ⏳

---

## Naming convention (carried forward)

| Prefix       | Owner                                                              |
|--------------|--------------------------------------------------------------------|
| `pumper_`    | Pumper Dashboard and Petro PD flows                                |
| `petro_`     | Direct Settlement flows (Petro module proper)                      |
| `settlement_`| Existing convention — keep                                         |
| `pumper_payment_lines` | New canonical working-layer table introduced in Phase 5 |

New tables follow the prefix rule. New columns on existing tables follow the table's own convention.

---

## Hard rules for the AI executing Part 3

1. **One phase per PR.** Phases are independently gated; do not bundle.
2. **Do not run the full test suite yourself.** Hand off to the user at each gate.
3. **Targeted migrations only.** Always `--path=database/migrations/<filename>.php`. Never `php artisan migrate`.
4. **No git commands.** The user handles version control.
5. **Per-phase acceptance gate must be green before declaring done.** No partial credit.
6. **All new migrations must be idempotent** (`Schema::hasColumn` / `hasTable` / `indexExists` guards).
7. **Dual-write / dual-read windows in Phase 5 are load-bearing.** Do not cut over before the soak period ends.
8. **No emojis. No multi-paragraph end-of-task recaps.** One-line per-phase status.
9. **Every Eloquent write on a `pump_operator_payments` row in Phase 1 must go through `PumperPaymentWriteService`** (introduced in 1.1). Direct `PumpOperatorPayment::create` / `->update()` outside the service is forbidden and tracked by a new static check.

---

## Phase 1 — Column overloading cleanup on `pump_operator_payments` (target: 4 working days)

**Goal:** stop using three columns (`is_used`, `parent_id`, `settlement_no`) for multiple semantic meanings each. Introduce typed, clearly-named replacement columns. Migrate every reader; keep the legacy columns dual-written for one release before dropping them.

### Why this matters

The `pump_operator_payments` table has three columns whose semantics drift across the codebase:

| Column | Documented purpose | Actual usage across the 1,500+ query sites |
|---|---|---|
| `is_used` | "consumed by settlement" | Sometimes set to 1 after settlement save, sometimes after card-account-book linkage, sometimes as a soft-delete marker |
| `parent_id` | "linked to settlement_*_payments row id" | Sometimes the scsp/scp/sccp/scqp row id, sometimes a daily_card_id, sometimes a daily_collection_id |
| `settlement_no` | "settlement code" | Sometimes the settlement_no string ('ST5', 'PDST3'), sometimes the numeric `settlements.id` as a string ('25'), sometimes NULL |

This is the column-overloading bug class that produced half the geetha-doc complaints. The locks + reconciler we shipped in Day 1/Part 2 fixed the symptoms; Phase 1 of Part 3 fixes the root.

### 1.1 — Introduce `PumperPaymentWriteService` (1 day)

`Modules/Petro/Services/PumperPaymentWriteService.php`. The canonical write surface for `pump_operator_payments`. Methods:

```php
public function record(int $businessId, array $row): PumpOperatorPayment;        // raw pumper entry (was ::create)
public function linkToSettlement(int $popId, int $settlementId): void;            // sets the new typed FK; sets legacy is_used + settlement_no for dual-write
public function unlinkFromSettlement(int $popId): void;                           // reverses 1.1.linkToSettlement
public function markConsumed(int $popId, string $reason, ?int $parentRowId = null): void;
public function withBypass(callable $cb);                                         // matching helper for seeders
```

All four `pump_operator_payments` mutation paths discovered during Day-1 Grep get routed through this service. The service is the only place where the legacy + new columns are written together during the dual-write window.

Add a static-discipline test `tests/Static/NoDirectPumpOperatorPaymentWritesTest.php` (Lock 4): fails CI if any code outside `PumperPaymentWriteService`, the seeder, or `tests/` calls `PumpOperatorPayment::create(` / `PumpOperatorPayment::*->save()` / `->update()` on a PumpOperatorPayment instance. Allowlist matches the same shape as Lock 3.

### 1.2 — Add typed replacement columns (1 day)

Migration `database/migrations/2026_XX_XX_add_typed_columns_to_pump_operator_payments.php`:

```sql
ALTER TABLE pump_operator_payments
  ADD COLUMN consumed_at        TIMESTAMP NULL DEFAULT NULL AFTER is_used,
  ADD COLUMN consumed_by_table  VARCHAR(64) NULL DEFAULT NULL AFTER consumed_at,
  ADD COLUMN consumed_by_row_id INT(10) UNSIGNED NULL DEFAULT NULL AFTER consumed_by_table,
  ADD COLUMN linked_settlement_id INT(10) UNSIGNED NULL DEFAULT NULL AFTER consumed_by_row_id,
  ADD INDEX idx_pop_consumed (consumed_by_table, consumed_by_row_id),
  ADD INDEX idx_pop_linked_settlement (linked_settlement_id);
```

Semantics:
- `consumed_at` (timestamp) replaces `is_used` (0/1 int): NULL = pending, timestamp = when consumed
- `consumed_by_table` (varchar) + `consumed_by_row_id` (int) replace `parent_id`: explicit polymorphic FK with type+id pair
- `linked_settlement_id` (int FK to `settlements.id`) replaces `settlement_no` for the numeric case: when set, the FK is canonical; `settlement_no` becomes deprecated

Idempotency guard on every column add. Indexes for the two new lookup patterns.

### 1.3 — Backfill (1 day)

Migration `database/migrations/2026_XX_XX_backfill_typed_columns_on_pump_operator_payments.php`:

For each row where `is_used = 1`:
- Set `consumed_at = updated_at` (best approximation — no row-level audit existed before)
- Set `consumed_by_table` + `consumed_by_row_id` by inspecting `parent_id` and reconstructing the linkage (look across `settlement_card_payments`, `settlement_cash_payments`, `settlement_cheque_payments`, `settlement_credit_sale_payments`, `daily_cards`, `daily_collections` for a row whose `pump_payment_id` or `id` matches `parent_id`). Unmatched rows → log to `petro_refactor_unmatched_typed_columns` audit table.

For each row where `settlement_no` is a numeric string:
- `JOIN settlements ON settlements.business_id = pop.business_id AND CAST(settlements.id AS CHAR) = pop.settlement_no` → set `linked_settlement_id = settlements.id`

For each row where `settlement_no` is a code-style string ('ST5', 'PDST3'):
- `JOIN settlements ON settlements.business_id = pop.business_id AND settlements.settlement_no = pop.settlement_no` → set `linked_settlement_id = settlements.id`

Preflight check: count rows that would be unmatched under each branch. If >5% unmatched, escalate before running.

### 1.4 — Migrate consumers in three waves (1 day across the file set)

**Wave A (reads only — non-breaking):** every `->where('is_used', 1)` becomes `->whereNotNull('consumed_at')`; every `->where('settlement_no', $id)` becomes `->where('linked_settlement_id', $id)`. The old column still exists, so this is a parallel-read change.

**Wave B (writes — dual-write):** `PumperPaymentWriteService::linkToSettlement` and `::markConsumed` write to BOTH the legacy columns and the new typed columns. Tracked by a feature flag `petro.dual_write_legacy_pop_columns` (default `true`).

**Wave C (drop legacy):** after one production release with dual-write working cleanly, flip the flag to `false`. Stop writing to the legacy columns. Two weeks later, migration drops `is_used`, `parent_id`, `settlement_no` from `pump_operator_payments`.

Wave C is **not part of Phase 1**. Wave C is scheduled separately as a column-drop migration once the dual-write has been observed in production. Document the schedule in `docs/refactor/phase1-cutover-schedule.md`.

### Phase 1 acceptance

- All 1.1 service methods covered by `tests/Feature/Petro/PumperPaymentWriteServiceTest.php`
- 1.2 migration applies cleanly + the 3 new columns + 2 new indexes exist
- 1.3 backfill: post-migration, every row that had `is_used = 1` has a non-NULL `consumed_at`; >95% have `consumed_by_table` set; the audit table tracks the residue
- 1.4 Wave A reads return identical row counts as the legacy queries on a snapshot of production data (regression test fixture)
- New static check (Lock 4) green: no direct `PumpOperatorPayment` writes outside the service
- All Day-1 + Part-2 tests still green

---

## Phase 2 — Settlement edit audit trail (target: 2 working days)

**Goal:** every settlement-payment write becomes traceable. Who edited what scsp / scp / sccp / scqp / pop row, when, what the prior values were, what the new values were.

### 2.1 — Audit table

Migration `database/migrations/2026_XX_XX_create_settlement_edit_history.php`:

```sql
CREATE TABLE settlement_edit_history (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id       INT UNSIGNED NOT NULL,
  settlement_id     INT UNSIGNED NULL,            -- references settlements.id when known
  settlement_no     VARCHAR(255) NULL,
  table_name        VARCHAR(64) NOT NULL,         -- settlement_card_payments, settlement_credit_sale_payments, pump_operator_payments, etc.
  row_id            INT UNSIGNED NOT NULL,
  operation         ENUM('create','update','delete','wipe') NOT NULL,
  changed_columns   JSON NULL,                    -- {"amount": {"from": 100, "to": 200}, "note": {"from": null, "to": "..."}}
  edited_by_user_id INT UNSIGNED NULL,
  edited_by_ip      VARCHAR(64) NULL,
  edited_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_seh_settlement (settlement_id),
  INDEX idx_seh_row (table_name, row_id),
  INDEX idx_seh_user (edited_by_user_id),
  INDEX idx_seh_edited_at (edited_at)
) ENGINE=InnoDB;
```

### 2.2 — `SettlementEditAuditService`

`Modules/Petro/Services/SettlementEditAuditService.php`:

```php
public function recordCreate(string $table, int $rowId, array $values, ?int $settlementId = null): void;
public function recordUpdate(string $table, int $rowId, array $before, array $after, ?int $settlementId = null): void;
public function recordDelete(string $table, int $rowId, array $values, ?int $settlementId = null): void;
public function recordWipe(string $table, int $businessId, string $settlementNo, int $deletedCount): void;
public function timelineForSettlement(int $settlementId): Collection;
public function timelineForRow(string $table, int $rowId): Collection;
```

`changed_columns` is computed as a column-by-column diff between `$before` and `$after`. NULL → "value" and "value" → NULL are explicit changes; equal values are omitted.

### 2.3 — Wire into existing services

`SettlementPaymentReconciler::upsertOne` and `wipeAllForSettlement` and `reconcileSet` call the audit service after every mutation. `SettlementPaymentEditService::editX` does likewise. `PumperPaymentWriteService::record / linkToSettlement / unlinkFromSettlement / markConsumed` from Phase 1.1 likewise.

User attribution: pulled from `Auth::user()->id` when available; falls back to `null` for system writes (seeders, queue jobs). IP pulled from the current request when available.

### 2.4 — UI hook (optional, deferred)

A read endpoint that returns `timelineForSettlement($id)` as JSON. Wire to a "View edit history" link on the settlement edit page. Optional — Phase 2 ships without the UI; the data is available for any tool that wants to read it. Phase 2 of Part 3 only ships the storage and service layer.

### Phase 2 acceptance

- Audit table exists with the 6 documented columns + 4 indexes
- `SettlementEditAuditService` covered by `tests/Feature/Petro/SettlementEditAuditServiceTest.php` (record + diff math + timeline queries)
- Existing Phase-1 EditService and Reconciler tests still green after wiring
- New end-to-end test that does upsertOne → editCardPayment → wipeAllForSettlement and asserts the audit log contains 3 rows with the expected `operation` and diff

---

## Phase 3 — Complete the LIKE-fallback removal (target: 1 working day code + 2 weeks calendar)

**Goal:** finish Phase 3.2 of Part 2 — turn off the LIKE fallback in `UpdatesSettlementTransactions` and delete the dead branch.

### Pre-conditions

- Phase 3.2 Stage A (config flag introduced, default `false`) — ✅ shipped in Part 2
- Phase 3.2 Stage B (staging env flipped to `true`, 5-day soak) — must be complete before Phase 3 starts
- If Stage B has not been run yet on the user's staging environment, Phase 3 of Part 3 is blocked. Set the env var first and wait 5 working days before starting.

### 3.1 — Stage C: production flip (calendar-time)

Set `PETRO_FK_ONLY_SETTLEMENT_LOOKUP=true` on production. Watch logs for 5 working days. Monitor:
- Any settlement edit that completes with a "transaction_date not updated" complaint
- Any error log entry mentioning `petro_settlement_id` or `UpdatesSettlementTransactions`
- The metric "transactions with `petro_settlement_id IS NULL` for status=0 settlements" — should stay near zero

Acceptance: 5 working days with no Stage-C-attributable issues in production.

### 3.2 — Stage D: code cleanup (1 day)

Once Stage C soak passes:
- Delete the LIKE branch from `UpdatesSettlementTransactions::updateSettlementRelatedTransactions`
- Remove the `if (config('petro.fk_only_settlement_lookup', false))` gate — only the FK path remains
- Remove the `Schema::hasColumn('transactions', 'petro_settlement_id')` defensive guards (the column is guaranteed)
- Delete `config/petro.php` if it contains nothing other than the retired flag
- Remove the `PETRO_FK_ONLY_SETTLEMENT_LOOKUP` env var from any deployment config
- Update test `TransactionsHaveSettlementFkOnNewWritesTest` — drop any test case that exercised the legacy LIKE path

The destroy-cascade ContactLedger by-note delete at `SettlementPDController.php:7016-7018` becomes a FK-based delete now that all transactions have `petro_settlement_id` populated.

### Phase 3 acceptance

- LIKE branch is gone from the trait
- The destroy path uses FK-based deletes for ContactLedger
- Config flag retired
- All tests still green
- One-week production observation confirms no regression

---

## Phase 4 — Controller decomposition (target: 4 working weeks, one controller per week)

**Goal:** decompose the four monster controllers into form-request validators, view-model classes, smaller action-specific controllers, and slim controllers that delegate to the existing service layer. End state: no Petro module controller exceeds 1,500 lines.

**Reality check:** this phase is the largest in calendar terms. It is **risk-free** because the locks + Reconciler + EditService + StateMachine + QueryService already prevent the bug class. Phase 4 is purely about developer ergonomics: making the code navigable, testable, and reviewable. If team capacity runs short, this phase can be paused at any week boundary without affecting correctness.

### Targets

| Controller | Current size | Week | Target after decomposition |
|---|---|---|---|
| `SettlementPDController.php` | 12,861 lines | Week 1 | ≤ 1,500 lines |
| `SettlementController.php` | 10,960 lines | Week 2 | ≤ 1,500 lines |
| `PumpOperatorPaymentController.php` (Petro + PumperDashboard) | 6,684 + 4,000+ lines | Week 3 | ≤ 1,500 lines each |
| `AddPaymentController.php` | 6,338 lines | Week 4 | ≤ 1,500 lines |

### Decomposition template (applied uniformly per controller)

For each target controller:

1. **Extract form requests.** Every controller method that takes `Request $request` and immediately validates fields gets a dedicated `App\Http\Requests\Petro\<Action>Request` class with rules. Rules currently inlined in the controller's body move into the form request.
2. **Extract view models.** Every controller action that returns a view gets a dedicated `App\ViewModels\Petro\<Page>ViewModel` class. The view model takes the validated data + injected services and produces the view-ready array. The controller method becomes 3 lines (`new ViewModel; return view(...);`).
3. **Extract action controllers.** Methods grouped by URL prefix get split into smaller controllers. E.g. `SettlementPDController::store` + `update` + `destroy` + `edit` become 4 controllers (`SettlementPDStoreController`, etc.) under `Modules/Petro/Http/Controllers/SettlementPD/`. Routes file is updated to point at the new controllers.
4. **Delete dead code.** Every commented-out block (there are many — the file shows ~200+ commented-out lines per controller). Every unused private helper. Every duplicated logic block that's now covered by a service.

### Acceptance gates (per controller)

- Decomposed controller's largest file is ≤ 1,500 lines
- All routes still resolve (artisan route:list passes; no 404s)
- Every existing test for that controller still green
- New `tests/Feature/Petro/<Controller>RouteSmokeTest.php` hits each route's GET endpoint and asserts a 200 response (or expected redirect)

### Phase 4 risks

- Route name collisions: middleware bindings often use controller class strings. Update `app/Http/Kernel.php` route middleware registrations and the route names in `Modules/Petro/Http/routes.php` consistently.
- Authorization checks scattered inline: every controller method has its own `abort_if($business_id != ..., 403)` style guards. Extract to a single `PetroAuthorize` middleware applied at the route level.
- Blade templates that reference `action('PumpOperatorPaymentController@edit')` in view files — these must be updated to point at the new controller class strings. Grep for `action(.*Controller@` across `Modules/Petro/Resources/views/` and `Modules/PumperDashboard/Resources/views/`.

### One controller per week, not all four at once

Do NOT parallelize Phase 4 across multiple controllers. The blast radius for any single regression is the whole settlement flow. One controller per week, with a green Petro test suite at the end of each week, is the safest cadence.

---

## Phase 5 — Raw + working layer split for `pump_operator_payments` (target: 3 working weeks)

**Goal:** the original Day-1 architectural vision. `pump_operator_payments` becomes the immutable raw ingestion table. A new `pumper_payment_lines` table is the canonical editable working-layer. Settlement edits mutate the working layer, never the raw table.

### Why this is the biggest move

`pump_operator_payments` is read by 1,500+ query sites across 12+ controllers across 5+ modules. Splitting it is a 3-week project with a deliberate dual-write window and gradual reader migration.

### 5.1 — Create `pumper_payment_lines` (1 day)

Migration `database/migrations/2026_XX_XX_create_pumper_payment_lines.php`:

```sql
CREATE TABLE pumper_payment_lines (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id       INT UNSIGNED NOT NULL,
  source_pop_id     INT UNSIGNED NOT NULL,            -- FK to pump_operator_payments.id (the raw row)
  pump_operator_id  INT UNSIGNED NOT NULL,
  shift_id          INT UNSIGNED NULL,
  payment_type      VARCHAR(20) NOT NULL,
  payment_amount    DECIMAL(15,4) NOT NULL,
  customer_id       INT UNSIGNED NULL,
  card_type         INT UNSIGNED NULL,
  card_number       VARCHAR(100) NULL,
  slip_no           VARCHAR(100) NULL,
  cheque_number     VARCHAR(100) NULL,
  cheque_date       DATE NULL,
  bank_name         VARCHAR(100) NULL,
  note              TEXT NULL,
  collection_form_no VARCHAR(64) NULL,
  linked_settlement_id INT UNSIGNED NULL,             -- FK to settlements.id (when settled)
  consumed_at       TIMESTAMP NULL,
  consumed_by_table VARCHAR(64) NULL,
  consumed_by_row_id INT UNSIGNED NULL,
  edited_by_user_id INT UNSIGNED NULL,
  edited_at         TIMESTAMP NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_pumper_payment_lines_source (source_pop_id),
  INDEX idx_ppl_business_settlement (business_id, linked_settlement_id),
  INDEX idx_ppl_shift (shift_id),
  INDEX idx_ppl_pump_operator (pump_operator_id),
  INDEX idx_ppl_consumed (consumed_by_table, consumed_by_row_id)
) ENGINE=InnoDB;
```

Schema includes everything that the working layer needs to read. Strict FK to `pump_operator_payments` via `source_pop_id` with a UNIQUE constraint — exactly one working line per raw payment.

### 5.2 — Backfill (1 day)

Migration `database/migrations/2026_XX_XX_backfill_pumper_payment_lines_from_pop.php`:

```sql
INSERT INTO pumper_payment_lines (
  business_id, source_pop_id, pump_operator_id, shift_id, payment_type, payment_amount,
  customer_id, card_type, card_number, slip_no, ..., linked_settlement_id, consumed_at, ...
)
SELECT
  pop.business_id, pop.id, pop.pump_operator_id, pop.shift_id, pop.payment_type, pop.payment_amount,
  pop.customer_id, pop.card_type, pop.card_number, pop.slip_no, ...,
  pop.linked_settlement_id, pop.consumed_at, ...
FROM pump_operator_payments pop
WHERE NOT EXISTS (
  SELECT 1 FROM pumper_payment_lines ppl WHERE ppl.source_pop_id = pop.id
);
```

(Card/cheque metadata only populated if `pop.payment_type` matches — defensive `CASE` statements.)

### 5.3 — Dual-write window (1 week)

`PumperPaymentWriteService` (introduced in Phase 1) gains a `writeToLines` method that writes the working-layer row alongside the raw row. Every write path goes through both. The `pumper_payment_lines.source_pop_id` UNIQUE constraint protects against double-write on retries.

Feature flag `petro.use_pumper_payment_lines` (default `false`) controls whether **readers** see the new table or the old one. Writers always write to both during this phase.

Hold the dual-write for 1 calendar week minimum. Monitor:
- Row counts match: `SELECT COUNT(*) FROM pump_operator_payments` ≈ `SELECT COUNT(*) FROM pumper_payment_lines` (with allowance for backfill drift)
- Any error log mentioning `pumper_payment_lines` insert/update failure

### 5.4 — Reader migration (1 week)

Wave through the 1,500+ reader call sites in batches:
- `SettlementPaymentQueryService::paymentSummaryBaseQuery` — change the base table from `pump_operator_payments` to `pumper_payment_lines`. All 4 read consumers from Phase 4.2 of Part 2 inherit the change.
- Other read sites discovered by `git grep "pump_operator_payments"` — migrated in batches of 10-20 per PR, with regression tests asserting row counts/totals unchanged.

The `petro.use_pumper_payment_lines` flag gates each batch: flip to `true` after each batch's readers are migrated. By end of week 4 the flag is `true` everywhere.

### 5.5 — Cutover (1 day)

Once all readers point at `pumper_payment_lines`:
- Flip writes to write ONLY to `pumper_payment_lines` (the new canonical writer path).
- `pump_operator_payments` becomes append-only ingestion: every new raw payment from the pumper dashboard inserts there, and a trigger / explicit service call mirrors to `pumper_payment_lines`.
- Old direct mutations on `pump_operator_payments` (e.g. settlement-link updates) move entirely to `pumper_payment_lines`.

### 5.6 — Maintenance mode (calendar-time)

After 1 month of `pumper_payment_lines` being the canonical working layer with no regressions, evaluate whether `pump_operator_payments` is still needed as a separate raw table. Two options:
- Keep it as an immutable audit log of original pumper entries.
- Phase it out entirely; `pumper_payment_lines` becomes the only payment line table.

This decision is deferred to a future plan.

### Phase 5 acceptance

- `pumper_payment_lines` table exists with all 25+ columns + 5 indexes
- Backfill completes with row-count parity within 1% drift (audit table tracks any unmatched)
- Dual-write window observed for ≥7 calendar days with zero regression
- All 1,500+ reader sites migrated to `pumper_payment_lines`
- Feature flag retired after stable production observation
- All existing Day-1 + Part-2 + Phase-1-4 tests still green
- New `tests/Feature/Petro/PumperPaymentLinesParityTest.php` asserts every `pumper_payment_lines` row's columns match the corresponding `pump_operator_payments` row's columns

---

## Risk register

| Risk | Likelihood | Mitigation |
|---|---|---|
| Phase 1 backfill leaves >5% rows unmatched | Medium | Pre-flight count; audit table for residue; manual review threshold |
| Phase 1.4 Wave A regression on reads | Medium | Snapshot-based regression tests; ship one read site at a time |
| Phase 2 audit writes slow down hot loops | Low | Audit writes are append-only INSERTs; benchmark `wipeAllForSettlement` before/after — fail Phase 2 if >10% latency increase |
| Phase 3 Stage C surfaces edge cases not seen on staging | Medium | Keep config flag for instant rollback; monitor for 5 working days |
| Phase 4 controller decomposition breaks routes | High | Per-controller route smoke test; one controller per week, not parallel |
| Phase 5 dual-write race condition produces orphan rows | High | UNIQUE on `source_pop_id`; per-PR regression test on every write path |
| Phase 5 reader migration causes wrong totals on Payment Summary | High | Row-count + sum parity assertion in regression test fixture |
| Phase 5 cutover happens before all readers are migrated | High | Feature flag is the gate; do not flip to true globally until 100% reader migration verified |

---

## Suggested order of execution

1. **Week 1: Phase 1** (column overloading cleanup) — smallest, highest-leverage, unblocks Phase 5
2. **Week 2: Phase 2** (audit trail) — small, valuable for client compliance, builds the change-tracking infrastructure Phase 4 / 5 will benefit from
3. **Week 3: Phase 3** (Stages C/D of LIKE-fallback removal) — completes Part 2 deferred work; depends on Stage B soak being done
4. **Weeks 4-7: Phase 4** (controller decomposition) — one controller per week; pause at any week boundary if capacity runs out
5. **Weeks 8-10: Phase 5** (table split) — biggest move; do it last when team has the most experience with the codebase

Phases 1, 2, 3 are independently safe to ship today (no dependencies between them except Stage B). Phase 4 is a quality-of-life sequence that can pause at any boundary. Phase 5 is the architectural finale and should only start after Phase 1 is done (depends on `linked_settlement_id` + `consumed_at` columns from Phase 1.2).

---

## What is explicitly NOT in Part 3

Items genuinely deferred beyond Part 3, with rationale:

| Item | Why deferred |
|---|---|
| IS1313 / IS1312 hotfix sprint | Client-affecting bug fixes; tracked separately for faster turnaround. Not part of the architectural plan. |
| VAT module deep refactor | Out of original Day-1 scope. VAT already has Lock 2 trait + UNIQUE constraints from Phase 1.2 of Part 2; full VAT decomposition is its own project. |
| EzyInvoice / EVCharging modules | Same — different modules with their own settlement flows. Out of original scope. |
| Real-time reporting / event sourcing for settlements | Architectural maturity level beyond what the business needs today. Audit trail (Phase 2) provides enough forensic data. |
| Caching / read replicas for hot Petro queries | Performance optimization, not architecture. Only relevant if Phase 5 reader migration surfaces a slow query. |

---

## What "done with Part 3" means

After all five phases land:
- `pump_operator_payments` is either immutable audit log or retired (Phase 5.6 decision)
- `pumper_payment_lines` is the canonical working layer
- The four monster controllers are each under 1,500 lines and delegate to services
- Settlement edits are tracked in `settlement_edit_history` with column-by-column diffs
- The trait `UpdatesSettlementTransactions` has only the FK path (LIKE branch deleted)
- Three legacy columns on `pump_operator_payments` (`is_used`, `parent_id`, `settlement_no`) are dropped
- Every architectural promise from Day-1 is delivered

**Part 3 is the last formally-planned phase.** After Part 3, ongoing maintenance / new feature work proceeds without a multi-phase plan doc — the codebase is structurally sound, the locks prevent the original bug class from returning, and the service layer is the canonical entry point for all settlement payment writes.
