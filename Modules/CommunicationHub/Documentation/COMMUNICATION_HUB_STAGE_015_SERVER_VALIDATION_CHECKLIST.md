# Communication Hub Stage 015 Server Validation Checklist

1. Upload the full `CommunicationHub` folder from this package.
2. Run `16_READINESS_ROUTE_MENU_VALIDATION.sql` in every tenant database.
3. For a full new tenant, run the latest master SQL instead.
4. Clear Laravel route/config/view cache.
5. Login to the tenant business and open `/communication-hub/readiness-check`.
6. Confirm all required tables and routes show Ready.
7. Test pages in this order: Dashboard, SMS, OTP, Email, WhatsApp, Push, In-App, Chat, Automation, Workflow, Analytics, API Gateway.
8. Confirm page visibility under Super Admin / Manage permissions and Business user permissions.
