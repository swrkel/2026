# MEMNEW_024 — Membership Settings action/status completion
Date: 25 Sep 2026

This release completes the Settings table pattern for the remaining option tabs.

## Membership Status
- Action is the first column.
- Action dropdown: Edit, Change Status.
- Date & Time column.
- Added By retained.
- Status column: Enabled / Disabled.
- New records default to Enabled.

## Renewal Period
- Action is the first column.
- Action dropdown: Edit, Change Status.
- Date & Time column.
- Added By retained.
- Status column: Enabled / Disabled.
- New records default to Enabled.
- Edit remains limited to Daily, Weekly, Monthly, Annually.

## Registration / Renewal Amount
- Action is the first column.
- Action dropdown: Edit, Change Status.
- Date & Time column.
- Added By retained.
- Status column: Enabled / Disabled.
- New records default to Enabled.
- Amount remains optional.

## Database
No new database migration is required by MEMNEW_024. It uses the `is_active`, `created_by`, and existing option columns supplied by MEMNEW_023.
