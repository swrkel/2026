<?php
namespace Modules\ProductsNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Integration\ProductsNewIntegrationBridge;

class IntegrationBridgeController extends Controller
{
    public function index(ProductsNewIntegrationBridge $bridge)
    {
        return view('productsnew::integration.index', [
            'consumers' => $bridge->supportedConsumers(),
        ]);
    }
}
