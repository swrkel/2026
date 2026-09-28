# RESTNEW 026 - Restaurant CRM & Loyalty

Adds RestaurantNew CRM foundation with customer 360, visit history, loyalty bridge, customer segmentation, feedback cases, and campaign placeholders.

## Important Architecture Rule
RestaurantNew stores restaurant-specific CRM history locally. Membership/Customers/Communication Hub integrations must be done through service bridges only. Do not directly depend on external module files in controllers or views.

## SQL Order
1. Database/SQL/create/026_crm_loyalty_tables.sql
2. Database/SQL/insert/026_crm_loyalty_permissions.sql
