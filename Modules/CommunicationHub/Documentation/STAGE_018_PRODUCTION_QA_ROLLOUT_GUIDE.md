# Communication Hub Stage 018 - Production QA & Rollout Validation

## Purpose
This stage adds a final production QA screen and SQL validation support after the Enterprise Excellence stage.

## New route
- `/communication-hub/production-qa`
- Route name: `communicationhub.quality.index`
- Permission key: `communicationhub.production_qa.view`

## What to verify after upload
1. Open Communication Hub Dashboard.
2. Open Diagnostics Centre.
3. Open Production QA.
4. Confirm the readiness score is high and no critical tenant tables are missing.
5. Check failed/error messages before enabling live provider sending.
6. Confirm each business can only see its own Communication Hub rows.
7. Confirm the Executive Centre and Event Registry still open correctly.

## SQL rollout
Run `19_PRODUCTION_QA_AND_ROLLOUT_VALIDATION.sql` in every tenant database after Stage 017 SQL.

## Notes
This stage does not delete or modify existing messages. It only adds QA/validation support and a deployment check table.
