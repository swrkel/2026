# HR-001 Standalone HR Manager Module Foundation

## Scope included
- Standalone module folder: `Modules/HRManager`
- Own routes, controllers, models, services, views, assets, language, config, permissions
- HR Dashboard
- Employee register foundation
- Manual attendance foundation
- Face attendance kiosk foundation
- Face recognition service placeholder for browser/external AI provider

## Important
This package is a first foundation parcel. It does not depend on Auto Service, Customers, Contacts, Petro, Accounting, or any other custom module. It only uses Laravel base features such as routes, controllers, models, migrations, auth middleware and Blade.

## Public assets
Copy:
- `Resources/assets/css/hrmanager.css` to `public/modules/hrmanager/css/hrmanager.css`
- `Resources/assets/js/hrmanager.js` to `public/modules/hrmanager/js/hrmanager.js`

## Route
`/hr-manager`

## Face attendance next step
Connect either:
1. Browser `face-api.js` embeddings; or
2. External face verification provider API; or
3. Dedicated biometric device API.
