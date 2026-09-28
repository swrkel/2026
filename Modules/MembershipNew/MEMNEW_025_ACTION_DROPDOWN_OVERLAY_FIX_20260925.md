# MEMNEW_025 - Action dropdown overlay fix

Date: 25 Sep 2026

## Change
- Membership New row Action dropdowns now float above scrollable/short table containers instead of being clipped by `.mn-table-wrap { overflow:auto; }`.
- The floating menu uses a page-level z-index and viewport positioning.
- It opens below the Action button when space is available and automatically opens above when close to the bottom of the viewport.
- The behavior applies to current and dynamically inserted `.mn-row-action` dropdowns, including Membership Settings tabs and the Members table.
- Opening another Action menu closes the previous one.
- Resize and scroll reposition the open menu so it remains anchored to its Action button.

No database change is required.
