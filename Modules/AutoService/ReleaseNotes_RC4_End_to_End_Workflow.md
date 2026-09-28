# Auto Service v1.0 RC4 - End-to-End Workflow

This release consolidates Auto Service into a UI-testable workflow package.

## Focus areas
- Reception workflow foundation
- Vehicle lookup and central registry-safe history service
- Job card workflow status service
- Inspection/estimate/workshop/QC/delivery lifecycle support
- Customer portal-safe vehicle history rules
- Reminder engine foundation
- Reporting service foundation
- UI testing checklist refreshed

## Privacy rule preserved
Workshops can see only technical vehicle history: date, mileage, service category, work performed, parts, products, oils and lubricants. Previous workshop identity, contact details, invoice numbers, invoice amounts, prices, discounts, and payment details are not exposed.

## Deployment
Replace `Modules/AutoService` with this package's module folder. Central migrations remain inside `Database/Migrations/Central` and must be run only on the central database. Tenant migrations stay in `Database/Migrations`.
