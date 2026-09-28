# DIST-311 - Distribution Standalone Separation Stage 9

## Focus
Customer / Contact dependency reduction.

## Added
- `Modules/Distribution/Services/Customers/DistributionCustomerService.php`
- `Modules/Distribution/Entities/DistributionContactLedger.php`

## Updated
- Distribution Sales Order controller now references Distribution module Contact wrapper instead of direct `\App\Contact`.
- Distribution Invoice controller now references Distribution module Contact wrapper instead of direct `\App\Contact`.
- VAT Distribution Invoice controller now references Distribution module Contact/ContactLedger wrappers where safe.

## Safety
No table structure or business logic was changed. Wrappers extend the original ERP models, so current behaviour remains unchanged while Distribution code now depends on module-owned classes.
