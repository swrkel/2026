# MEMNEW_020 - Blade / Sidebar correction

- Fixed Blade parse errors caused by inline `@php(...)` directives on Membership-New views.
- Normalized those directives module-wide so other pages do not fail after their compiled-view cache refreshes.
- Kept the Members list SQL fix that qualifies `mn_share_holdings.member_id` in the latest share holding eager load.
- Corrected Membership-New sidebar visibility: Business/Super administrators can see all registered module pages; ordinary users continue to see only routes allowed by their assigned permissions.
- Main Membership-New parent visibility still respects the existing Manage Sidebar enable/disable flag.
- Expanded the Membership-New permission registry and seeders so Members, Plans, Payments, Linked Businesses, Point Rules, Reports and other route permissions are not omitted from permission tooling.
