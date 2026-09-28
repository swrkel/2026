Finance Reports Complete Release
================================

Module name shown in sidebar: Finance Reports
Folder: Modules/FinanceReports
Mode: Read-only reporting module

Purpose
-------
This complete release consolidates all Finance Reports packages from FR001 through Enterprise RC2 into one replacement/install package.
It is designed to sit beside the existing Finance module without replacing the existing Finance controllers, routes, views, ledgers, cash books, bank books, or journal pages.

Important Safety Rule
---------------------
The existing Finance module is not replaced by this package. This module should be installed under Modules/FinanceReports only.

Included Report Areas
---------------------
1. Executive / Dashboard
2. Trial Balance - New
3. Balance Sheet - New
4. Profit & Loss - New
5. Income Statement - New
6. General Ledger - New
7. Account Ledger - New
8. Cash Book - New
9. Bank Book - New
10. Journal Register - New
11. Day Book - New
12. Management Analysis Reports
13. Receivables & Payables Reports
14. Cash, Banking & Treasury Reports
15. Audit, Compliance & Controls
16. Fixed Assets & Capital Assets
17. Executive BI Dashboard
18. Forecasting Reports
19. Consolidation Center
20. Performance Center
21. Export Center
22. Print Layout Center
23. Drilldown Center
24. Standalone Audit Center

Common Standards
----------------
- Branch/location wise reporting
- Consolidated reporting
- Date range / financial period support
- Read-only access
- Separate routes
- Separate controllers
- Separate services
- Separate views
- Separate language files
- Separate permissions framework
- Separate menu/sidebar framework

Installation Note
-----------------
Copy the FinanceReports folder into Modules/FinanceReports.
Register the module provider if your Laravel module loader does not auto-discover it.
Run permission seeding only if your application requires manual permission registration.
Clear Laravel cache/config/view caches after installation.

