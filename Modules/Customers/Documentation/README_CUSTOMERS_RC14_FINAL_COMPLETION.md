CUSTOMERS RC14 FINAL COMPLETION HANDOFF
Date: 2026-07-01

Purpose:
- Continue Contacts customer functionality migration into standalone Modules/Customers.
- Add final legacy endpoint handling for customer search/get-contacts/check-mobile/API quick customer create.
- Keep supplier/mixed Contacts routes untouched.

Changed in RC14:
1. Modules/Customers/Http/Controllers/CustomerCompatibilityController.php
   - Added search() for /customer/search using Customers module model/service.
   - Added getContacts() compatibility endpoint.
   - Added checkMobile() compatibility endpoint.
   - Added postCustomersApi() compatibility endpoint.
2. routes/web.php and routes/tenant.php
   - /customer/search now uses CustomerCompatibilityController@search.
   - get-contacts now uses CustomerCompatibilityController@getContacts.
   - check-mobile now uses CustomerCompatibilityController@checkMobile.
   - POST customers now uses CustomerCompatibilityController@postCustomersApi.

Important note:
- Existing database columns such as contacts/contact_id remain intentionally, because the live ERP tenant tables use those column names. This RC focuses on removing runtime page/controller/route dependence on Contact module customer pages, not renaming live transaction schema.

Install:
1. Upload/replace Modules/Customers.
2. Replace routes/web.php and routes/tenant.php.
3. Clear Laravel cache: route, config, view, application cache.
4. Test Customers dashboard, register, create/edit/view, ledger, statement, reports, import/export, search dropdowns, and quick customer creation from sales/POS/purchase forms.

Final status:
- Main customer pages have been moved/redirected into Customers standalone module.
- Supplier Contacts routes remain untouched for supplier workflows.
- If server testing reveals a screen-specific issue, use this RC14 package as the new base.
