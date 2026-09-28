# Communication Hub OTP Stage 002

## Scope
This package continues after SMS Stage 001 and upgrades the OTP Centre into a business-safe enterprise OTP platform.

## Main fixes
- OTP records are scoped by current tenant database and current business.
- Added business location support for multi-business tenants.
- Added support for SMS, Email, WhatsApp and Voice OTP channels.
- Added configurable OTP digits, expiry minutes and max attempts from UI.
- Added manual OTP verification form.
- Added OTP register filters: search, channel, status, purpose and location.
- Added resend and cancel actions for pending OTPs.
- Supersedes old pending OTPs for the same business + recipient/identifier + purpose before creating a new one.
- Blocks OTP after max attempts.
- Queues OTP through the existing EnterpriseMessagingEngine.
- Adds audit entries for generate, verify, invalid, blocked and not-found verification attempts.
- New installs get the upgraded OTP table schema.
- Existing installs can run the included SQL upgrade safely in each tenant DB.

## SQL
Run this only in the tenant database where Communication Hub is installed:

- `Database/SQL/05_OTP_BUSINESS_PLATFORM_UPGRADE.sql`

The SQL uses `DATABASE()` and `INFORMATION_SCHEMA`, so no tenant database name is hardcoded.

## Changed files
- `Http/Controllers/CommunicationHubOtpController.php`
- `Services/CommunicationHubOtpService.php`
- `Services/Support/BusinessContext.php`
- `Resources/views/otp/index.blade.php`
- `Routes/tenant.php`
- `Database/Migrations/2026_06_26_100005_create_communication_hub_otps_table.php`
- `Database/SQL/05_OTP_BUSINESS_PLATFORM_UPGRADE.sql`
- `Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql`

## Testing checklist
1. Open `/communication-hub/otp`.
2. Generate an SMS OTP for one recipient.
3. Confirm a row is created in `communication_hub_otps` with `business_id`.
4. Confirm a queued message is created in `communication_hub_messages` with source module `CommunicationHubOTP`.
5. Verify the OTP manually.
6. Generate another OTP for same recipient and purpose; confirm previous pending OTP becomes `superseded`.
7. Try wrong OTP more than max attempts; confirm status becomes `blocked`.
8. Test filters and action buttons.
