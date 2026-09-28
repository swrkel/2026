# Deposits Module

Standalone banking deposits module.

## Scope
- Deposit products
- Deposit accounts
- Nominee and beneficiary details
- Deposit transactions
- Interest posting
- Maturity due, renewal and closure
- Certificates
- Reports
- Settings

## Server placement
Upload this folder as:

`Modules/Deposits/`

Do not place this folder inside another wrapper folder.

## Important route
`/deposits/dashboard`

## Tables
- deposit_products
- deposit_accounts
- deposit_transactions
- deposit_account_parties
- deposit_settings

## Integration
This module uses the existing ERP `business_id` and `business_locations.id` as `location_id`.
