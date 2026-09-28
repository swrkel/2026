# Communication Hub - SMS Stage 001 + OTP Prep

## Scope completed
- Added standalone `CommercialSmsService` for SMS queueing, bulk queueing, scheduled SMS, testing send processing, wallet deduction, audit log, tenant/business scoping, and business location capture.
- Updated Commercial SMS controller to use the service instead of duplicating insert logic inside controller methods.
- SMS queueing now supports accurate SMS credit calculation based on 160-character segments.
- Wallet deductions are business-scoped and audit-safe.
- Message status actions are business-scoped.
- Added tenant SQL `04_SMS_OTP_BUSINESS_SAFE_UPGRADE.sql`.
- Added master SQL `MASTER_SQL_COMMUNICATION_HUB_ALL.sql`.

## OTP preparation completed
- Replaced OTP controller with tenant/business-safe implementation.
- Reworked OTP service so SMS OTPs are queued through the Communication Hub SMS workflow.
- Reworked OTP view to use Communication Hub layout and professional cards.
- OTP verification now blocks after max attempts and records verified time.

## Important upload steps
1. Replace the existing `Modules/CommunicationHub` folder with this package.
2. Run SQL files in each tenant database. For this ZIP, the new SQL is:
   - `04_SMS_OTP_BUSINESS_SAFE_UPGRADE.sql`
3. If you are installing fresh, use `MASTER_SQL_COMMUNICATION_HUB_ALL.sql` as the full list/order.
4. Clear route/view/config cache if your server uses caching.

## Next stage
OTP Stage 002 will add deeper OTP settings, expiry rules per business, API verification endpoints, templates, resend limits, and audit/reporting pages.
