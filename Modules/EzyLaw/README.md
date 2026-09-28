# EzyLaw V4 - Final Standalone Lawyer Management System

EzyLaw is a standalone Laravel module for the application's central-registry / single-code / multi-tenant / multi-business architecture.

## Architecture guarantees

- All EzyLaw source lives inside `Modules/EzyLaw`.
- All EzyLaw-owned tables use the `law_` prefix.
- Operational EzyLaw tables belong in each tenant database, never the central registry database.
- Every authenticated EzyLaw model query is business-scoped through `LawModel` + `EzyLawTenantGuard`.
- EzyLaw migrations create no foreign-key dependency to core tables or other modules.
- Finance, User Management New and outbound communications are optional bridges; EzyLaw remains usable when they are unavailable.
- Shared authentication, current business/session, Laravel routing/view/storage, and the application's `check.route.permission` middleware are treated as common platform services.
- `EZYLAW_AUTO_LOAD_MIGRATIONS` defaults to `false` to prevent tenant-owned tables being created accidentally in the central registry database.

## Complete V1-V4 scope

### Client & matter management
- Client master
- Matter/case master
- Practice areas and courts
- Parties/counsel
- Matter chronology
- Workflow templates, matter stages and stage history
- Matter closing/reopening with blocker checks and separate force-close permission
- Conflict checks

### Court & dispute workflow
- Hearings / court diary
- Court filing register and filing status
- Limitation/legal deadlines
- Evidence / exhibits
- Settlement, negotiation, consent and mediation register
- Mediation sessions

### Time, fees & finance-facing operations
- Time recording
- Billing rates
- Billable time/expense invoice generation
- Manual invoices and payments
- Invoice adjustments
- Fee estimates / quotations and conversion to invoice
- Retainers
- Client advances and invoice allocation
- Expense advances to users
- Matter expenses
- Optional Finance posting for invoices, payments, expenses, trust, adjustments and advances
- Retry-safe integration queue with same-request reuse and transactional debit/credit pair posting

### Trust accounting
- Trust accounts
- Client/matter trust transactions
- Apply trust to invoice
- Trust reconciliation
- Trust audit report comparing stored balance with ledger balance

### Documents & knowledge
- Matter/client documents
- Document versions
- Document templates
- Internal document approval workflow
- Client e-sign requests
- Evidence/document links
- Legal research / precedent library

### Client service & communications
- Appointments
- Legal calendar
- Reminders
- Communications register
- Client portal administration
- Optional public token-based client portal
- Portal matter/invoice/hearing/message views
- Portal e-sign response
- Portal audit logging
- Matter ownership validation on every portal message
- Client + email ownership validation on portal e-sign requests

### Notifications
- In-app/email/SMS/WhatsApp notification queue
- Rule-based deadline/hearing/task reminders
- Manual rule runner
- Standalone `php artisan ezylaw:notifications` command for scheduled execution
- External delivery remains an optional adapter; EzyLaw does not hard-depend on a messaging module

### Management & reports
- EzyLaw operational dashboard
- Management dashboard
- Lawyer targets, cost rates and workload
- Lawyer performance indicators
- Matter profitability using invoiced revenue less direct expenses and configured lawyer labour cost
- Advanced legal reports
- Court filing report
- Settlement report
- Receivables/WIP/trust/advance KPIs
- Trust audit

## User Management New compatibility

V4 uses underscore-normalized permission names such as `ezylaw_clients_view` and `ezylaw_matters_update`. This deliberately matches the current core `AutomaticModuleRegistry::normalizeKey()` behaviour and the strict `roleAllowsPermission()` checks used by `check.route.permission` for User Management New managed roles.

Permissions are declared in `Config/module_permissions.php`; no core permission file needs to be edited.

## Finance mapping

Optional account settings include:

- `finance_receivable_account_id`
- `finance_fee_income_account_id`
- `finance_cash_account_id`
- `finance_expense_account_id`
- `finance_trust_cash_account_id`
- `finance_client_trust_liability_account_id`
- `finance_client_advance_liability_account_id`
- `finance_expense_advance_account_id`

Finance posting is adapter-based. If Finance is absent or an account mapping is incomplete, EzyLaw remains operational and the integration request remains queued/mapping-required rather than making Finance mandatory.

## Client portal

Public portal routes are implemented but disabled by default:

`EZYLAW_PORTAL_PUBLIC_ROUTES=false`

After HTTPS and the desired deployment policy are confirmed, set it to `true` and clear configuration cache. Tokens are stored only as SHA-256 hashes. Public queries explicitly filter by both `business_id` and `client_id`; public matter messages are rejected when the matter does not belong to the portal client.

## SQL deployment

For an existing V3 tenant database:

`Database/SQL/EzyLaw_V4_INCREMENTAL_IDEMPOTENT.sql`

For a fresh tenant database:

`Database/SQL/EzyLaw_MASTER_IDEMPOTENT_V4.sql`

The V4 full schema contains **54 EzyLaw-owned tables**. All 54 names start with `law_`.

Never run EzyLaw tenant operational SQL in the central registry database.

## Scheduled notifications

Run the following command from the intended tenant database context using the host application's normal tenant command runner / scheduler:

`php artisan ezylaw:notifications`

The command generates rule-based reminders then dispatches due in-app records or marks external-channel records queued for an optional outbound adapter.

## Version

Final completion release: **EzyLaw V4.0.0** (6 September 2026).
