<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerPermissionService;

abstract class CustomerReportBaseController extends Controller
{
    protected $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    protected function businessId(): int
    {
        return (int) (request()->session()->get('business.id') ?: request()->session()->get('user.business_id'));
    }

    protected function authorizeReport(): void
    {
        $this->permissionService->authorize('reports');
    }

    protected function authorizeExport(): void
    {
        $this->permissionService->authorize('export');
    }
}
