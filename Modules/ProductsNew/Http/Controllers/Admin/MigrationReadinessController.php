<?php

namespace Modules\ProductsNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Migration\MigrationReadinessService;

class MigrationReadinessController extends Controller
{
    public function index(MigrationReadinessService $service)
    {
        $summary = $service->summary();
        return view('productsnew::admin.migration.index', compact('summary'));
    }
}
