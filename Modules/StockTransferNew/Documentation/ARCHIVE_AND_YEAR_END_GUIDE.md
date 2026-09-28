# StockTransferNew STN_022 - Archive and Year-End Guide

## Purpose
This parcel adds year-end archive readiness and historical review tools.

## Important safety note
The archive execution records an archive status and checksum. It does **not** delete live transfer data. This is intentional, because historical transfer deletion must only be done after full user acceptance testing and verified backups.

## Recommended use
1. Run the tenant SQL file in each tenant database.
2. Open Stock Transfer-New > Archive.
3. Create a preview using the selected year-end date.
4. Review eligible completed transfers.
5. Execute archive only after checking totals.
6. Use restore request tracking if old transfers need admin review.

## Design rule
Product master is not duplicated. Historical archive lines keep product/transfer references and summaries only.
