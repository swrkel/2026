# CommunicationHub Standalone Architecture

CommunicationHub is a new standalone ERP infrastructure module. It must not depend on the legacy SMS module, legacy Wallet module, MyHealthMembers, or any business module.

Allowed shared dependencies:
- Laravel framework
- Existing authentication/session middleware
- Existing tenancy bootstrap infrastructure
- Database connection configured by the host ERP

Not allowed:
- Calling old SMS module controllers/services/models
- Calling old Wallet module controllers/services/models
- Using MyHealthMembers-specific code
- Storing module screens outside `Modules/CommunicationHub`
- Storing reports outside `Modules/CommunicationHub`

Migration path:
1. Run CommunicationHub in parallel with legacy SMS/Wallet.
2. Test providers, templates, queue, OTP, reports.
3. Add adapter integration only through CommunicationHub contracts.
4. Switch modules gradually to CommunicationHub.
5. Disable legacy modules only after sign-off.
