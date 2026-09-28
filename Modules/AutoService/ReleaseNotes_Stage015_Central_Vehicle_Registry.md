# Auto Service Stage 015 - Central Vehicle Registry & Owner Verified Service Book

Added a centralized vehicle registry that can be shared across tenant/business databases.

## Included
- Customer self-registration of vehicle.
- Current/last registered owner record.
- SMS OTP verification for owner login.
- Central service record table for all businesses to post job/service/invoice history.
- Owner portal to view service date, mileage, spare parts used, oil used, work details and total cost spent across all linked service records.
- Independent central entities, services, routes and views inside AutoService module.

## Central database
Set `AUTO_SERVICE_CENTRAL_DB_CONNECTION` in `.env` to the central database connection name.

## URLs
- `/auto-service/central-vehicle/register`
- `/auto-service/central-vehicle/login`
- `/auto-service/central-vehicle/dashboard`
