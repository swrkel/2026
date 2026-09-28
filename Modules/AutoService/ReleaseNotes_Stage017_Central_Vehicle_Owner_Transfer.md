# AutoService Stage 017 - Central Vehicle Owner Transfer & Privacy Hardening

## Added
- Central vehicle ownership transfer request workflow.
- Previous owner approval gate before changing the registered owner.
- New owner OTP verification before portal access is transferred.
- Ownership history is preserved; service records remain attached to the vehicle, not the tenant.
- Workshop-safe history remains anonymized: no previous workshop name/contact, no invoice numbers, no prices, no payment information.
- Workshops can only see service date, mileage, work done, parts/products and lubricants/oils used.

## Important privacy rule
Only the verified current/last registered owner can see cost history. Other businesses/workshops cannot view previous service place, contact details, invoices, prices or payment details.
