# Architecture

## Standalone module boundary
Egg Management owns its domain and never imports application models from Customers, Suppliers, Finance or Products New. Shared master data is queried through Egg-owned gateways. This means a schema variation is corrected in one gateway/configuration file rather than across pages.

## Tenant boundary
The host application remains responsible for selecting the tenant database at login. Egg models inherit the selected default connection unless `EGG_DB_CONNECTION` is explicitly set. No master/central database table is created by this package.

## Business / Location / Store boundary
Every service scopes by the current `business_id`. Location and Store are written into transaction and stock records. Stock consumption is constrained to the selected location/store where provided.

## Inventory method
Stock is lot based. Grading and purchases create lots; sales/transfers/negative adjustments consume lots FIFO, ordered by best-before/collection date and then ID. Every movement produces an immutable movement row.

## Shared-module adapters
- CustomerGateway: reads customer options only.
- SupplierGateway: reads supplier options only.
- ProductGateway: reads Products New options only; Egg stores optional product IDs without cross-DB foreign keys.
- FinanceGateway: queues an integration event rather than touching Finance internals.
- MessagingGateway: Laravel Mail + configurable SMS HTTP endpoint + WhatsApp deep-link.

## Maintenance rule
Do not add business logic into Blade files or routes. Each functional area has its own controller/service/view. Reports are separate controllers and views.
