# Petro Refactor Day 1 — Orphan Writes

Settlement payment writes where `pump_payment_id` is legitimately NULL because no source `pump_operator_payments.id` is available in scope. After Step 3, every site listed here routes through `SettlementPaymentReconciler::upsertOne()` — Lock 2 lets it through, the orphan path always inserts (MySQL UNIQUE treats NULL as distinct so no conflicts).

These are NOT bugs. They are flows where the settlement row is born directly (e.g. Direct Settlement entry, DailyVoucher, DailyCollection without a pumper link).

| File | Method / Line | Reason |
|---|---|---|
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | `saveCashPayment` / line ~4050 | Direct Settlement cash entry; pump_payment_id linked further down. |
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | `saveCardPayment` / line ~4723 | Direct Settlement card entry; pump_payment_id linked further down at line ~4742. |
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | `saveChequePayment` / line ~4918 | Direct Settlement cheque entry; pump_payment_id linked further down. |
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | `saveCreditSalePayment` / line ~5225 | Direct Settlement manual credit sale; may have no pump_operator_payments source. |
| `Modules/Petro/Http/Controllers/AddPaymentController.php` | inside `if (empty($settlement_card_payment))` / line ~388 | Card payment created from daily_card; pump_payment_id linked later. |
| `Modules/Petro/Http/Controllers/DailyVoucherController.php` | `store` / line ~369 | Daily voucher creates the settlement credit-sale row before any matching `PumpOperatorPayment` source exists. |
| `Modules/Petro/Http/Controllers/SettlementController.php` | DailyCollection cash injection / line ~3261 | DailyCollection-sourced cash row, no pump_payment_id linkage in this flow. |
| `Modules/Petro/Http/Controllers/SettlementPDController.php` | DailyCollection no-pump-payment branch / line ~5302 | DailyCollection cash without a matching pump payment. |

## What this list is used for

1. The CI static check (`tests/Static/NoDirectSettlementPaymentWritesTest.php`) does NOT consult this file — it just confirms no DIRECT `::create()` exists outside the allowed locations. All sites above use `upsertOne()`, so the static check passes.
2. The Reconciler's orphan path uses NULL `pump_payment_id` deliberately. UNIQUE constraint allows that (NULL distinct).
3. Week 1 may decide to add a more specific identity for some of these (e.g. `daily_collection_id`, `daily_voucher_id`, `daily_card_id`) so duplicate-prevention works at the source-document level too. Until then, orphan writes can theoretically produce duplicates if the controller is hit twice — but each site listed above either has a manual guard above its `upsertOne()` call OR is in a flow that runs once per day-end.
