<?php
namespace Modules\ProductsNew\Http\Controllers;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Services\ProductTimelineService;
class ProductTimelineController extends Controller { public function __construct(protected ProductTimelineService $timeline) {} public function show(ProductsNewProduct $product){ $timeline=$this->timeline->list($product->id); return view('productsnew::products.timeline',compact('product','timeline')); } }
