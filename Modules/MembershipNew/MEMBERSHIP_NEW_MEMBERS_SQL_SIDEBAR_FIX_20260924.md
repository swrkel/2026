# Membership New - Members SQL + Sidebar Fix - 24 Sep 2026

## Fixes
- Fixed MySQL 1052 ambiguous `member_id` error on `/membership-new/members` by qualifying the `mn_share_holdings` columns used with `latestOfMany()`.
- Added module-owned host sidebar partial for the ERP hook `layouts.partials.sidebar-sections.sidebar-membership-new`.
- Membership-New sidebar now lists normal user pages and authorized advanced pages based on route existence + permission.
- Updated both MembershipNew menu registries for consistent page lists.
- No database changes required.

## Sidebar behavior
The main ERP sidebar still controls whether the module is enabled (`$show_membership_new_menu` / `$membership_new_module`). This fix does not bypass Manage Sidebar.
