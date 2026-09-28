# Sequence contract test

For a sequence configured with Prefix `CR-` and Starting Number `1001`:

1. The first `consume('cash_receipt')` must return `CR-1001`.
2. The stored `next_number` must become `1002` in the same transaction.
3. The second consume must return `CR-1002`.
4. Concurrent requests are protected by `lockForUpdate()` and a DB transaction.
5. An empty prefix returns only the numeric value, for example `1001`.
