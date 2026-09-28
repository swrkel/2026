# Products New Stage 009 — Import, Export & Data Quality Centre

This parcel adds standalone bulk data management for Products New.

## Added
- Import / Export Centre
- CSV import template download
- CSV validation before commit
- Import sessions and import line review
- CSV product export service
- Data Cleanup Centre
- Cleanup task records
- Import validation rules table
- Duplicate merge tracking table
- Stage SQL and updated master SQL

## Notes
- Existing Product module remains untouched.
- No database name is hardcoded.
- Designed for tenant database execution.
- Import commit is intentionally guarded through validation first to avoid corrupt product master data.
