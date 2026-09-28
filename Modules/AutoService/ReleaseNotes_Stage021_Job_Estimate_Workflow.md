# AutoService Stage 021 - Job Card and Estimate Workflow Completion

## Included
- Convert approved estimate into a full job card.
- Prevent duplicate job card creation when an estimate is converted more than once.
- Copy estimate lines into job lines.
- Add job workflow actions: Start, Put On Hold, Complete.
- Add timeline entries for conversion and workflow status changes.
- Improve estimate detail and job card detail screens with action buttons and totals.
- Add raw tenant SQL only for this stage.

## SQL
Run `Database/SQL/22_AUTOSERVICE_STAGE021_JOB_ESTIMATE_WORKFLOW_COMPLETION.sql` on every tenant database that uses Auto Service.
