# Membership New - Members List Columns - 24 Sep 2026

Updated `/membership-new/members` to the requested operational table layout.

## Changes
- Action is now the first column.
- View, Edit, Points and Card are grouped in a per-row Action dropdown.
- Name shows Title + Name on the first line and Gender underneath.
- Region shows the selected Region and its Region No when available.
- Mobile Number shows the member's saved primary mobile number.
- Business Name shows the member's saved business name, with the current business name as a safe display fallback for older records.
- Membership Type shows the saved Membership Settings type, with Shares underneath.
- Shares use the member's saved No of Shares and fall back to the latest share holding only for older records where the member field is empty.
- NIC, Joined Date, Address and Status are shown.
- Joined Date heading is split into two rows.
- Table remains responsive through horizontal scrolling on smaller screens.
- Region, Membership Type and Shares are eager-loaded to prevent per-row N+1 queries.
- Search now also covers Title, Gender, Region, Membership Type, Address and Active/Inactive status.

No database change is required.
