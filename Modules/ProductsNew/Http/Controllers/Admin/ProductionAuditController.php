<?php
namespace Modules\ProductsNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Audit\StandaloneAuditService;
use Modules\ProductsNew\Services\Security\ProductsNewSecurityAuditService;
use Modules\ProductsNew\Services\Performance\ProductsNewPerformanceChecklistService;
use Modules\ProductsNew\Services\Ui\ProductsNewUiStandardService;

class ProductionAuditController extends Controller
{
    public function index(
        StandaloneAuditService $audit,
        ProductsNewSecurityAuditService $security,
        ProductsNewPerformanceChecklistService $performance,
        ProductsNewUiStandardService $ui
    ) {
        return view('productsnew::admin.production_audit', [
            'audit' => $audit->scan(),
            'checklist' => $audit->checklist(),
            'security' => $security->checklist(),
            'performance' => $performance->checklist(),
            'ui' => $ui->standards(),
        ]);
    }
}
