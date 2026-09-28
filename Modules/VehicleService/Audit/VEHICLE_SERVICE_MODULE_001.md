# VEHICLE_SERVICE_MODULE_001

Initial standalone Vehicle Service module.

## Included
- New `VehicleService` module.
- Service job billing with header details, vehicle details, customer details, payment type, and status.
- Billing lines can use products from the existing Product module via search.
- Billing lines can also be custom items entered manually with custom amount.
- Line totals, subtotal, discounts, tax, grand total, paid amount, and balance are calculated.
- Job list includes page totals for total, paid, and balance.
- Tenant/business filter is applied using the existing session business id.

## Main URL
- `/vehicle-service`
- `/vehicle-service/jobs/create`

## Notes
- This package does not change existing PetroPD, Product, Finance, or other working module files.
- It is delivered as a new standalone module with its own routes, controllers, models, views, services, migrations, language file, JS, and cache provider file.
