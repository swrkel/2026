<?php

namespace Modules\Product\Http\Controllers\Product;

use Modules\Product\Http\Controllers\Legacy\ProductController as LegacyProductController;

class ProductListController extends LegacyProductController
{
    // Uses inherited index() until business logic is moved into ProductListService.
}
