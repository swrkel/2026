# Settlement SW Separation Package: SW_SEP_005 to SW_SEP_009

## Completed in this package

### SW_SEP_005 - JS Separation
- Extracted the large Add Payment inline JavaScript into module asset files:
  - `Resources/assets/js/swsettlement/add-payment.js`
  - `Resources/assets/js/swsettlement/payments-add-payment.js`
- Added Blade-side `window.SettlementSwRoutes` and `window.SettlementSwPage` configuration so the JS no longer needs Blade code inside the JavaScript file.
- Replaced Add Payment AJAX endpoints from `/settlement-sw/payment/...` to `/settlement-sw/sw-add-payment/...`.
- Added service provider asset publishing tag: `settlementsw-assets`.

### SW_SEP_006 - Routes & Permissions Separation
- Split route definitions into smaller files:
  - `Routes/web.php` module shell
  - `Routes/dashboard.php`
  - `Routes/payments.php`
  - `Routes/reports.php`
- Added module permission list:
  - `Config/permissions.php`
  - `Config/settlementsw.php`

### SW_SEP_007 - Reports Separation
- Added `SettlementSwReportController`.
- Added module-owned report routes under `/settlement-sw/reports/...`.

### SW_SEP_008 - Dependency Cleanup
- Changed direct controller/service imports from `Modules\Petro\Entities` to `Modules\SettlementSW\Entities` where module-owned entity copies already exist.
- Replaced direct `Modules\SettlementSW\Services\SettlementPaymentReconciler` reference with `Modules\SettlementSW\Services\SettlementPaymentReconciler`.
- Added `SettlementSwPaymentEditService` to remove a hard-coded Petro service resolution from Add Payment cash handling.

### SW_SEP_009 - Language & Config Separation
- Added module config file for route prefix, asset path and module permissions.
- JavaScript URLs are now generated through module route/config values instead of hard-coded Petro URLs.

## Validation done
- PHP syntax check passed for module PHP files.
- JavaScript syntax check passed for both extracted Add Payment JS files using `node --check`.
- No remaining `/settlement-sw/payment` URLs in SettlementSW views/assets.

## Remaining for SW_AUDIT_001
Some wider historical dependencies remain and should be cleaned in the final audit pass:
- Several Blade labels still use `petro::lang.*`; they should be copied into `SettlementSW::lang.*` and replaced carefully.
- Some views still reference Petro controllers/entities outside the Add Payment payment endpoint area.
- Some core ERP `App\...` model/util dependencies remain. These need a practical decision: either wrap inside SettlementSW services/entities or keep as minimal ERP dependency where unavoidable.
