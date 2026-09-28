# Enterprise Framework v1.0

Standalone shared framework for enterprise reporting modules.

## Purpose

This module provides common services for reports, dashboards, widgets, filters, exports, print layouts, scheduling, notifications, permissions, performance and report registry.

It is read-only and does not change operational module data.

## Main folder

`Modules/EnterpriseFramework`

## Included

- Shared Report Engine
- Shared Dashboard Engine
- Shared Widget Library
- Shared Filter Context
- Shared Export Engine
- Shared Print Engine
- Shared Drill-down Engine
- Shared Notification Center
- Shared Report Scheduler
- Shared Permission Engine
- Shared Report Registry
- Shared Performance Recommendations
- Shared UI toolbar component
- Module provider and routes
- Permissions config
- Menu config

## Integration contract

Future modules can expose reports using:

`Modules\EnterpriseFramework\Contracts\ReportProviderContract`

Required methods:

- `moduleName()`
- `reports()`
- `dashboardWidgets()`
- `alerts()`

## Safety

- Existing Finance module is not changed.
- Existing Finance Reports module is not replaced.
- This package only adds `Modules/EnterpriseFramework`.
- Operational data remains read-only from this framework.

## Suggested next step

Use Finance Reports as the first reference implementation to register its reports into the Enterprise Report Registry.

## EFW004 Extension

Adds shared export/print templates, scheduler definitions, alert rules, report cache wrapper, background report job descriptor and index recommendations.
