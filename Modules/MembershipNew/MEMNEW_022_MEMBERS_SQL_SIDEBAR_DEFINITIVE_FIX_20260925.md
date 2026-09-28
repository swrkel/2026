# MEMNEW_022 — Members SQL + Sidebar definitive repair

- Members list no longer eager-loads `shareHolding`; it displays the manually saved `mn_members.no_of_shares` value. This removes the `latestOfMany()` derived-table join that produced `Column member_id in SELECT is ambiguous`.
- Membership-New host sidebar view path is prepended and the Laravel view-finder cache is flushed during module boot, preventing an older global `sidebar-membership-new.blade.php` from winning.
- Sidebar renderer now recognizes `superadmin`, `Admin#<business_id>`, host privileged flags, and normal per-page permissions.
- All sidebar entries come from `MembershipNewNavigationRegistry` so Dashboard, Members, Plans, Payments, Reports and Membership Settings use the same current page registry as the advanced pages.
- No database schema change is required.
