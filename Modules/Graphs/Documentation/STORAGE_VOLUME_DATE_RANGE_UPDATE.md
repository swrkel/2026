# Storage Volume + Date Range Update

- Tank comparison now uses `fuel_tanks.storage_volume` and displays the label **Storage Volume**.
- Current stock and re-order percentages are calculated against Storage Volume.
- Reference Date has been replaced by **Date Range**.
- The Graphs filter reuses the ERP `window.dateRangeSettings` configuration when available, preserving the system date-range presets, financial-year ranges, locale/date format and Custom Range behaviour.
- A safe module fallback provides the same common presets if the global settings object is unavailable.
- Fuel and non-fuel sales APIs now receive exact `start_date` and `end_date` values.
- No core layout, sidebar, global CSS, global JavaScript or database files are changed.
