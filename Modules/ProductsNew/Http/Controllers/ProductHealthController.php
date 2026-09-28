<?php
namespace Modules\ProductsNew\Http\Controllers;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Services\ProductHealthService;
class ProductHealthController extends Controller { public function __construct(protected ProductHealthService $health) {} public function show(ProductsNewProduct $product){ $health=$this->health->score($product,[]); return view('productsnew::products.health',compact('product','health')); } }
