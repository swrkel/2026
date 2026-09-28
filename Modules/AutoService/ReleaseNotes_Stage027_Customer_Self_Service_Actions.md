# AutoService Stage 027 - Customer Self Service Actions

## Added
- Customer appointment request form inside the Auto Service Customer Portal.
- Recent appointment request list for the customer/vehicle.
- Customer approve/decline action for pending approval requests.
- Timeline entries when appointment requests and approval responses are submitted.
- SQL feature flags for appointment requests and approval responses.

## Strengthened
- Customer portal now covers status, current bill, service history, parts/accessories history, documents, feedback, appointment requests, and approval responses.
- All actions remain tenant-database safe and business-scoped.

## SQL
Run `28_AUTOSERVICE_STAGE027_CUSTOMER_SELF_SERVICE_ACTIONS.sql` on each tenant database using the Auto Service module.
