<?php

namespace Modules\Product\Services\Entity;

use Modules\Product\Entities\Barcode;
use Modules\Product\Entities\Brands;
use Modules\Product\Entities\Category;
use Modules\Product\Entities\DefaultProductCategory;
use Modules\Product\Entities\MergedSubCategory;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductRack;
use Modules\Product\Entities\ProductVariation;
use Modules\Product\Entities\SupplierProductMapping;
use Modules\Product\Entities\Unit;
use Modules\Product\Entities\Variation;
use Modules\Product\Entities\VariationGroupPrice;
use Modules\Product\Entities\VariationLocationDetails;
use Modules\Product\Entities\VariationPrice;
use Modules\Product\Entities\VariationStoreDetail;
use Modules\Product\Entities\VariationTemplate;
use Modules\Product\Entities\VariationValueTemplate;

class ProductEntityResolver
{
    public function product(): Product { return new Product(); }
    public function productVariation(): ProductVariation { return new ProductVariation(); }
    public function variation(): Variation { return new Variation(); }
    public function variationLocationDetails(): VariationLocationDetails { return new VariationLocationDetails(); }
    public function variationGroupPrice(): VariationGroupPrice { return new VariationGroupPrice(); }
    public function variationPrice(): VariationPrice { return new VariationPrice(); }
    public function variationStoreDetail(): VariationStoreDetail { return new VariationStoreDetail(); }
    public function variationTemplate(): VariationTemplate { return new VariationTemplate(); }
    public function variationValueTemplate(): VariationValueTemplate { return new VariationValueTemplate(); }
    public function category(): Category { return new Category(); }
    public function brand(): Brands { return new Brands(); }
    public function unit(): Unit { return new Unit(); }
    public function barcode(): Barcode { return new Barcode(); }
    public function productRack(): ProductRack { return new ProductRack(); }
    public function defaultProductCategory(): DefaultProductCategory { return new DefaultProductCategory(); }
    public function supplierProductMapping(): SupplierProductMapping { return new SupplierProductMapping(); }
    public function mergedSubCategory(): MergedSubCategory { return new MergedSubCategory(); }
}
