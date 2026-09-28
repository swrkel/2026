# ATN-079 to ATN-086 — 8 Section Parcel

## ATN-079 POS UI Completion
Reusable POS-style page header, toolbar and table components.

## ATN-080 Reusable Components
Status badges, money formatting, empty states and shared UI JavaScript.

## ATN-081 Advanced Permissions
Business-specific permission profiles and permission middleware.

## ATN-082 Audit Hardening
Security audit log with user, IP, browser, route and structured context.

## ATN-083 Encryption
Encrypted business settings and secure token hashing.

## ATN-084 API Security
Rate limiting and request-signature middleware.

## ATN-085 Mobile Enhancements
Pending task and workflow feeds for mobile interfaces.

## ATN-086 Enterprise Settings
Business-scoped enterprise setting storage and management page.

## Installation
1. Merge over ATN-001 through ATN-078.
2. Include `Routes/security-ui.php`.
3. Register middleware aliases for feature permission, API throttle and API signature.
4. Run migration or master SQL.
5. Publish CSS/JS and merge language files.
6. Assign included permissions.
7. Run `php artisan optimize:clear`.
