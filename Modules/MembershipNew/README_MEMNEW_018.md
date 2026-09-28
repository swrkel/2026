# MEMNEW_018 - Membership Settings performance

Optimizes Membership Settings / Regions without changing tenant/business database selection.

- Uses `simplePaginate()` to avoid the extra `COUNT(*)` query on each list load.
- Uses direct DATE comparisons so the existing date indexes remain usable.
- Loads only the columns required by the Regions table.
- Saves a new Region with one insert instead of a pre-save uniqueness SELECT; the database unique key remains authoritative.
- Add Region saves asynchronously and inserts the new row into the current table without reloading the entire page. The normal POST/redirect path remains as a browser fallback.
- Business context now checks session business IDs before resolving `Auth::user()`, avoiding an unnecessary user lookup in normal ERP sessions.
- Adds a composite `(business_id, deleted_at, date, id)` index for the default region list query.
