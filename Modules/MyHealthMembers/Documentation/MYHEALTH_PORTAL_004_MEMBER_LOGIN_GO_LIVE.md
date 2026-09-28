# MYHEALTH_PORTAL_004 – Member Login & Portal Go-Live

## Scope
- Passcode-only My Health member login.
- Optional OTP foundation through My Health settings.
- Email OTP always attempted where member email exists.
- SMS OTP queue hook through CommunicationHub where available; CommunicationHub/DigitalWallet controls wallet authorization.
- Secure member portal routes protected by MyHealthMemberPortalAuth middleware.
- Own-record-only portal service for dashboard, profile, medical history, prescriptions, labs, radiology, vaccinations, billing, appointments, notifications and documents.
- Document download restricted to the logged-in member.
- Appointment request from member portal.
- Passcode change from member portal settings.
- Portal access audit through myhealth_access_logs.

## Login URL
`/my-health-member/login`

## Portal URL
`/my-health-member/portal`

## Commands
```bash
php artisan optimize:clear
```

If migrations from earlier portal/member phases have not been run:
```bash
php artisan migrate
```

## Notes
- ERP staff login remains unchanged.
- Member portal does not show ERP menus.
- If OTP is disabled, member logs in with passcode only.
- If OTP is enabled, member enters passcode, then OTP.
- SMS OTP must not block login if CommunicationHub or wallet is unavailable; Email OTP remains the safe fallback.
