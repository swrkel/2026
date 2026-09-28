Customers Module Standalone Separation - Phase 5
================================================

Scope:
- No Contacts module files were removed or changed.
- The Customers module still safely uses the existing contacts table with type = customer.
- Added multi-branch / business location awareness.
- All customer records remain centralized under the same business_id for main/head office reporting.
- Branch/location is used for assignment and filtering only.

Files changed:
- Modules/Customers/Http/Controllers/CustomerController.php
- Modules/Customers/Services/CustomerService.php
- Modules/Customers/Resources/views/customers/index.blade.php
- Modules/Customers/Resources/views/customers/partials/form.blade.php
- Modules/Customers/Resources/views/customers/show.blade.php
- Modules/Customers/Resources/views/customers/print_profile.blade.php
- Modules/Customers/Resources/lang/*/lang.php

Notes:
- If contacts.business_location_id exists, it will be used.
- If contacts.location_id exists instead, it will be used.
- If neither column exists, the module still works and shows records centrally, but branch assignment is not saved until a location column is added.
