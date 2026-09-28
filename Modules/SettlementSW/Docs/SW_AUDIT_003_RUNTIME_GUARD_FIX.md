# SW_AUDIT_003 - Runtime Guard and Translation Namespace Fix

## Purpose
Continuation after `SW_AUDIT_002` to catch runtime-level standalone issues that do not always appear in simple reference searches.

## Changes

### 1. Added missing module-local reconciler guard trait
Added:

- `Entities/Concerns/RequiresReconcilerContext.php`

This fixes the missing trait referenced by Settlement SW payment entities:

- `SettlementCardPayment`
- `SettlementCashPayment`
- `SettlementChequePayment`
- `SettlementCreditSalePayment`

The trait is module-local and avoids depending on Petro/PetroPD/shared module guard traits.

### 2. Translation namespace compatibility
Updated:

- `Providers/SettlementSWServiceProvider.php`

Now registers both translation namespaces:

- `SettlementSW::lang.*`
- `settlementsw::lang.*`

This prevents label/lang failures because older copied views used mixed namespace casing.

## Audit Result

- PHP syntax audit passed for changed files.
- Full module PHP syntax audit passed.
- ZIP integrity check passed.

## Notes for next phase

Large legacy controllers still exist for backward compatibility:

- `SettlementSWController.php`
- `SWAddPaymentController.php`

They are now wrapped by smaller `SettlementSw*Controller` classes and should be gradually reduced in future feature-level cleanup, but they were not removed to avoid breaking existing settlement save/edit behavior.
