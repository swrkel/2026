# PROD-001 Product Module Audit & Foundation

Scope: create a Product module foundation without changing existing working files.

Source inspected:
- app/Http/Controllers/ProductController.php
- app/Utils/ProductUtil.php
- app/Product.php
- app/ProductVariation.php
- app/ProductRack.php
- app/DefaultProductCategory.php
- app/SupplierProductMapping.php
- resources/views/product
- resources/views/import_products
- public/js/product.js
- routes/web.php and routes/tenant.php product routes

Findings:
- Current Product logic is still centered around ProductController and ProductUtil.
- Product JavaScript is mainly in public/js/product.js.
- Product views already exist, but future packages should split large views and tabs further.
- Existing main routes still point to main ProductController. PROD-001 does not remove or replace them.

Safe strategy:
1. Copy Product-related files into Modules/Product.
2. Keep legacy Product files untouched until module UAT passes.
3. Split controllers/services/views/assets in later packages.
4. Remove main-system Product dependencies only after validation.

No SQL changes.
No stock logic changes.
No purchase/sales/barcode/pricing/variation logic changes.
