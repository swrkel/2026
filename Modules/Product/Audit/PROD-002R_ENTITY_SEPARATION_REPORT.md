# PROD-002R Product Entity Separation Report

This package is generated from the uploaded actual source files.

No main-system files are removed or overwritten. No stock, sales, purchase, pricing, barcode, inventory, or variation logic is changed.

## Files copied

- COPIED: app/Product.php -> Modules/Product/Entities/Product.php
-   Remaining App deps: App\BusinessLocation, App\Media, App\PurchaseLine, App\TaxRate, App\Warranty
- COPIED: app/ProductVariation.php -> Modules/Product/Entities/ProductVariation.php
- COPIED: app/Variation.php -> Modules/Product/Entities/Variation.php
-   Remaining App deps: App\Media, App\TransactionSellLine
- COPIED: app/VariationLocationDetails.php -> Modules/Product/Entities/VariationLocationDetails.php
- COPIED: app/VariationGroupPrice.php -> Modules/Product/Entities/VariationGroupPrice.php
- COPIED: app/VariationPrice.php -> Modules/Product/Entities/VariationPrice.php
-   Remaining App deps: App\Media, App\TransactionSellLine
- COPIED: app/VariationTemplate.php -> Modules/Product/Entities/VariationTemplate.php
- COPIED: app/VariationValueTemplate.php -> Modules/Product/Entities/VariationValueTemplate.php
- COPIED: app/VariationStoreDetail.php -> Modules/Product/Entities/VariationStoreDetail.php
- COPIED: app/Variation_store_detail.php -> Modules/Product/Entities/Variation_store_detail.php
- COPIED: app/ProductRack.php -> Modules/Product/Entities/ProductRack.php
- COPIED: app/DefaultProductCategory.php -> Modules/Product/Entities/DefaultProductCategory.php
- COPIED: app/SupplierProductMapping.php -> Modules/Product/Entities/SupplierProductMapping.php
- COPIED: app/Category.php -> Modules/Product/Entities/Category.php
- COPIED: app/Brands.php -> Modules/Product/Entities/Brands.php
- COPIED: app/Unit.php -> Modules/Product/Entities/Unit.php
- COPIED: app/Barcode.php -> Modules/Product/Entities/Barcode.php
- COPIED: app/MergedSubCategory.php -> Modules/Product/Entities/MergedSubCategory.php

## Next
PROD-003 will split controllers using safe bridges. Legacy App models must remain until PROD-010 validation and UAT are complete.
