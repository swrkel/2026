# S757 – MPCS F16A remaining fixes – 17 Sep 2026

Scope: only the items not highlighted green in S757.

## 1. Business Location selection
- F16A now builds the location list from active locations permitted to the logged-in user.
- The requested location remains selected when valid.
- When no location is supplied, the first permitted/assigned location is selected as the default.
- Select2 no longer clears the default location.
- The underlying select value is kept in sync with the visible Select2 value.
- Every F16A DataTable request sends the retained location together with the selected date.
- If a generic/global script attempts to clear the location, F16A restores the retained/default location.

## 2. Print font size
- Print-only font sizes increased by exactly 50% from the S755 values:
  - table headers: 7.5px -> 11.25px
  - table body / totals: 8px -> 12px
  - report label: 10px -> 15px
  - report business/date/form values: 13px -> 19.5px
  - report location: 11px -> 16.5px
- Existing A4 landscape, fixed-width columns and full-page print layout remain unchanged.

No F16A calculations, totals, transaction queries, form-number calculations, database schema, or other MPCS forms were changed.
