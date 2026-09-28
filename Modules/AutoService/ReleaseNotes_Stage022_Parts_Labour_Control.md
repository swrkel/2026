# Auto Service Stage 022 - Parts & Labour Control

## Completed
- Added dedicated Parts & Labour Control area.
- Added job-wise parts reservation, issue, return and warranty tracking.
- Added labour billing entry against job cards.
- Parts can now synchronize into job lines so estimates, invoices, print views and reports use the same totals.
- Job totals are recalculated after parts/labour updates.
- Fixed product select handling in Workshop job page.
- Added short URL aliases for `/autoservice/parts-labour`.
- Added permission keys:
  - `autoservice.parts_labour.view`
  - `autoservice.parts_labour.manage`

## SQL
- `23_AUTOSERVICE_STAGE022_PARTS_LABOUR_CONTROL.sql`
- `MASTER_AUTOSERVICE_SQL_STAGE022.sql`
