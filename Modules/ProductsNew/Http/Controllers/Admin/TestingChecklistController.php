<?php

namespace Modules\ProductsNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Migration\MigrationReadinessService;

class TestingChecklistController extends Controller
{
    public function index(MigrationReadinessService $service)
    {
        $items = $service->testingChecklist();
        return view('productsnew::admin.testing.index', compact('items'));
    }
}
