# Loan Stabilization

This stage adds a standalone stabilization workspace inside the Loan module.

## Routes

- `/loan/stabilization`
- `/loan/stabilization/arrears-aging`

## Purpose

- Monitor active, pending, closed and arrears loan counts.
- Surface location-aware overdue balances.
- Provide arrears aging buckets without depending on other product modules.
- Keep the module route namespace-safe.

## Design rules

- No direct dependency on Deposits, Pawning, Leasing, Savings or CurrentAccounts.
- Uses only Loan tables plus existing ERP `business_id` / `location_id` conventions.
- Guards against missing optional columns to avoid breaking older Loan installations.
