# RestaurantNew RESTNEW 005 - Kitchen Order Ticket Foundation

## Included
- Standalone KOT tables and models.
- Kitchen ticket service for KOT creation, queue, status updates, print/reprint and cancel foundation.
- Kitchen display page using POS-standard card layout.
- Ticket print view.
- Standalone routes and permission SQL.

## Tenant / business safety
All ticket and line records carry business_id and business_location_id. Controller checks current session business before updating order/ticket records.

## SQL
Run tenant DB CREATE SQL for operational kitchen tables. Permission SQL should be run where your permissions table is maintained.
