# ATN-017 to ATN-021 — Large Consolidated Parcel

## ATN-017 — Corporate Credit
- Corporate agreements
- Credit limits and periods
- Negotiated discounts
- Corporate ledger structure

## ATN-018 — Visa Management
- Visa applications
- Embassy and appointment fields
- Checklist items
- Fees and status tracking

## ATN-019 — Tour Packages
- Domestic and international packages
- Package cost/sale pricing
- Tour booking structure

## ATN-020 — Hotel Reservations
- Hotel master
- Hotel reservation structure
- Voucher numbering support

## ATN-021 — Transport & Transfers
- Vehicle master
- Driver details
- Airport pickup/drop and transfer booking structure

## Installation
1. Merge over ATN-001 through ATN-016.
2. Add route includes from `Routes/ATN_017_TO_021_WEB_ROUTE_PATCH.php`.
3. Register or merge `Resources/lang/en/travelservices.php`.
4. Publish `atn-travel-services.css` and `atn-travel-services.js`.
5. Run the migration or `Database/SQL/MASTER/MASTER_ATN_017_TO_021_LARGE_PARCEL.sql`.
6. Assign included permissions.
7. Run `php artisan optimize:clear`.
